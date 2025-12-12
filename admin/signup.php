<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
	header('Location: index.php');
	exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim($_POST['username'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$fullName = trim($_POST['full_name'] ?? '');
	$password = $_POST['password'] ?? '';
	$confirm = $_POST['confirm_password'] ?? '';

	if ($username === '' || $email === '' || $password === '') {
		$error = 'Please fill out all required fields.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$error = 'Please enter a valid email address.';
	} elseif (strlen($password) < 8) {
		$error = 'Password must be at least 8 characters.';
	} elseif ($password !== $confirm) {
		$error = 'Passwords do not match.';
	} else {
		$db = getDB();
		// Check duplicates
		$dup = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
		$dup->execute([$username, $email]);
		if ($dup->fetch()) {
			$error = 'Username or email already exists.';
		} else {
			$hash = password_hash($password, PASSWORD_DEFAULT);
			// Auto-approve based on allowed domains (comma-separated list in settings: auto_approve_domains)
			$role = 'pending';
			$domainsSetting = strtolower(trim(getSetting('auto_approve_domains', '')));
			if ($domainsSetting && strpos($email, '@') !== false) {
				$domain = strtolower(substr(strrchr($email, '@'), 1));
				$allowed = array_filter(array_map('trim', explode(',', $domainsSetting)));
				if (in_array($domain, $allowed, true)) {
					$role = 'editor';
				}
			}
			$ins = $db->prepare("
				INSERT INTO users (username, email, full_name, password, role, created_at)
				VALUES (?, ?, ?, ?, ?, NOW())
			");
			$ins->execute([$username, $email, $fullName ?: null, $hash, $role]);
			header('Location: login.php?registered=1');
			exit;
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Create Account - CMS Blog</title>
	<link rel="stylesheet" href="../assets/css/admin.css">
	<style>
		.signup-page { background: #ffffff; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
		.signup-container { width: 100%; max-width: 520px; padding: 16px; }
		.signup-box { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 28px; color: #111827; box-shadow: 0 10px 30px rgba(2,6,23,0.06); }
		.signup-box h1 { margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827; text-align: center; }
		.signup-box h2 { margin: 0 0 20px; font-size: 16px; font-weight: 500; color: #6b7280; text-align: center; }
		.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
		.form-group { margin-bottom: 14px; }
		label { display: block; margin-bottom: 6px; color: #374151; font-size: 13px; }
		input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px 12px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; color: #111827; outline: none; }
		input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.12); }
		.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
		.btn-primary { background: #4f46e5; color: #fff; }
		.btn-primary:hover { background: #4338ca; }
		.alert-error { background: #fef2f2; color: #991b1b; padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; border: 1px solid #fecaca; }
		.helper { text-align: center; margin-top: 12px; font-size: 12px; color: #6b7280; }
		.brand { display: flex; gap: 10px; align-items: center; justify-content: center; margin-bottom: 10px; }
		.brand img { height: 36px; width: auto; }
	</style>
	</head>
<body class="signup-page">
	<div class="signup-container">
		<div class="signup-box">
			<div class="brand">
				<img src="../assets/image/logo.png" alt="Logo">
			</div>
			<h1>Create your account</h1>
			<h2>Join the CMS to manage posts</h2>

			<?php if ($error): ?>
				<div class="alert alert-error"><?php echo escape($error); ?></div>
			<?php endif; ?>

			<form method="POST" action="">
				<div class="form-row">
					<div class="form-group">
						<label for="username">Username *</label>
						<input type="text" id="username" name="username" required value="<?php echo isset($username) ? escape($username) : ''; ?>">
					</div>
					<div class="form-group">
						<label for="email">Email *</label>
						<input type="email" id="email" name="email" required value="<?php echo isset($email) ? escape($email) : ''; ?>">
					</div>
				</div>

				<div class="form-group">
					<label for="full_name">Full name</label>
					<input type="text" id="full_name" name="full_name" value="<?php echo isset($fullName) ? escape($fullName) : ''; ?>">
				</div>

				<div class="form-row">
					<div class="form-group">
						<label for="password">Password *</label>
						<input type="password" id="password" name="password" required>
					</div>
					<div class="form-group">
						<label for="confirm_password">Confirm Password *</label>
						<input type="password" id="confirm_password" name="confirm_password" required>
					</div>
				</div>

				<button type="submit" class="btn btn-primary">Create account</button>
			</form>

			<p class="helper">Already have an account? <a href="login.php">Sign in</a></p>
		</div>
	</div>
</body>
</html>


