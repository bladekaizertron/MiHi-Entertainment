<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$db = getDB();
$error = '';
$success = '';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!hash_equals($csrfToken, $token)) {
        $error = 'Security token invalid.';
    } elseif (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } else {
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($updateStmt->execute([$newHash, $_SESSION['user_id']])) {
                $success = 'Password changed successfully.';
                // Clear form fields
                $currentPassword = '';
                $newPassword = '';
                $confirmPassword = '';
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - MiHi Entertainment CMS</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .password-change-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .password-card {
            background: var(--card-bg);
            padding: 48px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-color);
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .password-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .password-header-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(255, 79, 79, 0.3);
        }
        
        .password-header-icon svg {
            width: 40px;
            height: 40px;
            stroke: white;
        }
        
        .password-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .password-header p {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .password-form .form-group {
            position: relative;
            margin-bottom: 28px;
        }
        
        .password-form .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .password-form .form-group label svg {
            width: 18px;
            height: 18px;
            stroke: var(--primary-color);
        }
        
        .password-input-wrapper {
            position: relative;
        }
        
        .password-form input[type="password"] {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: var(--card-bg);
            color: var(--text-primary);
        }
        
        .password-form input[type="password"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 79, 79, 0.1);
        }
        
        .password-input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            stroke: var(--text-secondary);
            pointer-events: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            color: var(--text-secondary);
            transition: color 0.2s ease;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .password-toggle svg {
            width: 20px;
            height: 20px;
        }
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .password-strength-bar.weak {
            width: 33%;
            background: var(--danger-color);
        }
        
        .password-strength-bar.medium {
            width: 66%;
            background: var(--warning-color);
        }
        
        .password-strength-bar.strong {
            width: 100%;
            background: var(--success-color);
        }
        
        .password-requirements {
            margin-top: 12px;
            padding: 12px;
            background: var(--light-bg);
            border-radius: var(--radius-md);
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            list-style: none;
        }
        
        .password-requirements li {
            margin-bottom: 6px;
            position: relative;
            padding-left: 20px;
        }
        
        .password-requirements li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--success-color);
            font-weight: bold;
        }
        
        .password-requirements li.invalid::before {
            content: '○';
            color: var(--text-secondary);
        }
        
        .form-actions {
            margin-top: 40px;
            padding-top: 32px;
            border-top: 2px solid var(--border-color);
            display: flex;
            gap: 12px;
        }
        
        .form-actions .btn {
            flex: 1;
        }
        
        .alert {
            margin-bottom: 32px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="password-change-container">
            <div class="password-card">
                <div class="password-header">
                    <div class="password-header-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <h1>Change Password</h1>
                    <p>Update your account password to keep your account secure</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <strong>Error:</strong> <?php echo escape($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> <?php echo escape($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="password-form" id="passwordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label for="current_password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            Current Password
                        </label>
                        <div class="password-input-wrapper">
                            <svg class="password-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                required 
                                autofocus
                                placeholder="Enter your current password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password', this)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v20M2 12h20"/>
                            </svg>
                            New Password
                        </label>
                        <div class="password-input-wrapper">
                            <svg class="password-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                required 
                                minlength="8"
                                placeholder="Enter your new password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength" style="display: none;">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <div class="password-requirements" id="passwordRequirements">
                            <ul>
                                <li id="req-length" class="invalid">At least 8 characters</li>
                                <li id="req-uppercase" class="invalid">One uppercase letter</li>
                                <li id="req-lowercase" class="invalid">One lowercase letter</li>
                                <li id="req-number" class="invalid">One number</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            Confirm New Password
                        </label>
                        <div class="password-input-wrapper">
                            <svg class="password-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                required 
                                minlength="8"
                                placeholder="Confirm your new password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <small class="form-text" id="passwordMatch" style="display: none; color: var(--danger-color); margin-top: 8px;"></small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/>
                                <polyline points="7 3 7 8 15 8"/>
                            </svg>
                            Change Password
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Toggle password visibility
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const svg = button.querySelector('svg');
        
        if (input.type === 'password') {
            input.type = 'text';
            svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23" stroke-width="2" stroke-linecap="round"/>';
        } else {
            input.type = 'password';
            svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
        }
    }
    
    // Password strength checker
    document.addEventListener('DOMContentLoaded', function() {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthContainer = document.getElementById('passwordStrength');
        const passwordMatch = document.getElementById('passwordMatch');
        
        function checkPasswordStrength(password) {
            let strength = 0;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password)
            };
            
            // Update requirement indicators
            document.getElementById('req-length').className = requirements.length ? '' : 'invalid';
            document.getElementById('req-uppercase').className = requirements.uppercase ? '' : 'invalid';
            document.getElementById('req-lowercase').className = requirements.lowercase ? '' : 'invalid';
            document.getElementById('req-number').className = requirements.number ? '' : 'invalid';
            
            // Calculate strength
            if (requirements.length) strength++;
            if (requirements.uppercase) strength++;
            if (requirements.lowercase) strength++;
            if (requirements.number) strength++;
            
            // Show/hide strength bar
            if (password.length > 0) {
                strengthContainer.style.display = 'block';
                strengthBar.className = 'password-strength-bar';
                
                if (strength <= 1) {
                    strengthBar.classList.add('weak');
                } else if (strength <= 2) {
                    strengthBar.classList.add('medium');
                } else {
                    strengthBar.classList.add('strong');
                }
            } else {
                strengthContainer.style.display = 'none';
            }
        }
        
        function validatePasswords() {
            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                    passwordMatch.style.display = 'block';
                    passwordMatch.textContent = '✗ Passwords do not match';
                    passwordMatch.style.color = 'var(--danger-color)';
                } else {
                    confirmPassword.setCustomValidity('');
                    passwordMatch.style.display = 'block';
                    passwordMatch.textContent = '✓ Passwords match';
                    passwordMatch.style.color = 'var(--success-color)';
                }
            } else {
                passwordMatch.style.display = 'none';
            }
        }
        
        newPassword.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            validatePasswords();
        });
        
        confirmPassword.addEventListener('input', validatePasswords);
    });
    </script>
</body>
</html>

