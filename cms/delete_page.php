<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

$db = getDB();

// Get page info
$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$id]);
$page = $stmt->fetch();

if (!$page) {
    header('Location: index.php');
    exit;
}

// Delete static HTML file
$baseDir = dirname(__DIR__);
$filePath = $baseDir . '/' . $page['slug'] . '.html';
if (file_exists($filePath)) {
    unlink($filePath);
}

// Delete from database
$stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
$stmt->execute([$id]);

header('Location: index.php?deleted=1');
exit;
?>
