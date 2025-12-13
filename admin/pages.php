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
	$excludeFiles = ['index.html']; // We'll handle index separately
	
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
				'name' => $fileName,
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
		.category-section { margin-top: 32px; }
		.category-header { background:#f8fafc; padding:12px 16px; border:1px solid #e5e7eb; border-radius:8px 8px 0 0; font-weight:600; color:#111827; font-size:16px; }
		.category-content { border:1px solid #e5e7eb; border-top:none; border-radius:0 0 8px 8px; overflow:hidden; }
		.page-item { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #e5e7eb; }
		.page-item:last-child { border-bottom:none; }
		.page-item:hover { background:#f8fafc; }
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
	</style>
	<script>
		function showTab(tabName) {
			document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
			document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
			document.getElementById('tab-' + tabName).style.display = 'block';
			event.target.classList.add('active');
		}
	</script>
</head>
<body>
	<?php include __DIR__ . '/includes/header.php'; ?>
	<div class="admin-content">
		<div class="page-header">
			<h2>Pages</h2>
			<a class="btn" href="pages_edit.php">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<span>New Page</span>
			</a>
		</div>
		<?php if ($error): ?>
			<div class="alert alert-error" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:10px 12px;border-radius:8px;margin-bottom:14px;">
				<?php echo escape($error); ?>
			</div>
			<pre style="white-space:pre-wrap;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px;font-size:12px;color:#111827;">CREATE TABLE pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  content_html MEDIUMTEXT NULL,
  custom_css MEDIUMTEXT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>
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
		?>

		<!-- Statistics -->
		<div class="stats">
			<h3 style="margin:0 0 12px 0; font-size:16px; font-weight:600; color:#111827;">Website Statistics</h3>
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
			</div>
		</div>

		<!-- Tabs -->
		<div class="tabs">
			<button class="tab active" onclick="showTab('all')">All Website Pages</button>
			<?php if ($pages): ?>
			<button class="tab" onclick="showTab('database')">Database Pages</button>
			<?php endif; ?>
		</div>

		<!-- All Website Pages Tab -->
		<div id="tab-all" class="tab-content" style="display:block;">
			<?php if ($websitePages): ?>
				<?php
				$currentCategory = '';
				foreach ($websitePages as $page):
					if ($page['category'] !== $currentCategory):
						if ($currentCategory !== ''):
							echo '</div></div>';
						endif;
						$currentCategory = $page['category'];
				?>
				<div class="category-section">
					<div class="category-header">
						<?php echo escape($currentCategory); ?> (<?php echo $categories[$currentCategory]; ?>)
					</div>
					<div class="category-content">
				<?php endif; ?>
						<div class="page-item">
							<div class="page-info">
								<div class="page-name"><?php echo escape($page['name']); ?></div>
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
</body>
</html>


