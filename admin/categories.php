<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $message = 'Category name is required';
        } else {
            if (empty($slug)) {
                $slug = generateSlug($name);
            } else {
                $slug = generateSlug($slug);
            }
            
            try {
                $stmt = $db->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
                $stmt->execute([$name, $slug, $description]);
                $message = 'Category created successfully';
            } catch (PDOException $e) {
                $message = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Category deleted successfully';
    }
}

$categories = $db->query("SELECT c.*, COUNT(p.id) as post_count FROM categories c LEFT JOIN posts p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - CMS Blog Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Categories</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>"><?php echo escape($message); ?></div>
        <?php endif; ?>
        
        <div class="form-section">
            <h2>Add New Category</h2>
            <form method="POST" class="inline-form">
                <input type="hidden" name="action" value="create">
                <input type="text" name="name" placeholder="Category Name" required>
                <input type="text" name="slug" placeholder="Slug (optional)">
                <input type="text" name="description" placeholder="Description (optional)">
                <button type="submit" class="btn btn-primary">Add Category</button>
            </form>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Posts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?php echo escape($cat['name']); ?></td>
                    <td><?php echo escape($cat['slug']); ?></td>
                    <td><?php echo escape($cat['description']); ?></td>
                    <td><?php echo $cat['post_count']; ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
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