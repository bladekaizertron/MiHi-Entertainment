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
	</style>
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

		<?php if ($pages): ?>
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
		<?php endif; ?>
	</div>
</body>
</html>


