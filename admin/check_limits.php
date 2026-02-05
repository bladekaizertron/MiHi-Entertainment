<?php
// Diagnostic tool to check PHP configuration limits
// DELETE THIS FILE after checking for security reasons

require_once __DIR__ . '/../config/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Configuration Check</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .config-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .config-table th,
        .config-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #e2e8f0;
        }
        .config-table th {
            background: #f7fafc;
            font-weight: 600;
        }
        .status-ok {
            color: #10b981;
            font-weight: 600;
        }
        .status-warning {
            color: #f59e0b;
            font-weight: 600;
        }
        .status-error {
            color: #ef4444;
            font-weight: 600;
        }
        .alert-warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>PHP Configuration Check</h1>
            <a href="index.php" class="btn btn-secondary">Return to Dashboard</a>
        </div>
        
        <div class="alert-warning">
            <strong>⚠️ Security Warning:</strong> This file displays sensitive server information. 
            <strong>DELETE THIS FILE</strong> after checking the configuration.
        </div>
        
        <h2>Current PHP Limits</h2>
        <table class="config-table">
            <thead>
                <tr>
                    <th>Setting</th>
                    <th>Current Value</th>
                    <th>Recommended</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                function formatBytes($bytes) {
                    if ($bytes >= 1073741824) {
                        return number_format($bytes / 1073741824, 2) . ' GB';
                    } elseif ($bytes >= 1048576) {
                        return number_format($bytes / 1048576, 2) . ' MB';
                    } elseif ($bytes >= 1024) {
                        return number_format($bytes / 1024, 2) . ' KB';
                    } else {
                        return $bytes . ' bytes';
                    }
                }
                
                function parseSize($size) {
                    $unit = strtoupper(substr($size, -1));
                    $value = (int)$size;
                    
                    switch($unit) {
                        case 'G':
                            $value *= 1024;
                        case 'M':
                            $value *= 1024;
                        case 'K':
                            $value *= 1024;
                    }
                    
                    return $value;
                }
                
                $checks = [
                    'post_max_size' => ['recommended' => '1024M', 'min' => 104857600], // 100MB minimum
                    'upload_max_filesize' => ['recommended' => '1024M', 'min' => 104857600],
                    'memory_limit' => ['recommended' => '1024M', 'min' => 268435456], // 256MB minimum
                    'max_execution_time' => ['recommended' => '600', 'min' => 300],
                    'max_input_time' => ['recommended' => '600', 'min' => 300],
                    'max_input_vars' => ['recommended' => '10000', 'min' => 5000],
                ];
                
                foreach ($checks as $setting => $config) {
                    $currentValue = ini_get($setting);
                    $recommended = $config['recommended'];
                    $min = $config['min'];
                    
                    // Parse current value
                    if (in_array($setting, ['post_max_size', 'upload_max_filesize', 'memory_limit'])) {
                        $currentBytes = parseSize($currentValue);
                        $displayCurrent = formatBytes($currentBytes);
                        $displayRecommended = $recommended;
                        
                        if ($currentBytes >= parseSize($recommended)) {
                            $status = '<span class="status-ok">✓ OK</span>';
                        } elseif ($currentBytes >= $min) {
                            $status = '<span class="status-warning">⚠ Low</span>';
                        } else {
                            $status = '<span class="status-error">✗ Too Low</span>';
                        }
                    } else {
                        $currentInt = (int)$currentValue;
                        $displayCurrent = $currentValue;
                        $displayRecommended = $recommended;
                        
                        if ($currentInt >= (int)$recommended || $currentInt == -1) {
                            $status = '<span class="status-ok">✓ OK</span>';
                        } elseif ($currentInt >= $min) {
                            $status = '<span class="status-warning">⚠ Low</span>';
                        } else {
                            $status = '<span class="status-error">✗ Too Low</span>';
                        }
                    }
                    
                    echo "<tr>";
                    echo "<td><code>{$setting}</code></td>";
                    echo "<td><strong>{$displayCurrent}</strong></td>";
                    echo "<td>{$displayRecommended}</td>";
                    echo "<td>{$status}</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        
        <h2>Server Information</h2>
        <table class="config-table">
            <tbody>
                <tr>
                    <td><strong>PHP Version</strong></td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td><strong>Server Software</strong></td>
                    <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                </tr>
                <tr>
                    <td><strong>PHP SAPI</strong></td>
                    <td><?php echo php_sapi_name(); ?></td>
                </tr>
                <tr>
                    <td><strong>Loaded php.ini</strong></td>
                    <td><?php echo php_ini_loaded_file() ?: 'None'; ?></td>
                </tr>
                <tr>
                    <td><strong>Additional .ini files</strong></td>
                    <td><?php echo php_ini_scanned_files() ?: 'None'; ?></td>
                </tr>
            </tbody>
        </table>
        
        <h2>Configuration Files Check</h2>
        <table class="config-table">
            <tbody>
                <tr>
                    <td><strong>.htaccess</strong></td>
                    <td>
                        <?php 
                        $htaccess = __DIR__ . '/.htaccess';
                        if (file_exists($htaccess)) {
                            echo '<span class="status-ok">✓ Exists</span> (' . filesize($htaccess) . ' bytes)';
                        } else {
                            echo '<span class="status-error">✗ Not Found</span>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>.user.ini</strong></td>
                    <td>
                        <?php 
                        $userini = __DIR__ . '/.user.ini';
                        if (file_exists($userini)) {
                            echo '<span class="status-ok">✓ Exists</span> (' . filesize($userini) . ' bytes)';
                        } else {
                            echo '<span class="status-error">✗ Not Found</span>';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2>Recommendations</h2>
        <div style="background: #f7fafc; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <?php
            $postMax = parseSize(ini_get('post_max_size'));
            $uploadMax = parseSize(ini_get('upload_max_filesize'));
            $memLimit = parseSize(ini_get('memory_limit'));
            
            $hasIssues = false;
            
            if ($postMax < 104857600) {
                echo "<p>⚠️ <strong>post_max_size</strong> is too low. Increase to at least 100M (recommended: 1024M)</p>";
                $hasIssues = true;
            }
            
            if ($uploadMax < 104857600) {
                echo "<p>⚠️ <strong>upload_max_filesize</strong> is too low. Increase to at least 100M (recommended: 1024M)</p>";
                $hasIssues = true;
            }
            
            if ($memLimit < 268435456 && $memLimit != -1) {
                echo "<p>⚠️ <strong>memory_limit</strong> is too low. Increase to at least 256M (recommended: 1024M)</p>";
                $hasIssues = true;
            }
            
            if (!$hasIssues) {
                echo "<p class='status-ok'>✓ All settings look good! You should be able to submit large content.</p>";
            } else {
                echo "<hr>";
                echo "<p><strong>On GoDaddy:</strong></p>";
                echo "<ul>";
                echo "<li>Wait 5-10 minutes after uploading .user.ini for changes to take effect</li>";
                echo "<li>If limits don't increase, contact GoDaddy support</li>";
                echo "<li>Consider using external image hosting to reduce content size</li>";
                echo "</ul>";
            }
            ?>
        </div>
        
        <div class="alert-warning" style="margin-top: 30px;">
            <strong>🔒 IMPORTANT:</strong> Delete this file (<code>check_limits.php</code>) after reviewing the configuration to prevent unauthorized access to server information.
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
