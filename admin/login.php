<?php
require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $db = getDB();
			$stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
			if ($user && password_verify($password, $user['password'])) {
				$role = isset($user['role']) ? strtolower(trim($user['role'])) : '';
				$allowedRoles = ['admin', 'editor'];
				if (!in_array($role, $allowedRoles, true)) {
					$error = 'Your account is awaiting approval by an administrator.';
				} else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
				}
			} else {
            $error = 'Invalid username or password';
        }
    } else {
        $error = 'Please enter both username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CMS Blog</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
	<style>
		/* Minimal enhancements for a cleaner login */
		.login-page { background: #ffffff; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
		.login-container { width: 100%; max-width: 420px; padding: 16px; }
		.login-box { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 28px; color: #111827; box-shadow: 0 10px 30px rgba(2,6,23,0.06); }
		.login-box h1 { margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #111827; text-align: center; }
		.login-box h2 { margin: 0 0 20px; font-size: 16px; font-weight: 500; color: #6b7280; text-align: center; }
		.form-group { margin-bottom: 14px; }
		label { display: block; margin-bottom: 6px; color: #374151; font-size: 13px; }
		input[type="text"], input[type="password"] { width: 100%; padding: 10px 12px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; color: #111827; outline: none; }
		input[type="text"]:focus, input[type="password"]:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.12); }
		.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
		.btn-primary { background: #4f46e5; color: #fff; }
		.btn-primary:hover { background: #4338ca; }
		.btn-google { background: #ffffff; border-color: #cbd5e1; color: #111827; }
		.btn-google:hover { background: #f8fafc; }
		.alert-error { background: #fef2f2; color: #991b1b; padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; border: 1px solid #fecaca; }
		.login-note { margin-top: 12px; color: #6b7280; font-size: 12px; text-align: center; }
		.divider { display: flex; align-items: center; margin: 14px 0; color: #6b7280; font-size: 12px; }
		.divider::before, .divider::after { content: ""; height: 1px; background: #e5e7eb; flex: 1; }
		.divider span { padding: 0 8px; }
		.brand { display: flex; gap: 10px; align-items: center; justify-content: center; margin-bottom: 10px; }
		.brand img { height: 36px; width: auto; }
		.small { font-size: 12px; color: #6b7280; }
		.badge { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #f8fafc; border: 1px solid #e5e7eb; color: #6b7280; font-size: 11px; }
	</style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
			<div class="brand">
				<img src="../assets/images/logo.svg" alt="Logo">
			</div>
			<h1>CMS Admin</h1>
			<h2>Sign in to continue</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo escape($error); ?></div>
            <?php endif; ?>
			<?php if (!empty($_SESSION['oauth_error'])): ?>
				<div class="alert alert-error"><?php echo escape($_SESSION['oauth_error']); unset($_SESSION['oauth_error']); ?></div>
			<?php endif; ?>
			<?php if (!empty($_GET['registered'])): ?>
				<div class="alert alert-success" style="background:#064e3b;color:#d1fae5;border:1px solid #10b981;padding:10px 12px;border-radius:8px;margin-bottom:14px;">
					Account created. An administrator must approve your account before you can sign in.
				</div>
			<?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
			<div class="divider"><span>or</span></div>
			
			<a class="btn btn-google" href="oauth_google.php">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="M21.35 11.1h-9.18v2.96h5.31c-.23 1.46-1.6 4.28-5.31 4.28-3.2 0-5.81-2.65-5.81-5.91s2.61-5.91 5.81-5.91c1.82 0 3.04.77 3.74 1.44l2.55-2.46C17.42 3.8 15.27 2.8 12.17 2.8 6.98 2.8 2.8 6.98 2.8 12.17s4.18 9.37 9.37 9.37c5.41 0 8.98-3.8 8.98-9.17 0-.62-.07-1.09-.2-1.27z" fill="#fff" opacity=".9"/>
				</svg>
				<span>Continue with Google</span>
			</a>
			
			<p class="login-note"><span class="badge">Tip</span> You can still use your username and password. <a href="signup.php">Create an account</a></p>
        </div>
    </div>
</body>
</html>

