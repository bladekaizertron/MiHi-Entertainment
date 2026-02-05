<?php
// admin/upload_image.php

header('Content-Type: application/json');

// Prevent any output buffering junk or warnings from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Helper to send JSON response
function sendJson($data, $code = 200) {
    http_response_code($code);
    while (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode($data);
    exit;
}

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        // Log the error
        error_log("Fatal Error in upload_image.php: " . $error['message']);
        
        // Send JSON 500 response
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        
        echo json_encode(['error' => ['message' => 'Server error: ' . $error['message']]]);
    }
});

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/image_resize.php';
    
    // Manual Auth Check to return JSON instead of redirecting
    if (!function_exists('isLoggedIn')) {
        // Should be loaded from config, but just in case
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        function isLoggedIn() { return isset($_SESSION['user_id']); }
    }
    
    if (!isLoggedIn()) {
        sendJson(['error' => ['message' => 'Not logged in']], 401);
    }
    
    // Debug logging
    error_log("Upload image request received. Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);
    error_log("Script Name: " . $_SERVER['SCRIPT_NAME']);
    // Log headers to check for redirect indicators
    if (function_exists('getallheaders')) {
        error_log("Headers: " . print_r(getallheaders(), true));
    }
    
    // Use config constants for upload directory
    $uploadDir = UPLOAD_DIR . 'images/';
    
    // Auto-detect base URL for uploads
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Get the base path (remove /admin from the path)
    $requestUri = dirname($_SERVER['SCRIPT_NAME']); // Gets /admin
    $basePath = dirname($requestUri); // Gets the root path
    $uploadUrl = $protocol . '://' . $host . $basePath . '/uploads/images/';
    
    // Ensure directory exists
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $error = 'Failed to create upload directory: ' . $uploadDir;
            error_log($error);
            error_log("PHP UID: " . getmyuid() . ", Parent directory owner: " . (file_exists(dirname($uploadDir)) ? fileowner(dirname($uploadDir)) : 'unknown'));
            throw new Exception('Failed to create upload directory. Check permissions.');
        }
        // Set permissions after creation
        @chmod($uploadDir, 0777);
    }
    
    // Check if directory is writable
    // Note: is_writable() can sometimes return false even when we can write
    // So we'll try to write anyway and check the actual result
    $isWritable = is_writable($uploadDir);
    if (!$isWritable) {
        error_log("Warning: is_writable() returned false for: " . $uploadDir);
        error_log("Directory permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4));
        error_log("PHP UID: " . getmyuid() . ", Directory UID: " . fileowner($uploadDir));
        // Try to fix permissions
        @chmod($uploadDir, 0777);
    }
    
    // TinyMCE sends files with the key 'upload' (configured in your JS)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(['error' => ['message' => 'Method not allowed. Received: ' . $_SERVER['REQUEST_METHOD']]], 405);
    }
    
    if (!isset($_FILES['upload'])) {
        sendJson(['error' => ['message' => 'No file uploaded']], 400);
    }
    
    $file = $_FILES['upload'];
    
    // Validate Error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $errorMsg = $errorMessages[$file['error']] ?? 'Upload failed. Error code: ' . $file['error'];
        sendJson(['error' => ['message' => $errorMsg]], 500);
    }
    
    // Validate file size (10MB max)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        sendJson(['error' => ['message' => 'File is too large. Maximum size is 10MB.']], 400);
    }
    
    // Validate Type
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Get MIME type
    $mime = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } else {
        // Fallback to file extension if finfo not available
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        $mime = $mimeMap[$ext] ?? null;
    }
    
    // Also check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        sendJson(['error' => ['message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']], 400);
    }
    
    if ($mime && !in_array($mime, $allowed)) {
        sendJson(['error' => ['message' => 'Invalid file type.']], 400);
    }
    
    // Generate Filename
    $filename = 'img_' . uniqid() . '.' . $ext;
    $destination = $uploadDir . $filename;
    $tempDestination = $uploadDir . 'temp_' . $filename;
    
    // Move uploaded file to temp location first
    // Use @ to suppress warning that might leak to output buffer
    if (!@move_uploaded_file($file['tmp_name'], $tempDestination)) {
        $lastError = error_get_last();
        $errorMsg = 'Failed to save file. Check directory permissions.';
        if ($lastError) {
            $errorMsg .= ' Error: ' . $lastError['message'];
            error_log("move_uploaded_file failed: " . $lastError['message']);
        }
        
        // Try to fix permissions if we can't write
        if (!is_writable($uploadDir)) {
             @chmod($uploadDir, 0777);
        }
        
        sendJson(['error' => ['message' => $errorMsg]], 500);
    }
    
    // Get optimal settings for content images
    $settings = getOptimalImageSettings('content');
    
    // Try to resize and compress the image
    $resizeSuccess = resizeAndCompressImage(
        $tempDestination,
        $destination,
        $settings['width'],
        $settings['height'],
        $settings['quality']
    );
    
    // If resize failed (e.g., GD not available), just use the original
    if (!$resizeSuccess) {
        // Copy temp file to final destination
        if (!copy($tempDestination, $destination)) {
            @unlink($tempDestination);
            sendJson(['error' => ['message' => 'Failed to process image.']], 500);
        }
    }
    
    // Clean up temp file
    @unlink($tempDestination);
    
    // Return strictly the JSON structure TinyMCE expects
    sendJson(['url' => $uploadUrl . $filename]);
    
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    sendJson(['error' => ['message' => 'Upload failed: ' . $e->getMessage()]], 500);
}