<?php
/**
 * Regenerate a specific post by slug or ID
 */

require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
require_once __DIR__ . '/../includes/generate_post_page.php';

$slug = $_GET['slug'] ?? '';
$postId = $_GET['id'] ?? null;
$message = '';
$error = '';
$post = null;

// Find post by slug or ID
if ($postId) {
    $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
} elseif ($slug) {
    $stmt = $db->prepare("SELECT * FROM posts WHERE slug = ?");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
}

// Auto-regenerate if slug provided and post found (for direct links)
$autoRegenerate = isset($_GET['auto']) && $_GET['auto'] === '1';

// Regenerate if post found
if ($post) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate'])) {
        if (generatePostPage($post['id'])) {
            $message = "Post '{$post['title']}' has been successfully regenerated!";
        } else {
            $error = "Failed to regenerate post. Check error logs for details.";
        }
    } elseif ($autoRegenerate && $post['status'] === 'published') {
        // Auto-regenerate on GET request with auto=1 parameter
        if (generatePostPage($post['id'])) {
            $message = "Post '{$post['title']}' has been automatically regenerated!";
        } else {
            $error = "Failed to regenerate post. Check error logs for details.";
        }
    }
} else {
    $error = "Post not found. Please check the slug or ID.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerate Post - CMS Blog Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Regenerate Post</h1>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo escape($message); ?>
                <br><br>
                <a href="../post/<?php echo escape($post['slug']); ?>.html" target="_blank" class="btn btn-primary">View Post</a>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo escape($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($post): ?>
            <div class="post-info" style="background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <h2><?php echo escape($post['title']); ?></h2>
                <p><strong>Slug:</strong> <?php echo escape($post['slug']); ?></p>
                <p><strong>Status:</strong> <?php echo escape($post['status']); ?></p>
                <p><strong>Published:</strong> <?php echo $post['published_at'] ? date('Y-m-d H:i:s', strtotime($post['published_at'])) : 'Not published'; ?></p>
                
                <?php
                $htmlFile = __DIR__ . '/../post/' . $post['slug'] . '.html';
                $fileExists = file_exists($htmlFile);
                ?>
                <p><strong>HTML File:</strong> 
                    <?php if ($fileExists): ?>
                        <span style="color: green;">✓ Exists</span> 
                        (<?php echo date('Y-m-d H:i:s', filemtime($htmlFile)); ?>)
                    <?php else: ?>
                        <span style="color: red;">✗ Missing</span>
                    <?php endif; ?>
                </p>
                
                <?php if ($post['status'] === 'published'): ?>
                    <form method="POST" style="margin-top: 20px;">
                        <button type="submit" name="regenerate" class="btn btn-primary">Regenerate Post</button>
                    </form>
                <?php else: ?>
                    <p style="color: orange; margin-top: 10px;">
                        <strong>Note:</strong> This post is not published. Only published posts generate HTML files.
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <h3>Search for a Post</h3>
                <p>Enter a post slug or ID to regenerate:</p>
                <form method="GET" style="margin-top: 15px;">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" name="slug" placeholder="Enter post slug" value="<?php echo escape($slug); ?>" style="flex: 1; padding: 8px;">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                    <small style="display: block; margin-top: 5px;">Or use: <code>?id=123</code> to search by ID</small>
                </form>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            <a href="regenerate_pages.php" class="btn btn-secondary">Regenerate All Posts</a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

