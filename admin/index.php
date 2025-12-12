<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();

// Get statistics
$stats = [
    'total_posts' => $db->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'published_posts' => $db->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'draft_posts' => $db->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn(),
    'total_categories' => $db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'total_tags' => $db->query("SELECT COUNT(*) FROM tags")->fetchColumn(),
];

// Get recent posts
$recentPosts = $db->query("
    SELECT p.*, u.username as author, c.name as category_name
    FROM posts p
    LEFT JOIN users u ON p.author_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MiHi Entertainment CMS</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <a href="create.php" class="btn btn-primary">Create New Post</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_posts']; ?></h3>
                <p>Total Posts</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['published_posts']; ?></h3>
                <p>Published</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['draft_posts']; ?></h3>
                <p>Drafts</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_categories']; ?></h3>
                <p>Categories</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_tags']; ?></h3>
                <p>Tags</p>
            </div>
        </div>
        
        <div class="content-section">
            <h2>Recent Posts</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentPosts as $post): ?>
                    <tr>
                        <td><?php echo escape($post['title']); ?></td>
                        <td><?php echo escape($post['category_name'] ?? 'Uncategorized'); ?></td>
                        <td><?php echo escape($post['author']); ?></td>
                        <td><span class="badge badge-<?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                            <a href="delete.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

