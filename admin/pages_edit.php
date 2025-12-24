<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$role = strtolower(trim($currentUser['role'] ?? ''));
if (!in_array($role, ['admin','editor'], true)) {
	header('Location: index.php');
	exit;
}

$db = getDB();
$error = '';
$success = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Support both old 'file' parameter and new 'f' (base64 encoded) parameter
// The 'f' parameter is base64 encoded to bypass mod_security on GoDaddy
$filePath = '';
if (isset($_GET['f'])) {
	// Decode base64 encoded file path (new method - bypasses mod_security)
	$filePath = base64_decode($_GET['f']);
} elseif (isset($_GET['file'])) {
	// Legacy support for direct file path (may fail on GoDaddy due to mod_security)
	$filePath = $_GET['file'];
}
$originalFilePath = $filePath; // Preserve original for form submission
$isEdit = $id > 0;
$isFileEdit = !empty($filePath);

// CSRF
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Load existing
$page = [
	'title' => '',
	'slug' => '',
	'content_html' => '',
	'custom_css' => '',
	'status' => 'draft',
];
$originalSlug = '';
$fileContent = '';
$fileFullPath = '';

// Load from database
if ($isEdit) {
	try {
		$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
		$stmt->execute([$id]);
		$found = $stmt->fetch();
		if ($found) {
			$page = $found;
			$originalSlug = $found['slug'];
		}
	} catch (Throwable $e) {
		$error = 'Failed to load page. Ensure the pages table exists.';
	}
}

// Load from HTML file
if ($isFileEdit) {
	$rootDir = dirname(__DIR__);
	// Try to get realpath, but if it fails, use the original (case-insensitive filesystem)
	$realRoot = @realpath($rootDir);
	if (!$realRoot) {
		// If realpath fails, try to find the actual case of the directory
		$realRoot = $rootDir;
		// On case-insensitive filesystems, we can still use the path
		if (!is_dir($realRoot)) {
			$error = 'Root directory not found: ' . htmlspecialchars($rootDir);
			$isFileEdit = false;
		}
	}
	
	if ($isFileEdit) {
		// Decode URL encoding and normalize path
		$filePath = urldecode($filePath);
		$filePath = str_replace('\\', '/', $filePath); // Normalize to forward slashes
		$filePath = ltrim($filePath, '/');
		
		// Ensure .html extension is present
		if (substr($filePath, -5) !== '.html') {
			$filePath .= '.html';
		}
		
		// Try multiple path construction methods
		$possiblePaths = [
			$realRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath),
			$rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath),
			$realRoot . '/' . $filePath,
			$rootDir . '/' . $filePath,
		];
		
		$realFile = null;
		$rootNormalized = strtolower(str_replace('\\', '/', $realRoot));
		$rootDirNormalized = strtolower(str_replace('\\', '/', $rootDir));
		
		foreach ($possiblePaths as $tryPath) {
			// Check if file exists (works on case-insensitive filesystems)
			if (file_exists($tryPath) && is_file($tryPath)) {
				// Security check: ensure file is within root directory (case-insensitive)
				$tryPathNormalized = strtolower(str_replace('\\', '/', $tryPath));
				
				$isWithinRoot = (strpos($tryPathNormalized, $rootNormalized) === 0) || 
				                (strpos($tryPathNormalized, $rootDirNormalized) === 0);
				
				if ($isWithinRoot) {
					$ext = strtolower(pathinfo($tryPath, PATHINFO_EXTENSION));
					if ($ext === 'html') {
						$realFile = $tryPath;
						break;
					}
				}
			}
			
			// Also try with realpath for more accurate resolution
			$resolved = @realpath($tryPath);
			if ($resolved && is_file($resolved)) {
				$resolvedNormalized = strtolower(str_replace('\\', '/', $resolved));
				$isWithinRoot = (strpos($resolvedNormalized, $rootNormalized) === 0) || 
				                (strpos($resolvedNormalized, $rootDirNormalized) === 0);
				
				if ($isWithinRoot) {
					$ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
					if ($ext === 'html') {
						$realFile = $resolved;
						break;
					}
				}
			}
		}
		
			if ($realFile) {
				$fileContent = @file_get_contents($realFile);
				if ($fileContent !== false) {
					$page['title'] = pathinfo($filePath, PATHINFO_FILENAME);
					$page['slug'] = str_replace('.html', '', $filePath);
					
					// Extract body content from full HTML document
					$bodyContent = $fileContent;
					if (stripos($fileContent, '<body') !== false) {
						// Try regex first (faster and more reliable for large files)
						if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $fileContent, $matches)) {
							$bodyContent = $matches[1];
						} else {
							// Fallback to DOMDocument if regex fails
							libxml_use_internal_errors(true);
							$dom = new DOMDocument();
							@$dom->loadHTML($fileContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
							libxml_clear_errors();
							
							$body = $dom->getElementsByTagName('body')->item(0);
							if ($body) {
								$bodyContent = '';
								foreach ($body->childNodes as $child) {
									$bodyContent .= $dom->saveHTML($child);
								}
							}
						}
					}
					
					$page['content_html'] = $bodyContent;
					$fileFullPath = $realFile; // Store for saving
				} else {
					$error = 'Unable to read file: ' . htmlspecialchars($filePath);
					$isFileEdit = false;
				}
			} else {
			// Provide helpful error message with debugging info
			$error = 'File not found: ' . htmlspecialchars($filePath);
			$error .= '<br><small style="color:#6b7280;">Root: ' . htmlspecialchars($realRoot) . '</small>';
			$error .= '<br><small style="color:#6b7280;">Tried paths:<br>';
			foreach ($possiblePaths as $idx => $tryPath) {
				$exists = file_exists($tryPath) ? '✓' : '✗';
				$error .= ($idx + 1) . '. ' . $exists . ' ' . htmlspecialchars($tryPath) . '<br>';
			}
			$error .= '</small>';
			$isFileEdit = false;
		}
	}
}

function slugify($text) {
	$text = strtolower(trim($text));
	$text = preg_replace('/[^a-z0-9-]+/', '-', $text);
	$text = preg_replace('/-+/', '-', $text);
	return trim($text, '-');
}

