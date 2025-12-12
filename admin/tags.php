<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        
        if (empty($name)) {
            $message = 'Tag name is required';
        } else {
            if (empty($slug)) {
                $slug = generateSlug($name);
            } else {
                $slug = generateSlug($slug);
            }
            
            try {
                $stmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                $stmt->execute([$name, $slug]);
                $message = 'Tag created successfully';
            } catch (PDOException $e) {
                $message = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Tag deleted successfully';
    }
}

$tags = $db->query("SELECT t.*, COUNT(pt.post_id) as post_count FROM tags t LEFT JOIN post_tags pt ON t.id = pt.tag_id GROUP BY t.id ORDER BY t.name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags - CMS Blog Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Tags</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>"><?php echo escape($message); ?></div>
        <?php endif; ?>
        
        <div class="form-section">
            <h2>Add New Tag</h2>
            <form method="POST" class="inline-form">
                <input type="hidden" name="action" value="create">
                <input type="text" name="name" placeholder="Tag Name" required>
                <input type="text" name="slug" placeholder="Slug (optional)">
                <button type="submit" class="btn btn-primary">Add Tag</button>
            </form>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Posts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tags as $tag): ?>
                <tr>
                    <td><?php echo escape($tag['name']); ?></td>
                    <td><?php echo escape($tag['slug']); ?></td>
                    <td><?php echo $tag['post_count']; ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>