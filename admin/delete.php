<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id) {
    // Get post slug before deleting
    $stmt = $db->prepare("SELECT slug FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    // Delete the post
    $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    
    // Delete the static HTML file if it exists
    if ($post && !empty($post['slug'])) {
        $htmlFile = __DIR__ . '/../post/' . $post['slug'] . '.html';
        if (file_exists($htmlFile)) {
            @unlink($htmlFile);
        }
    }
}

header('Location: index.php');
exit;

