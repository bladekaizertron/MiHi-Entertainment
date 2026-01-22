<?php
/**
 * Upload Limits Test Script
 * This script checks if your server is configured to handle 1GB uploads
 * 
 * INSTRUCTIONS:
 * 1. Upload this file to your GoDaddy server in the /admin/ directory
 * 2. Access it via: https://yourdomain.com/admin/test_upload_limits.php
 * 3. Review the results to confirm your settings
 * 4. DELETE this file after testing for security
 */

// Security: Only allow access from localhost or if you're logged in
session_start();
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$isLoggedIn = !empty($_SESSION['user_id']);

if (!$isLocal && !$isLoggedIn) {
    die('Access denied. Please login to the admin panel first.');
}

function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function convertToBytes($value)
{
    $value = trim($value);
    $last = strtolower($value[strlen($value) - 1]);
    $value = (int) $value;
    switch ($last) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }
    return $value;
}

// Get PHP configuration values
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$memoryLimit = ini_get('memory_limit');
$maxExecutionTime = ini_get('max_execution_time');
$maxInputTime = ini_get('max_input_time');

// Convert to bytes for comparison
$uploadMaxBytes = convertToBytes($uploadMaxFilesize);
$postMaxBytes = convertToBytes($postMaxSize);
$memoryLimitBytes = convertToBytes($memoryLimit);

// Determine the effective upload limit
$effectiveLimit = min($uploadMaxBytes, $postMaxBytes);

// Target values (1GB = 1024MB)
$targetBytes = 1024 * 1024 * 1024; // 1GB
$targetMB = 1024; // 1024MB

