<?php
// admin/upload_thumbnail.php

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/image_resize.php';
    requireLogin(); // Require login for security
    
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
        // Don't throw error yet - try to write anyway as is_writable() can be unreliable
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => ['message' => 'Method not allowed']]);
        exit;
    }
    
    if (!isset($_FILES['thumbnail'])) {
        http_response_code(400);
        echo json_encode(['error' => ['message' => 'No file provided']]);
        exit;
    }
    
    $file = $_FILES['thumbnail'];
    
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
        http_response_code(500);
        echo json_encode(['error' => ['message' => $errorMsg]]);
        exit;
    }
    
    // Validate file size (10MB max)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => ['message' => 'File is too large. Maximum size is 10MB.']]);
        exit;
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
        http_response_code(400);
        echo json_encode(['error' => ['message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']]);
        exit;
    }
    
    if ($mime && !in_array($mime, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => ['message' => 'Invalid file type.']]);
        exit;
    }
    
    // Generate Filename
    $filename = 'thumb_' . uniqid() . '.' . $ext;
    $destination = $uploadDir . $filename;
    $tempDestination = $uploadDir . 'temp_' . $filename;
    
    // Move uploaded file to temp location first
    if (!@move_uploaded_file($file['tmp_name'], $tempDestination)) {
        $lastError = error_get_last();
        $errorMsg = 'Failed to save file. Check directory permissions.';
        if ($lastError) {
            $errorMsg .= ' Error: ' . $lastError['message'];
            error_log("move_uploaded_file failed: " . $lastError['message']);
        }
        error_log("Upload directory: " . $uploadDir);
        error_log("Directory exists: " . (is_dir($uploadDir) ? 'yes' : 'no'));
        error_log("Directory writable: " . (is_writable($uploadDir) ? 'yes' : 'no'));
        error_log("Directory permissions: " . (is_dir($uploadDir) ? substr(sprintf('%o', fileperms($uploadDir)), -4) : 'N/A'));
        http_response_code(500);
        echo json_encode(['error' => ['message' => $errorMsg]]);
        exit;
    }
    
    // Get optimal settings for thumbnails (smaller than content images)
    $settings = getOptimalImageSettings('thumbnail');
    
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
            http_response_code(500);
            echo json_encode(['error' => ['message' => 'Failed to process image.']]);
            exit;
        }
    }
    
    // Clean up temp file
    @unlink($tempDestination);
    
    echo json_encode([
        'url' => $uploadUrl . $filename,
        'success' => true
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Thumbnail upload error: " . $e->getMessage());
    echo json_encode(['error' => ['message' => 'Upload failed: ' . $e->getMessage()]]);
}
?>