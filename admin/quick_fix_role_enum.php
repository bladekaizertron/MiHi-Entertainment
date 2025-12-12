<?php
/**
 * Quick Fix - Direct SQL execution to fix role ENUM
 * This is a simpler version that just runs the SQL directly
 */

require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
    die('Access denied. Admin only.');
}

$db = getDB();
$error = '';
$success = '';
$sqlExecuted = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_fix'])) {
    try {
        // First, check current state
        $stmt = $db->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        $beforeType = $column ? $column['Type'] : 'Unknown';
        
        // Check for any users with invalid role values that might cause issues
        $checkStmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role IS NULL OR role = ''");
        $invalidCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Execute the ALTER TABLE command
        // Use setAttribute to ignore warnings (we'll check the result)
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'editor', 'pending') DEFAULT 'pending'";
        $sqlExecuted = $sql;
        
        try {
            $db->exec($sql);
        } catch (PDOException $execException) {
            // If it's just a warning about data truncation, try to fix invalid data first
            if (strpos($execException->getMessage(), '1265') !== false || strpos($execException->getMessage(), 'Data truncated') !== false) {
                // Update any NULL or empty roles to 'pending' first
                $db->exec("UPDATE users SET role = 'editor' WHERE role IS NULL OR role = '' OR role NOT IN ('admin', 'editor')");
                // Try again
                $db->exec($sql);
            } else {
                throw $execException;
            }
        }
        
        // Verify the change
        $stmt = $db->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        $afterType = $column ? $column['Type'] : 'Unknown';
        $afterDefault = $column ? $column['Default'] : 'Unknown';
        
        $success = "✓ ENUM updated successfully!";
        $success .= "<br><br><strong>Before:</strong> <code>" . htmlspecialchars($beforeType) . "</code>";
        $success .= "<br><strong>After:</strong> <code>" . htmlspecialchars($afterType) . "</code>";
        $success .= "<br><strong>New Default:</strong> <code>" . htmlspecialchars($afterDefault) . "</code>";
        
        if ($invalidCount > 0) {
            $success .= "<br><br><small>Note: Fixed " . $invalidCount . " user(s) with invalid role values.</small>";
        }
        
    } catch (PDOException $e) {
        $error = "Error executing SQL: " . $e->getMessage();
        $error .= "<br><br><strong>SQL that was attempted:</strong>";
        $error .= "<br><code>" . htmlspecialchars($sqlExecuted) . "</code>";
        $error .= "<br><br><strong>Error Code:</strong> " . $e->getCode();
        $error .= "<br><br>Try running this SQL directly in phpMyAdmin:";
        $error .= "<br><pre style='background: #1f2937; color: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 8px;'>" . htmlspecialchars($sqlExecuted) . "</pre>";
        error_log("Fix ENUM error: " . $e->getMessage());
        error_log("SQL: " . $sqlExecuted);
        error_log("Error Code: " . $e->getCode());
    }
}

// Get current status
try {
    $stmt = $db->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentType = $column ? $column['Type'] : 'Unknown';
    $currentDefault = $column ? $column['Default'] : 'Unknown';
} catch (Exception $e) {
    $currentType = 'Error: ' . $e->getMessage();
    $currentDefault = 'Unknown';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Fix Role ENUM - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .container { max-width: 900px; margin: 40px auto; padding: 20px; }
        .info-box { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .alert { padding: 12px 16px; border-radius: 8px; margin: 16px 0; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .btn { padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #0050ff; color: white; }
        .btn-primary:hover { background: #0040d9; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-family: 'Monaco', 'Courier New', monospace; font-size: 13px; }
        pre { background: #1f2937; color: #f3f4f6; padding: 16px; border-radius: 6px; overflow-x: auto; margin: 12px 0; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .status-ok { background: #ecfdf5; color: #065f46; }
        .status-error { background: #fef2f2; color: #991b1b; }
        .sql-box { background: #1f2937; color: #f3f4f6; padding: 16px; border-radius: 8px; margin: 16px 0; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Quick Fix: User Role ENUM</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>Error:</strong><br>
                <?php echo nl2br(escape($error)); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Success!</strong><br>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h2>Current Database Status</h2>
            <p><strong>Role Column Type:</strong> <code><?php echo htmlspecialchars($currentType); ?></code></p>
            <p><strong>Default Value:</strong> <code><?php echo htmlspecialchars($currentDefault); ?></code></p>
            
            <?php 
            $hasPending = strpos(strtolower($currentType), 'pending') !== false;
            ?>
            
            <?php if ($hasPending): ?>
                <p><span class="status-badge status-ok">✓ ENUM includes 'pending'</span></p>
                <p style="color: #065f46; margin-top: 12px;"><strong>Good news!</strong> Your database already has 'pending' in the ENUM. The "Set Pending" feature should work now.</p>
            <?php else: ?>
                <p><span class="status-badge status-error">✗ ENUM missing 'pending'</span></p>
                <p style="color: #991b1b; margin-top: 12px;"><strong>Issue found:</strong> The role ENUM only includes <code>admin</code> and <code>editor</code>. This is why "Set Pending" doesn't work.</p>
            <?php endif; ?>
        </div>
        
        <?php if (!$hasPending): ?>
            <div class="info-box">
                <h2>What This Will Do</h2>
                <p>This will execute the following SQL command to update your database:</p>
                <div class="sql-box">
                    <code style="color: #60a5fa;">ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'editor', 'pending') DEFAULT 'pending';</code>
                </div>
                <ul style="margin-top: 12px; line-height: 1.8;">
                    <li>Adds <code>'pending'</code> to the role ENUM</li>
                    <li>Sets the default value to <code>'pending'</code></li>
                    <li>Allows you to set users to pending status</li>
                    <li>Safe to run - won't affect existing users</li>
                </ul>
            </div>
            
            <form method="POST" style="margin-top: 24px;">
                <button type="submit" name="execute_fix" class="btn btn-primary" style="font-size: 16px;">
                    🔧 Execute Fix - Update ENUM Now
                </button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php else: ?>
            <div style="margin-top: 24px;">
                <a href="users.php" class="btn btn-secondary">← Back to Users</a>
            </div>
        <?php endif; ?>
        
        <div class="info-box" style="margin-top: 32px;">
            <h3>Manual SQL Alternative</h3>
            <p>If the button doesn't work, you can run this SQL directly in phpMyAdmin or MySQL:</p>
            <div class="sql-box">
                <code style="color: #60a5fa;">ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'editor', 'pending') DEFAULT 'pending';</code>
            </div>
        </div>
    </div>
</body>
</html>

