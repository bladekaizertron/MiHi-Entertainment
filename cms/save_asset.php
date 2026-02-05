<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    if (!isset($_FILES['files'])) {
        echo json_encode(['data' => []]);
        exit;
    }
    
    $uploadDir = dirname(__DIR__) . '/uploads/cms/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $uploaded = [];
    $files = $_FILES['files'];
    
    // Handle multiple files
    if (is_array($files['name'])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $filename = basename($files['name'][$i]);
                $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
                $filename = time() . '_' . $filename;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
                    $uploaded[] = [
                        'src' => '/uploads/cms/' . $filename,
                        'type' => mime_content_type($filepath),
                        'height' => 350,
                        'width' => 250,
                    ];
                }
            }
        }
    } else {
        // Single file
        if ($files['error'] === UPLOAD_ERR_OK) {
            $filename = basename($files['name']);
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
            $filename = time() . '_' . $filename;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($files['tmp_name'], $filepath)) {
                $uploaded[] = [
                    'src' => '/uploads/cms/' . $filename,
                    'type' => mime_content_type($filepath),
                    'height' => 350,
                    'width' => 250,
                ];
            }
        }
    }
    
    echo json_encode(['data' => $uploaded]);
    
} catch (Exception $e) {
    error_log("Asset upload error: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
?>
