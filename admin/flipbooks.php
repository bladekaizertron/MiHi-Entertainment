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

// CSRF
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($csrf, $token)) {
		$error = 'Security token invalid.';
	} else {
		$action = $_POST['action'] ?? '';
		if ($action === 'delete') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id > 0) {
				// Get flipbook info before deleting
				$fetch = $db->prepare("SELECT slug, pdf_path FROM flipbooks WHERE id = ? LIMIT 1");
				$fetch->execute([$id]);
				$flipbook = $fetch->fetch();
				
				$stmt = $db->prepare("DELETE FROM flipbooks WHERE id = ?");
				try {
					$stmt->execute([$id]);
					
					// Delete generated HTML file
					if ($flipbook && isset($flipbook['slug'])) {
						$htmlFile = __DIR__ . '/../' . $flipbook['slug'] . '.html';
						if (file_exists($htmlFile)) {
							@unlink($htmlFile);
						}
					}
					
					// Delete PDF file if exists
					if ($flipbook && isset($flipbook['pdf_path'])) {
						$pdfFile = __DIR__ . '/../' . $flipbook['pdf_path'];
						if (file_exists($pdfFile)) {
							@unlink($pdfFile);
						}
					}
					
					$success = 'Flipbook deleted successfully.';
				} catch (Throwable $e) {
					$error = 'Delete failed: ' . $e->getMessage();
				}
			}
		}
	}
}

// Fetch all flipbooks
$flipbooks = [];
try {
	$stmt = $db->query("SELECT * FROM flipbooks ORDER BY created_at DESC");
	$flipbooks = $stmt->fetchAll();
} catch (PDOException $e) {
	// Table might not exist yet
	if (strpos($e->getMessage(), "doesn't exist") !== false) {
		$error = 'Flipbooks table does not exist. Please run the database migration.';
	} else {
		$error = 'Error fetching flipbooks: ' . $e->getMessage();
	}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Flipbooks - Admin</title>
	<link rel="stylesheet" href="../assets/css/admin.css">
	<style>
		.admin-content { padding: 24px; }
		.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
		.page-header h1 { margin: 0; font-size: 24px; font-weight: 600; color: #111827; }
		.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; cursor: pointer; text-decoration: none; font-size: 14px; transition: all 0.2s; font-family: inherit; }
		.btn:hover { background: #f8fafc; }
		.btn-primary { background: #667eea; color: #ffffff; border-color: #667eea; }
		.btn-primary:hover { background: #5568d3; }
		.btn-sm { padding: 6px 12px; font-size: 13px; }
		.btn-danger { background: #ef4444; color: #ffffff; border-color: #ef4444; }
		.btn-danger:hover { background: #dc2626; }
		.btn button { all: unset; display: inline-flex; align-items: center; gap: 6px; }
		.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
		.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
		.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
		.data-table { width: 100%; border-collapse: collapse; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
		.data-table th, .data-table td { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 14px; }
		.data-table th { background: #f8fafc; color: #111827; font-weight: 600; }
		.data-table tbody tr:hover { background: #f8fafc; }
		.data-table tbody tr:last-child td { border-bottom: none; }
		.data-table code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 13px; color: #6b7280; }
		.empty-state { text-align: center; padding: 48px 24px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; }
		.empty-state p { color: #6b7280; font-size: 14px; margin: 0; }
		.empty-state a { color: #667eea; text-decoration: none; }
		.empty-state a:hover { text-decoration: underline; }
		.action-buttons { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
		.action-buttons .btn { min-width: 100px; justify-content: center; height: 32px; }
		.action-buttons form { display: inline-flex; margin: 0; }
		.action-buttons form button { min-width: 100px; height: 32px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
		.text-muted { color: #9ca3af; font-size: 13px; }
		.table-container { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
		.btn svg { width: 16px; height: 16px; flex-shrink: 0; }
		.btn span { white-space: nowrap; }
	</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="page-header">
	<h1>Flipbooks</h1>
	<a href="flipbook_create.php" class="btn btn-primary">Create New Flipbook</a>
</div>

<?php if ($error): ?>
	<div class="alert alert-error"><?php echo escape($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
	<div class="alert alert-success"><?php echo escape($success); ?></div>
<?php endif; ?>

<?php if (empty($flipbooks)): ?>
	<div class="empty-state">
		<p>No flipbooks found. <a href="flipbook_create.php">Create your first flipbook</a></p>
	</div>
<?php else: ?>
	<div class="table-container">
		<table class="data-table">
			<thead>
				<tr>
					<th>Title</th>
					<th>Slug</th>
					<th>PDF File</th>
					<th>Created</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($flipbooks as $flipbook): ?>
					<tr>
						<td><strong><?php echo escape($flipbook['title']); ?></strong></td>
						<td><code><?php echo escape($flipbook['slug']); ?></code></td>
						<td>
							<?php if ($flipbook['pdf_path']): ?>
								<a href="../<?php echo escape($flipbook['pdf_path']); ?>" target="_blank" class="btn btn-sm">
									<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
									</svg>
									<span>View PDF</span>
								</a>
							<?php else: ?>
								<span class="text-muted">No PDF</span>
							<?php endif; ?>
						</td>
						<td><?php echo date('M d, Y', strtotime($flipbook['created_at'])); ?></td>
						<td>
							<div class="action-buttons">
								<a href="../<?php echo escape($flipbook['slug']); ?>.html" target="_blank" class="btn btn-sm btn-primary">
									<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
									</svg>
									<span>View</span>
								</a>
								<form method="POST" style="display:inline-flex;" onsubmit="return confirm('Are you sure you want to delete this flipbook?');">
									<input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="id" value="<?php echo $flipbook['id']; ?>">
									<button type="submit" class="btn btn-sm btn-danger">
										<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
										</svg>
										<span>Delete</span>
									</button>
								</form>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

