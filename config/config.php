<?php
/**
 * Main Configuration File
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Site configuration
define('SITE_URL', '/cms');
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Timezone
date_default_timezone_set('UTC');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        // Get the directory of the file calling this function
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $callingFile = $backtrace[0]['file'] ?? __FILE__;
        $callingDir = realpath(dirname($callingFile));
        $adminDir = realpath(__DIR__ . '/../admin');
        $cmsDir = realpath(__DIR__ . '/../cms');
        
        // Check if calling file is in admin or cms directory
        $isInAdmin = ($callingDir === $adminDir || strpos($callingDir, $adminDir . DIRECTORY_SEPARATOR) === 0);
        $isInCms = ($callingDir === $cmsDir || strpos($callingDir, $cmsDir . DIRECTORY_SEPARATOR) === 0);
        
        if ($isInAdmin) {
            // Already in admin folder, use relative path
            $redirectPath = 'login.php';
        } elseif ($isInCms) {
            // Already in cms folder, use relative path
            $redirectPath = 'login.php';
        } else {
            // Not in admin or cms folder, use absolute URL
            // Use the ADMIN_URL constant for absolute URL (more reliable)
            $redirectPath = ADMIN_URL . '/login.php';
        }
        
        header('Location: ' . $redirectPath);
        exit;
    }
}

// Helper function to get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email, full_name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Helper function to generate slug
function generateSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Helper function to sanitize output
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Helper function to get setting
function getSetting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

