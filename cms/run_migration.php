<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();

// Read SQL file
$sql = file_get_contents(__DIR__ . '/migration.sql');

try {
    // Execute SQL
    $db->exec($sql);
    $message = "✅ Database migration completed successfully! The 'pages' table has been created.";
    $success = true;
} catch (PDOException $e) {
    $message = "❌ Error running migration: " . $e->getMessage();
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - CMS</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .migration-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success {
            color: #10b981;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .error {
            color: #ef4444;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .message {
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <div class="migration-container">
        <div class="<?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $success ? '✅' : '❌'; ?>
        </div>
        <div class="message">
            <?php echo $message; ?>
        </div>
        <a href="index.php" class="btn">Go to Website Builder</a>
    </div>
</body>
</html>
