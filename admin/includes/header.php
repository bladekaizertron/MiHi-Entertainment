<?php
$currentUser = getCurrentUser();
$currentPath = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
// Ensure a CSRF token exists for sensitive actions like logout
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('admin_nav_icon')) {
    function admin_nav_icon($key) {
        switch ($key) {
            case 'dashboard':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 12l2-2 4 4 8-8 4 4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'create':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'categories':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9.5 3.5l-6 6V11h6V5h1.5zm5 0l6 6V11h-6V5h-1.5zM3.5 13.5h6V19H4a.5.5 0 0 1-.5-.5zm11 0h6V19H17a.5.5 0 0 1-.5-.5z" stroke-width="1.6"/></svg>';
            case 'tags':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 7l6.29-6.29a1 1 0 0 1 1.42 0L21 9.58a1 1 0 0 1 0 1.42L13.42 18.6a1 1 0 0 1-1.42 0L5 11.6V7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>';
            case 'posts':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 5h14v14H5z" stroke-width="2"/><path d="M9 3v4m6-4v4M9 17h6" stroke-width="2" stroke-linecap="round"/></svg>';
            case 'flipbooks':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 7h8M8 11h8M8 15h4" stroke-width="2" stroke-linecap="round"/></svg>';
            case 'password':
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke-width="2"/></svg>';
            default:
                return '<svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>';
        }
    }
}

$navItems = [
    ['label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'dashboard'],
    ['label' => 'Create Post', 'href' => 'create.php', 'icon' => 'create'],
    ['label' => 'Categories', 'href' => 'categories.php', 'icon' => 'categories'],
    ['label' => 'Tags', 'href' => 'tags.php', 'icon' => 'tags'],
    ['label' => 'All Posts', 'href' => 'post.php', 'icon' => 'posts'],
];

$avatarUrl = !empty($currentUser['avatar_url'])
    ? $currentUser['avatar_url']
    : 'https://ui-avatars.com/api/?background=6366F1&color=fff&name=' . urlencode($currentUser['full_name'] ?? $currentUser['username'] ?? 'Admin');
$fullName = $currentUser['full_name'] ?? $currentUser['username'] ?? 'Admin';
$email = $currentUser['email'] ?? 'admin@example.com';
?>
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
                    <?php echo admin_nav_icon($item['icon']); ?>
                    <span><?php echo $item['label']; ?></span>
                </a>
            <?php endforeach; ?>
            <?php if (in_array(strtolower($currentUser['role'] ?? ''), ['admin','editor'], true)): ?>
                <?php $isActive = ($currentPath === 'pages.php' || $currentPath === 'pages_edit.php'); ?>
                <a href="pages.php" class="<?php echo $isActive ? 'active' : ''; ?>">
                    <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="16" rx="2" ry="2" stroke-width="2"/><path d="M7 8h10M7 12h10M7 16h6" stroke-width="2" stroke-linecap="round"/></svg>
                    <span>Pages</span>
                </a>
                <?php $isActive = ($currentPath === 'flipbooks.php' || $currentPath === 'flipbook_create.php' || $currentPath === 'flipbook_edit.php'); ?>
                <a href="flipbooks.php" class="<?php echo $isActive ? 'active' : ''; ?>">
                    <?php echo admin_nav_icon('flipbooks'); ?>
                    <span>Flipbooks</span>
                </a>
            <?php endif; ?>
            <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                <?php $isActive = ($currentPath === 'users.php'); ?>
                <a href="users.php" class="<?php echo $isActive ? 'active' : ''; ?>">
                    <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 14a4 4 0 1 0-8 0" stroke-width="2"/><circle cx="12" cy="8" r="3" stroke-width="2"/><path d="M20 21a6 6 0 0 0-12 0" stroke-width="2"/></svg>
                    <span>Users</span>
                </a>
            <?php endif; ?>
            <?php $isActive = ($currentPath === 'change_password.php'); ?>
            <a href="change_password.php" class="<?php echo $isActive ? 'active' : ''; ?>">
                <?php echo admin_nav_icon('password'); ?>
                <span>Change Password</span>
            </a>
            <a href="../blog.php" target="_blank" class="external-link">
                <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 3h7v7m-1-6L10 14M5 5h5M5 10v9h9v-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>View Blog</span>
            </a>
        </div>

		<div class="sidebar-footer">
			<form method="POST" action="logout.php" style="margin:0;" class="logout-form">
				<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
				<button type="submit" class="btn btn-sm btn-logout" style="background:#ef4444;border-color:#ef4444;color:#ffffff;display:flex;align-items:center;gap:8px;">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display:block">
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