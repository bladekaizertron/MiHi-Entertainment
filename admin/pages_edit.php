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
		.media-editable { position: relative; }
		.media-editable:hover::after { content: 'Click to change image/video'; position: absolute; top: 10px; left: 10px; background: #667eea; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 12px; z-index: 1001; pointer-events: none; }
		.media-editable:hover { outline: 3px solid #667eea; cursor: pointer; }
		.edit-mode-indicator { position: fixed; top: 20px; right: 20px; background: #667eea; color: #fff; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; font-size: 14px; font-weight: 600; }
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
						element.classList.contains('modern-btn-call')
					)) {
						return;
					}
					
					// Make text nodes editable
					if (element.nodeType === 3 && element.textContent.trim()) {
						const parent = element.parentNode;
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
				
				// Make hero backgrounds editable
				function setupHeroBackgroundEditing() {
					// Find hero sections - look for sections with id="hero" or class containing "hero"
					const heroSections = iframeDoc.querySelectorAll('section#hero, section[class*="hero"], .hero-section, .hero-video-container');
					
					heroSections.forEach(heroSection => {
						// Skip if already set up
						if (heroSection.hasAttribute('data-hero-editable')) {
							return;
						}
						heroSection.setAttribute('data-hero-editable', 'true');
						
						// Find background container - could be a div with background-image or a video element
						// First check for video
						let bgContainer = heroSection.querySelector('video.hero-video, video');
						
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
									if ((style.includes('background-image') || computedStyle.backgroundImage !== 'none') && 
									    !div.classList.contains('hero-overlay') && 
									    !div.classList.contains('overlay') &&
									    div.getAttribute('role') !== 'presentation' || style.includes('background-image')) {
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
							
							// Create "Change Background" button (only if not already present)
							let changeBgButton = heroSection.querySelector('.hero-change-bg-btn');
							if (!changeBgButton) {
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
									z-index: 10001;
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
							}
							
							// Check if already has listeners
							if (!heroSection.hasAttribute('data-hero-hover-handler')) {
								heroSection.setAttribute('data-hero-hover-handler', 'true');
								
								// Show button on hover over hero section (when in edit mode)
								heroSection.addEventListener('mouseenter', function() {
									if (editMode && changeBgButton) {
										changeBgButton.style.display = 'block';
										// Add subtle outline to background
										if (bgContainer) {
											bgContainer.style.outline = '3px solid rgba(102, 126, 234, 0.5)';
											bgContainer.style.outlineOffset = '2px';
										}
									}
								});
							
								heroSection.addEventListener('mouseleave', function(e) {
									// Only hide if not hovering over the button itself
									if (changeBgButton && !changeBgButton.contains(e.relatedTarget)) {
										changeBgButton.style.display = 'none';
										if (bgContainer) {
											bgContainer.style.outline = '';
											bgContainer.style.outlineOffset = '';
										}
									}
								});
								
								// Keep button visible when hovering over it
								changeBgButton.addEventListener('mouseenter', function() {
									this.style.display = 'block';
								});
								
								// Button click handler
								changeBgButton.addEventListener('click', function(e) {
									if (editMode) {
										e.preventDefault();
										e.stopPropagation();
										editHeroBackground(heroSection, bgContainer);
									}
								});
							}
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
					loadingIndicator.style.cssText = 'position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:rgba(102,126,234,0.9); color:white; padding:12px 20px; border-radius:8px; z-index:10001; font-weight:600;';
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
				
				// Add hover effects in edit mode
				iframeDoc.addEventListener('mouseover', function(e) {
					if (editMode && (e.target.classList.contains('editable-text') || e.target.classList.contains('media-editable') || e.target.classList.contains('hero-bg-editable'))) {
						e.target.classList.add('editable-highlight');
					}
				});
				
				iframeDoc.addEventListener('mouseout', function(e) {
					e.target.classList.remove('editable-highlight');
				});
				
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
			// Hide all change background buttons when exiting edit mode
			try {
				const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
				const buttons = iframeDoc.querySelectorAll('.hero-change-bg-btn');
				buttons.forEach(btn => btn.style.display = 'none');
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
					
					const editorElements = tempDiv.querySelectorAll('*');
					editorElements.forEach(el => {
						el.classList.remove('editable-text', 'media-editable', 'editable-highlight', 'gjs-selected');
						el.removeAttribute('data-original-text');
						el.removeAttribute('data-id');
						el.removeAttribute('contenteditable');
						el.removeAttribute('data-initialized'); // Remove phone-protection artifact
						
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