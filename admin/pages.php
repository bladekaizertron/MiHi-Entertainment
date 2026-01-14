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

function removeStaticPage($slug) {
	if (!$slug) return;
	$path = __DIR__ . '/../' . $slug . '.html';
	if (is_file($path)) {
		unlink($path);
	}
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($csrf, $token)) {
		$error = 'Security token invalid.';
	} else {
		$action = $_POST['action'] ?? '';
		if ($action === 'delete') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id > 0) {
				// Get slug before deleting so we can remove static html
				$fetch = $db->prepare("SELECT slug FROM pages WHERE id = ? LIMIT 1");
				$fetch->execute([$id]);
				$pageRow = $fetch->fetch();
				$stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
				try {
					$stmt->execute([$id]);
					if ($pageRow && isset($pageRow['slug'])) {
						removeStaticPage($pageRow['slug']);
					}
					$success = 'Page deleted.';
				} catch (Throwable $e) {
					$error = 'Delete failed. Ensure the pages table exists.';
				}
			}
		} elseif ($action === 'duplicate_file') {
			$f = $_POST['f'] ?? '';
			if ($f) {
				$filePath = base64_decode($f);
				$rootDir = dirname(__DIR__);
				$sourcePath = $rootDir . '/' . ltrim($filePath, '/');
				
				if (file_exists($sourcePath) && is_file($sourcePath)) {
					$info = pathinfo($sourcePath);
					$newName = $info['filename'] . '-copy.' . $info['extension'];
					$targetPath = $info['dirname'] . '/' . $newName;
					
					// Avoid overwriting existing copy
					$counter = 1;
					while (file_exists($targetPath)) {
						$newName = $info['filename'] . '-copy-' . $counter . '.' . $info['extension'];
						$targetPath = $info['dirname'] . '/' . $newName;
						$counter++;
					}
					
					if (copy($sourcePath, $targetPath)) {
						$success = 'File duplicated successfully: ' . $newName;
						// Refresh scan
						$websitePages = scanWebsitePages($rootDir);
					} else {
						$error = 'Failed to copy file.';
					}
				} else {
					$error = 'Source file not found.';
				}
			}
		} elseif ($action === 'duplicate_db') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id > 0) {
				try {
					// Get existing page data
					$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
					$stmt->execute([$id]);
					$page = $stmt->fetch(PDO::FETCH_ASSOC);
					
					if ($page) {
						$newTitle = $page['title'] . ' (Copy)';
						$newSlug = $page['slug'] . '-copy';
						
						// Ensure unique slug
						$check = $db->prepare("SELECT id FROM pages WHERE slug = ?");
						$check->execute([$newSlug]);
						$counter = 1;
						while ($check->fetch()) {
							$newSlug = $page['slug'] . '-copy-' . $counter;
							$check->execute([$newSlug]);
							$counter++;
						}
						
						// Prepare clone data (exclude ID, set status to draft, nullify published_at)
						unset($page['id']);
						$page['title'] = $newTitle;
						$page['slug'] = $newSlug;
						$page['status'] = 'draft';
						$page['published_at'] = null;
						$page['created_at'] = date('Y-m-d H:i:s');
						$page['updated_at'] = date('Y-m-d H:i:s');
						
						$cols = implode(', ', array_keys($page));
						$placeholders = implode(', ', array_fill(0, count($page), '?'));
						
						$insert = $db->prepare("INSERT INTO pages ($cols) VALUES ($placeholders)");
						$insert->execute(array_values($page));
						
						$success = 'Page duplicated in database as draft.';
						// Refresh pages list
						$pages = $db->query("SELECT id, title, slug, status, updated_at, created_at FROM pages ORDER BY updated_at DESC, created_at DESC")->fetchAll();
					} else {
						$error = 'Source page not found.';
					}
				} catch (Throwable $e) {
					$error = 'Duplication failed: ' . $e->getMessage();
				}
			}
		} elseif ($action === 'sync_to_db') {
			// Sync directory pages to database
			$rootDir = dirname(__DIR__);
			try {
				$websitePages = scanWebsitePages($rootDir);
				$synced = 0;
				$skipped = 0;
				$errors = [];
				
				// Get existing slugs from database
				$existingSlugs = [];
				try {
					$existingPages = $db->query("SELECT slug FROM pages")->fetchAll();
					foreach ($existingPages as $ep) {
						$existingSlugs[$ep['slug']] = true;
					}
				} catch (Throwable $e) {
					// Table might not exist
				}
				
				foreach ($websitePages as $page) {
					// Generate slug from path (remove .html, replace / with -)
					$slug = str_replace('.html', '', $page['path']);
					$slug = str_replace('/', '-', $slug);
					$slug = preg_replace('/[^a-z0-9\-]/i', '-', $slug);
					$slug = preg_replace('/-+/', '-', $slug);
					$slug = trim($slug, '-');
					
					// Skip if already exists
					if (isset($existingSlugs[$slug])) {
						$skipped++;
						continue;
					}
					
					// Generate title from filename
					$title = str_replace('.html', '', $page['name']);
					$title = str_replace(['-', '_'], ' ', $title);
					$title = ucwords($title);
					
					// Read file content
					$filePath = $rootDir . '/' . $page['path'];
					$content = '';
					if (file_exists($filePath)) {
						$content = file_get_contents($filePath);
					}
					
					// Insert into database
					try {
						$stmt = $db->prepare("INSERT INTO pages (title, slug, content_html, status, created_at, updated_at) VALUES (?, ?, ?, 'published', NOW(), NOW())");
						$stmt->execute([$title, $slug, $content]);
						$synced++;
					} catch (Throwable $e) {
						$errors[] = "Failed to sync {$page['name']}: " . $e->getMessage();
					}
				}
				
				if ($synced > 0) {
					$success = "Successfully synced {$synced} page(s) to database.";
					if ($skipped > 0) {
						$success .= " {$skipped} page(s) already existed and were skipped.";
					}
				} else {
					$success = "No new pages to sync. {$skipped} page(s) already exist in database.";
				}
				
				if (!empty($errors)) {
					$error = implode('<br>', $errors);
				}
			} catch (Throwable $e) {
				$error = 'Sync failed: ' . $e->getMessage();
			}
		} elseif ($action === 'sync_single_page') {
			$f = $_POST['f'] ?? '';
			if ($f) {
				$filePath = base64_decode($f);
				$rootDir = dirname(__DIR__);
				$sourcePath = $rootDir . '/' . ltrim($filePath, '/');
				
				if (file_exists($sourcePath) && is_file($sourcePath)) {
					// Generate metadata same as batch sync
					$fileName = basename($sourcePath);
					$slug = str_replace('.html', '', ltrim($filePath, '/'));
					$slug = str_replace('/', '-', $slug);
					$slug = preg_replace('/[^a-z0-9\-]/i', '-', $slug);
					$slug = preg_replace('/-+/', '-', $slug);
					$slug = trim($slug, '-');
					
					// Check if already exists in database
					$check = $db->prepare("SELECT id FROM pages WHERE slug = ?");
					$check->execute([$slug]);
					if ($check->fetch()) {
						$error = "A page with the slug '{$slug}' already exists in the database.";
					} else {
						$title = str_replace('.html', '', $fileName);
						$title = str_replace(['-', '_'], ' ', $title);
						$title = ucwords($title);
						
						$content = file_get_contents($sourcePath);
						
						try {
							$stmt = $db->prepare("INSERT INTO pages (title, slug, content_html, status, created_at, updated_at) VALUES (?, ?, ?, 'published', NOW(), NOW())");
							$stmt->execute([$title, $slug, $content]);
							$success = "Successfully synced '{$fileName}' to database.";
							// Refresh scan
							$websitePages = scanWebsitePages($rootDir);
							$pages = $db->query("SELECT id, title, slug, status, updated_at, created_at FROM pages ORDER BY updated_at DESC, created_at DESC")->fetchAll();
						} catch (Throwable $e) {
							$error = "Failed to sync file: " . $e->getMessage();
						}
					}
				} else {
					$error = 'Source file not found.';
				}
			}
		}
	}
}

