<?php
/**
 * Generate Static HTML Page for Blog Post
 */

function generatePostPage($postId) {
    // Ensure config is loaded
    if (!function_exists('getDB')) {
        require_once __DIR__ . '/../config/config.php';
    }
    
    $db = getDB();
    
    // Get post data
    $stmt = $db->prepare("
        SELECT p.*, u.username as author, u.full_name, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if (!$post) {
        return false;
    }
    
    // Only generate for published posts
    if ($post['status'] !== 'published') {
        // Delete the file if post is not published
        $postsDir = __DIR__ . '/../post/';
        $htmlFile = $postsDir . $post['slug'] . '.html';
        if (file_exists($htmlFile)) {
            @unlink($htmlFile);
        }
        return false; // Return false to indicate no file was generated
    }
    
    // Get SEO metadata
    $stmt = $db->prepare("SELECT * FROM seo_metadata WHERE post_id = ?");
    $stmt->execute([$postId]);
    $seo = $stmt->fetch();
    
    // Get tags
    $stmt = $db->prepare("
        SELECT t.name, t.slug 
        FROM tags t
        INNER JOIN post_tags pt ON t.id = pt.tag_id
        WHERE pt.post_id = ?
    ");
    $stmt->execute([$postId]);
    $tags = $stmt->fetchAll();
    
    // SEO values
    $metaTitle = $seo['meta_title'] ?? $post['title'];
    $metaDescription = $seo['meta_description'] ?? $post['excerpt'] ?? substr(strip_tags($post['content']), 0, 160);
    $metaKeywords = $seo['meta_keywords'] ?? '';
    $ogTitle = $seo['og_title'] ?? $post['title'];
    $ogDescription = $seo['og_description'] ?? $post['excerpt'] ?? $metaDescription;
    $ogImage = $seo['og_image'] ?? '';
    $canonicalUrl = $seo['canonical_url'] ?? SITE_URL . '/post/' . $post['slug'] . '.html';
    $robots = $seo['robots'] ?? 'index, follow';
    
    $siteName = getSetting('site_name', 'My Blog');
    
    // Generate HTML content
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . escape($metaTitle) . ' - ' . escape($siteName) . '</title>
    <meta name="description" content="' . escape($metaDescription) . '">';
    
    if ($metaKeywords) {
        $html .= '
    <meta name="keywords" content="' . escape($metaKeywords) . '">';
    }
    
    $html .= '
    <meta name="robots" content="' . escape($robots) . '">
    <link rel="canonical" href="' . escape($canonicalUrl) . '">
    
    <!-- Open Graph -->
    <meta property="og:title" content="' . escape($ogTitle) . '">
    <meta property="og:description" content="' . escape($ogDescription) . '">
    <meta property="og:type" content="article">
    <meta property="og:url" content="' . escape($canonicalUrl) . '">';
    
    if ($ogImage) {
        $html .= '
    <meta property="og:image" content="' . escape($ogImage) . '">';
    }
    
    $html .= '
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="' . escape($seo['twitter_card'] ?? 'summary_large_image') . '">
    <meta name="twitter:title" content="' . escape($ogTitle) . '">
    <meta name="twitter:description" content="' . escape($ogDescription) . '">
    
    <!-- Structured Data (JSON-LD) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": "' . escape($post['title']) . '",
        "description": "' . escape($metaDescription) . '",
        "author": {
            "@type": "Person",
            "name": "' . escape($post['full_name'] ?: $post['author']) . '"
        },
        "datePublished": "' . date('c', strtotime($post['published_at'] ?: $post['created_at'])) . '",
        "dateModified": "' . date('c', strtotime($post['updated_at'])) . '"';
    
    if ($post['category_name']) {
        $html .= ',
        "articleSection": "' . escape($post['category_name']) . '"';
    }
    
    $html .= ',
        "publisher": {
            "@type": "Organization",
            "name": "' . escape($siteName) . '"
        }
    }
    </script>
    
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1 class="site-title"><a href="' . SITE_URL . '">' . escape($siteName) . '</a></h1>
        </div>
    </header>
    
    <main class="container">
        <article class="post-single">
            <header class="post-header">';
    
    if ($post['category_name']) {
        $html .= '
                <span class="post-category">
                    <a href="' . SITE_URL . '/category.php?slug=' . escape($post['category_slug']) . '">
                        ' . escape($post['category_name']) . '
                    </a>
                </span>';
    }
    
    $html .= '
                
                <h1 class="post-title">' . escape($post['title']) . '</h1>
                
                <div class="post-meta">
                    <span>By ' . escape($post['full_name'] ?: $post['author']) . '</span>
                    <span>•</span>
                    <span>' . date('F j, Y', strtotime($post['published_at'] ?: $post['created_at'])) . '</span>';
    
    if ($post['views'] > 0) {
        $html .= '
                    <span>•</span>
                    <span>' . $post['views'] . ' views</span>';
    }
    
    $html .= '
                </div>';
    
    if (!empty($tags)) {
        $html .= '
                
                <div class="post-tags">';
        foreach ($tags as $tag) {
            $html .= '
                    <a href="' . SITE_URL . '/tag.php?slug=' . escape($tag['slug']) . '" class="tag">' . escape($tag['name']) . '</a>';
        }
        $html .= '
                </div>';
    }
    
    $html .= '
            </header>';
    $html .= '
            
            <div class="post-content">
                ' . $post['content'] . '
            </div>
        </article>
        
        <nav class="post-navigation">
            <a href="' . SITE_URL . '/blog.php" class="btn btn-secondary">← Back to Blog</a>
        </nav>
    </main>
    
    <footer class="site-footer">
        <div class="container">
            <p>&copy; ' . date('Y') . ' ' . escape($siteName) . '. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>';
    
    // Create post directory if it doesn't exist
    $postsDir = __DIR__ . '/../post/';
    if (!is_dir($postsDir)) {
        if (!mkdir($postsDir, 0755, true)) {
            error_log("Failed to create post directory: " . $postsDir);
            return false;
        }
    }
    
    // Check if directory is writable
    if (!is_writable($postsDir)) {
        error_log("Post directory is not writable: " . $postsDir);
        return false;
    }
    
    // Write HTML file
    $htmlFile = $postsDir . $post['slug'] . '.html';
    $result = file_put_contents($htmlFile, $html);
    
    if ($result === false) {
        error_log("Failed to write HTML file: " . $htmlFile);
        return false;
    }
    
    return true;
}

