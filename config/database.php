<?php
/**
 * Database Configuration
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cms_blog');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get database connection
 */
function getDB() {
    static $pdo = null;
    static $reconnectAttempts = 0;
    $maxReconnectAttempts = 2;
    
    if ($pdo === null) {
        $reconnectAttempts = 0; // Reset counter on new connection
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 30, // Connection timeout in seconds
                PDO::ATTR_PERSISTENT         => false, // Don't use persistent connections
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Set MySQL-specific timeouts to prevent "server has gone away" errors
            $pdo->exec("SET SESSION wait_timeout = 600"); // 10 minutes
            $pdo->exec("SET SESSION interactive_timeout = 600"); // 10 minutes
            
            // Increase max_allowed_packet to handle large content (256MB)
            // This prevents "Got a packet bigger than 'max_allowed_packet' bytes" errors
            try {
                // First, try to set GLOBAL (requires SUPER privilege, but works for root user)
                // This is important because SESSION can't exceed GLOBAL
                try {
                    $pdo->exec("SET GLOBAL max_allowed_packet = 268435456"); // 256MB
                    error_log("Set GLOBAL max_allowed_packet to 256MB");
                } catch (PDOException $globalError) {
                    // If GLOBAL fails, try SESSION (but it's limited by GLOBAL value)
                    error_log("Could not set GLOBAL max_allowed_packet: " . $globalError->getMessage() . " - Trying SESSION instead");
                }
                
                // Always try to set SESSION as well
                $pdo->exec("SET SESSION max_allowed_packet = 268435456"); // 256MB
                
                // Verify the setting was applied
                $stmt = $pdo->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
                $result = $stmt->fetch();
                if ($result) {
                    $currentValue = (int)$result['Value'];
                    $currentMB = round($currentValue / 1048576, 2);
                    error_log("MySQL max_allowed_packet is now: " . $currentMB . " MB");
                    
                    // Warn if it's still too low
                    if ($currentValue < 16777216) { // Less than 16MB
                        error_log("WARNING: max_allowed_packet is only " . $currentMB . " MB. This may cause issues with large content. Consider setting it in my.ini/my.cnf");
                    }
                }
            } catch (PDOException $e) {
                // If setting fails, log but don't die - might be a permission issue
                error_log("Warning: Could not set max_allowed_packet: " . $e->getMessage());
                error_log("You may need to set max_allowed_packet in MySQL configuration file (my.ini or my.cnf)");
            }
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    } else {
        // Check if connection is still alive, reconnect if needed
        try {
            $pdo->query("SELECT 1");
        } catch (PDOException $e) {
            // Connection lost, reset and reconnect (with safety limit)
            if ($reconnectAttempts < $maxReconnectAttempts) {
                $pdo = null;
                $reconnectAttempts++;
                return getDB(); // Recursive call to reconnect
            } else {
                die("Database connection lost and reconnection failed after " . $maxReconnectAttempts . " attempts: " . $e->getMessage());
            }
        }
    }
    
    return $pdo;
}

/**
 * Get current MySQL max_allowed_packet setting
 * Uses a fresh connection to ensure we can query even if main connection is in error state
 */
function getMaxAllowedPacket() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ];
        
        $tempPdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $stmt = $tempPdo->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
        $result = $stmt->fetch();
        
        if ($result) {
            return (int)$result['Value'];
        }
    } catch (Exception $e) {
        error_log("Could not check max_allowed_packet: " . $e->getMessage());
    }
    
    return null;
}

