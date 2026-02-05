<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $db = getDB();
    
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $html_content = $_POST['html_content'] ?? '';
    $css_content = $_POST['css_content'] ?? '';
    $components = $_POST['components'] ?? '';
    $styles = $_POST['styles'] ?? '';
    
    // Validation
    if (empty($title) || empty($slug)) {
        echo json_encode(['success' => false, 'message' => 'Title and slug are required']);
        exit;
    }
    
    // Sanitize slug
    $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $slug));
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    if ($id) {
        // Update existing page
        $stmt = $db->prepare("
            UPDATE pages 
            SET title = ?, slug = ?, html_content = ?, css_content = ?, 
                components = ?, styles = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $slug, $html_content, $css_content, $components, $styles, $id]);
        
        // Generate static HTML file
        generateStaticPage($id, $slug, $title, $html_content, $css_content);
        
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        // Create new page
        // Check if slug already exists
        $stmt = $db->prepare("SELECT id FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Slug already exists']);
            exit;
        }
        
        $stmt = $db->prepare("
            INSERT INTO pages (title, slug, html_content, css_content, components, styles, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$title, $slug, $html_content, $css_content, $components, $styles]);
        
        $newId = $db->lastInsertId();
        
        // Generate static HTML file
        generateStaticPage($newId, $slug, $title, $html_content, $css_content);
        
        echo json_encode(['success' => true, 'id' => $newId]);
    }
    
} catch (Exception $e) {
    error_log("Save page error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateStaticPage($id, $slug, $title, $html, $css) {
    $baseDir = dirname(__DIR__);
    $filePath = $baseDir . '/' . $slug . '.html';
    
    $fullHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - MiHi Entertainment</title>
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Page Specific Styles -->
    <style>
{$css}
    </style>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Navigation Component -->
    <script src="assets/components/navigation.js"></script>
</head>
<body>
    <!-- Navigation will be injected here by navigation.js -->
    
    <!-- Page Content -->
{$html}
    
    <!-- Footer Component -->
    <script src="assets/components/footer.js"></script>
</body>
</html>
HTML;
    
    file_put_contents($filePath, $fullHtml);
    
    return true;
}
?>
