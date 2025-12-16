<?php
/**
 * Database Migration: Create flipbooks table
 * Run this file once to create the flipbooks table
 */

require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$role = strtolower(trim($currentUser['role'] ?? ''));
if ($role !== 'admin') {
	die('Access denied. Admin only.');
}

$db = getDB();
$success = false;
$error = '';

// Check if table already exists
try {
	$db->query("SELECT 1 FROM flipbooks LIMIT 1");
	$tableExists = true;
} catch (PDOException $e) {
	$tableExists = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_table'])) {
	try {
		$sql = "CREATE TABLE IF NOT EXISTS flipbooks (
			id INT AUTO_INCREMENT PRIMARY KEY,
			title VARCHAR(255) NOT NULL,
			slug VARCHAR(255) NOT NULL UNIQUE,
			pdf_path VARCHAR(500) NOT NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_slug (slug)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
		
		$db->exec($sql);
		$success = true;
		$tableExists = true;
	} catch (PDOException $e) {
		$error = 'Failed to create table: ' . htmlspecialchars($e->getMessage());
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Create Flipbooks Table - Admin</title>
	<link rel="stylesheet" href="../assets/css/admin.css">
	<style>
		.admin-content { padding: 24px; max-width: 600px; margin: 0 auto; }
		.migration-box { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; }
		.migration-box h1 { margin: 0 0 16px 0; font-size: 24px; font-weight: 600; color: #111827; }
		.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
		.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
		.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
		.alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
		.btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 8px; background: #667eea; color: #ffffff; cursor: pointer; text-decoration: none; font-size: 14px; transition: all 0.2s; }
		.btn:hover { background: #5568d3; }
		.btn-secondary { background: #6b7280; }
		.btn-secondary:hover { background: #4b5563; }
		.form-actions { margin-top: 20px; display: flex; gap: 12px; }
	</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="migration-box">
	<h1>Create Flipbooks Table</h1>
	
	<?php if ($tableExists && !$success): ?>
		<div class="alert alert-info">
			<strong>Table Already Exists:</strong> The flipbooks table already exists in the database.
		</div>
		<div class="form-actions">
			<a href="flipbooks.php" class="btn">Go to Flipbooks</a>
		</div>
	<?php elseif ($success): ?>
		<div class="alert alert-success">
			<strong>Success!</strong> The flipbooks table has been created successfully.
		</div>
		<div class="form-actions">
			<a href="flipbooks.php" class="btn">Go to Flipbooks</a>
		</div>
	<?php else: ?>
		<?php if ($error): ?>
			<div class="alert alert-error"><?php echo $error; ?></div>
		<?php endif; ?>
		
		<p>This will create the <code>flipbooks</code> table in your database. This is required to use the flipbook management system.</p>
		
		<form method="POST">
			<div class="form-actions">
				<button type="submit" name="create_table" class="btn">Create Table</button>
				<a href="index.php" class="btn btn-secondary">Cancel</a>
			</div>
		</form>
	<?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

