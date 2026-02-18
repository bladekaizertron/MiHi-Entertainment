<?php
$currentUser = getCurrentUser();
$currentPath = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
// Ensure a CSRF token exists for sensitive actions like logout
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('cms_nav_icon')) {
    function cms_nav_icon($key) {
        switch ($key) {
            case 'dashboard':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="9 22 9 12 15 12 15 22" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'builder':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7m0-18H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7m0-18v18" stroke-width="2"/></svg>';
            case 'wordpress':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'pages':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'assets':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'password':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke-width="2"/></svg>';
            default:
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>';
        }
    }
}

$navItems = [
    ['label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'dashboard'],
    ['label' => 'Visual Builder', 'href' => 'builder.php', 'icon' => 'builder'],
    ['label' => 'WordPress Editor', 'href' => 'wp-builder.php', 'icon' => 'wordpress'],
    ['label' => 'All Pages', 'href' => 'index.php', 'icon' => 'pages'],
];

$avatarUrl = !empty($currentUser['avatar_url'])
    ? $currentUser['avatar_url']
    : 'https://ui-avatars.com/api/?background=18F1E1&color=1F1F1F&name=' . urlencode($currentUser['full_name'] ?? $currentUser['username'] ?? 'Admin');
$fullName = $currentUser['full_name'] ?? $currentUser['username'] ?? 'Admin';
$email = $currentUser['email'] ?? 'admin@example.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            background: var(--mihi-white);
            color: var(--mihi-black);
            min-height: 100vh;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 280px;
            background: var(--mihi-white);
            border-right: 2px solid rgba(31, 31, 31, 0.1);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.05);
        }

        .sidebar-logo {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(31, 31, 31, 0.1);
            display: block;
        }

        .sidebar-logo img {
            height: 40px;
            width: auto;
        }

        .sidebar-profile {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(31, 31, 31, 0.1);
            text-align: center;
        }

        .sidebar-profile img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin-bottom: 12px;
            border: 2px solid var(--mihi-aqua);
            box-shadow: 0 0 20px rgba(24, 241, 225, 0.3);
        }

        .sidebar-profile h4 {
            font-family: var(--font-header);
            font-size: 16px;
            font-weight: 600;
            color: var(--mihi-black);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .sidebar-profile p {
            font-size: 13px;
            color: rgba(31, 31, 31, 0.6);
        }

        .sidebar-menu {
            flex: 1;
            padding: 16px 12px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(31, 31, 31, 0.7);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 6px;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .sidebar-menu a:hover {
            background: rgba(24, 241, 225, 0.08);
            color: var(--mihi-coral);
            transform: translateX(4px);
        }

        .sidebar-menu a.active {
            background: rgba(255, 79, 79, 0.1);
            color: var(--mihi-coral);
            border-left: 3px solid var(--mihi-coral);
            font-weight: 600;
        }

        .sidebar-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(31, 31, 31, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-logout {
            width: 100%;
            background: var(--mihi-coral);
            color: var(--mihi-white);
            box-shadow: 0 4px 12px rgba(255, 79, 79, 0.3);
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 79, 79, 0.5);
            background: #ff6b6b;
        }

        .admin-content {
            flex: 1;
            margin-left: 280px;
            padding: 32px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(31, 31, 31, 0.1);
        }

        .page-header h1 {
            font-family: var(--font-header);
            font-size: 2rem;
            font-weight: 400;
            color: var(--mihi-black);
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin-bottom: 8px;
        }

        .page-header h1 .highlight {
            color: var(--mihi-coral);
        }

        /* Scrollbar styling */
        .admin-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .admin-sidebar::-webkit-scrollbar-track {
            background: rgba(31, 31, 31, 0.05);
        }

        .admin-sidebar::-webkit-scrollbar-thumb {
            background: var(--mihi-coral);
            border-radius: 3px;
        }

        .admin-sidebar::-webkit-scrollbar-thumb:hover {
            background: #ff6b6b;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 240px;
            }

            .admin-content {
                margin-left: 240px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a href="index.php" class="sidebar-logo">
            <img src="../assets/images/logo.svg" alt="MiHi Entertainment">
        </a>

        <div class="sidebar-profile">
            <img src="<?php echo $avatarUrl; ?>" alt="<?php echo escape($fullName); ?>">
            <h4><?php echo escape($fullName); ?></h4>
            <p><?php echo escape($email); ?></p>
        </div>

        <div class="sidebar-menu">
            <?php foreach ($navItems as $item): ?>
                <?php $isActive = ($currentPath === basename($item['href'])); ?>
                <a href="<?php echo $item['href']; ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                    <?php echo cms_nav_icon($item['icon']); ?>
                    <span><?php echo $item['label']; ?></span>
                </a>
            <?php endforeach; ?>
            
            <a href="../cms/index.php" class="external-link">
                <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 3h7v7m-1-6L10 14M5 5h5M5 10v9h9v-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Admin Panel</span>
            </a>
        </div>

        <div class="sidebar-footer">
            <form method="POST" action="cms/logout.php" style="margin:0;" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" class="btn btn-logout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 17l5-5-5-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 12H9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="admin-content">

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('logout-form')) {
            if (!confirm('Are you sure you want to log out?')) {
                e.preventDefault();
            }
        }
    });
});
</script>