// Check if configuration meets requirements
$isConfigured = $effectiveLimit >= $targetBytes;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Limits Test - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 32px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .content {
            padding: 32px;
        }

        .status-card {
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 2px solid;
        }

        .status-card.success {
            background: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
        }

        .status-card.warning {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }

        .status-card.error {
            background: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }

        .status-icon {
            font-size: 48px;
            margin-bottom: 16px;
            text-align: center;
        }

        .status-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
            text-align: center;
        }

        .status-message {
            text-align: center;
            font-size: 14px;
            line-height: 1.6;
        }

        .settings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
            background: #f9fafb;
            border-radius: 8px;
            overflow: hidden;
        }

        .settings-table th,
        .settings-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .settings-table th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .settings-table td {
            color: #1f2937;
            font-size: 14px;
        }

        .settings-table tr:last-child td {
            border-bottom: none;
        }

        .value-good {
            color: #10b981;
            font-weight: 600;
        }

        .value-bad {
            color: #ef4444;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }

        .badge.success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .instructions {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 24px;
        }

        .instructions h3 {
            color: #0c4a6e;
            font-size: 16px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .instructions ol {
            margin-left: 20px;
            color: #075985;
            line-height: 1.8;
        }

        .instructions li {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .instructions code {
            background: #e0f2fe;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }

        .warning-box {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
            color: #92400e;
            font-size: 14px;
            line-height: 1.6;
        }

        .warning-box strong {
            display: block;
            margin-bottom: 8px;
            color: #78350f;
        }

        .footer {
            text-align: center;
            padding: 24px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 13px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            margin-top: 16px;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📊 Upload Limits Test</h1>
            <p>Checking server configuration for 1GB PDF uploads</p>
        </div>

        <div class="content">
            <?php if ($isConfigured): ?>
                <div class="status-card success">
                    <div class="status-icon">✅</div>
                    <div class="status-title">Configuration Successful!</div>
                    <div class="status-message">
                        Your server is configured to handle uploads up to <strong>
                            <?php echo formatBytes($effectiveLimit); ?>
                        </strong>.<br>
                        You can now upload PDF files up to 1GB for your flipbooks.
                    </div>
                </div>
            <?php else: ?>
                <div class="status-card error">
                    <div class="status-icon">❌</div>
                    <div class="status-title">Configuration Needed</div>
                    <div class="status-message">
                        Current upload limit is <strong>
                            <?php echo formatBytes($effectiveLimit); ?>
                        </strong>.<br>
                        Target limit is <strong>1 GB (1024 MB)</strong>.<br>
                        Please follow the instructions below to increase your limits.
                    </div>
                </div>
            <?php endif; ?>

            <h2 style="margin-bottom: 16px; color: #111827; font-size: 18px;">Current PHP Settings</h2>
            <table class="settings-table">
                <thead>
                    <tr>
                        <th>Setting</th>
                        <th>Current Value</th>
                        <th>Target Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>upload_max_filesize</strong></td>
                        <td class="<?php echo $uploadMaxBytes >= $targetBytes ? 'value-good' : 'value-bad'; ?>">
                            <?php echo $uploadMaxFilesize; ?> (
                            <?php echo formatBytes($uploadMaxBytes); ?>)
                        </td>
                        <td>1024M (1 GB)</td>
                        <td>
                            <?php if ($uploadMaxBytes >= $targetBytes): ?>
                                <span class="badge success">✓ OK</span>
                            <?php else: ?>
                                <span class="badge error">✗ Too Low</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>post_max_size</strong></td>
                        <td class="<?php echo $postMaxBytes >= $targetBytes ? 'value-good' : 'value-bad'; ?>">
                            <?php echo $postMaxSize; ?> (
                            <?php echo formatBytes($postMaxBytes); ?>)
                        </td>
                        <td>1024M (1 GB)</td>
                        <td>
                            <?php if ($postMaxBytes >= $targetBytes): ?>
                                <span class="badge success">✓ OK</span>
                            <?php else: ?>
                                <span class="badge error">✗ Too Low</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>memory_limit</strong></td>
                        <td class="<?php echo $memoryLimitBytes >= $targetBytes ? 'value-good' : 'value-bad'; ?>">
                            <?php echo $memoryLimit; ?> (
                            <?php echo formatBytes($memoryLimitBytes); ?>)
                        </td>
                        <td>1024M (1 GB)</td>
                        <td>
                            <?php if ($memoryLimitBytes >= $targetBytes): ?>
                                <span class="badge success">✓ OK</span>
                            <?php else: ?>
                                <span class="badge error">✗ Too Low</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>max_execution_time</strong></td>
                        <td>
                            <?php echo $maxExecutionTime; ?> seconds
                        </td>
                        <td>600 seconds (10 min)</td>
                        <td>
                            <?php if ($maxExecutionTime >= 600): ?>
                                <span class="badge success">✓ OK</span>
                            <?php else: ?>
                                <span class="badge error">✗ Too Low</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>max_input_time</strong></td>
                        <td>
                            <?php echo $maxInputTime; ?> seconds
                        </td>
                        <td>600 seconds (10 min)</td>
                        <td>
                            <?php if ($maxInputTime >= 600): ?>
                                <span class="badge success">✓ OK</span>
                            <?php else: ?>
                                <span class="badge error">✗ Too Low</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php if (!$isConfigured): ?>
                <div class="instructions">
                    <h3>🔧 How to Fix on GoDaddy Hosting</h3>
                    <ol>
                        <li><strong>Option 1: Use cPanel (Recommended)</strong>
                            <ul style="margin-top: 8px; margin-left: 20px;">
                                <li>Login to your GoDaddy cPanel</li>
                                <li>Find <code>Select PHP Version</code> or <code>MultiPHP INI Editor</code></li>
                                <li>Set these values to <code>1024M</code>:
                                    <ul style="margin-top: 4px;">
                                        <li>upload_max_filesize</li>
                                        <li>post_max_size</li>
                                        <li>memory_limit</li>
                                    </ul>
                                </li>
                                <li>Set execution times to <code>600</code> seconds</li>
                                <li>Save changes and refresh this page</li>
                            </ul>
                        </li>
                        <li style="margin-top: 12px;"><strong>Option 2: Check .htaccess and .user.ini files</strong>
                            <ul style="margin-top: 8px; margin-left: 20px;">
                                <li>Verify <code>.htaccess</code> has the PHP settings at the top</li>
                                <li>Verify <code>.user.ini</code> exists in your root directory</li>
                                <li>Wait 5 minutes for changes to take effect</li>
                                <li>Refresh this page to see updated values</li>
                            </ul>
                        </li>
                        <li style="margin-top: 12px;"><strong>Option 3: Contact GoDaddy Support</strong>
                            <ul style="margin-top: 8px; margin-left: 20px;">
                                <li>Some hosting plans have hard limits</li>
                                <li>You may need to upgrade your plan</li>
                                <li>Ask support to increase PHP upload limits to 1GB</li>
                            </ul>
                        </li>
                    </ol>
                </div>
            <?php endif; ?>

            <div class="warning-box">
                <strong>⚠️ Security Warning</strong>
                Please delete this test file (<code>test_upload_limits.php</code>) after you've confirmed your settings.
                This file exposes server configuration details and should not be left accessible on a production server.
            </div>

            <div style="text-align: center;">
                <a href="flipbook_create.php" class="btn">Go to Create Flipbook</a>
            </div>
        </div>

        <div class="footer">
            <p>Server:
                <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
            </p>
            <p>PHP Version:
                <?php echo phpversion(); ?>
            </p>
            <p style="margin-top: 8px; font-size: 12px;">
                Effective Upload Limit: <strong>
                    <?php echo formatBytes($effectiveLimit); ?>
                </strong>
            </p>
        </div>
    </div>
</body>

</html>