// Fetch pages (if table exists)
$pages = [];
try {
	$pages = $db->query("SELECT id, title, slug, status, updated_at, created_at FROM pages ORDER BY updated_at DESC, created_at DESC")->fetchAll();
} catch (Throwable $e) {
	$error = 'Pages table not found. Please create it using the SQL below.';
}

// Function to scan for all HTML pages in the website
function scanWebsitePages($rootDir) {
	$pages = [];
	$excludeDirs = ['admin', 'config', 'database', 'includes', 'uploads', '__MACOSX', 'flipbook', 'node_modules', '.git'];
	
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	
	foreach ($iterator as $file) {
		if ($file->isFile() && $file->getExtension() === 'html') {
			$path = $file->getPathname();
			$relativePath = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $path);
			$relativePath = str_replace('\\', '/', $relativePath);
			
			// Skip excluded directories
			$shouldExclude = false;
			foreach ($excludeDirs as $excludeDir) {
				if (strpos($relativePath, $excludeDir . '/') === 0 || strpos($relativePath, '/' . $excludeDir . '/') !== false) {
					$shouldExclude = true;
					break;
				}
			}
			
			if ($shouldExclude) continue;
			
			// Get file info
			$fileName = $file->getFilename();
			$dir = dirname($relativePath);
			$url = '/' . $relativePath;
			$modified = filemtime($path);
			$size = filesize($path);
			
			// Try to get title from HTML content
			$extractedTitle = '';
			$handle = @fopen($path, 'r');
			if ($handle) {
				$chunk = fread($handle, 4096); // Read 4KB to be safe for title
				fclose($handle);
				if (preg_match('/<title>(.*?)<\/title>/is', $chunk, $matches)) {
					$extractedTitle = trim(strip_tags($matches[1]));
				}
			}
			
			if (!$extractedTitle) {
				// Fallback to filename
				$extractedTitle = str_replace('.html', '', $fileName);
				$extractedTitle = str_replace(['-', '_'], ' ', $extractedTitle);
				$extractedTitle = ucwords($extractedTitle);
			}
			
			// Determine category
			$category = 'Other';
			if ($dir === '.' || $dir === '') {
				$category = 'Main Pages';
			} elseif (strpos($dir, 'product') !== false) {
				$category = 'Product Pages';
			} elseif (strpos($dir, 'products') !== false) {
				$category = 'Product Pages';
			} elseif (strpos($dir, 'locations') !== false) {
				$category = 'Location Pages';
			} elseif (strpos($dir, 'event-themes') !== false) {
				$category = 'Event Themes';
			} elseif (strpos($dir, 'event-type') !== false) {
				$category = 'Event Types';
			} elseif (strpos($dir, 'event-decor') !== false) {
				$category = 'Event Decor';
			} elseif (strpos($dir, 'av-services') !== false) {
				$category = 'AV Services';
			} elseif (strpos($dir, 'game-rentals') !== false) {
				$category = 'Game Rentals';
			} elseif (strpos($dir, 'custom-sets') !== false) {
				$category = 'Custom Sets';
			} elseif (strpos($dir, 'post') !== false) {
				$category = 'Blog Posts';
			}
			
			$pages[] = [
				'path' => $relativePath,
				'url' => $url,
				'name' => $extractedTitle, // Use extracted title as name
				'filename' => $fileName, // Keep original filename
				'category' => $category,
				'directory' => $dir === '.' ? 'Root' : $dir,
				'modified' => $modified,
				'size' => $size,
				'size_formatted' => formatBytes($size)
			];
		}
	}
	
	// Sort by category, then by name
	usort($pages, function($a, $b) {
		$catCompare = strcmp($a['category'], $b['category']);
		if ($catCompare !== 0) return $catCompare;
		return strcmp($a['name'], $b['name']);
	});
	
	return $pages;
}

