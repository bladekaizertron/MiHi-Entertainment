<?php
require_once __DIR__ . '/../config/config.php';

// If already logged in, go home
if (isLoggedIn()) {
	header('Location: index.php');
	exit;
}

// Validate required params
if (empty($_GET['code']) || empty($_GET['state']) || empty($_SESSION['oauth2_state'])) {
	$_SESSION['oauth_error'] = 'Invalid Google response.';
	header('Location: login.php');
	exit;
}

// CSRF state check
if (!hash_equals($_SESSION['oauth2_state'], $_GET['state'])) {
	unset($_SESSION['oauth2_state']);
	$_SESSION['oauth_error'] = 'Security check failed.';
	header('Location: login.php');
	exit;
}
unset($_SESSION['oauth2_state']);

$clientId = getSetting('google_client_id', '');
$clientSecret = getSetting('google_client_secret', '');
$redirectUri = ADMIN_URL . '/oauth_google_callback.php';
$code = $_GET['code'];

if (empty($clientId) || empty($clientSecret)) {
	$_SESSION['oauth_error'] = 'Google Sign-In is not configured.';
	header('Location: login.php');
	exit;
}

// Exchange code for tokens
$tokenResponse = null;
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
	CURLOPT_POST => true,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
	CURLOPT_POSTFIELDS => http_build_query([
		'code' => $code,
		'client_id' => $clientId,
		'client_secret' => $clientSecret,
		'redirect_uri' => $redirectUri,
		'grant_type' => 'authorization_code'
	]),
]);
$raw = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErr || $httpCode !== 200) {
	$_SESSION['oauth_error'] = 'Failed to authenticate with Google.';
	header('Location: login.php');
	exit;
}
$tokenResponse = json_decode($raw, true);

// Fetch user info
$accessToken = $tokenResponse['access_token'] ?? '';
if (empty($accessToken)) {
	$_SESSION['oauth_error'] = 'Missing access token.';
	header('Location: login.php');
	exit;
}

$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
	CURLOPT_HTTPGET => true,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken]
]);
$userRaw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr || $httpCode !== 200) {
	$_SESSION['oauth_error'] = 'Failed to retrieve Google profile.';
	header('Location: login.php');
	exit;
}
$google = json_decode($userRaw, true);

$googleId = $google['sub'] ?? null;
$email = $google['email'] ?? null;
$fullName = $google['name'] ?? null;
$picture = $google['picture'] ?? null;

if (!$email) {
	$_SESSION['oauth_error'] = 'Google account has no email.';
	header('Location: login.php');
	exit;
}

// Upsert local user
$db = getDB();
$db->beginTransaction();
try {
	$stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE email = ? LIMIT 1");
	$stmt->execute([$email]);
	$user = $stmt->fetch();

	if (!$user) {
		$usernameBase = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $fullName ?: explode('@', $email)[0]));
		$usernameBase = trim($usernameBase, '.');
		if ($usernameBase === '') { $usernameBase = 'user'; }

		// Ensure unique username
		$username = $usernameBase;
		$attempt = 1;
		while (true) {
			$check = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
			$check->execute([$username]);
			if (!$check->fetch()) break;
			$attempt++;
			$username = $usernameBase . $attempt;
		}

		// Default role for Google sign-ins; allow auto-approve by domain
		$role = 'pending';
		$domainsSetting = strtolower(trim(getSetting('auto_approve_domains', '')));
		if ($domainsSetting && $email && strpos($email, '@') !== false) {
			$domain = strtolower(substr(strrchr($email, '@'), 1));
			$allowed = array_filter(array_map('trim', explode(',', $domainsSetting)));
			if (in_array($domain, $allowed, true)) {
				$role = 'editor';
			}
		}
		$randomPass = bin2hex(random_bytes(12));
		$hash = password_hash($randomPass, PASSWORD_DEFAULT);

		$insert = $db->prepare("
			INSERT INTO users (username, email, full_name, password, role, created_at)
			VALUES (?, ?, ?, ?, ?, NOW())
		");
		$insert->execute([$username, $email, $fullName, $hash, $role]);
		$userId = (int)$db->lastInsertId();
		$user = [
			'id' => $userId,
			'username' => $username,
			'email' => $email,
			'role' => $role
		];
	} else {
		// Optionally refresh name on login
		$upd = $db->prepare("UPDATE users SET full_name = COALESCE(?, full_name) WHERE id = ?");
		$upd->execute([$fullName, $user['id']]);
	}

	$db->commit();
} catch (Throwable $e) {
	// Try to rollback, but handle case where connection is lost
	try {
		// Check if connection is still valid before attempting rollback
		if ($db && $db->inTransaction()) {
	$db->rollBack();
		}
	} catch (PDOException $rollbackException) {
		// Connection lost - can't rollback, but that's okay
		// The transaction will be automatically rolled back by MySQL
		error_log("Could not rollback transaction: " . $rollbackException->getMessage());
	}
	$_SESSION['oauth_error'] = 'Account provisioning failed.';
	header('Location: login.php');
	exit;
}

// Only allow approved roles
$normalizedRole = isset($user['role']) ? strtolower(trim($user['role'])) : '';
if (!in_array($normalizedRole, ['admin','editor'], true)) {
	$_SESSION['oauth_error'] = 'Your account is awaiting approval by an administrator.';
	header('Location: login.php');
	exit;
}

// Login
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

header('Location: index.php');
exit;


