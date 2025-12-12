<?php
/**
 * MySQL Settings Diagnostic Tool
 * This script checks your MySQL configuration and provides recommendations
 */

require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$results = [];

try {
    // Check max_allowed_packet
    $stmt = $db->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
    $result = $stmt->fetch();
    if ($result) {
        $value = (int)$result['Value'];
        $valueMB = round($value / 1048576, 2);
        $results['max_allowed_packet'] = [
            'value' => $value,
            'value_mb' => $valueMB,
            'status' => $valueMB >= 256 ? 'good' : ($valueMB >= 64 ? 'warning' : 'error'),
            'recommendation' => $valueMB < 256 ? 'Consider increasing to 256MB or higher' : 'Setting is adequate'
        ];
    }
    
    // Check wait_timeout
    $stmt = $db->query("SHOW VARIABLES LIKE 'wait_timeout'");
    $result = $stmt->fetch();
    if ($result) {
        $results['wait_timeout'] = [
            'value' => $result['Value'],
            'status' => (int)$result['Value'] >= 600 ? 'good' : 'warning'
        ];
    }
    
    // Check version
    $stmt = $db->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    if ($result) {
        $results['mysql_version'] = $result['version'];
    }
    
} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySQL Settings Diagnostic - CMS Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .diagnostic-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .diagnostic-table th,
        .diagnostic-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .diagnostic-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .status-good { color: #28a745; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .error-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>MySQL Settings Diagnostic</h1>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if (isset($results['error'])): ?>
            <div class="error-box">
                <strong>Error:</strong> <?php echo escape($results['error']); ?>
            </div>
        <?php else: ?>
            
            <div class="info-box">
                <strong>MySQL Version:</strong> <?php echo escape($results['mysql_version'] ?? 'Unknown'); ?>
            </div>
            
            <table class="diagnostic-table">
                <thead>
                    <tr>
                        <th>Setting</th>
                        <th>Current Value</th>
                        <th>Status</th>
                        <th>Recommendation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($results['max_allowed_packet'])): ?>
                    <tr>
                        <td><strong>max_allowed_packet</strong></td>
                        <td><?php echo $results['max_allowed_packet']['value_mb']; ?> MB (<?php echo number_format($results['max_allowed_packet']['value']); ?> bytes)</td>
                        <td class="status-<?php echo $results['max_allowed_packet']['status']; ?>">
                            <?php 
                            echo ucfirst($results['max_allowed_packet']['status']);
                            if ($results['max_allowed_packet']['status'] === 'error') echo ' - Too Low!';
                            ?>
                        </td>
                        <td><?php echo escape($results['max_allowed_packet']['recommendation']); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (isset($results['wait_timeout'])): ?>
                    <tr>
                        <td><strong>wait_timeout</strong></td>
                        <td><?php echo $results['wait_timeout']['value']; ?> seconds</td>
                        <td class="status-<?php echo $results['wait_timeout']['status']; ?>">
                            <?php echo ucfirst($results['wait_timeout']['status']); ?>
                        </td>
                        <td><?php echo (int)$results['wait_timeout']['value'] < 600 ? 'Consider increasing to 600 seconds (10 minutes)' : 'Setting is adequate'; ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (isset($results['max_allowed_packet']) && $results['max_allowed_packet']['status'] !== 'good'): ?>
            <div class="warning-box">
                <h3>How to Fix max_allowed_packet</h3>
                <p><strong>For XAMPP Users:</strong></p>
                <ol>
                    <li>Navigate to your XAMPP MySQL config file:
                        <ul>
                            <li>Mac: <code>/Applications/XAMPP/xamppfiles/etc/my.cnf</code></li>
                            <li>Windows: <code>C:\xampp\mysql\bin\my.ini</code></li>
                        </ul>
                    </li>
                    <li>Find the <code>[mysqld]</code> section</li>
                    <li>Add or modify: <code>max_allowed_packet = 256M</code></li>
                    <li>Save the file</li>
                    <li><strong>Restart MySQL</strong> in XAMPP Control Panel</li>
                </ol>
                <p><strong>Note:</strong> Changes only take effect after restarting MySQL.</p>
                <p>See <code>config/MYSQL_MAX_PACKET_FIX.md</code> for detailed instructions.</p>
            </div>
            <?php endif; ?>
            
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

