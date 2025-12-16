<?php
/**
 * Setup script to create flipbook directories with proper permissions
 * Run this once to set up the required directories
 */

require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$role = strtolower(trim($currentUser['role'] ?? ''));
if (!in_array($role, ['admin','editor'], true)) {
	die('Access denied.');
}

$baseDir = __DIR__ . '/../flipbook/';
$uploadDir = $baseDir . 'uploads/';
$errors = [];
$success = [];

// Try to create directories (try 0755 first, then 0777 for shared hosting like GoDaddy)
if (!file_exists($baseDir)) {
	if (@mkdir($baseDir, 0755, true)) {
		@chmod($baseDir, 0755);
		$success[] = "Created directory: flipbook/";
	} elseif (@mkdir($baseDir, 0777, true)) {
		@chmod($baseDir, 0777);
		$success[] = "Created directory: flipbook/ (with 777 permissions for shared hosting)";
	} else {
		$errors[] = "Failed to create directory: flipbook/";
		$errors[] = "Please manually create the directory with write permissions via cPanel File Manager or FTP.";
	}
} else {
	$success[] = "Directory exists: flipbook/";
	if (!is_writable($baseDir)) {
		@chmod($baseDir, 0755);
		if (!is_writable($baseDir)) {
			@chmod($baseDir, 0777);
		}
		if (is_writable($baseDir)) {
			$success[] = "Fixed permissions for: flipbook/";
		} else {
			$errors[] = "Directory flipbook/ is not writable. Please set permissions to 755 or 777 via cPanel/FTP.";
		}
	}
}

if (!file_exists($uploadDir)) {
	if (@mkdir($uploadDir, 0755, true)) {
		@chmod($uploadDir, 0755);
		$success[] = "Created directory: flipbook/uploads/";
	} elseif (@mkdir($uploadDir, 0777, true)) {
		@chmod($uploadDir, 0777);
		$success[] = "Created directory: flipbook/uploads/ (with 777 permissions for shared hosting)";
	} else {
		$errors[] = "Failed to create directory: flipbook/uploads/";
		$errors[] = "Please manually create the directory with write permissions via cPanel File Manager or FTP.";
	}
} else {
	$success[] = "Directory exists: flipbook/uploads/";
	if (!is_writable($uploadDir)) {
		@chmod($uploadDir, 0755);
		if (!is_writable($uploadDir)) {
			@chmod($uploadDir, 0777);
		}
		if (is_writable($uploadDir)) {
			$success[] = "Fixed permissions for: flipbook/uploads/";
		} else {
			$errors[] = "Directory flipbook/uploads/ is not writable. Please set permissions to 755 or 777 via cPanel/FTP.";
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Setup Flipbook Directories - Admin</title>
	<link rel="stylesheet" href="../assets/css/admin.css">
	<style>
		.admin-content { padding: 24px; max-width: 600px; margin: 0 auto; }
		.setup-box { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; }
		.setup-box h1 { margin: 0 0 16px 0; font-size: 24px; font-weight: 600; color: #111827; }
		.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 12px; }
		.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
		.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
		.alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
		.btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 8px; background: #667eea; color: #ffffff; cursor: pointer; text-decoration: none; font-size: 14px; transition: all 0.2s; }
		.btn:hover { background: #5568d3; }
		.btn-secondary { background: #6b7280; }
		.btn-secondary:hover { background: #4b5563; }
		.form-actions { margin-top: 20px; display: flex; gap: 12px; }
		ul { margin: 8px 0; padding-left: 20px; }
		li { margin: 4px 0; }
		code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 13px; }
	</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="setup-box">
	<h1>Setup Flipbook Directories</h1>
	
	<?php if (!empty($success)): ?>
		<div class="alert alert-success">
			<strong>Success:</strong>
			<ul>
				<?php foreach ($success as $msg): ?>
					<li><?php echo htmlspecialchars($msg); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	
	<?php if (!empty($errors)): ?>
		<div class="alert alert-error">
			<strong>Errors:</strong>
			<ul>
				<?php foreach ($errors as $msg): ?>
					<li><?php echo htmlspecialchars($msg); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		
		<div class="alert alert-info">
			<strong>Manual Setup Instructions:</strong>
			<p>If automatic creation failed, please run these commands in your terminal:</p>
			<pre style="background: #f3f4f6; padding: 12px; border-radius: 8px; overflow-x: auto;"><code>cd /Applications/XAMPP/xamppfiles/htdocs/MiHi-Entertainment
mkdir -p flipbook/uploads
chmod 755 flipbook
chmod 755 flipbook/uploads</code></pre>
			<p>Or on Windows, create the folders manually and ensure they have write permissions.</p>
		</div>
	<?php endif; ?>
	
	<?php if (empty($errors)): ?>
		<div class="alert alert-success">
			<strong>All directories are set up correctly!</strong> You can now create flipbooks.
		</div>
	<?php endif; ?>
	
	<div class="form-actions">
		<a href="flipbooks.php" class="btn">Go to Flipbooks</a>
		<a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
	</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

