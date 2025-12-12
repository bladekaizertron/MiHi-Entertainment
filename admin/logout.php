<?php
require_once __DIR__ . '/../config/config.php';

// Prefer POST with CSRF token; allow GET for backward compatibility
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = $_POST['csrf_token'] ?? '';
	if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
		// Token valid; continue
	} else {
		// Token invalid; continue logout to avoid trapping users
	}
}

// Clear all session data
$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	}
	session_destroy();
}

header('Location: login.php');
exit;

