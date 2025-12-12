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
    <title>' . escape($metaTitle) . ' | ' . escape($siteName) . '</title>
    <meta name="description" content="' . escape($metaDescription) . '">';
    
    if ($metaKeywords) {
        $html .= '
    <meta name="keywords" content="' . escape($metaKeywords) . '">';
    }
    
    $html .= '
    <meta name="robots" content="' . escape($robots) . '">
    <meta name="author" content="' . escape($post['full_name'] ?: $post['author']) . '">
    <link rel="canonical" href="' . escape($canonicalUrl) . '">
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    
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
    <meta name="twitter:description" content="' . escape($ogDescription) . '">';
    
    if ($ogImage) {
        $html .= '
    <meta name="twitter:image" content="' . escape($ogImage) . '">';
    }
    
    $html .= '
    
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
    
    if ($ogImage) {
        $html .= ',
        "image": "' . escape($ogImage) . '"';
    }
    
    $html .= ',
        "publisher": {
            "@type": "Organization",
            "name": "' . escape($siteName) . '",
            "logo": {
                "@type": "ImageObject",
                "url": "' . escape($canonicalUrl) . '/../assets/images/logo.png"
            }
        }
    }
    </script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        html, body {
            overflow-x: hidden;
            width: 100%;
        }
        
        body {
            font-family: \'Inter\', sans-serif;
            color: #1a202c;
            padding-top: 80px; /* Space for fixed header */
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: \'Montserrat\', sans-serif;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        
        .post-content {
            font-size: 1.125rem;
            line-height: 1.75;
            color: #374151;
        }
        
        .post-content p {
            margin-bottom: 1.5rem;
        }
        
        .post-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 2rem 0;
        }
        
        .post-content h2 {
            font-size: 2rem;
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            color: #1f2937;
        }
        
        .post-content h3 {
            font-size: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #374151;
        }
        
        .post-content a {
            color: #0050ff;
            text-decoration: underline;
        }
        
        .post-content a:hover {
            color: #0040d9;
        }
        
        .post-content ul, .post-content ol {
            margin: 1.5rem 0;
            padding-left: 2rem;
        }
        
        .post-content li {
            margin-bottom: 0.75rem;
        }
        
        .post-content blockquote {
            border-left: 4px solid #0050ff;
            padding-left: 1.5rem;
            margin: 2rem 0;
            font-style: italic;
            color: #6b7280;
        }
        
        .category-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, rgba(0, 80, 255, 0.1), rgba(139, 111, 71, 0.1));
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #0050ff;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .category-badge:hover {
            background: linear-gradient(135deg, rgba(0, 80, 255, 0.2), rgba(139, 111, 71, 0.2));
            transform: translateY(-2px);
        }
        
        .tag-badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            background: #f3f4f6;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .tag-badge:hover {
            background: #e5e7eb;
            color: #0050ff;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation will be loaded by navigation.js -->
    <script src="../assets/components/navigation.js"></script>
    
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-blue-50 via-white to-purple-50 py-12 md:py-20">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-4xl">
            <div class="text-center mb-8">';
    
    if ($post['category_name']) {
        $html .= '
                <a href="../blog.php?category=' . escape($post['category_slug']) . '" class="category-badge">
                        ' . escape($post['category_name']) . '
                </a>';
    }
    
    $html .= '
            </div>
                
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                ' . escape($post['title']) . '
            </h1>
                
            <div class="flex flex-wrap items-center justify-center gap-4 text-gray-600 mb-8">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="font-medium">' . escape($post['full_name'] ?: $post['author']) . '</span>
                </div>
                    <span>•</span>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>' . date('F j, Y', strtotime($post['published_at'] ?: $post['created_at'])) . '</span>
                </div>';
    
    if ($post['views'] > 0) {
        $html .= '
                    <span>•</span>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <span>' . number_format($post['views']) . ' views</span>
                </div>';
    }
    
    $html .= '
                </div>';
    
    if (!empty($tags)) {
        $html .= '
            <div class="flex flex-wrap justify-center gap-2 mb-8">';
        foreach ($tags as $tag) {
            $html .= '
                <a href="../blog.php?tag=' . escape($tag['slug']) . '" class="tag-badge">
                    ' . escape($tag['name']) . '
                </a>';
        }
        $html .= '
                </div>';
    }
    
    if ($post['featured_image']) {
    $html .= '
            <div class="mb-8 rounded-2xl overflow-hidden shadow-2xl">
                <img src="' . escape($post['featured_image']) . '" alt="' . escape($post['title']) . '" class="w-full h-auto">
            </div>';
    }
    
    $html .= '
        </div>
    </section>
            
    <!-- Main Content -->
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-4xl py-12">
        <article class="bg-white rounded-2xl shadow-lg p-8 md:p-12">
            <div class="post-content prose prose-lg max-w-none">
                ' . $post['content'] . '
            </div>
        </article>
        
        <!-- Navigation -->
        <nav class="mt-12 flex justify-center">
            <a href="../blog.php" class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-full font-semibold transition-all duration-300 transform hover:scale-105">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Blog
            </a>
        </nav>
    </main>
    
    <!-- Footer will be loaded by footer.js -->
    <script src="../assets/components/footer.js"></script>
