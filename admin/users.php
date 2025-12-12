<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
	header('Location: index.php');
	exit;
}

$db = getDB();
$error = '';
$success = '';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = $_POST['csrf_token'] ?? '';
	$action = $_POST['action'] ?? '';
	$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

	if (!hash_equals($csrfToken, $token)) {
		$error = 'Security token invalid.';
	} elseif ($userId <= 0) {
		$error = 'Invalid user.';
	} elseif ($userId === (int)$currentUser['id']) {
		$error = 'You cannot change your own role.';
	} else {
		try {
			if ($action === 'approve') {
				$stmt = $db->prepare("UPDATE users SET role = 'editor' WHERE id = ?");
				$stmt->execute([$userId]);
				$success = 'User approved as editor.';
			} elseif ($action === 'make_admin') {
				$stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
				$stmt->execute([$userId]);
				$success = 'User promoted to admin.';
			} elseif ($action === 'make_editor') {
				$stmt = $db->prepare("UPDATE users SET role = 'editor' WHERE id = ?");
				$stmt->execute([$userId]);
				$success = 'User set to editor.';
			} elseif ($action === 'set_pending') {
				$stmt = $db->prepare("UPDATE users SET role = 'pending' WHERE id = ?");
				$result = $stmt->execute([$userId]);
				if ($result && $stmt->rowCount() > 0) {
					$success = 'User set to pending.';
				} else {
					$error = 'Failed to update user. User may not exist or role may already be pending.';
					error_log("Set pending failed - User ID: $userId, Rows affected: " . $stmt->rowCount());
				}
			} elseif ($action === 'delete') {
				// Prevent deleting yourself
				if ($userId === (int)$currentUser['id']) {
					$error = 'You cannot delete your own account.';
				} else {
					$del = $db->prepare("DELETE FROM users WHERE id = ?");
					$del->execute([$userId]);
					$success = 'User deleted.';
				}
			} else {
				$error = 'Unknown action.';
			}
		} catch (Throwable $e) {
			$errorMsg = $e->getMessage();
			
			// Check if it's the ENUM issue
			if (strpos($errorMsg, 'Data truncated') !== false || strpos($errorMsg, '1265') !== false) {
				$error = 'Update failed: The database role ENUM does not include "pending" yet. ';
				$error .= '<a href="quick_fix_role_enum.php" style="color: #0050ff; text-decoration: underline; font-weight: 600;">Click here to fix this</a> - ';
				$error .= 'This will update the database to allow "pending" status.';
			} else {
				$error = 'Update failed: ' . $errorMsg;
			}
			
			error_log("User update error: " . $errorMsg);
			error_log("Stack trace: " . $e->getTraceAsString());
		}
	}
}

