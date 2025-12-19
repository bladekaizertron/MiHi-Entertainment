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
							@$dom->loadHTML('<?xml encoding="UTF-8">' . $fileContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
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
		$status = in_array($_POST['status'] ?? 'draft', ['draft','published'], true) ? $_POST['status'] : 'draft';
		
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
						if ($originalContent !== false && preg_match('/<body[^>]*>/i', $originalContent)) {
							// Replace body content using regex (preserve body tag attributes)
							$finalContent = preg_replace(
								'/(<body[^>]*>)(.*?)(<\/body>)/is',
								'$1' . $content . '$3',
								$originalContent
							);
							
							// If regex didn't match (unlikely), fall back to DOMDocument
							if ($finalContent === $originalContent) {
								libxml_use_internal_errors(true);
								$dom = new DOMDocument();
								@$dom->loadHTML('<?xml encoding="UTF-8">' . $originalContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
								libxml_clear_errors();
								
								$body = $dom->getElementsByTagName('body')->item(0);
								if ($body) {
									// Clear existing body content
									while ($body->firstChild) {
										$body->removeChild($body->firstChild);
									}
									
									// Parse new content into a temporary DOM
									$tempDom = new DOMDocument();
									libxml_use_internal_errors(true);
									@$tempDom->loadHTML('<?xml encoding="UTF-8"><body>' . $content . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
									libxml_clear_errors();
									
									// Import nodes from temp DOM
									$tempBody = $tempDom->getElementsByTagName('body')->item(0);
									if ($tempBody) {
										foreach ($tempBody->childNodes as $node) {
											$importedNode = $dom->importNode($node, true);
											$body->appendChild($importedNode);
										}
									}
									
									// Get the full HTML
									$finalContent = $dom->saveHTML();
								}
							}
						}
					}
					
					// Save file
					if (@file_put_contents($targetFile, $finalContent) !== false) {
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
						echo htmlspecialchars($iframePath, ENT_QUOTES, 'UTF-8');
					} elseif ($isFileEdit) {
						// Fallback: construct from current filePath
						$iframePath = '../' . ltrim($filePath, '/');
						if (substr($iframePath, -5) !== '.html') {
							$iframePath .= '.html';
						}
						echo htmlspecialchars($iframePath, ENT_QUOTES, 'UTF-8');
					} else {
						echo '../page.php?slug=' . urlencode($page['slug']);
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
				
				// Make text elements editable
				function makeEditable(element) {
					if (element.tagName && ['SCRIPT', 'STYLE', 'NOSCRIPT'].includes(element.tagName)) {
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
				
				// Add hover effects in edit mode
				iframeDoc.addEventListener('mouseover', function(e) {
					if (editMode && (e.target.classList.contains('editable-text') || e.target.classList.contains('media-editable'))) {
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
					const newHtml = iframeDoc.documentElement.outerHTML;
					document.getElementById('content_html').value = newHtml;
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
