<?php
// Public API to get navigation items
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow CORS if needed

function getDBConnection() {
    // Duplicate getDB logic or use the one from config if available globally
    // Config likely defines getDB().
    if (function_exists('getDB')) {
        return getDB();
    }
    // Fallback if config doesn't expose it globally (it does based on previous reads)
    // ...
}

$db = getDBConnection();

try {
    // Check/Create Table logic for robustness
    try {
        $db->query("SELECT 1 FROM navigation_items LIMIT 1");
         // Check for description column
        try {
            $db->query("SELECT description FROM navigation_items LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE navigation_items ADD COLUMN description TEXT NULL AFTER url");
        }
    } catch (PDOException $e) {
        // Create table
        $sql = "CREATE TABLE IF NOT EXISTS navigation_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            parent_id INT NULL DEFAULT NULL,
            label VARCHAR(255) NOT NULL,
            url VARCHAR(500) NULL,
            description TEXT NULL,
            sort_order INT DEFAULT 0,
            is_header TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES navigation_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->exec($sql);
        
        // Seed initial Data
        $db->exec("INSERT INTO navigation_items (label, url, sort_order) VALUES ('Products', '#products', 1)");
        $pId = $db->lastInsertId();
         $db->exec("INSERT INTO navigation_items (parent_id, label, url, sort_order, is_header) VALUES ($pId, 'Photo Booths', '', 1, 1)");
          $pbId = $db->lastInsertId();
          $db->exec("INSERT INTO navigation_items (parent_id, label, url, description, sort_order) VALUES ($pbId, 'AI Photo Booth', 'product/ai-photo-booth.html', 'Custom AI-generated characters in seconds', 1)");
          // Add more seeds if desired to avoid empty look, but this is enough to prove it works
        
        $db->exec("INSERT INTO navigation_items (label, url, sort_order) VALUES ('Events', '#events', 2)");
        $db->exec("INSERT INTO navigation_items (label, url, sort_order) VALUES ('Rentals', '#rentals', 3)");
        $db->exec("INSERT INTO navigation_items (label, url, sort_order) VALUES ('Gallery', '#gallery', 4)");
        $db->exec("INSERT INTO navigation_items (label, url, sort_order) VALUES ('About Us', '#about', 5)");
        $db->exec("INSERT INTO navigation_items (label, url, sort_order) VALUES ('Contact Us', 'contact-us.html', 6)");
    }

    $items = $db->query("SELECT * FROM navigation_items ORDER BY sort_order ASC, label ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    function buildTree(array $elements, $parentId = null) {
        $branch = array();
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
    
    $tree = buildTree($items);
    echo json_encode($tree);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