function renderStaticPageHtml($pageData) {
	$cssHref = SITE_URL . '/assets/css/style.css';
	$title = htmlspecialchars($pageData['title'] ?? '', ENT_QUOTES, 'UTF-8');
	$content = $pageData['content_html'] ?? '';
	$customCss = $pageData['custom_css'] ?? '';
	ob_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $title; ?></title>
	<link rel="stylesheet" href="<?php echo $cssHref; ?>">
	<style>
		.page-container { max-width: 1280px; margin: 0 auto; padding: 40px 20px 80px; }
		@media (min-width: 1536px) { .page-container { max-width: 1440px; } }
		.hero-section { border-radius: 24px; padding: 48px; margin-bottom: 32px; position: relative; overflow: hidden; background: #f8fafc; color: #0f172a; }
		.hero-section.dark { background: #0f172a; color: #f8fafc; }
		.hero-section.hero-image { background-size: cover; background-position: center; color: #ffffff; }
		.hero-section .eyebrow { text-transform: uppercase; letter-spacing: .15em; font-size: 12px; margin-bottom: 10px; opacity: .75; }
		.hero-section h1 { font-size: clamp(2.2rem, 4vw, 3.5rem); margin-bottom: 16px; }
		.hero-section p { font-size: 1.1rem; max-width: 640px; }
		.hero-section .hero-btn { display: inline-flex; align-items: center; padding: 12px 22px; border-radius: 999px; background: #FF4F4F; color: #fff; text-decoration: none; margin-top: 20px; font-weight: 600; }
		.text-image { display: flex; gap: 28px; align-items: center; padding: 36px; border-radius: 20px; background: #f8fafc; margin-bottom: 32px; flex-wrap: wrap; }
		.text-image.reverse { flex-direction: row-reverse; }
		.text-image img { width: 42%; border-radius: 18px; object-fit: cover; flex: 1 1 320px; }
		.text-image .text { flex: 1 1 320px; }
		.text-image h3 { margin-top: 0; }
		.text-image .btn-primary { margin-top: 16px; display: inline-flex; padding: 10px 18px; border-radius: 10px; background:#111827; color:#fff; text-decoration:none; }
		.feature-section { margin: 40px 0; }
		.feature-section h3 { margin-bottom: 12px; }
		.feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
		.feature-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; background: #fff; }
		.feature-card h4 { margin-top: 0; margin-bottom: 8px; }
		.page-content p { line-height: 1.7; margin-bottom: 16px; }
		.page-content h1, .page-content h2, .page-content h3 { margin-top: 24px; }
		@media (max-width: 768px) {
			.hero-section { padding: 32px 24px; }
			.text-image { padding: 24px; }
			.text-image img { width: 100%; }
		}
	</style>
	<?php if (!empty($customCss)): ?>
	<style><?php echo $customCss; ?></style>
	<?php endif; ?>
</head>
<body>
	<main class="page-container">
		<article class="page-content">
			<?php echo $content; ?>
		</article>
	</main>
</body>
</html>
<?php
	return ob_get_clean();
}

function writeStaticPage($pageData) {
	$dir = __DIR__ . '/..';
	$path = $dir . '/' . $pageData['slug'] . '.html';
	file_put_contents($path, renderStaticPageHtml($pageData));
}

function deleteStaticPage($slug) {
	$path = __DIR__ . '/../' . $slug . '.html';
	if (is_file($path)) {
		unlink($path);
	}
}

	$action = $_POST['action'] ?? '';
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'export_static') {
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($csrf, $token)) {
		$error = 'Security token invalid.';
	} else {
		$title = trim($_POST['title'] ?? '');
		$slug = trim($_POST['slug'] ?? '');
		$content = $_POST['content_html'] ?? '';
		$customCss = $_POST['custom_css'] ?? '';
		$status = $_POST['status'] ?? 'draft';
		if (!in_array($status, ['draft', 'published'], true)) {
			$status = 'draft';
		}
		
		// Handle HTML file save
		$postFilePath = $_POST['file_path'] ?? '';
		$saveAsFile = !empty($postFilePath) || ($isFileEdit && !empty($filePath));
		$filePathToUse = !empty($postFilePath) ? $postFilePath : $filePath;
		
		if ($saveAsFile && !empty($filePathToUse)) {
			$rootDir = dirname(__DIR__);
			$realRoot = realpath($rootDir);
			
			if ($realRoot) {
				// Decode and normalize path (same as loading)
				$normalizedPath = urldecode($filePathToUse);
				$normalizedPath = str_replace('\\', '/', $normalizedPath);
				$normalizedPath = ltrim($normalizedPath, '/');
				
				// Ensure .html extension is present
				if (substr($normalizedPath, -5) !== '.html') {
					$normalizedPath .= '.html';
				}
				
				// Try to find the file using same method as loading
				$possiblePaths = [
					$realRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath),
					$rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath),
					$realRoot . '/' . $normalizedPath,
					$rootDir . '/' . $normalizedPath,
				];
				
				$targetFile = null;
				foreach ($possiblePaths as $tryPath) {
					$resolved = realpath(dirname($tryPath));
					if ($resolved && stripos($resolved, $realRoot) === 0) {
						// Directory exists and is within root, use this path
						$targetFile = $tryPath;
						break;
					}
				}
				
				// If no existing file found, use the first valid path
				if (!$targetFile) {
					$targetFile = $realRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);
				}
				
				// Final security check
				$targetDir = realpath(dirname($targetFile));
				if ($targetDir && stripos($targetDir, $realRoot) === 0) {
					// Ensure directory exists
					if (!is_dir($targetDir)) {
						@mkdir($targetDir, 0755, true);
					}
					
					// If file exists, preserve HTML structure and only update body
					$finalContent = $content;
					if (file_exists($targetFile)) {
						$originalContent = @file_get_contents($targetFile);
						if ($originalContent !== false && !empty($content)) {
							// Split by body tags to preserve everything else (head, attributes, etc)
							// This is much safer than DOMDocument or complex regex for large files
							$bodyStartPos = stripos($originalContent, '<body');
							if ($bodyStartPos !== false) {
								$bodyTagEndPos = strpos($originalContent, '>', $bodyStartPos);
								$bodyEndTagPos = stripos($originalContent, '</body>');
								
								if ($bodyTagEndPos !== false && $bodyEndTagPos !== false) {
									$head = substr($originalContent, 0, $bodyTagEndPos + 1);
									$tail = substr($originalContent, $bodyEndTagPos);
									$finalContent = $head . $content . $tail;
								}
							}
						}
					}
					
					// Save file if we have content
					if (!empty($finalContent) && @file_put_contents($targetFile, $finalContent) !== false) {
						$success = 'File saved successfully.';
						$page['content_html'] = $content; // Keep body content for editor
						// Update fileFullPath for potential reload
						$fileFullPath = $targetFile;
					} else {
						$error = 'Unable to write to file: ' . htmlspecialchars($normalizedPath);
					}
				} else {
					$error = 'Invalid file path (security check failed).';
				}
			} else {
				$error = 'Root directory not found.';
			}
		} else {
			// Handle database page save
			if ($title === '') {
				$error = 'Title is required.';
			} else {
				if ($slug === '') $slug = slugify($title);
				// Ensure slug does not conflict with existing static file outside of this page
				$staticPath = __DIR__ . '/../' . $slug . '.html';
				if (file_exists($staticPath) && (!$isEdit || $slug !== $originalSlug)) {
					$error = 'A static file with that slug already exists. Please choose a different slug.';
				} else {
				// Unique slug
				$check = $db->prepare("SELECT id FROM pages WHERE slug = ? AND id <> ?");
				try {
					$check->execute([$slug, $id]);
					if ($check->fetch()) {
						$error = 'Slug already exists.';
					} else {
						if ($isEdit) {
							$stmt = $db->prepare("UPDATE pages SET title = ?, slug = ?, content_html = ?, custom_css = ?, status = ?, published_at = CASE WHEN ? = 'published' AND published_at IS NULL THEN NOW() ELSE published_at END WHERE id = ?");
							$stmt->execute([$title, $slug, $content, $customCss ?: null, $status, $status, $id]);
							$success = 'Page updated.';
						} else {
							$stmt = $db->prepare("INSERT INTO pages (title, slug, content_html, custom_css, status, published_at) VALUES (?, ?, ?, ?, ?, ?)");
							$publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
							$stmt->execute([$title, $slug, $content, $customCss ?: null, $status, $publishedAt]);
							$id = (int)$db->lastInsertId();
							$isEdit = true;
							$success = 'Page created.';
						}
						// reload
						$page = ['title'=>$title,'slug'=>$slug,'content_html'=>$content,'custom_css'=>$customCss,'status'=>$status];
						$pageDataForExport = [
							'title' => $title,
							'slug' => $slug,
							'content_html' => $content,
							'custom_css' => $customCss
						];
						if ($status === 'published') {
							writeStaticPage($pageDataForExport);
						} else {
							deleteStaticPage($slug);
						}
						if ($originalSlug && $originalSlug !== $slug) {
							deleteStaticPage($originalSlug);
						}
					}
				} catch (Throwable $e) {
					$error = 'Save failed. Ensure the pages table exists.';
				}
				}
			}
		}
	}
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'gallery_upload') {
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($csrf, $token)) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Invalid token']);
		exit;
	}

	if (!isset($_FILES['file'])) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'No file uploaded']);
		exit;
	}

	$file = $_FILES['file'];
	$maxSize = 50 * 1024 * 1024; // 50MB
	if ($file['size'] > $maxSize) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'File too large. Max 50MB.']);
		exit;
	}

	$allowedImageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
	$allowedVideoExt = ['mp4', 'webm', 'ogg'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	
	$type = '';
	if (in_array($ext, $allowedImageExt)) {
		$type = 'image';
	} elseif (in_array($ext, $allowedVideoExt)) {
		$type = 'video';
	} else {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
		exit;
	}

	$uploadDir = __DIR__ . '/../uploads/gallery/';
	if (!file_exists($uploadDir)) {
		@mkdir($uploadDir, 0755, true);
	}

	$filename = 'gal_' . uniqid() . '.' . $ext;
	$destination = $uploadDir . $filename;

	if (move_uploaded_file($file['tmp_name'], $destination)) {
		$url = SITE_URL . '/uploads/gallery/' . $filename;
		echo json_encode(['success' => true, 'url' => $url, 'type' => $type]);
	} else {
		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
	}
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'hero_background_upload') {
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($csrf, $token)) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Invalid token']);
		exit;
	}

	if (!isset($_FILES['file'])) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'No file uploaded']);
		exit;
	}

	$file = $_FILES['file'];
	$maxSize = 100 * 1024 * 1024; // 100MB for hero backgrounds (videos can be larger)
	if ($file['size'] > $maxSize) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'File too large. Max 100MB.']);
		exit;
	}

	$allowedImageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
	$allowedVideoExt = ['mp4', 'webm', 'ogg'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	
	$type = '';
	if (in_array($ext, $allowedImageExt)) {
		$type = 'image';
	} elseif (in_array($ext, $allowedVideoExt)) {
		$type = 'video';
	} else {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images and videos allowed.']);
		exit;
	}

	$uploadDir = __DIR__ . '/../uploads/hero/';
	if (!file_exists($uploadDir)) {
		@mkdir($uploadDir, 0755, true);
	}

	$filename = 'hero_' . uniqid() . '.' . $ext;
	$destination = $uploadDir . $filename;

	if (move_uploaded_file($file['tmp_name'], $destination)) {
		$url = SITE_URL . '/uploads/hero/' . $filename;
		echo json_encode(['success' => true, 'url' => $url, 'type' => $type]);
	} else {
		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
	}
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'export_static') {
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($csrf, $token)) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Invalid token']);
		exit;
	}
	$pageId = (int)($_POST['page_id'] ?? 0);
	if ($pageId <= 0) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Missing page id']);
		exit;
	}
	$stmt = $db->prepare("SELECT title, slug, content_html, custom_css FROM pages WHERE id = ? LIMIT 1");
	$stmt->execute([$pageId]);
	$pageRow = $stmt->fetch();
	if (!$pageRow) {
		http_response_code(404);
		echo json_encode(['success' => false, 'message' => 'Page not found']);
		exit;
	}
	$html = renderStaticPageHtml($pageRow);
	header('Content-Type: application/json');
	echo json_encode(['success' => true, 'html' => $html, 'slug' => $pageRow['slug']]);
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $isEdit || $isFileEdit ? 'Edit Page' : 'New Page'; ?> - Admin</title>
	<link rel="stylesheet" href="../assets/css/admin.css">
	<!-- Live Preview Editor Styles -->
	<style>
		.admin-content { padding: 24px; }
		.form-grid { display:grid; grid-template-columns: 2fr 1fr; gap:16px; }
		.card { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; }
		.form-group { margin-bottom:12px; }
		label { display:block; font-size:13px; color:#374151; margin-bottom:6px; font-weight:500; }
		input[type="text"], select, textarea { width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; outline:none; font-size:14px; transition:border-color 0.2s; }
		input[type="text"]:focus, select:focus, textarea:focus { border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,0.1); }
		textarea { min-height: 280px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
		.actions { display:flex; gap:8px; }
		.btn { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#ffffff; cursor:pointer; text-decoration:none; transition:all 0.2s; }
		.btn:hover { background:#f8fafc; transform:translateY(-1px); box-shadow:0 2px 4px rgba(0,0,0,0.1); }
		/* Live Preview Editor Styles */
		.editor-container { position: relative; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background: #fff; }
		.editor-toolbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 12px 16px; display: flex; align-items: center; gap: 12px; }
		.editor-toolbar button { padding: 8px 16px; border: 1px solid #e5e7eb; border-radius: 6px; background: #fff; cursor: pointer; font-size: 14px; transition: all 0.2s; }
		.editor-toolbar button:hover { background: #f8fafc; border-color: #667eea; }
		.editor-toolbar button.active { background: #667eea; color: #fff; border-color: #667eea; }
		.preview-iframe { width: 100%; height: calc(100vh - 400px); min-height: 600px; border: none; background: #fff; }
		.editor-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; z-index: 1000; }
		.editable-highlight { outline: 2px dashed #667eea; outline-offset: 2px; background: rgba(102, 126, 234, 0.1) !important; cursor: text !important; }
		/* Text Formatting Toolbar */
		.text-format-toolbar { position: fixed; background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 100001; display: none; flex-wrap: wrap; gap: 8px; align-items: center; font-size: 12px; min-width: 400px; }
		.text-format-toolbar.active { display: flex; }
		.text-format-group { display: flex; align-items: center; gap: 6px; padding: 4px 8px; border-right: 1px solid #e5e7eb; }
		.text-format-group:last-child { border-right: none; }
		.text-format-label { font-weight: 600; color: #374151; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
		.text-format-color-container { display: flex; gap: 4px; }
		.text-format-color-btn { width: 28px; height: 28px; border-radius: 4px; border: 2px solid #e5e7eb; cursor: pointer; transition: all 0.2s; flex-shrink: 0; }
		.text-format-color-btn:hover { transform: scale(1.15); border-color: #667eea; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
		.text-format-color-btn.active { border-color: #667eea; border-width: 3px; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2); }
		.text-format-select, .text-format-input { padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; background: white; cursor: pointer; }
		.text-format-select:focus, .text-format-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
		.text-format-input { width: 70px; }
		.media-editable { position: relative; }
		.media-editable:hover::after { content: 'Click to change image/video'; position: absolute; top: 10px; left: 10px; background: #667eea; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 12px; z-index: 1001; pointer-events: none; }
		.media-editable:hover { outline: 3px solid #667eea; cursor: pointer; }
		.edit-mode-indicator { position: fixed; top: 20px; right: 20px; background: #667eea; color: #fff; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; font-size: 14px; font-weight: 600; }
		.draggable-section { cursor: move !important; }
		.draggable-section.dragging { opacity: 0.5; cursor: grabbing !important; }
		.draggable-section .drag-handle { cursor: grab !important; }
		.draggable-section .drag-handle:active { cursor: grabbing !important; }
		.gjs-block-category { 
			background:#667eea; 
			color:white; 
			padding:8px 12px; 
			margin:12px 0 8px 0; 
			border-radius:6px; 
			font-size:12px; 
			font-weight:600; 
			text-transform:uppercase; 
			letter-spacing:0.5px;
		}
		.gjs-block-category:first-child { margin-top:0; }
		.gjs-cv-canvas { background:#f8fafc; }
		.gjs-frame { background:#ffffff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin:20px; }
		.gjs-cv-canvas__frames { padding:20px; }
		.gjs-sm-sector { border-bottom:1px solid #e5e7eb; padding:12px 0; }
		.gjs-sm-sector .gjs-sm-title { font-weight:600; color:#111827; font-size:13px; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px; }
		.gjs-sm-label { font-weight:500; color:#374151; font-size:12px; }
		.gjs-sm-property { border-bottom:1px solid #f3f4f6; padding:8px 0; }
		.gjs-sm-property input, .gjs-sm-property select { border:1px solid #cbd5e1; border-radius:6px; padding:8px 10px; font-size:13px; width:100%; }
		.gjs-sm-property input:focus, .gjs-sm-property select:focus { border-color:#667eea; outline:none; box-shadow:0 0 0 3px rgba(102,126,234,0.1); }
		.gjs-clm-tags { background:#f8fafc; border-radius:6px; padding:4px; }
		.gjs-clm-tag { background:#667eea; color:white; border-radius:4px; padding:4px 8px; font-size:11px; }
		.gjs-selected { outline:2px solid #667eea !important; outline-offset:2px; }
		.panel__devices button, .panel__switcher button { 
			padding:8px 12px; 
			border:1px solid #e5e7eb; 
			background:#ffffff; 
			border-radius:6px; 
			cursor:pointer; 
			transition:all 0.2s;
			font-size:16px;
		}
		.panel__devices button:hover, .panel__switcher button:hover { 
			background:#f8fafc; 
			border-color:#667eea; 
		}
		.panel__devices button.active, .panel__switcher button.active { 
			background:#667eea; 
			color:#ffffff; 
			border-color:#667eea; 
		}
		.preview { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; position:sticky; top:24px; max-height:calc(100vh - 120px); display:flex; flex-direction:column; }
		.preview-header { padding:8px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280; font-size:12px; background:#f8fafc; display:flex; justify-content:space-between; align-items:center; }
		.preview-body { padding:16px; overflow:auto; flex:1; }
		/* Modal Popup for Live Preview */
		.preview-modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.75); backdrop-filter:blur(4px); }
		.preview-modal.active { display:flex; align-items:center; justify-content:center; }
		.preview-modal-content { background:#ffffff; border-radius:16px; width:95%; max-width:1400px; height:90vh; max-height:900px; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation:modalSlideIn 0.3s ease-out; }
		@keyframes modalSlideIn { from { opacity:0; transform:scale(0.95) translateY(-20px); } to { opacity:1; transform:scale(1) translateY(0); } }
		.preview-modal-header { padding:16px 20px; border-bottom:1px solid #e5e7eb; background:#f8fafc; display:flex; justify-content:space-between; align-items:center; border-radius:16px 16px 0 0; }
		.preview-modal-header h3 { margin:0; font-size:16px; font-weight:600; color:#111827; }
		.preview-modal-close { background:none; border:none; font-size:24px; color:#6b7280; cursor:pointer; padding:0; width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:6px; transition:all 0.2s; }
		.preview-modal-close:hover { background:#e5e7eb; color:#111827; }
		.preview-modal-body { padding:24px; overflow:auto; flex:1; background:#ffffff; }
		.preview-modal-actions { padding:12px 20px; border-top:1px solid #e5e7eb; background:#f8fafc; display:flex; gap:8px; justify-content:flex-end; border-radius:0 0 16px 16px; }
		/* Blocks */
		.builder-toolbar { margin-bottom:16px; }
		.builder-toolbar h4 { margin:0 0 8px; font-size:13px; color:#64748b; letter-spacing:.04em; text-transform:uppercase; }
		.block-palette { display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:8px; }
		.block-palette button { display:flex; align-items:center; gap:6px; padding:10px 12px; border-radius:10px; border:1px dashed #cbd5e1; background:#f8fafc; cursor:pointer; font-size:13px; color:#0f172a; transition:all .2s ease; }
		.block-palette button:hover { border-color:#FF4F4F; background:#fff5f5; color:#991b1b; }
		.preset-gallery { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-bottom:20px; }
		.preset-card { border:1px solid #e5e7eb; border-radius:12px; padding:12px; background:#fff; display:flex; flex-direction:column; gap:8px; }
		.preset-card h5 { margin:0; font-size:14px; }
		.preset-card p { margin:0; font-size:12px; color:#6b7280; }
		.preset-card button { align-self:flex-start; }
		.blocks-toolbar { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
		.btn-sm { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:8px; border:1px solid #e5e7eb; background:#ffffff; cursor:pointer; font-size:12px; }
		.btn-sm:hover { background:#f8fafc; }
		.block-item { border:1px solid #e5e7eb; border-radius:10px; padding:12px; margin-bottom:10px; background:#fff; transition:box-shadow .15s ease; }
		.block-item:hover { box-shadow:0 6px 12px rgba(15,23,42,.08); }
		.block-item.collapsed .block-fields { display:none; }
		.block-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; gap:8px; }
		.block-title { font-size:13px; color:#374151; font-weight:600; }
		.block-actions { display:flex; gap:6px; }
		.block-fields { display:grid; gap:8px; }
		.row-2 { display:grid; grid-template-columns: 1fr 1fr; gap:8px; }
		.helper-note { font-size:12px; color:#6b7280; margin-bottom:8px; }
		/* Drag in preview */
		.preview-block { border:1px dashed #e5e7eb; padding:10px; border-radius:8px; margin-bottom:10px; background:#fff; cursor:move; }
		.preview-block.drag-over { background:#f8fafc; border-color:#93c5fd; }
		/* preview section styles */
		.hero-section { border-radius:20px; padding:48px; position:relative; overflow:hidden; }
		.hero-section.dark { background:#0f172a; color:#f8fafc; }
		.hero-section.light { background:#f8fafc; color:#0f172a; }
		.hero-section .hero-btn { display:inline-flex; align-items:center; padding:10px 18px; border-radius:999px; background:#FF4F4F; color:#fff; text-decoration:none; margin-top:16px; }
		.text-image { display:flex; gap:24px; align-items:center; padding:32px; border-radius:16px; background:#f8fafc; }
		.text-image .text { flex:1; }
		.text-image.reverse { flex-direction:row-reverse; }
		.text-image img { width:45%; border-radius:16px; object-fit:cover; flex:1; }
		.feature-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; }
		.feature-card { border:1px solid #e5e7eb; border-radius:12px; padding:16px; background:#fff; }
	</style>
</head>
<body>
	<?php include __DIR__ . '/includes/header.php'; ?>
	<div class="admin-content">
		<div class="actions" style="margin-bottom:12px;">
			<a class="btn" href="pages.php">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 18l-6-6 6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<span>Back to Pages</span>
			</a>
			<?php if ($isEdit): ?>
			<button type="button" class="btn" onclick="exportStaticPage()" title="Generate and download static HTML">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<span>Export Static HTML</span>
			</button>
			<?php endif; ?>
		</div>

		<?php if ($error): ?>
			<div class="alert alert-error" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:10px 12px;border-radius:8px;margin-bottom:14px;">&nbsp;<?php echo escape($error); ?></div>
		<?php endif; ?>
		<?php if ($success): ?>
			<div class="alert alert-success" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:10px 12px;border-radius:8px;margin-bottom:14px;">&nbsp;<?php echo escape($success); ?></div>
		<?php endif; ?>

		<form method="POST" action="" id="page-form">
			<input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
			<?php if ($isFileEdit): ?>
			<input type="hidden" name="file_path" value="<?php echo escape($originalFilePath); ?>">
			<?php endif; ?>
			
			<div style="margin-bottom:16px; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px;">
				<div class="form-grid" style="grid-template-columns: 1fr 1fr; gap:16px;">
					<div class="form-group">
						<label for="title">Title</label>
						<input type="text" id="title" name="title" value="<?php echo escape($page['title']); ?>" required>
					</div>
					<?php if (!$isFileEdit): ?>
					<div class="form-group">
						<label for="slug">Slug</label>
						<input type="text" id="slug" name="slug" value="<?php echo escape($page['slug']); ?>" placeholder="auto-from-title">
					</div>
					<?php endif; ?>
				</div>
				<?php if (!$isFileEdit): ?>
				<div class="form-group" style="margin-top:12px;">
					<label for="status">Status</label>
					<select id="status" name="status">
						<option value="draft" <?php echo $page['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
						<option value="published" <?php echo $page['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
					</select>
				</div>
				<?php endif; ?>
			</div>
			
			<!-- Live Preview Editor -->
			<div style="margin-bottom:16px; padding:12px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); border:1px solid #e5e7eb; border-radius:8px; color:white;">
				<p style="margin:0; font-size:13px;">
					<strong>💡 Live Preview Editor:</strong> Click on any text to edit it directly. Click on images or videos to replace them. Your changes are saved when you click "Save Page".
				</p>
			</div>
			<div class="editor-container">
				<div class="editor-toolbar">
					<button type="button" id="edit-mode-btn" class="active">✏️ Edit Mode</button>
					<button type="button" id="preview-mode-btn">👁️ Preview Mode</button>
					<button type="button" id="toggle-gallery-btn" style="display: none; margin-left: 8px; border-color: #18F1E1; color: #1F1F1F; font-weight: 600;">🖼️ Add Gallery</button>
					<div style="flex: 1;"></div>
					<button type="button" id="mobile-preview-btn">📱 Mobile</button>
					<button type="button" id="tablet-preview-btn">📱 Tablet</button>
					<button type="button" id="desktop-preview-btn" class="active">🖥️ Desktop</button>
				</div>
				<div id="edit-mode-indicator" class="edit-mode-indicator" style="display: none;">✏️ Edit Mode Active - Click text to edit</div>
				<iframe id="preview-iframe" class="preview-iframe" src="<?php 
					if ($isFileEdit && !empty($originalFilePath)) {
						// Use the original file path and ensure it's a proper relative URL
						$iframePath = $originalFilePath;
						// Remove leading slash if present, then add ../
						$iframePath = ltrim($iframePath, '/');
						$iframePath = '../' . $iframePath;
						// Ensure .html extension is present
						if (substr($iframePath, -5) !== '.html') {
							$iframePath .= '.html';
						}
						$iframeUrl = htmlspecialchars($iframePath, ENT_QUOTES, 'UTF-8');
						echo $iframeUrl . (strpos($iframeUrl, '?') !== false ? '&' : '?') . 'v=' . time();
					} elseif ($isFileEdit) {
						// Fallback: construct from current filePath
						$iframePath = '../' . ltrim($filePath, '/');
						if (substr($iframePath, -5) !== '.html') {
							$iframePath .= '.html';
						}
						$iframeUrl = htmlspecialchars($iframePath, ENT_QUOTES, 'UTF-8');
						echo $iframeUrl . (strpos($iframeUrl, '?') !== false ? '&' : '?') . 'v=' . time();
					} else {
						echo '../page.php?slug=' . urlencode($page['slug']) . '&v=' . time();
					}
				?>"></iframe>
			</div>
			
			<!-- Hidden inputs for form submission -->
			<textarea id="content_html" name="content_html" style="display:none;"><?php echo htmlspecialchars($page['content_html'] ?? '', ENT_NOQUOTES, 'UTF-8'); ?></textarea>
			<textarea id="custom_css" name="custom_css" style="display:none;"><?php echo htmlspecialchars($page['custom_css'] ?? '', ENT_NOQUOTES, 'UTF-8'); ?></textarea>
			
			<div class="actions" style="margin-top:16px;">
				<button class="btn" type="submit" style="background:#667eea; color:#fff; border-color:#667eea;">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="17 21 17 13 7 13 7 21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="7" y1="3" x2="7" y2="8" x1="15" y2="8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<span>Save Page</span>
				</button>
				<?php if ($isEdit && !$isFileEdit): ?>
				<a class="btn" href="../page.php?slug=<?php echo urlencode($page['slug']); ?>" target="_blank">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 3h7v7m-1-6L10 14M5 5h5M5 10v9h9v-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<span>View</span>
				</a>
				<?php elseif ($isFileEdit): ?>
				<a class="btn" href="<?php echo escape($filePath); ?>" target="_blank">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 3h7v7m-1-6L10 14M5 5h5M5 10v9h9v-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<span>View</span>
				</a>
				<?php endif; ?>
			</div>
		</form>
	</div>
	<!-- Live Preview Editor JS -->
	<script>
	const csrfToken = '<?php echo $csrf; ?>';
	const currentPageId = <?php echo $isEdit ? (int)$id : 'null'; ?>;
	const isFileEdit = <?php echo $isFileEdit ? 'true' : 'false'; ?>;
	const pageUrl = <?php echo json_encode($isFileEdit ? $filePath : '../page.php?slug=' . urlencode($page['slug'])); ?>;
	let editMode = true;
	let changes = {};

	// Live Preview Editor
	document.addEventListener('DOMContentLoaded', function() {
		const iframe = document.getElementById('preview-iframe');
		const editModeBtn = document.getElementById('edit-mode-btn');
		const previewModeBtn = document.getElementById('preview-mode-btn');
		const mobileBtn = document.getElementById('mobile-preview-btn');
		const tabletBtn = document.getElementById('tablet-preview-btn');
		const desktopBtn = document.getElementById('desktop-preview-btn');
		const indicator = document.getElementById('edit-mode-indicator');
		
		if (!iframe) {
			console.error('Preview iframe not found!');
			return;
		}
		
		// Wait for iframe to load
		iframe.addEventListener('load', function() {
			initEditor();
		});
		
		// Initialize editor if iframe is already loaded
		if (iframe.contentDocument && iframe.contentDocument.readyState === 'complete') {
			initEditor();
		}
		
		function initEditor() {
			try {
				const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
				const iframeBody = iframeDoc.body;
				
				if (!iframeBody) {
					console.error('Iframe body not found');
					return;
				}

				// Check for Gallery Section
				const galleryBtn = document.getElementById('toggle-gallery-btn');
				let gallerySection = null;
				
				// Look for section with Gallery heading
				const sections = iframeDoc.querySelectorAll('section');
				sections.forEach(sec => {
					const h2 = sec.querySelector('h2');
					if (h2 && h2.textContent.trim().toLowerCase() === 'gallery') {
						gallerySection = sec;
					}
				});

				if (gallerySection && galleryBtn) {
					galleryBtn.style.display = 'inline-flex';
					const computedStyle = iframeDoc.defaultView.getComputedStyle(gallerySection);
					const isHidden = computedStyle.display === 'none';
					galleryBtn.innerHTML = isHidden ? '🖼️ Add Gallery' : '🖼️ Hide Gallery';
					
					// Add "Add Item" button if it doesn't exist
					let galleryGrid = gallerySection.querySelector('.gallery-grid');
					if (galleryGrid) {
						// Add hover action to gallery items for removal
						const setupGalleryItems = () => {
							galleryGrid.querySelectorAll('.gallery-item-container').forEach(item => {
								if (item.querySelector('.remove-item-btn')) return;
								
								item.style.position = 'relative';
								const removeBtn = iframeDoc.createElement('button');
								removeBtn.innerHTML = '×';
								removeBtn.className = 'remove-item-btn';
								removeBtn.style.cssText = 'position:absolute; top:5px; right:5px; background:rgba(255,0,0,0.7); color:white; border:none; border-radius:50%; width:24px; height:24px; cursor:pointer; z-index:10; display:none; align-items:center; justify-content:center; font-size:18px; font-weight:bold;';
								
								item.appendChild(removeBtn);
								
								item.addEventListener('mouseenter', () => { if(editMode) removeBtn.style.display = 'flex'; });
								item.addEventListener('mouseleave', () => { removeBtn.style.display = 'none'; });
								
								removeBtn.onclick = (e) => {
									e.stopPropagation();
									if (confirm('Remove this item from gallery?')) {
										item.remove();
										changes['gallery-update'] = { type: 'gallery', action: 'update' };
									}
								};
							});
						};
						
						setupGalleryItems();

						// Global Add Item logic
						galleryBtn.onclick = function() {
							const currentStyle = gallerySection.getAttribute('style') || '';
							if (gallerySection.style.display === 'none' || computedStyle.display === 'none') {
								gallerySection.style.display = 'block';
								gallerySection.setAttribute('style', currentStyle.replace(/display:\s*none;?/gi, '').trim());
								galleryBtn.innerHTML = '🖼️ Hide Gallery';
								changes['gallery-visibility'] = { type: 'visibility', value: 'block' };
								
								// If it was hidden, show the "Add Media" prompt
								if (galleryGrid.children.length === 0 || (galleryGrid.children.length === 3 && galleryGrid.querySelector('svg'))) {
									// Clear placeholders if they exist
									if (galleryGrid.querySelector('svg')) {
										galleryGrid.innerHTML = '';
									}
									promptAddMedia();
								}
							} else {
								gallerySection.style.display = 'none';
								if (!gallerySection.getAttribute('style')?.includes('display: none')) {
									const style = gallerySection.getAttribute('style') || '';
									gallerySection.setAttribute('style', (style.endsWith(';') ? style : style + (style ? ';' : '')) + ' display: none;');
								}
								galleryBtn.innerHTML = '🖼️ Add Gallery';
								changes['gallery-visibility'] = { type: 'visibility', value: 'none' };
							}
						};

						// Add a second button specifically for adding media if gallery is visible
						let addMediaBtn = document.getElementById('add-gallery-item-btn');
						if (!addMediaBtn) {
							addMediaBtn = document.createElement('button');
							addMediaBtn.id = 'add-gallery-item-btn';
							addMediaBtn.type = 'button';
							addMediaBtn.innerHTML = '➕ Add Photo/Video';
							addMediaBtn.style.cssText = 'display:none; margin-left:8px; border-color:#FF4F4F; color:#1F1F1F; font-weight:600;';
							galleryBtn.parentNode.insertBefore(addMediaBtn, galleryBtn.nextSibling);
						}
						
						const updateAddMediaBtnVisibility = () => {
							const isVisible = gallerySection.style.display !== 'none' && computedStyle.display !== 'none';
							addMediaBtn.style.display = isVisible ? 'inline-flex' : 'none';
						};
						
						updateAddMediaBtnVisibility();
						
						// Observe visibility changes to update addMediaBtn
						const observer = new MutationObserver(updateAddMediaBtnVisibility);
						observer.observe(gallerySection, { attributes: true, attributeFilter: ['style'] });

						function promptAddMedia() {
							const input = document.createElement('input');
							input.type = 'file';
							input.accept = 'image/*,video/*';
							input.onchange = function(e) {
								const file = e.target.files[0];
								if (file) {
									uploadGalleryFile(file);
								}
							};
							input.click();
						}

						addMediaBtn.onclick = promptAddMedia;

						function uploadGalleryFile(file) {
							const formData = new FormData();
							formData.append('file', file);
							formData.append('action', 'gallery_upload');
							formData.append('csrf_token', csrfToken);

							addMediaBtn.disabled = true;
							addMediaBtn.innerHTML = '⏳ Uploading...';

							fetch(window.location.href, {
								method: 'POST',
								body: formData
							})
							.then(res => res.json())
							.then(data => {
								if (data.success) {
									const container = iframeDoc.createElement('div');
									container.className = 'gallery-item-container';
									
									let media;
									const filename = data.url.split('/').pop();
									// Use relative path for static files, absolute for DB pages
									const finalUrl = isFileEdit ? '../uploads/gallery/' + filename : data.url;
									
									if (data.type === 'image') {
										media = iframeDoc.createElement('img');
										media.src = finalUrl;
									} else {
										media = iframeDoc.createElement('video');
										media.src = finalUrl;
										media.controls = true;
									}
									
									container.appendChild(media);
									
									// Remove placeholders if they are still there
									if (galleryGrid.querySelector('svg')) {
										galleryGrid.innerHTML = '';
									}
									
									galleryGrid.appendChild(container);
									setupGalleryItems();
									changes['gallery-update'] = { type: 'gallery', action: 'add' };
								} else {
									alert('Upload failed: ' + (data.message || 'Unknown error'));
								}
							})
							.catch(err => {
								console.error(err);
								alert('Upload failed. Please check file size and try again.');
							})
							.finally(() => {
								addMediaBtn.disabled = false;
								addMediaBtn.innerHTML = '➕ Add Photo/Video';
							});
						}
					}
				}
				
				// Make text elements editable
				function makeEditable(element) {
					if (element.tagName && ['SCRIPT', 'STYLE', 'NOSCRIPT', 'SVG'].includes(element.tagName)) {
						return;
					}

					// Skip elements that are likely used for icons or specific functional components
					if (element.classList && (
						element.classList.contains('feature-item-icon') || 
						element.classList.contains('feature-icon') ||
						element.classList.contains('modern-btn-call') ||
						element.classList.contains('drag-handle') ||
						element.classList.contains('move-section-btn') ||
						element.classList.contains('section-controls')
					)) {
						return;
					}
					
					// Skip elements with data-non-editable attribute
					if (element.hasAttribute && element.hasAttribute('data-non-editable')) {
						return;
					}
					
					// Skip section controls and their children completely
					if (element.closest && (
						element.closest('.drag-handle') ||
						element.closest('.section-controls') ||
						element.closest('.move-section-btn') ||
						element.closest('[data-non-editable]')
					)) {
						return;
					}
					
					// Handle links and buttons specially to prevent navigation in edit mode
					// BUT skip section control buttons - they should not be editable
					if ((element.tagName === 'A' || element.tagName === 'BUTTON') && 
					    !element.classList.contains('move-section-btn') &&
					    !element.closest('.section-controls')) {
						if (!element.hasAttribute('data-link-editable')) {
							element.setAttribute('data-link-editable', 'true');
							element.classList.add('editable-link');
							
							// Prevent navigation/action in edit mode
							element.addEventListener('click', function(e) {
								if (editMode) {
									e.preventDefault();
									e.stopPropagation();
									e.stopImmediatePropagation();
									// Make it editable on click
									this.contentEditable = 'true';
									this.focus();
									// Select all text for easy editing
									const range = iframeDoc.createRange();
									range.selectNodeContents(this);
									const selection = iframeDoc.defaultView.getSelection();
									selection.removeAllRanges();
									selection.addRange(range);
								}
							}, true); // Use capture phase to catch before navigation
							
							// Track changes when editing is done
							element.addEventListener('blur', function() {
								this.contentEditable = 'false';
								const newText = this.textContent;
								const oldText = this.getAttribute('data-original-text') || this.textContent;
								if (newText !== oldText) {
									changes[this.getAttribute('data-id') || this.outerHTML.substring(0, 50)] = {
										old: oldText,
										new: newText,
										element: this
									};
								}
							});
							
							element.setAttribute('data-original-text', element.textContent);
							element.setAttribute('data-id', 'link-' + Date.now() + '-' + Math.random());
						}
						return; // Don't process children of links/buttons to avoid double processing
					}
					
					// Make text nodes editable
					if (element.nodeType === 3 && element.textContent.trim()) {
						const parent = element.parentNode;
						// Skip if parent is a link or button (handled separately)
						if (parent && (parent.tagName === 'A' || parent.tagName === 'BUTTON')) {
							return;
						}
						if (parent && !parent.isContentEditable) {
							parent.contentEditable = 'true';
							parent.classList.add('editable-text');
							parent.addEventListener('blur', function() {
								const newText = this.textContent;
								const oldText = this.getAttribute('data-original-text');
								if (newText !== oldText) {
									changes[this.getAttribute('data-id') || this.outerHTML.substring(0, 50)] = {
										old: oldText,
										new: newText,
										element: this
									};
								}
							});
							parent.setAttribute('data-original-text', element.textContent);
							parent.setAttribute('data-id', 'text-' + Date.now() + '-' + Math.random());
						}
					}
					
					// Make images and videos editable
					if (element.tagName === 'IMG' || element.tagName === 'VIDEO') {
						element.classList.add('media-editable');
						element.setAttribute('data-id', 'media-' + Date.now() + '-' + Math.random());
						element.addEventListener('click', function(e) {
							if (editMode) {
								e.preventDefault();
								editMedia(this);
							}
						});
					}
					
					// Recursively process children
					for (let child of element.childNodes) {
						makeEditable(child);
					}
				}
				
				// Initialize editing on all elements
				makeEditable(iframeBody);
				
				// Setup text formatting toolbar
				let textFormatToolbar = null;
				let currentFormattedElement = null;
				
				function createTextFormatToolbar() {
					if (textFormatToolbar) return textFormatToolbar;
					
					// Create toolbar in parent document (not iframe)
					textFormatToolbar = document.createElement('div');
					textFormatToolbar.className = 'text-format-toolbar';
					textFormatToolbar.id = 'text-format-toolbar';
					
					// Color options
					const colors = [
						{ name: 'Carbon Black', value: '#1F1F1F' },
						{ name: 'Canvas White', value: '#FFFFFF' },
						{ name: 'Vibrant Coral', value: '#FF4F4F' },
						{ name: 'Electric Aqua', value: '#18F1E1' }
					];
					
					const colorButtons = colors.map(color => 
						`<button class="text-format-color-btn" data-color="${color.value}" style="background:${color.value};" title="${color.name}"></button>`
					).join('');
					
					textFormatToolbar.innerHTML = `
						<div class="text-format-group">
							<span class="text-format-label">Color</span>
							<div class="text-format-color-container">
								${colorButtons}
							</div>
						</div>
						<div class="text-format-group">
							<span class="text-format-label">Font</span>
							<select class="text-format-select" id="text-format-font-family">
								<option value="Azo Sans">Azo Sans</option>
								<option value="Azo Sans Uber">Azo Sans Uber</option>
							</select>
						</div>
						<div class="text-format-group">
							<span class="text-format-label">Size</span>
							<input type="number" class="text-format-input" id="text-format-font-size" min="8" max="120" value="16" step="1">
						</div>
						<div class="text-format-group">
							<span class="text-format-label">Weight</span>
							<select class="text-format-select" id="text-format-font-weight">
								<option value="300">Light (300)</option>
								<option value="400" selected>Regular (400)</option>
								<option value="500">Medium (500)</option>
								<option value="600">Semi Bold (600)</option>
								<option value="700">Bold (700)</option>
								<option value="800">Extra Bold (800)</option>
								<option value="900">Black (900)</option>
							</select>
						</div>
					`;
					
					document.body.appendChild(textFormatToolbar);
					
					// Color button handlers
					textFormatToolbar.querySelectorAll('.text-format-color-btn').forEach(btn => {
						btn.addEventListener('click', function() {
							const color = this.getAttribute('data-color');
							applyTextFormatting('color', color);
							// Update active state
							textFormatToolbar.querySelectorAll('.text-format-color-btn').forEach(b => b.classList.remove('active'));
							this.classList.add('active');
						});
					});
					
					// Font family handler
					const fontFamilySelect = textFormatToolbar.querySelector('#text-format-font-family');
					fontFamilySelect.addEventListener('change', function() {
						applyTextFormatting('fontFamily', this.value);
					});
					
					// Font size handler
					const fontSizeInput = textFormatToolbar.querySelector('#text-format-font-size');
					fontSizeInput.addEventListener('change', function() {
						applyTextFormatting('fontSize', this.value + 'px');
					});
					
					// Font weight handler
					const fontWeightSelect = textFormatToolbar.querySelector('#text-format-font-weight');
					fontWeightSelect.addEventListener('change', function() {
						applyTextFormatting('fontWeight', this.value);
					});
					
					// Keep toolbar visible when hovering over it
					textFormatToolbar.addEventListener('mouseenter', function() {
						// Keep it visible
					});
					textFormatToolbar.addEventListener('mouseleave', function() {
						hideTextFormatToolbar();
					});
					
					return textFormatToolbar;
				}
				
				function showTextFormatToolbar(element, event) {
					if (!editMode) return;
					
					// Only show for editable text elements
					if (!element.classList.contains('editable-text') && 
					    !element.classList.contains('editable-link') &&
					    !element.closest('.editable-text') &&
					    !element.closest('.editable-link')) {
						return;
					}
					
					// Skip if element is inside section controls or other non-editable areas
					if (element.closest('.section-controls') || 
					    element.closest('.hero-change-bg-btn') ||
					    element.closest('.change-section-bg-btn')) {
						return;
					}
					
					// Get the actual editable element
					const editableEl = element.classList.contains('editable-text') || element.classList.contains('editable-link') 
						? element 
						: element.closest('.editable-text, .editable-link');
					
					if (!editableEl) return;
					
					currentFormattedElement = editableEl;
					
					// Create toolbar if it doesn't exist
					if (!textFormatToolbar) {
						createTextFormatToolbar();
					}
					
					// Get current formatting
					const computedStyle = iframeDoc.defaultView.getComputedStyle(editableEl);
					const currentColor = computedStyle.color;
					const currentFontSize = computedStyle.fontSize;
					const currentFontWeight = computedStyle.fontWeight;
					const currentFontFamily = computedStyle.fontFamily;
					
					// Update toolbar with current values
					const fontSizeInput = textFormatToolbar.querySelector('#text-format-font-size');
					const fontWeightSelect = textFormatToolbar.querySelector('#text-format-font-weight');
					const fontFamilySelect = textFormatToolbar.querySelector('#text-format-font-family');
					
					// Extract numeric font size
					const fontSizeNum = parseInt(currentFontSize);
					if (!isNaN(fontSizeNum)) {
						fontSizeInput.value = fontSizeNum;
					}
					
					// Set font weight
					fontWeightSelect.value = currentFontWeight;
					
					// Set font family (check if it contains Azo Sans Uber)
					if (currentFontFamily.includes('Azo Sans Uber') || currentFontFamily.includes('AzoSansUber')) {
						fontFamilySelect.value = 'Azo Sans Uber';
					} else {
						fontFamilySelect.value = 'Azo Sans';
					}
					
					// Update active color button
					textFormatToolbar.querySelectorAll('.text-format-color-btn').forEach(btn => {
						btn.classList.remove('active');
						const btnColor = btn.getAttribute('data-color');
						// Convert current color to hex for comparison
						const rgbToHex = (rgb) => {
							if (rgb.startsWith('#')) return rgb.toUpperCase();
							const match = rgb.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
							if (!match) return null;
							const toHex = (n) => {
								const hex = parseInt(n).toString(16);
								return hex.length === 1 ? '0' + hex : hex;
							};
							return '#' + toHex(match[1]) + toHex(match[2]) + toHex(match[3]);
						};
						const currentHex = rgbToHex(currentColor);
						if (currentHex && currentHex.toUpperCase() === btnColor.toUpperCase()) {
							btn.classList.add('active');
						}
					});
					
					// Position toolbar near the element
					// Get element position relative to iframe
					const rect = editableEl.getBoundingClientRect();
					// Get iframe position relative to viewport
					const iframeRect = iframe.getBoundingClientRect();
					
					// Calculate position relative to viewport (parent document)
					const toolbarWidth = 450;
					const toolbarX = iframeRect.left + rect.left + (rect.width / 2) - (toolbarWidth / 2);
					const toolbarY = iframeRect.top + rect.top - 65; // Above the element
					
					// Ensure toolbar stays within viewport
					const finalX = Math.max(10, Math.min(toolbarX, window.innerWidth - toolbarWidth - 10));
					const finalY = Math.max(10, Math.min(toolbarY, window.innerHeight - 60));
					
					textFormatToolbar.style.left = finalX + 'px';
					textFormatToolbar.style.top = finalY + 'px';
					textFormatToolbar.classList.add('active');
				}
				
				function hideTextFormatToolbar() {
					if (textFormatToolbar) {
						textFormatToolbar.classList.remove('active');
						currentFormattedElement = null;
					}
				}
				
				function applyTextFormatting(property, value) {
					if (!currentFormattedElement) return;
					
					const element = currentFormattedElement;
					
					// Apply formatting
					if (property === 'color') {
						element.style.color = value;
					} else if (property === 'fontFamily') {
						if (value === 'Azo Sans Uber') {
							element.style.fontFamily = '"Azo Sans Uber", "AzoSansUber-Regular", sans-serif';
						} else {
							element.style.fontFamily = '"Azo Sans", "AzoSans-Regular", sans-serif';
						}
					} else if (property === 'fontSize') {
						element.style.fontSize = value;
					} else if (property === 'fontWeight') {
						element.style.fontWeight = value;
					}
					
					// Track changes
					const elementId = element.getAttribute('data-id') || 'text-' + Date.now();
					changes[elementId] = {
						type: 'text-formatting',
						property: property,
						value: value,
						element: element
					};
				}
				
				// Add hover listeners for formatting toolbar
				let hoverTimeout = null;
				
				iframeDoc.addEventListener('mouseover', function(e) {
					if (!editMode) return;
					
					// Clear any existing timeout
					if (hoverTimeout) {
						clearTimeout(hoverTimeout);
					}
					
					// Check if element is editable text
					const isEditable = e.target.classList.contains('editable-text') || 
					                  e.target.classList.contains('editable-link') ||
					                  e.target.closest('.editable-text') ||
					                  e.target.closest('.editable-link');
					
					if (isEditable && !e.target.closest('.section-controls')) {
						// Small delay to avoid flickering
						hoverTimeout = setTimeout(() => {
							showTextFormatToolbar(e.target, e);
						}, 200);
					}
				});
				
				iframeDoc.addEventListener('mouseout', function(e) {
					// Clear show timeout
					if (hoverTimeout) {
						clearTimeout(hoverTimeout);
						hoverTimeout = null;
					}
					
					// Hide toolbar when mouse leaves editable elements (unless moving to toolbar)
					const relatedTarget = e.relatedTarget;
					const isMovingToToolbar = textFormatToolbar && 
					                         (relatedTarget === textFormatToolbar || 
					                          (relatedTarget && textFormatToolbar.contains(relatedTarget)));
					
					if (!isMovingToToolbar) {
						setTimeout(() => {
							// Double check that mouse is not over toolbar
							if (!textFormatToolbar || !textFormatToolbar.matches(':hover')) {
								hideTextFormatToolbar();
							}
						}, 150);
					}
				});
				
				
				// Make hero backgrounds editable
				function setupHeroBackgroundEditing() {
					// Find hero sections - look for sections with id="hero" or class containing "hero"
					const heroSections = iframeDoc.querySelectorAll('section#hero, section[class*="hero"], .hero-section, .hero-video-container');
					
					heroSections.forEach(heroSection => {
						// Remove existing button if present (to allow re-initialization)
						const existingButton = heroSection.querySelector('.hero-change-bg-btn');
						if (existingButton) {
							existingButton.remove();
						}
						
						// Remove the data attribute to allow re-initialization
						heroSection.removeAttribute('data-hero-editable');
						heroSection.removeAttribute('data-hero-hover-handler');
						heroSection.setAttribute('data-hero-editable', 'true');
						
						// Find background container - could be a div with background-image or a video element
						// First check for video (including newly added ones)
						let bgContainer = heroSection.querySelector('video.hero-video, video[class*="hero"], video');
						
						// If no video, look for div with background-image style
						if (!bgContainer) {
							// Try multiple selectors to find the background div
							const possibleSelectors = [
								'.absolute.inset-0[style*="background-image"]',
								'.absolute.inset-0.bg-cover',
								'div[style*="background-image"]',
								'.absolute.inset-0'
							];
							
							for (let selector of possibleSelectors) {
								const divs = heroSection.querySelectorAll(selector);
								for (let div of divs) {
									const style = div.getAttribute('style') || '';
									const computedStyle = iframeDoc.defaultView.getComputedStyle(div);
									// Check if it has background-image and is not the overlay
									const hasBgImage = style.includes('background-image') || computedStyle.backgroundImage !== 'none';
									const isNotOverlay = !div.classList.contains('hero-overlay') && !div.classList.contains('overlay');
									
									if (hasBgImage && isNotOverlay) {
										bgContainer = div;
										break;
									}
								}
								if (bgContainer) break;
							}
						}
						
						// If still no container found, check if section itself has background
						if (!bgContainer) {
							const computedStyle = iframeDoc.defaultView.getComputedStyle(heroSection);
							if (computedStyle.backgroundImage && computedStyle.backgroundImage !== 'none') {
								bgContainer = heroSection;
							}
						}
						
						if (bgContainer) {
							bgContainer.classList.add('hero-bg-editable');
							if (!bgContainer.getAttribute('data-hero-id')) {
								bgContainer.setAttribute('data-hero-id', 'hero-' + Date.now() + '-' + Math.random());
							}
							
							// Ensure hero section has relative positioning for button placement
							const heroPosition = iframeDoc.defaultView.getComputedStyle(heroSection).position;
							if (heroPosition === 'static') {
								heroSection.style.position = 'relative';
							}
							
							// Always create a fresh button (remove old one first)
							let changeBgButton = heroSection.querySelector('.hero-change-bg-btn');
							if (changeBgButton) {
								changeBgButton.remove();
							}
							
							// Create new button
							changeBgButton = iframeDoc.createElement('button');
							changeBgButton.className = 'hero-change-bg-btn';
							changeBgButton.innerHTML = '🖼️ Change Background';
							changeBgButton.style.cssText = `
								position: absolute;
								top: 20px;
								right: 20px;
								background: rgba(102, 126, 234, 0.95);
								color: white;
								border: 2px solid white;
								padding: 12px 20px;
								border-radius: 8px;
								font-size: 14px;
								font-weight: 600;
								cursor: pointer;
								z-index: 40;
								display: none;
								box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
								transition: all 0.3s ease;
								pointer-events: auto;
							`;
							changeBgButton.onmouseenter = function() {
								this.style.background = 'rgba(102, 126, 234, 1)';
								this.style.transform = 'scale(1.05)';
							};
							changeBgButton.onmouseleave = function() {
								this.style.background = 'rgba(102, 126, 234, 0.95)';
								this.style.transform = 'scale(1)';
							};
							heroSection.appendChild(changeBgButton);
							
							// Remove old event listeners by cloning (clean way to remove all listeners)
							// But we'll use a flag to prevent duplicate listeners instead
							if (!heroSection.hasAttribute('data-hero-hover-handler')) {
								heroSection.setAttribute('data-hero-hover-handler', 'true');
							} else {
								// If already has handler, remove and re-add to ensure fresh listeners
								heroSection.removeAttribute('data-hero-hover-handler');
								heroSection.setAttribute('data-hero-hover-handler', 'true');
							}
							
							// Remove any existing listeners by using a new function wrapper
							const mouseEnterHandler = function() {
								if (editMode && changeBgButton) {
									changeBgButton.style.display = 'block';
									// Add subtle outline to background
									if (bgContainer) {
										bgContainer.style.outline = '3px solid rgba(102, 126, 234, 0.5)';
										bgContainer.style.outlineOffset = '2px';
									}
								}
							};
							
							const mouseLeaveHandler = function(e) {
								// Only hide if not hovering over the button itself
								if (changeBgButton && !changeBgButton.contains(e.relatedTarget)) {
									changeBgButton.style.display = 'none';
									if (bgContainer) {
										bgContainer.style.outline = '';
										bgContainer.style.outlineOffset = '';
									}
								}
							};
							
							const buttonMouseEnterHandler = function() {
								this.style.display = 'block';
							};
							
							const buttonClickHandler = function(e) {
								if (editMode) {
									e.preventDefault();
									e.stopPropagation();
									editHeroBackground(heroSection, bgContainer);
								}
							};
							
							// Store handlers on element for potential cleanup
							heroSection._heroMouseEnter = mouseEnterHandler;
							heroSection._heroMouseLeave = mouseLeaveHandler;
							changeBgButton._buttonMouseEnter = buttonMouseEnterHandler;
							changeBgButton._buttonClick = buttonClickHandler;
							
							// Remove old listeners if they exist, then add new ones
							if (heroSection._oldMouseEnter) {
								heroSection.removeEventListener('mouseenter', heroSection._oldMouseEnter);
							}
							if (heroSection._oldMouseLeave) {
								heroSection.removeEventListener('mouseleave', heroSection._oldMouseLeave);
							}
							
							// Show button on hover over hero section (when in edit mode)
							heroSection.addEventListener('mouseenter', mouseEnterHandler);
							heroSection.addEventListener('mouseleave', mouseLeaveHandler);
							
							// Keep button visible when hovering over it
							changeBgButton.addEventListener('mouseenter', buttonMouseEnterHandler);
							
							// Button click handler
							changeBgButton.addEventListener('click', buttonClickHandler);
							
							// Store for potential cleanup
							heroSection._oldMouseEnter = mouseEnterHandler;
							heroSection._oldMouseLeave = mouseLeaveHandler;
						}
					});
				}
				
				// Function to edit hero background
				function editHeroBackground(heroSection, bgContainer) {
					
					// Remove any existing modal first
					const existingModal = document.getElementById('hero-upload-modal');
					if (existingModal) {
						existingModal.remove();
					}
					
					// Create modal in parent document (we're in parent window context)
					const modal = document.createElement('div');
					modal.id = 'hero-upload-modal';
					modal.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.75); z-index:100000; display:flex; align-items:center; justify-content:center;';
					
					const modalContent = document.createElement('div');
					modalContent.style.cssText = 'background:white; padding:24px; border-radius:12px; max-width:500px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3);';
					
					const imageBtnId = 'hero-upload-image-' + Date.now();
					const videoBtnId = 'hero-upload-video-' + Date.now();
					const cancelBtnId = 'hero-upload-cancel-' + Date.now();
					
					modalContent.innerHTML = `
						<h3 style="margin:0 0 16px 0; font-size:18px; font-weight:600;">Change Hero Background</h3>
						<p style="margin:0 0 20px 0; color:#6b7280; font-size:14px;">Choose to upload an image or video for the hero background.</p>
						<div style="display:flex; gap:12px;">
							<button id="${imageBtnId}" style="flex:1; padding:12px; border:2px solid #667eea; background:#667eea; color:white; border-radius:8px; cursor:pointer; font-weight:600;">📷 Image</button>
							<button id="${videoBtnId}" style="flex:1; padding:12px; border:2px solid #667eea; background:white; color:#667eea; border-radius:8px; cursor:pointer; font-weight:600;">🎥 Video</button>
						</div>
						<button id="${cancelBtnId}" style="margin-top:12px; width:100%; padding:10px; border:1px solid #e5e7eb; background:white; color:#6b7280; border-radius:8px; cursor:pointer;">Cancel</button>
					`;
					
					modal.appendChild(modalContent);
					document.body.appendChild(modal);
					
					// Store references for the handlers
					const heroSectionRef = heroSection;
					const bgContainerRef = bgContainer;
					
					// Use setTimeout to ensure DOM is ready
					setTimeout(() => {
						const imageBtn = document.getElementById(imageBtnId);
						const videoBtn = document.getElementById(videoBtnId);
						const cancelBtn = document.getElementById(cancelBtnId);
						
						if (imageBtn) {
							imageBtn.onclick = function() {
								uploadHeroBackground(heroSectionRef, bgContainerRef, 'image');
								if (document.body.contains(modal)) {
									document.body.removeChild(modal);
								}
							};
						}
						
						if (videoBtn) {
							videoBtn.onclick = function() {
								uploadHeroBackground(heroSectionRef, bgContainerRef, 'video');
								if (document.body.contains(modal)) {
									document.body.removeChild(modal);
								}
							};
						}
						
						if (cancelBtn) {
							cancelBtn.onclick = function() {
								if (document.body.contains(modal)) {
									document.body.removeChild(modal);
								}
							};
						}
					}, 10);
					
					modal.onclick = function(e) {
						if (e.target === modal) {
							if (document.body.contains(modal)) {
								document.body.removeChild(modal);
							}
						}
					};
				}
				
				// Function to upload hero background
				function uploadHeroBackground(heroSection, bgContainer, type) {
					// Create input in parent document
					const input = document.createElement('input');
					input.type = 'file';
					input.accept = type === 'image' ? 'image/*' : 'video/*';
					input.style.display = 'none';
					document.body.appendChild(input);
					
					input.onchange = function(e) {
						const file = e.target.files[0];
						if (file) {
							uploadHeroFile(file, heroSection, bgContainer, type);
						}
						// Clean up
						if (document.body.contains(input)) {
							document.body.removeChild(input);
						}
					};
					
					// Trigger click
					setTimeout(() => {
						input.click();
					}, 100);
				}
				
				// Function to handle file upload
				function uploadHeroFile(file, heroSection, bgContainer, expectedType) {
					const formData = new FormData();
					formData.append('file', file);
					formData.append('action', 'hero_background_upload');
					formData.append('csrf_token', csrfToken);
					
					// Show loading indicator
					const loadingIndicator = iframeDoc.createElement('div');
					loadingIndicator.style.cssText = 'position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:rgba(102,126,234,0.9); color:white; padding:12px 20px; border-radius:8px; z-index:40; font-weight:600;';
					loadingIndicator.textContent = 'Uploading...';
					heroSection.appendChild(loadingIndicator);
					
					fetch(window.location.href, {
						method: 'POST',
						body: formData
					})
					.then(res => res.json())
					.then(data => {
						if (data.success) {
							updateHeroBackground(heroSection, bgContainer, data.url, data.type);
							changes['hero-background'] = { type: 'hero-background', url: data.url, mediaType: data.type };
						} else {
							alert('Upload failed: ' + (data.message || 'Unknown error'));
						}
					})
					.catch(err => {
						console.error(err);
						alert('Upload failed. Please check file size and try again.');
					})
					.finally(() => {
						if (loadingIndicator.parentNode) {
							loadingIndicator.parentNode.removeChild(loadingIndicator);
						}
					});
				}
				
				// Function to update hero background in DOM
				function updateHeroBackground(heroSection, bgContainer, url, mediaType) {
					const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
					
					// Use relative path for static files, absolute for DB pages
					const filename = url.split('/').pop();
					const finalUrl = isFileEdit ? '../uploads/hero/' + filename : url;
					
					if (mediaType === 'video') {
						// Remove existing background image if present
						if (bgContainer.tagName === 'DIV' || bgContainer.tagName === 'SECTION') {
							bgContainer.style.backgroundImage = 'none';
							bgContainer.style.background = 'none';
						}
						
						// Check if video already exists
						let videoEl = heroSection.querySelector('video.hero-video, video');
						if (!videoEl) {
							// Create new video element
							videoEl = iframeDoc.createElement('video');
							videoEl.className = 'hero-video';
							videoEl.style.cssText = 'position:absolute; top:50%; left:50%; width:100%; height:100%; object-fit:cover; transform:translate(-50%, -50%); z-index:1; filter:brightness(0.4);';
							videoEl.autoplay = true;
							videoEl.muted = true;
							videoEl.loop = true;
							videoEl.playsInline = true;
							videoEl.setAttribute('aria-label', 'Hero background video');
							
							// Insert before the overlay or first content div
							const overlay = heroSection.querySelector('.hero-overlay, .absolute.inset-0[class*="overlay"]');
							const firstContent = heroSection.querySelector('.relative.z-10, .hero-content, [class*="z-10"]');
							
							if (overlay) {
								heroSection.insertBefore(videoEl, overlay);
							} else if (firstContent) {
								heroSection.insertBefore(videoEl, firstContent);
							} else {
								heroSection.insertBefore(videoEl, heroSection.firstChild);
							}
						}
						
						videoEl.src = finalUrl;
						const source = iframeDoc.createElement('source');
						source.src = finalUrl;
						const ext = filename.split('.').pop().toLowerCase();
						if (ext === 'mp4') {
							source.type = 'video/mp4';
						} else if (ext === 'webm') {
							source.type = 'video/webm';
						} else if (ext === 'ogg') {
							source.type = 'video/ogg';
						}
						videoEl.innerHTML = '';
						videoEl.appendChild(source);
						
						// Make video editable
						videoEl.classList.add('hero-bg-editable');
						videoEl.setAttribute('data-hero-id', bgContainer.getAttribute('data-hero-id') || 'hero-' + Date.now());
						
						// Keep the original bgContainer for editing purposes but hide it
						if (bgContainer.tagName === 'DIV' && bgContainer !== videoEl) {
							bgContainer.style.display = 'none';
						}
						
					} else {
						// Remove video if present
						const existingVideo = heroSection.querySelector('video.hero-video, video');
						if (existingVideo) {
							existingVideo.remove();
						}
						
						// Update background image
						if (bgContainer.tagName === 'VIDEO') {
							// Replace video with div (restore original structure)
							const newDiv = iframeDoc.createElement('div');
							newDiv.className = 'absolute inset-0 bg-cover bg-center';
							newDiv.style.cssText = 'position:absolute; inset:0; background-size:cover; background-position:center; background-image:url(' + finalUrl + ');';
							newDiv.setAttribute('aria-hidden', 'true');
							newDiv.setAttribute('role', 'presentation');
							newDiv.classList.add('hero-bg-editable');
							newDiv.setAttribute('data-hero-id', bgContainer.getAttribute('data-hero-id') || 'hero-' + Date.now());
							bgContainer.parentNode.replaceChild(newDiv, bgContainer);
							bgContainer = newDiv;
						} else {
							// Update existing div background
							if (bgContainer.style.display === 'none') {
								bgContainer.style.display = '';
							}
							bgContainer.style.backgroundImage = 'url(' + finalUrl + ')';
							bgContainer.style.backgroundSize = 'cover';
							bgContainer.style.backgroundPosition = 'center';
							bgContainer.style.backgroundRepeat = 'no-repeat';
						}
					}
					
					// Re-setup editing for the updated element
					setupHeroBackgroundEditing();
				}
				
				// Setup hero background editing
				setupHeroBackgroundEditing();
				
				// Function to edit section background
				function editSectionBackground(section) {
					// Remove any existing modal first
					const existingModal = document.getElementById('section-bg-modal');
					if (existingModal) {
						existingModal.remove();
					}
					
					// Create modal in parent document
					const modal = document.createElement('div');
					modal.id = 'section-bg-modal';
					modal.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.75); z-index:100000; display:flex; align-items:center; justify-content:center;';
					
					const modalContent = document.createElement('div');
					modalContent.style.cssText = 'background:white; padding:24px; border-radius:12px; max-width:600px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3);';
					
					const colorBtnId = 'section-bg-color-' + Date.now();
					const imageBtnId = 'section-bg-image-' + Date.now();
					const videoBtnId = 'section-bg-video-' + Date.now();
					const cancelBtnId = 'section-bg-cancel-' + Date.now();
					
					// Color options
					const colors = [
						{ name: 'Carbon Black', value: '#1F1F1F' },
						{ name: 'Canvas White', value: '#FFFFFF' },
						{ name: 'Vibrant Coral', value: '#FF4F4F' },
						{ name: 'Electric Aqua', value: '#18F1E1' }
					];
					
					const colorOptionsHtml = colors.map(color => 
						`<button class="color-option-btn" data-color="${color.value}" style="width:80px; height:80px; border-radius:8px; border:3px solid #e5e7eb; cursor:pointer; background:${color.value}; transition:all 0.2s; box-shadow:0 2px 4px rgba(0,0,0,0.1);" title="${color.name}"></button>`
					).join('');
					
					modalContent.innerHTML = `
						<h3 style="margin:0 0 16px 0; font-size:18px; font-weight:600;">Change Section Background</h3>
						<p style="margin:0 0 20px 0; color:#6b7280; font-size:14px;">Choose a color, image, or video for the section background.</p>
						
						<div style="margin-bottom:20px;">
							<label style="display:block; font-size:14px; font-weight:600; margin-bottom:12px; color:#111827;">Color Options</label>
							<div style="display:flex; gap:12px; flex-wrap:wrap;">
								${colorOptionsHtml}
							</div>
						</div>
						
						<div style="display:flex; gap:12px; margin-top:20px;">
							<button id="${imageBtnId}" style="flex:1; padding:12px; border:2px solid #667eea; background:#667eea; color:white; border-radius:8px; cursor:pointer; font-weight:600;">📷 Image</button>
							<button id="${videoBtnId}" style="flex:1; padding:12px; border:2px solid #667eea; background:white; color:#667eea; border-radius:8px; cursor:pointer; font-weight:600;">🎥 Video</button>
						</div>
						<button id="${cancelBtnId}" style="margin-top:12px; width:100%; padding:10px; border:1px solid #e5e7eb; background:white; color:#6b7280; border-radius:8px; cursor:pointer;">Cancel</button>
					`;
					
					modal.appendChild(modalContent);
					document.body.appendChild(modal);
					
					// Store section reference
					const sectionRef = section;
					
					// Use setTimeout to ensure DOM is ready
					setTimeout(() => {
						// Color option buttons
						modalContent.querySelectorAll('.color-option-btn').forEach(btn => {
							btn.addEventListener('mouseenter', function() {
								this.style.transform = 'scale(1.1)';
								this.style.borderColor = '#667eea';
								this.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.4)';
							});
							btn.addEventListener('mouseleave', function() {
								this.style.transform = 'scale(1)';
								this.style.borderColor = '#e5e7eb';
								this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
							});
							btn.addEventListener('click', function() {
								const color = this.getAttribute('data-color');
								updateSectionBackground(sectionRef, color, 'color');
								if (document.body.contains(modal)) {
									document.body.removeChild(modal);
								}
							});
						});
						
						const imageBtn = document.getElementById(imageBtnId);
						const videoBtn = document.getElementById(videoBtnId);
						const cancelBtn = document.getElementById(cancelBtnId);
						
						if (imageBtn) {
							imageBtn.onclick = function() {
								uploadSectionBackground(sectionRef, 'image');
								if (document.body.contains(modal)) {
									document.body.removeChild(modal);
								}
							};
						}
						
						if (videoBtn) {
							videoBtn.onclick = function() {
								uploadSectionBackground(sectionRef, 'video');
								if (document.body.contains(modal)) {
									document.body.removeChild(modal);
								}
							};
						}
						
						if (cancelBtn) {
							cancelBtn.onclick = function() {
								if (document.body.contains(modal)) {
									document.body.removeChild(modal);
								}
							};
						}
					}, 10);
					
					modal.onclick = function(e) {
						if (e.target === modal) {
							if (document.body.contains(modal)) {
								document.body.removeChild(modal);
							}
						}
					};
				}
				
				// Function to upload section background
				function uploadSectionBackground(section, type) {
					// Create input in parent document
					const input = document.createElement('input');
					input.type = 'file';
					input.accept = type === 'image' ? 'image/*' : 'video/*';
					input.style.display = 'none';
					document.body.appendChild(input);
					
					input.onchange = function(e) {
						const file = e.target.files[0];
						if (file) {
							uploadSectionFile(file, section, type);
						}
						// Clean up
						if (document.body.contains(input)) {
							document.body.removeChild(input);
						}
					};
					
					// Trigger click
					setTimeout(() => {
						input.click();
					}, 100);
				}
				
				// Function to handle section file upload
				function uploadSectionFile(file, section, expectedType) {
					const formData = new FormData();
					formData.append('file', file);
					formData.append('action', 'hero_background_upload'); // Reuse hero upload endpoint
					formData.append('csrf_token', csrfToken);
					
					// Show loading indicator
					const loadingIndicator = iframeDoc.createElement('div');
					loadingIndicator.style.cssText = 'position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:rgba(102,126,234,0.9); color:white; padding:12px 20px; border-radius:8px; z-index:40; font-weight:600;';
					loadingIndicator.textContent = 'Uploading...';
					section.appendChild(loadingIndicator);
					
					fetch(window.location.href, {
						method: 'POST',
						body: formData
					})
					.then(res => res.json())
					.then(data => {
						if (data.success) {
							updateSectionBackground(section, data.url, data.type);
							changes['section-background-' + Date.now()] = { type: 'section-background', url: data.url, mediaType: data.type, section: section };
						} else {
							alert('Upload failed: ' + (data.message || 'Unknown error'));
						}
					})
					.catch(err => {
						console.error(err);
						alert('Upload failed. Please check file size and try again.');
					})
					.finally(() => {
						if (loadingIndicator.parentNode) {
							loadingIndicator.parentNode.removeChild(loadingIndicator);
						}
					});
				}
				
				// Function to update section background
				function updateSectionBackground(section, value, backgroundType) {
					const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
					
					// Remove existing video background if present
					const existingVideo = section.querySelector('video.section-bg-video');
					if (existingVideo) {
						existingVideo.remove();
					}
					
					if (backgroundType === 'color') {
						// Set background color
						section.style.backgroundColor = value;
						section.style.backgroundImage = 'none';
						section.style.background = value;
						
						// Remove any background image/video containers
						const bgContainer = section.querySelector('.section-bg-container');
						if (bgContainer) {
							bgContainer.remove();
						}
						
						changes['section-background-' + Date.now()] = { type: 'section-background', value: value, backgroundType: 'color', section: section };
					} else if (backgroundType === 'image') {
						// Use relative path for static files, absolute for DB pages
						const filename = value.split('/').pop();
						const finalUrl = isFileEdit ? '../uploads/hero/' + filename : value;
						
						// Set background image
						section.style.backgroundImage = `url(${finalUrl})`;
						section.style.backgroundSize = 'cover';
						section.style.backgroundPosition = 'center';
						section.style.backgroundRepeat = 'no-repeat';
						section.style.backgroundColor = ''; // Clear color if set
						
						// Remove video if present
						const existingVideo = section.querySelector('video.section-bg-video');
						if (existingVideo) {
							existingVideo.remove();
						}
						
						changes['section-background-' + Date.now()] = { type: 'section-background', url: finalUrl, backgroundType: 'image', section: section };
					} else if (backgroundType === 'video') {
						// Use relative path for static files, absolute for DB pages
						const filename = value.split('/').pop();
						const finalUrl = isFileEdit ? '../uploads/hero/' + filename : value;
						
						// Remove existing background image/color
						section.style.backgroundImage = 'none';
						section.style.backgroundColor = '';
						
						// Check if video already exists
						let videoEl = section.querySelector('video.section-bg-video');
						if (!videoEl) {
							// Create new video element
							videoEl = iframeDoc.createElement('video');
							videoEl.className = 'section-bg-video';
							videoEl.style.cssText = 'position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; z-index:0; pointer-events:none;';
							videoEl.autoplay = true;
							videoEl.muted = true;
							videoEl.loop = true;
							videoEl.playsInline = true;
							videoEl.setAttribute('aria-hidden', 'true');
							
							// Insert as first child
							section.insertBefore(videoEl, section.firstChild);
						}
						
						videoEl.src = finalUrl;
						const source = iframeDoc.createElement('source');
						source.src = finalUrl;
						const ext = filename.split('.').pop().toLowerCase();
						if (ext === 'mp4') {
							source.type = 'video/mp4';
						} else if (ext === 'webm') {
							source.type = 'video/webm';
						} else if (ext === 'ogg') {
							source.type = 'video/ogg';
						}
						videoEl.innerHTML = '';
						videoEl.appendChild(source);
						
						// Ensure section content is above video
						const sectionChildren = Array.from(section.children).filter(child => !child.classList.contains('section-bg-video'));
						sectionChildren.forEach(child => {
							const childStyle = iframeDoc.defaultView.getComputedStyle(child);
							if (childStyle.position === 'static' || childStyle.position === 'relative') {
								child.style.position = 'relative';
								child.style.zIndex = '1';
							}
						});
						
						changes['section-background-' + Date.now()] = { type: 'section-background', url: finalUrl, backgroundType: 'video', section: section };
					}
				}
				
				// Setup drag and drop for sections
				function setupSectionDragDrop() {
					// Find all main sections (exclude header, footer, nav)
					const allSections = iframeDoc.querySelectorAll('main section, body > section, .section-padding, [class*="section"]');
					const sections = Array.from(allSections).filter(section => {
						// Exclude sections that are inside other sections (nested)
						const parentSection = section.closest('section');
						return !parentSection || parentSection === section;
					});
					
					sections.forEach((section, index) => {
						// Remove any existing controls first (to allow re-initialization)
						// This ensures buttons work properly after page reload
						const existingControls = section.querySelector('.section-controls');
						if (existingControls) {
							existingControls.remove();
						}
						
						// Remove old setup attributes to allow fresh initialization
						section.removeAttribute('data-draggable-setup');
						section.removeAttribute('data-section-index');
						
						section.setAttribute('data-draggable-setup', 'true');
						section.setAttribute('data-section-index', index);
						
						// Make section draggable
						section.draggable = editMode;
						section.classList.add('draggable-section');
						
						// Ensure child elements don't block dragging
						// Make links, buttons, and other interactive elements not draggable
						const interactiveElements = section.querySelectorAll('a, button, input, select, textarea, [contenteditable="true"]');
						interactiveElements.forEach(el => {
							el.setAttribute('draggable', 'false');
						});
						
						// Add section controls with up/down buttons for easier navigation
						if (!section.querySelector('.section-controls')) {
							const controlsContainer = iframeDoc.createElement('div');
							controlsContainer.className = 'section-controls';
							controlsContainer.style.cssText = `
								position: absolute;
								top: 15px;
								left: 15px;
								display: none;
								flex-direction: column;
								gap: 6px;
								z-index: 40;
							`;
							
							// Move Up button
							const moveUpBtn = iframeDoc.createElement('button');
							moveUpBtn.className = 'move-section-btn move-up';
							moveUpBtn.innerHTML = '↑ Move Up';
							moveUpBtn.title = 'Move Section Up';
							moveUpBtn.type = 'button'; // Prevent form submission
							moveUpBtn.setAttribute('contenteditable', 'false');
							moveUpBtn.contentEditable = false;
							moveUpBtn.setAttribute('data-non-editable', 'true');
							moveUpBtn.style.cssText = `
								background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
								color: white;
								border: 2px solid rgba(255, 255, 255, 0.3);
								padding: 8px 14px;
								border-radius: 6px;
								font-size: 12px;
								font-weight: 600;
								cursor: pointer;
								box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
								transition: all 0.2s ease;
								white-space: nowrap;
								user-select: none;
								-webkit-user-select: none;
								-moz-user-select: none;
								-ms-user-select: none;
							`;
							if (index === 0) {
								moveUpBtn.disabled = true;
								moveUpBtn.style.opacity = '0.5';
								moveUpBtn.style.cursor = 'not-allowed';
							}
							
							// Move Down button
							const moveDownBtn = iframeDoc.createElement('button');
							moveDownBtn.className = 'move-section-btn move-down';
							moveDownBtn.innerHTML = '↓ Move Down';
							moveDownBtn.title = 'Move Section Down';
							moveDownBtn.type = 'button'; // Prevent form submission
							moveDownBtn.setAttribute('contenteditable', 'false');
							moveDownBtn.contentEditable = false;
							moveDownBtn.setAttribute('data-non-editable', 'true');
							moveDownBtn.style.cssText = moveUpBtn.style.cssText;
							if (index === sections.length - 1) {
								moveDownBtn.disabled = true;
								moveDownBtn.style.opacity = '0.5';
								moveDownBtn.style.cursor = 'not-allowed';
							}
							
							// Hover effects
							[moveUpBtn, moveDownBtn].forEach(btn => {
								btn.addEventListener('mouseenter', function() {
									if (!this.disabled) {
										this.style.transform = 'scale(1.05)';
										this.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.6)';
									}
								});
								btn.addEventListener('mouseleave', function() {
									this.style.transform = 'scale(1)';
									this.style.boxShadow = '0 2px 8px rgba(102, 126, 234, 0.4)';
								});
							});
							
							// Move up functionality - use capture phase to catch before text editing
							moveUpBtn.addEventListener('click', function(e) {
								e.stopPropagation();
								e.stopImmediatePropagation();
								e.preventDefault();
								if (index > 0 && !this.disabled) {
									const parent = section.parentNode;
									parent.insertBefore(section, sections[index - 1]);
									changes['sections-reordered'] = { type: 'sections', action: 'reorder' };
									// Re-setup immediately
									iframeDoc.querySelectorAll('[data-draggable-setup]').forEach(el => {
										el.removeAttribute('data-draggable-setup');
										el.removeAttribute('data-section-index');
									});
									iframeDoc.querySelectorAll('.section-controls').forEach(c => c.remove());
									setupSectionDragDrop();
								}
								return false;
							}, true);
							
							// Move down functionality - use capture phase
							moveDownBtn.addEventListener('click', function(e) {
								e.stopPropagation();
								e.stopImmediatePropagation();
								e.preventDefault();
								if (index < sections.length - 1 && !this.disabled) {
									const parent = section.parentNode;
									const nextSibling = sections[index + 1].nextSibling;
									if (nextSibling) {
										parent.insertBefore(section, nextSibling);
									} else {
										parent.appendChild(section);
									}
									changes['sections-reordered'] = { type: 'sections', action: 'reorder' };
									// Re-setup immediately
									iframeDoc.querySelectorAll('[data-draggable-setup]').forEach(el => {
										el.removeAttribute('data-draggable-setup');
										el.removeAttribute('data-section-index');
									});
									iframeDoc.querySelectorAll('.section-controls').forEach(c => c.remove());
									setupSectionDragDrop();
								}
								return false;
							}, true);
							
							// Prevent all editing interactions on buttons
							[moveUpBtn, moveDownBtn].forEach(btn => {
								btn.addEventListener('mousedown', function(e) {
									e.stopPropagation();
									e.stopImmediatePropagation();
								}, true);
								
								btn.addEventListener('dblclick', function(e) {
									e.stopPropagation();
									e.stopImmediatePropagation();
									e.preventDefault();
									return false;
								}, true);
								
								btn.addEventListener('contextmenu', function(e) {
									e.stopPropagation();
									e.preventDefault();
									return false;
								}, true);
							});
							
							// Check if this is a hero section (skip change background button for hero sections)
							const isHeroSection = section.id === 'hero' || 
							                      section.classList.contains('hero-section') || 
							                      section.classList.contains('hero-video-container') ||
							                      section.className.includes('hero');
							
							// Change Background button (skip for hero sections as they have their own button)
							let changeBgBtn = null;
							if (!isHeroSection) {
								changeBgBtn = iframeDoc.createElement('button');
								changeBgBtn.className = 'change-section-bg-btn';
								changeBgBtn.innerHTML = '🎨 Change Background';
								changeBgBtn.title = 'Change Section Background';
								changeBgBtn.type = 'button';
								changeBgBtn.setAttribute('contenteditable', 'false');
								changeBgBtn.contentEditable = false;
								changeBgBtn.setAttribute('data-non-editable', 'true');
								changeBgBtn.style.cssText = `
									background: linear-gradient(135deg, #FF4F4F 0%, #e63946 100%);
									color: white;
									border: 2px solid rgba(255, 255, 255, 0.3);
									padding: 8px 14px;
									border-radius: 6px;
									font-size: 12px;
									font-weight: 600;
									cursor: pointer;
									box-shadow: 0 2px 8px rgba(255, 79, 79, 0.4);
									transition: all 0.2s ease;
									white-space: nowrap;
									user-select: none;
									-webkit-user-select: none;
									-moz-user-select: none;
									-ms-user-select: none;
								`;
								
								// Hover effects for change background button
								changeBgBtn.addEventListener('mouseenter', function() {
									this.style.transform = 'scale(1.05)';
									this.style.boxShadow = '0 4px 12px rgba(255, 79, 79, 0.6)';
								});
								changeBgBtn.addEventListener('mouseleave', function() {
									this.style.transform = 'scale(1)';
									this.style.boxShadow = '0 2px 8px rgba(255, 79, 79, 0.4)';
								});
								
								// Change background functionality
								changeBgBtn.addEventListener('click', function(e) {
									e.stopPropagation();
									e.stopImmediatePropagation();
									e.preventDefault();
									editSectionBackground(section);
									return false;
								}, true);
								
								// Prevent editing interactions on button
								changeBgBtn.addEventListener('mousedown', function(e) {
									e.stopPropagation();
									e.stopImmediatePropagation();
								}, true);
								
								changeBgBtn.addEventListener('dblclick', function(e) {
									e.stopPropagation();
									e.stopImmediatePropagation();
									e.preventDefault();
									return false;
								}, true);
								
								changeBgBtn.addEventListener('contextmenu', function(e) {
									e.stopPropagation();
									e.preventDefault();
									return false;
								}, true);
							}
							
							controlsContainer.appendChild(moveUpBtn);
							controlsContainer.appendChild(moveDownBtn);
							if (changeBgBtn) {
								controlsContainer.appendChild(changeBgBtn);
							}
							
							// Ensure section has relative positioning
							const sectionPosition = iframeDoc.defaultView.getComputedStyle(section).position;
							if (sectionPosition === 'static') {
								section.style.position = 'relative';
							}
							
							section.appendChild(controlsContainer);
							
							// Show controls always in edit mode for better UX
							if (editMode) {
								controlsContainer.style.display = 'flex';
							}
							
							section.addEventListener('mouseenter', function() {
								if (editMode) {
									controlsContainer.style.display = 'flex';
									controlsContainer.style.opacity = '1';
									section.style.outline = '2px dashed rgba(102, 126, 234, 0.6)';
									section.style.outlineOffset = '2px';
								}
							});
							
							section.addEventListener('mouseleave', function(e) {
								if (!section.classList.contains('dragging') && editMode) {
									controlsContainer.style.opacity = '0.8';
									section.style.outline = '2px dashed rgba(102, 126, 234, 0.3)';
								}
							});
						}
						
						// Drag start
						section.addEventListener('dragstart', function(e) {
							if (!editMode) {
								e.preventDefault();
								return false;
							}
							
							// Allow drag from anywhere on section or handle
							this.classList.add('dragging');
							e.dataTransfer.effectAllowed = 'move';
							e.dataTransfer.setData('text/html', this.outerHTML);
							e.dataTransfer.setData('text/plain', index.toString());
							
							// Create a semi-transparent clone for visual feedback
							this.style.opacity = '0.5';
							
							return true;
						});
						
						// Drag end
						section.addEventListener('dragend', function(e) {
							this.classList.remove('dragging');
							this.style.opacity = '';
							this.style.outline = '';
							this.style.outlineOffset = '';
							
							// Remove all drop indicators
							iframeDoc.querySelectorAll('.drop-indicator').forEach(ind => ind.remove());
						});
						
						// Drag over - allow drop
						section.addEventListener('dragover', function(e) {
							if (!editMode) return;
							
							e.preventDefault();
							e.dataTransfer.dropEffect = 'move';
							
							// Show drop indicator
							const rect = this.getBoundingClientRect();
							const y = e.clientY - rect.top;
							const height = rect.height;
							
							// Remove existing indicators
							iframeDoc.querySelectorAll('.drop-indicator').forEach(ind => ind.remove());
							
							// Create drop indicator
							const indicator = iframeDoc.createElement('div');
							indicator.className = 'drop-indicator';
							indicator.style.cssText = `
								position: absolute;
								left: 0;
								right: 0;
								height: 4px;
								background: #667eea;
								z-index: 10003;
								pointer-events: none;
								box-shadow: 0 0 10px rgba(102, 126, 234, 0.8);
							`;
							
							// Position indicator above or below based on mouse position
							if (y < height / 2) {
								indicator.style.top = '0';
								this.insertBefore(indicator, this.firstChild);
							} else {
								indicator.style.bottom = '0';
								this.appendChild(indicator);
							}
						});
						
						// Drag leave
						section.addEventListener('dragleave', function(e) {
							// Only remove indicator if we're actually leaving the section
							if (!this.contains(e.relatedTarget)) {
								iframeDoc.querySelectorAll('.drop-indicator').forEach(ind => {
									if (ind.parentNode === this) ind.remove();
								});
								// Remove highlight
								this.style.backgroundColor = '';
							}
						});
						
						// Drop
						section.addEventListener('drop', function(e) {
							if (!editMode) return;
							
							e.preventDefault();
							e.stopPropagation();
							
							// Remove all indicators
							iframeDoc.querySelectorAll('.drop-indicator').forEach(ind => ind.remove());
							
							const draggedIndex = parseInt(e.dataTransfer.getData('text/plain'));
							const draggedSection = sections[draggedIndex];
							
							if (!draggedSection || draggedSection === this) {
								return;
							}
							
							// Determine drop position
							const rect = this.getBoundingClientRect();
							const y = e.clientY - rect.top;
							const height = rect.height;
							
							// Get parent container
							const parent = this.parentNode;
							
							if (y < height / 2) {
								// Insert before this section
								parent.insertBefore(draggedSection, this);
							} else {
								// Insert after this section
								if (this.nextSibling) {
									parent.insertBefore(draggedSection, this.nextSibling);
								} else {
									parent.appendChild(draggedSection);
								}
							}
							
							// Mark that sections were reordered
							changes['sections-reordered'] = { type: 'sections', action: 'reorder' };
							
							// Re-setup immediately for better responsiveness
							iframeDoc.querySelectorAll('[data-draggable-setup]').forEach(el => {
								el.removeAttribute('data-draggable-setup');
								el.removeAttribute('data-section-index');
							});
							iframeDoc.querySelectorAll('.section-controls').forEach(c => c.remove());
							setupSectionDragDrop();
						});
					});
				}
				
				// Setup section drag and drop (only in edit mode)
				if (editMode) {
					setupSectionDragDrop();
				}
				
				// Add hover effects in edit mode
				iframeDoc.addEventListener('mouseover', function(e) {
					if (editMode && (e.target.classList.contains('editable-text') || e.target.classList.contains('media-editable') || e.target.classList.contains('hero-bg-editable') || e.target.classList.contains('editable-link'))) {
						e.target.classList.add('editable-highlight');
						// Add special styling for editable links/buttons
						if (e.target.classList.contains('editable-link')) {
							e.target.style.cursor = 'text';
							e.target.style.outline = '2px dashed #667eea';
							e.target.style.outlineOffset = '2px';
						}
					}
				});
				
				iframeDoc.addEventListener('mouseout', function(e) {
					e.target.classList.remove('editable-highlight');
					if (e.target.classList.contains('editable-link') && !e.target.isContentEditable) {
						e.target.style.cursor = '';
						e.target.style.outline = '';
						e.target.style.outlineOffset = '';
					}
				});
				
				// Initialize formatting toolbar after a short delay
				setTimeout(() => {
					createTextFormatToolbar();
				}, 500);
				
				console.log('Editor initialized');
			} catch(e) {
				console.error('Error initializing editor:', e);
			}
		}
		
		// Edit mode toggle
		editModeBtn.addEventListener('click', function() {
			editMode = true;
			editModeBtn.classList.add('active');
			previewModeBtn.classList.remove('active');
			indicator.style.display = 'block';
			initEditor();
		});
		
		previewModeBtn.addEventListener('click', function() {
			editMode = false;
			previewModeBtn.classList.add('active');
			editModeBtn.classList.remove('active');
			indicator.style.display = 'none';
			// Hide formatting toolbar
			const toolbar = document.getElementById('text-format-toolbar');
			if (toolbar) {
				toolbar.classList.remove('active');
			}
			// Hide all change background buttons when exiting edit mode
			try {
				const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
				const buttons = iframeDoc.querySelectorAll('.hero-change-bg-btn');
				buttons.forEach(btn => btn.style.display = 'none');
				
				// Disable drag and drop
				const sections = iframeDoc.querySelectorAll('.draggable-section');
				sections.forEach(section => {
					section.draggable = false;
					const controls = section.querySelector('.section-controls');
					if (controls) controls.style.display = 'none';
					section.style.outline = '';
					section.style.outlineOffset = '';
				});
			} catch(e) {}
		});
		
		// Device preview buttons
		desktopBtn.addEventListener('click', function() {
			iframe.style.width = '100%';
			desktopBtn.classList.add('active');
			tabletBtn.classList.remove('active');
			mobileBtn.classList.remove('active');
		});
		
		tabletBtn.addEventListener('click', function() {
			iframe.style.width = '768px';
			tabletBtn.classList.add('active');
			desktopBtn.classList.remove('active');
			mobileBtn.classList.remove('active');
		});
		
		mobileBtn.addEventListener('click', function() {
			iframe.style.width = '375px';
			mobileBtn.classList.add('active');
			desktopBtn.classList.remove('active');
			tabletBtn.classList.remove('active');
		});
		
		// Media editor function
		function editMedia(element) {
			const input = document.createElement('input');
			input.type = 'file';
			input.accept = element.tagName === 'IMG' ? 'image/*' : 'video/*';
			input.onchange = function(e) {
				const file = e.target.files[0];
				if (file) {
					const reader = new FileReader();
					reader.onload = function(e) {
						if (element.tagName === 'IMG') {
							element.src = e.target.result;
						} else {
							element.src = e.target.result;
						}
						changes[element.getAttribute('data-id')] = {
							old: element.src,
							new: e.target.result,
							element: element
						};
					};
					reader.readAsDataURL(file);
				}
			};
			input.click();
		}
		
		// Save changes on form submit
		document.getElementById('page-form').addEventListener('submit', function(e) {
			if (Object.keys(changes).length > 0) {
				try {
					const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
					let contentToSave = '';
					
					if (isFileEdit) {
						// For static files, we want to exclude components injected by JS (header, footer, etc)
						const bodyClone = iframeDoc.body.cloneNode(true);
						
						// Remove elements injected by components
						// These are identified by common tags/IDs or if they don't exist in the original source
						const injectedSelectors = [
							'header', 
							'footer', 
							'#mobile-menu-overlay',
							'.back-to-top'
						];
						
						injectedSelectors.forEach(selector => {
							bodyClone.querySelectorAll(selector).forEach(el => el.remove());
						});
						
						contentToSave = bodyClone.innerHTML;
					} else {
						// For DB pages, try to get the specific article content if it exists
						const contentArticle = iframeDoc.querySelector('article.page-content') || 
						                     iframeDoc.querySelector('.page-content') || 
						                     iframeDoc.querySelector('main') || 
						                     iframeDoc.body;
						contentToSave = contentArticle.innerHTML;
					}

					// Clean up editor-specific attributes and classes
					const tempDiv = document.createElement('div');
					tempDiv.innerHTML = contentToSave;
					
					// Remove editor-specific elements
					tempDiv.querySelectorAll('.remove-item-btn').forEach(el => el.remove());
					tempDiv.querySelectorAll('.section-controls').forEach(el => el.remove());
					tempDiv.querySelectorAll('.move-section-btn').forEach(el => el.remove());
					tempDiv.querySelectorAll('.change-section-bg-btn').forEach(el => el.remove());
					tempDiv.querySelectorAll('.hero-change-bg-btn').forEach(el => el.remove());
					tempDiv.querySelectorAll('.drag-handle').forEach(el => el.remove());
					tempDiv.querySelectorAll('.drop-indicator').forEach(el => el.remove());
					tempDiv.querySelectorAll('.hero-bg-indicator').forEach(el => el.remove());
					
					const editorElements = tempDiv.querySelectorAll('*');
					editorElements.forEach(el => {
						el.classList.remove('editable-text', 'media-editable', 'editable-highlight', 'editable-link', 'gjs-selected', 'draggable-section', 'dragging', 'hero-bg-editable');
						el.removeAttribute('data-original-text');
						el.removeAttribute('data-id');
						el.removeAttribute('contenteditable');
						el.removeAttribute('data-link-editable');
						el.removeAttribute('data-initialized'); // Remove phone-protection artifact
						el.removeAttribute('data-draggable-setup');
						el.removeAttribute('data-section-index');
						el.removeAttribute('data-hero-editable');
						el.removeAttribute('data-hero-hover-handler');
						el.removeAttribute('data-hero-listener');
						el.removeAttribute('data-hero-id');
						el.removeAttribute('data-non-editable');
						el.removeAttribute('draggable');
						// Remove inline styles added by editor
						if (el.hasAttribute('style')) {
							let style = el.getAttribute('style');
							style = style.replace(/cursor:\s*text;?/gi, '');
							style = style.replace(/outline:\s*[^;]+;?/gi, '');
							style = style.replace(/outline-offset:\s*[^;]+;?/gi, '');
							style = style.replace(/;\s*;/g, ';');
							style = style.trim();
							if (style && !style.endsWith(';')) style += ';';
							if (style === ';' || !style) {
								el.removeAttribute('style');
							} else {
								el.setAttribute('style', style);
							}
						}
						
						// Fix common style artifacts like &:hover or empty class
						if (el.hasAttribute('style')) {
							let style = el.getAttribute('style');
							style = style.replace(/&amp;:hover\s*\{[^}]*\}/g, ''); // Fix hover artifact
							style = style.replace(/&:hover\s*\{[^}]*\}/g, '');
							el.setAttribute('style', style);
						}
						
						if (el.getAttribute('class') === '') el.removeAttribute('class');
					});
					
					// Final cleanup for the HTML string (fix encoding artifacts)
					let finalHtml = tempDiv.innerHTML;
					finalHtml = finalHtml.replace(/â€”/g, '—');
					finalHtml = finalHtml.replace(/â€“/g, '–');
					finalHtml = finalHtml.replace(/â€™/g, "'");
					finalHtml = finalHtml.replace(/â€œ/g, '"');
					finalHtml = finalHtml.replace(/â€\?/g, '"');
					
					document.getElementById('content_html').value = finalHtml;
				} catch(err) {
					console.error('Error getting iframe content:', err);
				}
			}
		});
		
	});
	
	// Export static page function
	function exportStaticPage() {
		if (!currentPageId) {
			alert('Please save this page first.');
			return;
		}
		const formData = new FormData();
		formData.append('csrf_token', csrfToken);
		formData.append('page_id', currentPageId);
		formData.append('action', 'export_static');
		fetch(window.location.href, {
			method: 'POST',
			body: formData
		})
		.then(function(res) { return res.json(); })
		.then(function(data) {
			if (!data.success) {
				alert(data.message || 'Export failed.');
				return;
			}
			const blob = new Blob([data.html], { type: 'text/html' });
			const url = URL.createObjectURL(blob);
			const a = document.createElement('a');
			a.href = url;
			a.download = (data.slug || 'page') + '.html';
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
		})
		.catch(function() {
			alert('Export failed. Please try again.');
		});
	}
	</script>
</body>
</html>