<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

// Get list of pages from database
$db = getDB();
$stmt = $db->query("SELECT * FROM pages ORDER BY updated_at DESC");
$pages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Builder - CMS</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .page-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .page-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .page-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .page-card h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: #1f2937;
        }
        .page-card p {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: #6b7280;
        }
        .page-card .actions {
            display: flex;
            gap: 10px;
        }
        .page-card .btn {
            flex: 1;
            text-align: center;
            padding: 8px 12px;
            font-size: 14px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f9fafb;
            border-radius: 12px;
            margin-top: 20px;
        }
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <?php include '../admin/includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Website Builder</h1>
            <div style="display: flex; gap: 10px;">
                <div class="dropdown" style="position: relative;">
                    <button class="btn btn-primary" onclick="toggleDropdown()" style="display: flex; align-items: center; gap: 8px;">
                        + Create New Page
                        <span style="font-size: 10px;">▼</span>
                    </button>
                    <div id="builderDropdown" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 8px; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 220px; z-index: 1000;">
                        <a href="wp-builder.php" style="display: block; padding: 12px 16px; color: #1f2937; text-decoration: none; border-bottom: 1px solid #f0f0f0;">
                            <strong>📝 WordPress Editor</strong><br>
                            <small style="color: #6b7280;">Classic WYSIWYG editor</small>
                        </a>
                        <a href="builder.php" style="display: block; padding: 12px 16px; color: #1f2937; text-decoration: none;">
                            <strong>🎨 Visual Builder</strong><br>
                            <small style="color: #6b7280;">Drag & drop interface</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            function toggleDropdown() {
                const dropdown = document.getElementById('builderDropdown');
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dropdown = document.getElementById('builderDropdown');
                const button = event.target.closest('.dropdown');
                if (!button && dropdown.style.display === 'block') {
                    dropdown.style.display = 'none';
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
    </div>
    
    <?php include '../admin/includes/footer.php'; ?>
</body>
</html>