</body>
</html>';
    
    // Create post directory if it doesn't exist
    // Get the base directory (parent of includes)
    $baseDir = dirname(__DIR__);
    $postsDir = $baseDir . DIRECTORY_SEPARATOR . 'post' . DIRECTORY_SEPARATOR;
    
    // Try to normalize path if base directory exists
    $normalizedBase = realpath($baseDir);
    if ($normalizedBase !== false) {
        $postsDir = $normalizedBase . DIRECTORY_SEPARATOR . 'post' . DIRECTORY_SEPARATOR;
    }
    
    if (!is_dir($postsDir)) {
        if (!mkdir($postsDir, 0755, true)) {
            $error = "Failed to create post directory: " . $postsDir;
            error_log($error);
            error_log("Current working directory: " . getcwd());
            error_log("Script directory: " . __DIR__);
            return false;
        }
        error_log("Created post directory: " . $postsDir);
    }
    
    // Check if directory is writable
    // Note: is_writable() can sometimes return false even when we can write
    // So we'll try to write anyway and check the actual result
    $isWritable = is_writable($postsDir);
    if (!$isWritable) {
        error_log("Warning: is_writable() returned false for: " . $postsDir);
        error_log("Directory permissions: " . substr(sprintf('%o', fileperms($postsDir)), -4));
        error_log("PHP UID: " . getmyuid() . ", Directory UID: " . fileowner($postsDir));
        // Don't return false yet - try to write anyway as is_writable() can be unreliable
    }
    
    // Write HTML file (try even if is_writable() says no)
    $htmlFile = $postsDir . $post['slug'] . '.html';
    $result = @file_put_contents($htmlFile, $html);
    
    if ($result === false) {
        $error = "Failed to write HTML file: " . $htmlFile;
        error_log($error);
        error_log("Directory path: " . $postsDir);
        error_log("Directory exists: " . (is_dir($postsDir) ? 'yes' : 'no'));
        error_log("is_writable() result: " . ($isWritable ? 'yes' : 'no'));
        error_log("Directory permissions: " . substr(sprintf('%o', fileperms($postsDir)), -4));
        error_log("PHP UID: " . getmyuid() . ", Directory UID: " . fileowner($postsDir));
        error_log("HTML content length: " . strlen($html) . " bytes");
        $lastError = error_get_last();
        if ($lastError) {
            error_log("Last PHP error: " . $lastError['message']);
        }
        return false;
    }
    
    // Verify file was created
    if (!file_exists($htmlFile)) {
        error_log("ERROR: File was not created even though file_put_contents returned success. File: " . $htmlFile);
        return false;
    }
    
    error_log("Successfully generated post page: " . $htmlFile . " (" . $result . " bytes)");
    return true;
}

