<?php
/**
 * Regenerate all static HTML pages for published posts
 * Useful for debugging or regenerating all pages
 */

require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
require_once __DIR__ . '/../includes/generate_post_page.php';

// Get all published posts
$posts = $db->query("SELECT id FROM posts WHERE status = 'published'")->fetchAll();

$generated = 0;
$failed = 0;

foreach ($posts as $post) {
    if (generatePostPage($post['id'])) {
        $generated++;
    } else {
        $failed++;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerate Pages - CMS Blog Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Regenerate Static Pages</h1>
        
        <div class="alert alert-success">
            <h2>Results:</h2>
            <p><strong>Generated:</strong> <?php echo $generated; ?> pages</p>
            <p><strong>Failed:</strong> <?php echo $failed; ?> pages</p>
            <p><strong>Total:</strong> <?php echo count($posts); ?> published posts</p>
        </div>
        
        <p><a href="../blog.php" class="btn btn-primary">Back to Blog</a></p>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

