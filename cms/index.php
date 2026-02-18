<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

// Get list of pages from database
$db = getDB();
$stmt = $db->query("SELECT * FROM pages ORDER BY updated_at DESC");
$pages = $stmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>

<style>
    .page-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
        margin-top: 24px;
    }
    .page-card {
        background: var(--mihi-black);
        border: 2px solid rgba(24, 241, 225, 0.2);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(24, 241, 225, 0.1);
        transition: all 0.3s ease;
    }
    .page-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(24, 241, 225, 0.2),
                    0 0 40px rgba(255, 79, 79, 0.1);
        border-color: var(--mihi-aqua);
    }
    .page-card h3 {
        margin: 0 0 12px 0;
        font-size: 20px;
        font-weight: 600;
        color: var(--mihi-white);
        font-family: var(--font-header);
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .page-card p {
        margin: 0 0 20px 0;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.6;
    }
    .page-card p strong {
        color: var(--mihi-aqua);
        font-weight: 600;
    }
    .page-card .actions {
        display: flex;
        gap: 10px;
    }
    .page-card .btn {
        flex: 1;
        text-align: center;
        padding: 10px 14px;
        font-size: 13px;
        border-radius: 8px;
    }
    .btn-primary {
        background: var(--mihi-coral);
        color: var(--mihi-white);
        border: none;
        font-weight: 700;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(255, 79, 79, 0.4);
        background: #ff6b6b;
    }
    .btn-secondary {
        background: rgba(24, 241, 225, 0.1);
        color: var(--mihi-aqua);
        border: 1px solid var(--mihi-aqua);
    }
    .btn-secondary:hover {
        background: rgba(24, 241, 225, 0.2);
    }
    .btn-danger {
        background: rgba(255, 79, 79, 0.1);
        color: var(--mihi-coral);
        border: 1px solid var(--mihi-coral);
    }
    .btn-danger:hover {
        background: rgba(255, 79, 79, 0.2);
    }
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: rgba(24, 241, 225, 0.05);
        border: 2px dashed rgba(24, 241, 225, 0.3);
        border-radius: 16px;
        margin-top: 24px;
    }
    .empty-state svg {
        width: 80px;
        height: 80px;
        margin-bottom: 24px;
        color: var(--mihi-aqua);
        opacity: 0.6;
    }
    .empty-state h2 {
        font-family: var(--font-header);
        font-size: 24px;
        color: var(--mihi-white);
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .empty-state p {
        color: rgba(255, 255, 255, 0.6);
        font-size: 15px;
        margin-bottom: 24px;
    }
    .create-dropdown {
        position: relative;
        display: inline-block;
    }
    .dropdown-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        background: var(--mihi-coral);
        color: var(--mihi-white);
        border: none;
        border-radius: 10px;
        font-family: var(--font-header);
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        box-shadow: 0 4px 16px rgba(255, 79, 79, 0.3);
        transition: all 0.3s ease;
    }
    .dropdown-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 24px rgba(255, 79, 79, 0.5);
        background: #ff6b6b;
    }
    .dropdown-menu {
        display: none;
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: var(--mihi-black);
        border: 2px solid rgba(24, 241, 225, 0.3);
        border-radius: 12px;
        min-width: 280px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5),
                    0 0 40px rgba(24, 241, 225, 0.2);
        z-index: 1000;
        overflow: hidden;
    }
    .dropdown-menu.show {
        display: block;
        animation: slideDown 0.3s ease;
    }
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .dropdown-menu a {
        display: block;
        padding: 16px 20px;
        color: var(--mihi-white);
        text-decoration: none;
        border-bottom: 1px solid rgba(24, 241, 225, 0.1);
        transition: all 0.2s ease;
    }
    .dropdown-menu a:last-child {
        border-bottom: none;
    }
    .dropdown-menu a:hover {
        background: rgba(24, 241, 225, 0.1);
        padding-left: 24px;
    }
    .dropdown-menu a strong {
        display: block;
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 4px;
        color: var(--mihi-aqua);
    }
    .dropdown-menu a small {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
    }
</style>

<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1><span class="highlight">Website</span> Builder</h1>
        <div class="create-dropdown">
            <button class="dropdown-toggle" onclick="toggleDropdown()">
                + Create New Page
                <span style="font-size: 10px;">▼</span>
            </button>
            <div id="builderDropdown" class="dropdown-menu">
                <a href="wp-builder.php">
                    <strong>📝 WordPress Editor</strong>
                    <small>Classic WYSIWYG editor</small>
                </a>
                <a href="builder.php">
                    <strong>🎨 Visual Builder</strong>
                    <small>Drag & drop interface</small>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleDropdown() {
        const dropdown = document.getElementById('builderDropdown');
        dropdown.classList.toggle('show');
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('builderDropdown');
        const button = event.target.closest('.create-dropdown');
        if (!button && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    });
</script>

<?php if (empty($pages)): ?>
    <div class="empty-state">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h2>No pages yet</h2>
        <p>Get started by creating your first page with the visual builder</p>
        <a href="builder.php" class="btn btn-primary" style="margin-top: 20px; display: inline-block;">Create Your First Page</a>
    </div>
<?php else: ?>
    <div class="page-grid">
        <?php foreach ($pages as $page): ?>
            <div class="page-card">
                <h3><?php echo htmlspecialchars($page['title']); ?></h3>
                <p>
                    <strong>Slug:</strong> <?php echo htmlspecialchars($page['slug']); ?><br>
                    <strong>Updated:</strong> <?php echo date('M d, Y', strtotime($page['updated_at'])); ?>
                </p>
                <div class="actions">
                    <a href="builder.php?id=<?php echo $page['id']; ?>" class="btn btn-primary">Edit</a>
                    <a href="../<?php echo htmlspecialchars($page['slug']); ?>.html" target="_blank" class="btn btn-secondary">View</a>
                    <a href="delete_page.php?id=<?php echo $page['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this page?')">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