function formatBytes($bytes, $precision = 2) {
	$units = ['B', 'KB', 'MB', 'GB'];
	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);
	$bytes /= pow(1024, $pow);
	return round($bytes, $precision) . ' ' . $units[$pow];
}

// Scan website pages
$websitePages = [];
$rootDir = dirname(__DIR__);
try {
	$websitePages = scanWebsitePages($rootDir);
} catch (Throwable $e) {
	// Silently fail if scanning fails
}

// Check which pages are in directory but not in database
$pagesToSync = [];
$existingSlugs = [];
try {
	$existingPages = $db->query("SELECT slug FROM pages")->fetchAll();
	foreach ($existingPages as $ep) {
		$existingSlugs[$ep['slug']] = true;
	}
	
	// Check each website page
	foreach ($websitePages as $page) {
		$slug = str_replace('.html', '', $page['path']);
		$slug = str_replace('/', '-', $slug);
		$slug = preg_replace('/[^a-z0-9\-]/i', '-', $slug);
		$slug = preg_replace('/-+/', '-', $slug);
		$slug = trim($slug, '-');
		
		if (!isset($existingSlugs[$slug])) {
			$pagesToSync[] = $page;
		}
	}
} catch (Throwable $e) {
	// If table doesn't exist, all pages can be synced
	$pagesToSync = $websitePages;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Pages - Admin</title>
	<link rel="stylesheet" href="../assets/css/admin.css">
	<style>
		.admin-content { padding: 24px; }
		.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
		.btn { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#ffffff; cursor:pointer; text-decoration:none; font-size:14px; }
		.btn:hover { background:#f8fafc; }
		.table { width:100%; border-collapse:collapse; background:#ffffff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
		.table th, .table td { padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:14px; }
		.table th { background:#f8fafc; color:#111827; font-weight:600; }
		.badge { padding:2px 8px; border-radius:999px; border:1px solid #e5e7eb; font-size:12px; color:#374151; background:#f8fafc; }
		.badge.published { color:#065f46; background:#ecfdf5; border-color:#10b981; }
		.badge.draft { color:#92400e; background:#fffbeb; border-color:#fcd34d; }
		.actions { display:flex; gap:8px; flex-wrap:wrap; }
		.category-section { margin-top: 16px; }
		.category-header { background:#f8fafc; padding:12px 16px; border:1px solid #e5e7eb; border-radius:8px; font-weight:600; color:#111827; font-size:16px; cursor:pointer; display:flex; align-items:center; justify-content:space-between; user-select:none; transition:background-color 0.2s; }
		.category-header:hover { background:#f1f5f9; }
		.category-header.collapsed { border-radius:8px; }
		.category-header .category-title { display:flex; align-items:center; gap:8px; flex:1; }
		.category-header .category-arrow { transition:transform 0.3s ease; width:20px; height:20px; display:flex; align-items:center; justify-content:center; }
		.category-header.collapsed .category-arrow { transform:rotate(-90deg); }
		.category-content { border:1px solid #e5e7eb; border-top:none; border-radius:0 0 8px 8px; overflow:hidden; max-height:5000px; transition:max-height 0.3s ease, opacity 0.2s ease; opacity:1; }
		.category-content.collapsed { max-height:0; opacity:0; overflow:hidden; border:none; }
		.page-item { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #e5e7eb; }
		.page-item:last-child { border-bottom:none; }
		.page-item:hover { background:#f8fafc; }
		.page-item.sync-available { background:#fef3c7; }
		.page-item.sync-available:hover { background:#fde68a; }
		.page-info { flex:1; }
		.page-name { font-weight:500; color:#111827; margin-bottom:4px; }
		.page-path { font-size:12px; color:#6b7280; font-family:monospace; }
		.page-meta { display:flex; gap:16px; font-size:12px; color:#6b7280; margin-top:4px; }
		.stats { margin-top:24px; padding:16px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; }
		.stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-top:12px; }
		.stat-item { text-align:center; }
		.stat-value { font-size:24px; font-weight:600; color:#111827; }
		.stat-label { font-size:12px; color:#6b7280; margin-top:4px; }
		.tabs { display:flex; gap:8px; margin-bottom:24px; border-bottom:2px solid #e5e7eb; }
		.tab { padding:12px 20px; background:transparent; border:none; border-bottom:2px solid transparent; cursor:pointer; font-size:14px; color:#6b7280; margin-bottom:-2px; }
		.tab.active { color:#111827; border-bottom-color:#667eea; font-weight:600; }
		.tab:hover { color:#111827; }

		/* Modal Styles */
		.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: none; align-items: center; justify-content: center; z-index: 1000; animation: fadeIn 0.2s ease-out; }
		.modal { background: #ffffff; border-radius: 12px; width: 90%; max-width: 800px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
		.modal-header { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; background: #f8fafc; }
		.modal-header h3 { margin: 0; font-size: 18px; color: #111827; }
		.modal-close { background: transparent; border: none; cursor: pointer; color: #6b7280; display: flex; align-items: center; justify-content: center; padding: 4px; border-radius: 4px; }
		.modal-close:hover { background: #f1f5f9; color: #111827; }
		.modal-body { padding: 0; overflow-y: auto; flex: 1; }
		.modal-footer { padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 12px; background: #f8fafc; }
		
		.sync-list-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; border-bottom: 1px solid #e5e7eb; transition: background 0.1s; }
		.sync-list-item:hover { background: #f9fafb; }
		.sync-list-item:last-child { border-bottom: none; }
		
		@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
		
		.clickable-stat { cursor: pointer; transition: transform 0.1s, box-shadow 0.1s; position: relative; }
		.clickable-stat:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3); border-color: #fbbf24 !important; }
		.clickable-stat::after { content: 'Click to view'; position: absolute; bottom: 4px; right: 8px; font-size: 10px; color: #92400e; opacity: 0.7; font-weight: 500; }
	</style>
	<script>
		function showTab(tabName, element) {
			document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
			document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
			document.getElementById('tab-' + tabName).style.display = 'block';
			if (element) {
				element.classList.add('active');
			}
			// Show/hide category controls based on active tab
			const controls = document.getElementById('category-controls');
			if (controls) {
				controls.style.display = tabName === 'all' ? 'flex' : 'none';
			}
		}
		
		function toggleCategory(categoryId) {
			const header = document.getElementById('category-header-' + categoryId);
			const content = document.getElementById('category-content-' + categoryId);
			
			if (content.classList.contains('collapsed')) {
				content.classList.remove('collapsed');
				header.classList.remove('collapsed');
			} else {
				content.classList.add('collapsed');
				header.classList.add('collapsed');
			}
		}
		
		function openSyncModal() {
			document.getElementById('sync-modal-overlay').style.display = 'flex';
			document.body.style.overflow = 'hidden';
		}
		
		function closeSyncModal() {
			document.getElementById('sync-modal-overlay').style.display = 'none';
			document.body.style.overflow = 'auto';
		}
		
		// Close modal on escape key
		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape') closeSyncModal();
		});

		function expandAllCategories() {
			document.querySelectorAll('.category-content').forEach(content => {
				content.classList.remove('collapsed');
			});
			document.querySelectorAll('.category-header').forEach(header => {
				header.classList.remove('collapsed');
			});
		}
		
		function collapseAllCategories() {
			document.querySelectorAll('.category-content').forEach(content => {
				content.classList.add('collapsed');
			});
			document.querySelectorAll('.category-header').forEach(header => {
				header.classList.add('collapsed');
			});
		}
	</script>
</head>
<body>
	<?php include __DIR__ . '/includes/header.php'; ?>
	<div class="admin-content">
		<div class="page-header">
			<h2>Pages</h2>
			<a class="btn" href="pages_add.php">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<span>New Page</span>
			</a>
		</div>
		<?php if ($error): ?>
			<div class="alert alert-error" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:10px 12px;border-radius:8px;margin-bottom:14px;">
				<?php echo escape($error); ?>
			</div>
			<?php if (strpos($error, 'table not found') !== false || strpos($error, 'Ensure the pages table exists') !== false): ?>
			<pre style="white-space:pre-wrap;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px;font-size:12px;color:#111827;">CREATE TABLE pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  content_html MEDIUMTEXT NULL,
  custom_css MEDIUMTEXT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  meta_title VARCHAR(255) NULL,
  meta_description TEXT NULL,
  meta_keywords TEXT NULL,
  og_title VARCHAR(255) NULL,
  og_description TEXT NULL,
  og_image VARCHAR(255) NULL,
  canonical_url VARCHAR(255) NULL,
  robots VARCHAR(50) DEFAULT 'index, follow',
  structured_data TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ($success): ?>
			<div class="alert alert-success" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:10px 12px;border-radius:8px;margin-bottom:14px;">
				<?php echo escape($success); ?>
			</div>
		<?php endif; ?>

		<?php
		// Calculate statistics
		$totalWebsitePages = count($websitePages);
		$categories = [];
		foreach ($websitePages as $page) {
			$cat = $page['category'];
			if (!isset($categories[$cat])) {
				$categories[$cat] = 0;
			}
			$categories[$cat]++;
		}
		$totalCategories = count($categories);
		$totalDbPages = count($pages);
		$totalPagesToSync = count($pagesToSync);
		?>

		<!-- Statistics -->
		<div class="stats">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:12px;">
				<h3 style="margin:0; font-size:16px; font-weight:600; color:#111827;">Website Statistics</h3>
				<?php if ($totalPagesToSync > 0): ?>
				<div style="display:flex; align-items:center; gap:12px;">
					<span style="font-size:12px; color:#6b7280;">Pages highlighted in yellow are not in the database</span>
					<form method="POST" action="" onsubmit="return confirm('This will import <?php echo $totalPagesToSync; ?> page(s) from the directory into the database. Continue?')" style="display:inline;">
						<input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
						<input type="hidden" name="action" value="sync_to_db">
						<button type="submit" class="btn" style="background:#667eea; color:#ffffff; border-color:#667eea;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
								<polyline points="7 10 12 15 17 10"></polyline>
								<line x1="12" y1="15" x2="12" y2="3"></line>
							</svg>
							<span>Sync <?php echo $totalPagesToSync; ?> Page(s) to Database</span>
						</button>
					</form>
				</div>
				<?php endif; ?>
			</div>
			<div class="stats-grid">
				<div class="stat-item">
					<div class="stat-value"><?php echo $totalWebsitePages; ?></div>
					<div class="stat-label">Total HTML Pages</div>
				</div>
				<div class="stat-item">
					<div class="stat-value"><?php echo $totalCategories; ?></div>
					<div class="stat-label">Categories</div>
				</div>
				<div class="stat-item">
					<div class="stat-value"><?php echo $totalDbPages; ?></div>
					<div class="stat-label">Database Pages</div>
				</div>
				<?php if ($totalPagesToSync > 0): ?>
				<div class="stat-item clickable-stat" onclick="openSyncModal()" style="background:#fef3c7; border:1px solid #fcd34d; border-radius:8px; padding:12px;">
					<div class="stat-value" style="color:#92400e;"><?php echo $totalPagesToSync; ?></div>
					<div class="stat-label" style="color:#92400e;">Pages to Sync</div>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Tabs -->
		<div class="tabs">
			<button class="tab active" onclick="showTab('all', this)">All Website Pages</button>
			<?php if ($pages): ?>
			<button class="tab" onclick="showTab('database', this)">Database Pages</button>
			<?php endif; ?>
		</div>
		
		<!-- Expand/Collapse Controls -->
		<div id="category-controls" style="margin-bottom:16px; display:flex; gap:8px; align-items:center;">
			<button class="btn" onclick="expandAllCategories()" style="font-size:12px;">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
				<span>Expand All</span>
			</button>
			<button class="btn" onclick="collapseAllCategories()" style="font-size:12px;">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
				<span>Collapse All</span>
			</button>
		</div>

		<!-- All Website Pages Tab -->
		<div id="tab-all" class="tab-content" style="display:block;">
			<?php if ($websitePages): ?>
				<?php
				$currentCategory = '';
				$categoryIndex = 0;
				foreach ($websitePages as $page):
					if ($page['category'] !== $currentCategory):
						if ($currentCategory !== ''):
							echo '</div></div>';
						endif;
						$currentCategory = $page['category'];
						$categoryId = 'cat-' . $categoryIndex++;
				?>
				<div class="category-section">
					<div class="category-header" id="category-header-<?php echo $categoryId; ?>" onclick="toggleCategory('<?php echo $categoryId; ?>')">
						<div class="category-title">
							<svg class="category-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<polyline points="6 9 12 15 18 9"></polyline>
							</svg>
							<span><?php echo escape($currentCategory); ?> (<?php echo $categories[$currentCategory]; ?>)</span>
						</div>
					</div>
					<div class="category-content" id="category-content-<?php echo $categoryId; ?>">
				<?php endif; ?>
						<?php
						// Check if this page can be synced
						$canSync = false;
						$pageSlug = str_replace('.html', '', $page['path']);
						$pageSlug = str_replace('/', '-', $pageSlug);
						$pageSlug = preg_replace('/[^a-z0-9\-]/i', '-', $pageSlug);
						$pageSlug = preg_replace('/-+/', '-', $pageSlug);
						$pageSlug = trim($pageSlug, '-');
						$canSync = !isset($existingSlugs[$pageSlug]);
						?>
						<div class="page-item <?php if ($canSync): ?>sync-available<?php endif; ?>">
							<div class="page-info">
								<div class="page-name" style="display:flex; align-items:center; gap:8px;">
									<?php echo escape($page['name']); ?>
									<?php if ($canSync): ?>
									<span class="badge" style="background:#fbbf24; color:#78350f; border-color:#f59e0b; font-size:10px; padding:2px 6px;">
										Not in DB
									</span>
									<?php endif; ?>
								</div>
								<div class="page-path"><?php echo escape($page['path']); ?></div>
								<div class="page-meta">
									<span>Modified: <?php echo date('Y-m-d H:i', $page['modified']); ?></span>
									<span>Size: <?php echo $page['size_formatted']; ?></span>
									<?php if ($page['directory'] !== 'Root'): ?>
									<span>Directory: <?php echo escape($page['directory']); ?></span>
									<?php endif; ?>
								</div>
							</div>
							<div class="actions">
								<a class="btn" href="pages_edit.php?f=<?php echo urlencode(base64_encode($page['path'])); ?>">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
									<span>Edit</span>
								</a>
								<form method="POST" action="" style="display:inline;">
									<input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
									<input type="hidden" name="action" value="duplicate_file">
									<input type="hidden" name="f" value="<?php echo base64_encode($page['path']); ?>">
									<button type="submit" class="btn" title="Duplicate file">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
										<span>Duplicate</span>
									</button>
								</form>
								<a class="btn" href="<?php echo escape($page['url']); ?>" target="_blank">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 3h7v7m-1-6L10 14M5 5h5M5 10v9h9v-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
									<span>View</span>
								</a>
							</div>
						</div>
				<?php endforeach; ?>
				<?php if ($currentCategory !== ''): ?>
					</div>
				</div>
				<?php endif; ?>
			<?php else: ?>
				<div style="padding:24px; text-align:center; color:#6b7280;">
					No HTML pages found.
				</div>
			<?php endif; ?>
		</div>

		<!-- Database Pages Tab -->
		<?php if ($pages): ?>
		<div id="tab-database" class="tab-content" style="display:none;">
			<table class="table">
				<thead>
					<tr>
						<th>Title</th>
						<th>Slug</th>
						<th>Status</th>
						<th>Updated</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($pages as $p): ?>
					<tr>
						<td><?php echo escape($p['title']); ?></td>
						<td><?php echo escape($p['slug']); ?></td>
						<td><span class="badge <?php echo $p['status'] === 'published' ? 'published' : 'draft'; ?>"><?php echo escape(ucfirst($p['status'])); ?></span></td>
						<td><?php echo escape(date('Y-m-d H:i', strtotime($p['updated_at'] ?: $p['created_at']))); ?></td>
						<td>
							<div class="actions">
								<a class="btn" href="pages_edit.php?id=<?php echo (int)$p['id']; ?>">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
									<span>Edit</span>
								</a>
								<form method="POST" action="" style="display:inline;">
									<input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
									<input type="hidden" name="action" value="duplicate_db">
									<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
									<button type="submit" class="btn" title="Duplicate database page">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
										<span>Duplicate</span>
									</button>
								</form>
								<form method="POST" action="" onsubmit="return confirm('Delete this page? This cannot be undone.')" style="display:inline;">
									<input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
									<button class="btn" type="submit" style="border-color:#fecaca;color:#991b1b;background:#fef2f2;">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
											<polyline points="3 6 5 6 21 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											<path d="M10 11v6M14 11v6" stroke-width="2" stroke-linecap="round"/>
											<path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
										<span>Delete</span>
									</button>
								</form>
								<a class="btn" href="../page.php?slug=<?php echo urlencode($p['slug']); ?>" target="_blank">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 3h7v7m-1-6L10 14M5 5h5M5 10v9h9v-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
									<span>View</span>
								</a>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</div>

	<!-- Sync Modal -->
	<?php if ($totalPagesToSync > 0): ?>
	<div id="sync-modal-overlay" class="modal-overlay" onclick="if(event.target === this) closeSyncModal()">
		<div class="modal">
			<div class="modal-header">
				<h3>Select Pages to Sync</h3>
				<button class="modal-close" onclick="closeSyncModal()">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
				</button>
			</div>
			<div class="modal-body">
				<div style="padding: 12px 20px; background: #fffbeb; border-bottom: 1px solid #fef3c7; font-size: 13px; color: #92400e; display: flex; align-items: center; gap: 8px;">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
					Syncing will create a database record for the selected HTML file.
				</div>
				<?php foreach ($pagesToSync as $page): ?>
				<div class="sync-list-item">
					<div class="page-info">
						<div class="page-name"><?php echo escape($page['name']); ?></div>
						<div class="page-path" style="font-size: 11px;"><?php echo escape($page['path']); ?></div>
					</div>
					<form method="POST" action="">
						<input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
						<input type="hidden" name="action" value="sync_single_page">
						<input type="hidden" name="f" value="<?php echo base64_encode($page['path']); ?>">
						<button type="submit" class="btn" style="background:#667eea; color:#fff; border-color:#667eea; padding: 4px 12px; font-size: 12px;">
							<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
							<span>Sync</span>
						</button>
					</form>
				</div>
				<?php endforeach; ?>
			</div>
			<div class="modal-footer">
				<button class="btn" onclick="closeSyncModal()">Close</button>
				<form method="POST" action="" onsubmit="return confirm('Sync all <?php echo $totalPagesToSync; ?> pages to database?')">
					<input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
					<input type="hidden" name="action" value="sync_to_db">
					<button type="submit" class="btn" style="background:#667eea; color:#fff; border-color:#667eea;">Sync All</button>
				</form>
			</div>
		</div>
	</div>
	<?php endif; ?>
</body>
</html>


