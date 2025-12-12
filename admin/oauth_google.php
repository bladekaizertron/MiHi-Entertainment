<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
	header('Location: index.php');
	exit;
}

$clientId = getSetting('google_client_id', '');
$clientSecret = getSetting('google_client_secret', '');
$redirectUri = ADMIN_URL . '/oauth_google_callback.php';

if (empty($clientId) || empty($clientSecret)) {
	$_SESSION['oauth_error'] = 'Google Sign-In is not configured.';
	header('Location: login.php');
	exit;
}

// CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['oauth2_state'] = $state;

$params = [
	'client_id' => $clientId,
	'redirect_uri' => $redirectUri,
	'response_type' => 'code',
	'scope' => 'openid email profile',
	'access_type' => 'offline',
	'include_granted_scopes' => 'true',
	'state' => $state,
	'prompt' => 'select_account'
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
header('Location: ' . $authUrl);
exit;


