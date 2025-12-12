<?php
/**
 * MySQL max_allowed_packet Fix Helper
 * This script provides instructions and can help fix the MySQL configuration
 */

require_once __DIR__ . '/../config/config.php';
requireLogin();

$configFile = '/Applications/XAMPP/xamppfiles/etc/my.cnf';
$configFileWindows = 'C:\\xampp\\mysql\\bin\\my.ini';
$isMac = (PHP_OS === 'Darwin');
$currentOS = $isMac ? 'Mac' : (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'Windows' : 'Linux');

// Check current setting
$currentSetting = null;
$currentSettingMB = null;
try {
    $db = getDB();
    $stmt = $db->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
    $result = $stmt->fetch();
    if ($result) {
        $currentSetting = (int)$result['Value'];
        $currentSettingMB = round($currentSetting / 1048576, 2);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Check if config file exists
$configPath = $isMac ? $configFile : $configFileWindows;
$configExists = file_exists($configPath);
$configReadable = $configExists && is_readable($configPath);
$configWritable = $configExists && is_writable($configPath);

// Read current config if possible
$configContent = null;
if ($configReadable) {
    $configContent = file_get_contents($configPath);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix MySQL max_allowed_packet - CMS Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .status-box {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .status-error {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .status-warning {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .status-success {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .status-info {
            background-color: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .code-block {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }
        .step-list {
            counter-reset: step-counter;
            list-style: none;
            padding-left: 0;
        }
        .step-list li {
            counter-increment: step-counter;
            margin: 15px 0;
            padding-left: 40px;
            position: relative;
        }
        .step-list li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            background-color: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Fix MySQL max_allowed_packet Setting</h1>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if ($currentSettingMB !== null): ?>
            <div class="status-box <?php echo $currentSettingMB >= 64 ? 'status-success' : ($currentSettingMB >= 16 ? 'status-warning' : 'status-error'); ?>">
                <strong>Current Setting:</strong> <?php echo $currentSettingMB; ?> MB (<?php echo number_format($currentSetting); ?> bytes)
                <?php if ($currentSettingMB < 16): ?>
                    <br><strong>⚠️ This is too low!</strong> Your content (5.32 MB) exceeds this limit.
                <?php elseif ($currentSettingMB < 64): ?>
                    <br><strong>⚠️ This may be too low</strong> for large content with embedded images.
                <?php else: ?>
                    <br>✓ This setting should be adequate for most content.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="status-box status-error">
                <strong>Error:</strong> Could not check current MySQL setting. <?php echo isset($error) ? escape($error) : ''; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($configExists): ?>
            <div class="status-box status-info">
                <strong>Config File Found:</strong> <?php echo escape($configPath); ?>
                <?php if (!$configReadable): ?>
                    <br>⚠️ File exists but is not readable. You may need to check permissions.
                <?php elseif (!$configWritable): ?>
                    <br>⚠️ File is readable but not writable. You'll need to edit it manually with admin privileges.
                <?php else: ?>
                    <br>✓ File is readable and writable.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="status-box status-warning">
                <strong>Config File Not Found:</strong> <?php echo escape($configPath); ?>
                <br>You may need to locate your MySQL configuration file manually.
            </div>
        <?php endif; ?>
        
        <h2>How to Fix (<?php echo $currentOS; ?> - XAMPP)</h2>
        
        <ol class="step-list">
            <li>
                <strong>Locate your MySQL configuration file:</strong>
                <div class="code-block">
                    <?php if ($isMac): ?>
                        /Applications/XAMPP/xamppfiles/etc/my.cnf
                    <?php else: ?>
                        C:\xampp\mysql\bin\my.ini
                    <?php endif; ?>
                </div>
            </li>
            
            <li>
                <strong>Open the file in a text editor</strong> (you may need admin/root privileges):
                <div class="code-block">
                    <?php if ($isMac): ?>
                        sudo nano /Applications/XAMPP/xamppfiles/etc/my.cnf
                        <br># OR
                        <br>sudo open -a TextEdit /Applications/XAMPP/xamppfiles/etc/my.cnf
                    <?php else: ?>
                        # Right-click my.ini → Open with → Notepad (Run as Administrator)
                    <?php endif; ?>
                </div>
            </li>
            
            <li>
                <strong>Find the [mysqld] section</strong> and look for <code>max_allowed_packet</code>
            </li>
            
            <li>
                <strong>Change or add this line:</strong>
                <div class="code-block">
                    max_allowed_packet = 256M
                </div>
                <p><strong>Important:</strong> Make sure this is under the <code>[mysqld]</code> section, not under <code>[mysql]</code> or <code>[client]</code>.</p>
            </li>
            
            <li>
                <strong>Remove any duplicate entries.</strong> There should only be ONE <code>max_allowed_packet</code> line under <code>[mysqld]</code>.
            </li>
            
            <li>
                <strong>Save the file</strong>
            </li>
            
            <li>
                <strong>Restart MySQL in XAMPP Control Panel:</strong>
                <ul>
                    <li>Stop MySQL</li>
                    <li>Start MySQL again</li>
                </ul>
                <p><strong>⚠️ Important:</strong> Changes only take effect after restarting MySQL!</p>
            </li>
            
            <li>
                <strong>Verify the fix:</strong>
                <a href="check_mysql_settings.php" class="btn btn-primary">Check MySQL Settings</a>
            </li>
        </ol>
        
        <?php if ($configReadable && $configContent): ?>
            <h2>Current Config File Contents</h2>
            <div class="code-block" style="max-height: 400px; overflow-y: auto;">
                <pre><?php echo escape($configContent); ?></pre>
            </div>
            <p><small>Look for <code>max_allowed_packet</code> in the <code>[mysqld]</code> section above.</small></p>
        <?php endif; ?>
        
        <div class="status-box status-info" style="margin-top: 30px;">
            <strong>Need Help?</strong> See <code>config/MYSQL_MAX_PACKET_FIX.md</code> for detailed instructions and troubleshooting.
        </div>
        
        <div style="margin-top: 30px;">
            <a href="check_mysql_settings.php" class="btn btn-primary">Check Current Settings</a>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

