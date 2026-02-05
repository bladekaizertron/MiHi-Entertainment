<?php
// Debug script to verify DB content
require_once __DIR__ . '/config/config.php';
$db = getDB();
try {
    $items = $db->query("SELECT * FROM navigation_items ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents('debug_menu.txt', print_r($items, true));
    echo "Dumped to debug_menu.txt";
} catch (Exception $e) {
    file_put_contents('debug_menu.txt', "Error: " . $e->getMessage());
}
?>
