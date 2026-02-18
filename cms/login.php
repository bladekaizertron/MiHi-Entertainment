<?php
require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $role = isset($user['role']) ? strtolower(trim($user['role'])) : '';
            $allowedRoles = ['admin', 'editor'];
            if (!in_array($role, $allowedRoles, true)) {
                $error = 'Your account is awaiting approval by an administrator.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header('Location: index.php');
                exit;
            }
        } else {
            $error = 'Invalid username or password';
        }
    } else {
        $error = 'Please enter both username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Login - Website Builder</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Azo+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* MiHi Brand Variables */
        :root {
            --mihi-black: #1F1F1F;
            --mihi-coral: #FF4F4F;
            --mihi-aqua: #18F1E1;
            --mihi-white: #FFFFFF;
            --font-header: 'Azo Sans', sans-serif;
            --font-body: 'Azo Sans', sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-body);
            background: var(--mihi-black);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated neon glow elements */
        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(24, 241, 225, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            top: -300px;
            right: -300px;
            animation: float 20s infinite ease-in-out;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 79, 79, 0.12) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -250px;
            left: -250px;
            animation: float 15s infinite ease-in-out reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, 40px) scale(1.15); }
        }
        
        .login-container {
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 1;
        }
        
        .login-box {
            background: var(--mihi-black);
            border: 2px solid rgba(24, 241, 225, 0.3);
            border-radius: 20px;
            padding: 48px 40px;
            box-shadow: 0 0 60px rgba(24, 241, 225, 0.2),
                        0 0 100px rgba(255, 79, 79, 0.1),
                        inset 0 0 80px rgba(24, 241, 225, 0.03);
            animation: slideUp 0.6s ease-out;
            position: relative;
        }
        
        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--mihi-aqua), var(--mihi-coral), var(--mihi-aqua));
            border-radius: 20px 20px 0 0;
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .brand {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .brand-icon {
            width: 80px;
            height: 80px;
            background: var(--mihi-coral);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 32px rgba(255, 79, 79, 0.4),
                        0 0 60px rgba(255, 79, 79, 0.3);
            animation: pulse 3s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { 
                box-shadow: 0 8px 32px rgba(255, 79, 79, 0.4),
                            0 0 60px rgba(255, 79, 79, 0.3);
            }
            50% { 
                box-shadow: 0 8px 40px rgba(255, 79, 79, 0.6),
                            0 0 80px rgba(255, 79, 79, 0.5);
            }
        }
        
        .brand-icon svg {
            width: 40px;
            height: 40px;
            color: var(--mihi-white);
        }
        
        .brand h1 {
            font-family: var(--font-header);
            font-size: 2.25rem;
            font-weight: 400;
            color: var(--mihi-white);
            margin-bottom: 8px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            line-height: 1.2;
        }
        
        .brand h1 .highlight {
            color: var(--mihi-aqua);
            text-shadow: 0 0 20px rgba(24, 241, 225, 0.5);
        }
        
        .brand p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 400;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        
        .alert {
            padding: 16px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s ease-in-out;
            border: 1px solid;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
        
        .alert-error {
            background: rgba(255, 79, 79, 0.1);
            color: var(--mihi-coral);
            border-color: var(--mihi-coral);
        }
        
        .alert-success {
            background: rgba(24, 241, 225, 0.1);
            color: var(--mihi-aqua);
            border-color: var(--mihi-aqua);
        }
        
        .alert svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: var(--mihi-white);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--mihi-aqua);
            width: 20px;
            height: 20px;
            opacity: 0.7;
        }
        
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 16px 18px 16px 52px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(24, 241, 225, 0.2);
            border-radius: 12px;
            color: var(--mihi-white);
            font-size: 15px;
            font-family: inherit;
            outline: none;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--mihi-aqua);
            box-shadow: 0 0 0 4px rgba(24, 241, 225, 0.1),
                        0 0 20px rgba(24, 241, 225, 0.2);
        }
        
        input[type="text"]::placeholder,
        input[type="password"]::placeholder,
        input[type="email"]::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .btn {
            width: 100%;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .btn-primary {
            background: var(--mihi-coral);
            color: var(--mihi-white);
            box-shadow: 0 4px 20px rgba(255, 79, 79, 0.4),
                        0 0 40px rgba(255, 79, 79, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(255, 79, 79, 0.6),
                        0 0 60px rgba(255, 79, 79, 0.5);
            background: #ff6b6b;
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .footer-links {
            margin-top: 32px;
            text-align: center;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .footer-links a {
            color: var(--mihi-aqua);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            text-shadow: 0 0 10px rgba(24, 241, 225, 0.3);
        }
        
        .footer-links a:hover {
            color: var(--mihi-coral);
            text-shadow: 0 0 15px rgba(255, 79, 79, 0.5);
        }
        
        .features {
            margin-top: 36px;
            padding-top: 32px;
            border-top: 1px solid rgba(24, 241, 225, 0.2);
        }
        
        .feature-list {
            display: grid;
            gap: 14px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }
        
        .feature-item:hover {
            color: var(--mihi-aqua);
            transform: translateX(4px);
        }
        
        .feature-item svg {
            width: 18px;
            height: 18px;
            color: var(--mihi-coral);
            flex-shrink: 0;
        }
        
        @media (max-width: 480px) {
            .login-box {
                padding: 36px 28px;
            }
            
            .brand h1 {
                font-size: 1.75rem;
            }
            
            .brand-icon {
                width: 64px;
                height: 64px;
            }
            
            .brand-icon svg {
                width: 32px;
                height: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="brand">
                <div class="brand-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <h1><span class="highlight">CMS</span> Login</h1>
                <p>Website Builder & Content Manager</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?php echo escape($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?php echo escape($success); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <input type="text" id="username" name="username" placeholder="Enter your username or email" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    Sign In
                </button>
            </form>
            
            <div class="features">
                <div class="feature-list">
                    <div class="feature-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Drag & Drop Page Builder</span>
                    </div>
                    <div class="feature-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>WordPress-Style Editor</span>
                    </div>
                    <div class="feature-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>SEO & Media Management</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-links">
                Don't have an account? <a href="../admin/signup.php">Create Account</a>
            </div>
        </div>
    </div>
</body>
</html>
