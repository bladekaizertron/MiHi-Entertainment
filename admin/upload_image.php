<?php
// admin/upload_image.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

// Check login if needed
// requireLogin(); 

$uploadDir = __DIR__ . '/../assets/uploads/';
$uploadUrl = '../assets/uploads/';

// Ensure directory exists
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// TinyMCE sends files with the key 'upload' (configured in your JS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload'])) {
    $file = $_FILES['upload'];
    
    // Validate Error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(500);
        echo json_encode(['error' => ['message' => 'Upload failed. Error code: ' . $file['error']]]);
        exit;
    }

    // Validate Type
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => ['message' => 'Invalid file type.']]);
        exit;
    }

    // Generate Filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'content_' . uniqid() . '.' . $ext;
    $destination = $uploadDir . $filename;

    // Move File
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Return strictly the JSON structure TinyMCE expects
        echo json_encode([
            'url' => $uploadUrl . $filename
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => ['message' => 'Failed to save file. Check permissions.']]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'No file uploaded']]);
}
?>