// Fetch users
$users = $db->query("SELECT id, username, email, full_name, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Users - Admin</title>
	<link rel="stylesheet" href="../assets/css/admin.css">
	<style>
		.admin-content { padding: 24px; }
		.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
		.table { width: 100%; border-collapse: collapse; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
		.table th, .table td { padding: 12px 14px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 14px; }
		.table th { background: #f8fafc; color: #111827; font-weight: 600; }
		.badge { padding: 2px 8px; border-radius: 999px; border: 1px solid #e5e7eb; font-size: 12px; color: #374151; background: #f8fafc; }
		.badge.pending { color: #92400e; background: #fffbeb; border-color: #fcd34d; }
		.badge.editor { color: #065f46; background: #ecfdf5; border-color: #10b981; }
		.badge.admin { color: #1f2937; background: #e5e7eb; border-color: #d1d5db; }
		.actions { display: flex; gap: 8px; flex-wrap: wrap; }
		.btn-sm { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 8px; border: 1px solid #e5e7eb; background: #ffffff; cursor: pointer; font-size: 12px; }
		.btn-sm:hover { background: #f8fafc; }
		.alert { margin-bottom: 14px; padding: 10px 12px; border-radius: 8px; border: 1px solid; }
		.alert-success { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
		.alert-error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
	</style>
</head>
<body>
	<?php include __DIR__ . '/includes/header.php'; ?>
	<div class="admin-content">
		<div class="page-header">
			<h2>Users</h2>
		</div>
		<?php if ($error): ?>
			<div class="alert alert-error">
				<?php 
				// Check if error contains HTML (like our fix link)
				if (strpos($error, '<a href') !== false || strpos($error, '<br>') !== false) {
					echo $error; // Don't escape if it contains HTML links
				} else {
					echo escape($error); // Escape for security
				}
				?>
			</div>
		<?php endif; ?>
		<?php if ($success): ?>
			<div class="alert alert-success"><?php echo escape($success); ?></div>
		<?php endif; ?>

		<table class="table">
			<thead>
				<tr>
					<th>ID</th>
					<th>User</th>
					<th>Email</th>
					<th>Role</th>
					<th>Created</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($users as $u): ?>
				<tr>
					<td><?php echo (int)$u['id']; ?></td>
					<td><?php echo escape($u['full_name'] ?: $u['username']); ?></td>
					<td><?php echo escape($u['email']); ?></td>
					<td>
						<?php
							$roleRaw = $u['role'] ?? '';
							$roleNorm = strtolower(trim((string)$roleRaw));
							$roleForClass = $roleNorm ?: 'pending';
							$roleLabel = ucfirst($roleNorm ?: 'pending');
						?>
						<span class="badge <?php echo escape($roleForClass); ?>">
							<?php echo escape($roleLabel); ?>
						</span>
					</td>
					<td><?php echo escape(date('Y-m-d H:i', strtotime($u['created_at']))); ?></td>
					<td>
						<div class="actions">
							<?php $isPending = ($roleNorm === 'pending' || $roleNorm === ''); ?>
							<?php if ($isPending): ?>
								<form method="POST" action="">
									<input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
									<input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
									<input type="hidden" name="action" value="approve">
									<button class="btn-sm" type="submit">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
											<path d="M20 6L9 17l-5-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
										<span>Approve</span>
									</button>
								</form>
							<?php endif; ?>
							<?php if ($roleNorm !== 'admin'): ?>
								<form method="POST" action="">
									<input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
									<input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
									<input type="hidden" name="action" value="make_admin">
									<button class="btn-sm" type="submit">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
											<path d="M12 2l7 4v6c0 5-3.5 9-7 10-3.5-1-7-5-7-10V6l7-4z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
										<span>Make Admin</span>
									</button>
								</form>
							<?php endif; ?>
							<?php if ($roleNorm !== 'editor'): ?>
								<form method="POST" action="">
									<input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
									<input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
									<input type="hidden" name="action" value="make_editor">
									<button class="btn-sm" type="submit">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
											<path d="M12 20h9" stroke-width="2" stroke-linecap="round"/>
											<path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
										<span>Make Editor</span>
									</button>
								</form>
							<?php endif; ?>
							<?php if (!$isPending): ?>
								<form method="POST" action="">
									<input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
									<input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
									<input type="hidden" name="action" value="set_pending">
									<button class="btn-sm" type="submit">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
											<circle cx="12" cy="12" r="9" stroke-width="2"/>
											<path d="M12 7v5l3 3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
										<span>Set Pending</span>
									</button>
								</form>
							<?php endif; ?>
							<?php if ((int)$u['id'] !== (int)$currentUser['id']): ?>
								<form method="POST" action="" class="delete-user-form" data-username="<?php echo escape($u['full_name'] ?: $u['username']); ?>">
									<input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
									<input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
									<input type="hidden" name="action" value="delete">
									<button class="btn-sm" type="submit" style="border-color:#fecaca;color:#991b1b;background:#fef2f2;">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
											<polyline points="3 6 5 6 21 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											<path d="M10 11v6M14 11v6" stroke-width="2" stroke-linecap="round"/>
											<path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
										<span>Delete</span>
									</button>
								</form>
							<?php endif; ?>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		document.querySelectorAll('.delete-user-form').forEach(function(form) {
			form.addEventListener('submit', function(e) {
				var name = form.getAttribute('data-username') || 'this user';
				if (!confirm('Delete ' + name + '? This action cannot be undone.')) {
					e.preventDefault();
				}
			});
		});
	});
	</script>
</body>
</html>


