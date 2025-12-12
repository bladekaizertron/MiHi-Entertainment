<?php
// admin/upload_thumbnail.php

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['thumbnail'])) {
    $file = $_FILES['thumbnail'];
    
    // Validate Error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => ['message' => 'Upload failed. Error code: ' . $file['error']]]);
        exit;
    }

    // Validate Type
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed)) {
        echo json_encode(['error' => ['message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']]);
        exit;
    }

    // Generate Filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'thumb_' . uniqid() . '.' . $ext;
    $destination = $uploadDir . $filename;

    // Move File
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        echo json_encode([
            'url' => $uploadUrl . $filename,
            'success' => true
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => ['message' => 'Failed to save file to disk. Check permissions.']]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'No file provided']]);
}
?>