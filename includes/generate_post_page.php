<?php
/**
 * Generate Static HTML Page for Blog Post
 */

function generatePostPage($postId, $googleFormEmbed = '')
{
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

    // Get related posts (blog suggestions — exclude current post)
    $stmt = $db->prepare("
        SELECT p.title, p.slug, p.featured_image, p.excerpt, p.published_at,
               c.name as category_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'published' AND p.id != ?
        ORDER BY p.published_at DESC
        LIMIT 3
    ");
    $stmt->execute([$postId]);
    $relatedPosts = $stmt->fetchAll();

    // Get archives (posts grouped by month) for sidebar
    $archivesStmt = $db->query("
        SELECT DATE_FORMAT(published_at, '%Y-%m') as month_key,
               DATE_FORMAT(published_at, '%M %Y') as month_label,
               COUNT(*) as post_count
        FROM posts
        WHERE status = 'published'
          AND published_at IS NOT NULL
          AND published_at != ''
        GROUP BY month_key
        ORDER BY month_key DESC
        LIMIT 12
    ");
    $archives = $archivesStmt ? $archivesStmt->fetchAll() : [];

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
        /* ===== Azo Sans Uber — local font ===== */
        @font-face {
            font-family: \'Azo Sans Uber\';
            src: url(\'../assets/fonts/AzoSansUber-Regular.woff2\') format(\'woff2\'),
                 url(\'../assets/fonts/AzoSansUber-Regular.woff\') format(\'woff\');
            font-weight: 400 700 900;
            font-style: normal;
            font-display: swap;
        }

        /* ===== Azo Sans — local font ===== */
        @font-face {
            font-family: \'Azo Sans\';
            src: url(\'../assets/fonts/AzoSans-Regular.woff2\') format(\'woff2\'),
                 url(\'../assets/fonts/AzoSans-Regular.woff\') format(\'woff\');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

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
            font-family: \'Azo Sans Uber\', \'Montserrat\', sans-serif;
            font-weight: 400 !important;
            letter-spacing: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-smooth: always;
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
        }
        
        .post-content h2 {
            font-size: 2rem;
            font-weight: 400;
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            color: #FF4F4F;
        }
        
        .post-content h3 {
            font-size: 1.5rem;
            font-weight: 400;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #FF4F4F;
        }

        .post-content h4, .post-content h5, .post-content h6 {
            font-weight: 400;
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

        .post-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        /* Image Alignment Support - High Specificity */
        .post-content .alignleft, .post-content img.alignleft {
            display: inline;
            float: left;
            margin-right: 1.5em;
            margin-bottom: 1em;
        }

        .post-content .alignright, .post-content img.alignright {
            display: inline;
            float: right;
            margin-left: 1.5em;
            margin-bottom: 1em;
        }


        .post-content .aligncenter, .post-content img.aligncenter {
            display: block;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 2rem;
        }

        /* ===== Sidebar Layout ===== */
        .blog-layout {
            display: flex;
            gap: 1.5rem;
            align-items: flex-start;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem 1rem;
            box-sizing: border-box;
        }
        .blog-main {
            flex: 1;
            min-width: 0;
            background: #fff;
            border-radius: 0.75rem;
            padding: 1.75rem 1.75rem;
            box-shadow: 0 1px 8px rgba(0,0,0,0.07);
        }
        .blog-sidebar {
            width: 290px;
            flex-shrink: 0;
            position: sticky;
            top: 90px;
        }
        .sidebar-card {
            background: #fff;
            border-radius: 0.625rem;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .sidebar-card-title {
            font-size: 0.95rem;
            font-weight: 400;
            font-family: \'Azo Sans\', \'Montserrat\', sans-serif;
            color: #111827;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            margin: 0;
            letter-spacing: 0;
        }
        .archive-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.55rem 1rem;
            color: #374151;
            text-decoration: none;
            font-size: 0.85rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.15s, color 0.15s;
        }
        .archive-link:last-child { border-bottom: none; }
        .archive-link:hover { background: #eff6ff; color: #2563eb; }
        .archive-count {
            background: #f3f4f6;
            color: #9ca3af;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.1rem 0.45rem;
            border-radius: 9999px;
        }
        .post-meta-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem 0.5rem;
            font-size: 0.82rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
        }
        .cat-pill {
            background: #1d4ed8;
            color: #fff !important;
            padding: 0.175rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-decoration: none !important;
        }

        /* ===== Responsive ===== */

        /* Tablet (768px – 1024px) */
        @media (max-width: 1024px) {
            .blog-layout { padding: 1rem 1.25rem; gap: 1rem; }
            .blog-sidebar { width: 240px; }
            .blog-main { padding: 1.5rem 1.25rem; }
            .post-content { font-size: 1.05rem; }
            .post-content h2 { font-size: 1.65rem; }
            .post-content h3 { font-size: 1.3rem; }
        }

        /* Mobile (≤768px): sidebar stacks below article */
        @media (max-width: 768px) {
            .blog-layout {
                flex-direction: column;
                padding: 0.75rem 0.625rem;
                gap: 0.75rem;
            }
            .blog-sidebar {
                width: 100%;
                position: static;
            }

            /* Article card */
            .blog-main {
                padding: 1.125rem 0.875rem;
                border-radius: 0.5rem;
                box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            }

            /* Title */
            .blog-main h1 {
                font-size: 1.45rem !important;
                line-height: 1.3 !important;
                margin-bottom: 1rem !important;
            }

            /* Article typography */
            .post-content {
                font-size: 0.97rem;
                line-height: 1.7;
            }
            .post-content p { margin-bottom: 1.1rem; }
            .post-content h2 { font-size: 1.35rem; margin-top: 2rem; margin-bottom: 1rem; }
            .post-content h3 { font-size: 1.15rem; margin-top: 1.5rem; margin-bottom: 0.75rem; }
            .post-content ul, .post-content ol { padding-left: 1.375rem; }
            .post-content blockquote { padding-left: 1rem; margin: 1.25rem 0; font-size: 0.95rem; }

            /* Kill floats on mobile — images go full width */
            .post-content .alignleft,
            .post-content img.alignleft,
            .post-content .alignright,
            .post-content img.alignright {
                display: block;
                float: none;
                margin: 1rem auto;
                max-width: 100%;
            }

            /* Meta bar */
            .post-meta-bar { font-size: 0.78rem; gap: 0.3rem 0.45rem; margin-bottom: 0.625rem; }

            /* Tag badges — larger tap target */
            .tag-badge { padding: 0.4rem 0.75rem; font-size: 0.82rem; }

            /* Archive links — taller tap target */
            .archive-link { padding: 0.75rem 1rem; font-size: 0.85rem; }

            /* Sidebar cards side by side on landscape phones */
            .sidebar-cards-row {
                display: flex;
                gap: 0.75rem;
                flex-wrap: wrap;
            }
            .sidebar-cards-row .sidebar-card {
                flex: 1;
                min-width: 220px;
                margin-bottom: 0;
            }

            /* Google Form iframe: shorter on mobile */
            .sidebar-card iframe { height: 420px !important; }
        }

        /* Small phones (≤480px) */
        @media (max-width: 480px) {
            .blog-layout { padding: 0.5rem 0.375rem; gap: 0.5rem; }
            .blog-main { padding: 1rem 0.75rem; border-radius: 0.375rem; }
            .blog-main h1 { font-size: 1.25rem !important; }
            .post-content { font-size: 0.94rem; }
            .post-content h2 { font-size: 1.2rem; }
            .post-content h3 { font-size: 1.05rem; }
            /* Full-width featured image on tiny screens */
            .blog-main > div[style*="border-radius"] { border-radius: 0 !important; margin-left: -0.75rem; margin-right: -0.75rem; }
            /* Google Form: compact height */
            .sidebar-card iframe { height: 360px !important; }
        }

        /* Landscape phones (short viewport) */
        @media (max-width: 768px) and (orientation: landscape) {
            .blog-layout { padding: 0.5rem 0.75rem; }
            .blog-main h1 { font-size: 1.3rem !important; }
            .post-content { font-size: 0.95rem; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation will be loaded by navigation.js -->
    <script src="../assets/components/navigation.js"></script>
    <!-- Blog Two-Column Layout -->
    <div class="blog-layout">

        <!-- ===== LEFT: Article ===== -->
        <main class="blog-main">

            <!-- Meta bar -->
            <div class="post-meta-bar">';

    if ($post['category_name']) {
        $html .= '<a href="../blog.php?category=' . escape($post['category_slug']) . '" class="cat-pill">' . escape($post['category_name']) . '</a>
                <span>&bull;</span>';
    }

    $html .= '
                <span>' . date('F j, Y', strtotime($post['published_at'] ?: $post['created_at'])) . '</span>
                <span>&bull;</span>
                <span>By ' . escape($post['full_name'] ?: $post['author']) . '</span>
            </div>

            <!-- Title -->
            <h1 style="font-family:\'Azo Sans Uber\',\'Montserrat\',sans-serif; font-size:2rem; font-weight:400; -webkit-font-smoothing:antialiased; color:#FF4F4F; line-height:1.25; margin:0 0 1.5rem;">
                ' . escape($post['title']) . '
            </h1>';

    // Featured image
    if ($post['featured_image']) {
        $html .= '
            <div style="margin-bottom:2rem; border-radius:0.75rem; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08);">
                <img src="' . escape($post['featured_image']) . '" alt="' . escape($post['title']) . '" style="width:100%; height:auto; display:block;">
            </div>';
    }

    // Article content
    $html .= '
            <div class="post-content">
                ' . $post['content'] . '
            </div>';

    // Tags
    if (!empty($tags)) {
        $html .= '
            <div style="margin-top:2rem; padding-top:1.25rem; border-top:1px solid #f3f4f6; display:flex; flex-wrap:wrap; gap:0.5rem;">';
        foreach ($tags as $tag) {
            $html .= '<a href="../blog.php?tag=' . escape($tag['slug']) . '" class="tag-badge">' . escape($tag['name']) . '</a>';
        }
        $html .= '</div>';
    }

    // Back to Blog
    $html .= '
            <div style="margin-top:2rem;">
                <a href="../blog.php" style="display:inline-flex; align-items:center; gap:0.4rem; color:#2563eb; font-weight:600; text-decoration:none; font-size:0.9rem;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to Blog
                </a>
            </div>
        </main><!-- end blog-main -->

        <!-- ===== RIGHT: Sidebar ===== -->
        <aside class="blog-sidebar">';

    // --- Contact Us / Google Form card ---
    // Parse src from full iframe or use bare URL
    $formSrcUrl = '';
    if (!empty($googleFormEmbed)) {
        if (stripos($googleFormEmbed, '<iframe') !== false) {
            if (preg_match('/\bsrc=["\']([^"\']+)["\']/', $googleFormEmbed, $m)) {
                $formSrcUrl = $m[1];
            }
        } else {
            $formSrcUrl = trim($googleFormEmbed);
        }
    }

    if (!empty($formSrcUrl)) {
        $cleanEmbed = htmlspecialchars($formSrcUrl, ENT_QUOTES, 'UTF-8');
        $html .= '
            <div class="sidebar-card">
                <h2 class="sidebar-card-title">Contact Us</h2>
                <iframe
                    src="' . $cleanEmbed . '"
                    width="100%"
                    height="520"
                    frameborder="0"
                    marginheight="0"
                    marginwidth="0"
                    style="display:block;"
                    loading="lazy"
                    title="Contact Us">
                    Loading&hellip;
                </iframe>
            </div>';
    }

    // --- Archives card ---
    if (!empty($archives)) {
        $html .= '
            <div class="sidebar-card">
                <h2 class="sidebar-card-title">Archives</h2>';
        foreach ($archives as $archive) {
            $html .= '
                <a href="../blog.php?month=' . escape($archive['month_key']) . '" class="archive-link">
                    <span>' . escape($archive['month_label']) . '</span>
                    <span class="archive-count">' . (int) $archive['post_count'] . '</span>
                </a>';
        }
        $html .= '
            </div>';
    }

    $html .= '
        </aside><!-- end blog-sidebar -->

    </div><!-- end blog-layout -->';

    // --- Blog Suggestions Section ---
    if (!empty($relatedPosts)) {
        $html .= '

    <!-- Blog Suggestions -->
    <section style="padding: 4rem 0; background: #f9fafb;">
        <div style="max-width: 80rem; margin: 0 auto; padding: 0 1.5rem;">
            <div style="text-align: center; margin-bottom: 3rem;">
                <span style="display:inline-block; padding: 0.375rem 1rem; background:#FF4F4F; color:#FFFFFF; border-radius:9999px; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.75rem;">Keep Reading</span>
                <h2 style="font-size:2rem; font-weight:800; color:#FF4F4F; margin:0;">You Might Also Like</h2>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:2rem;">';

        foreach ($relatedPosts as $related) {
            $relatedDate = date('F j, Y', strtotime($related['published_at'] ?: date('Y-m-d')));
            $relatedExcerpt = $related['excerpt'] ? htmlspecialchars(substr($related['excerpt'], 0, 120), ENT_QUOTES, 'UTF-8') : '';
            $relatedTitle = htmlspecialchars($related['title'], ENT_QUOTES, 'UTF-8');
            $relatedSlug = htmlspecialchars($related['slug'], ENT_QUOTES, 'UTF-8');
            $relatedCat = $related['category_name'] ? htmlspecialchars($related['category_name'], ENT_QUOTES, 'UTF-8') : '';
            $relatedImg = $related['featured_image'] ? htmlspecialchars($related['featured_image'], ENT_QUOTES, 'UTF-8') : '';

            $html .= '
                <a href="' . $relatedSlug . '.html" style="display:block; background:#fff; border-radius:1rem; box-shadow:0 4px 20px rgba(0,0,0,0.07); overflow:hidden; text-decoration:none; color:inherit; transition:transform 0.25s, box-shadow 0.25s;" onmouseover="this.style.transform=\'translateY(-4px)\';this.style.boxShadow=\'0 12px 40px rgba(0,0,0,0.13)\'" onmouseout="this.style.transform=\'none\';this.style.boxShadow=\'0 4px 20px rgba(0,0,0,0.07)\'">';

            if ($relatedImg) {
                $html .= '
                    <div style="aspect-ratio:16/9; overflow:hidden;">
                        <img src="' . $relatedImg . '" alt="' . $relatedTitle . '" style="width:100%; height:100%; object-fit:cover; transition:transform 0.5s;" loading="lazy">
                    </div>';
            } else {
                $html .= '
                    <div style="aspect-ratio:16/9; background:linear-gradient(135deg,#dbeafe,#e0e7ff); display:flex; align-items:center; justify-content:center;">
                        <svg style="width:3rem;height:3rem;color:#93c5fd" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                    </div>';
            }

            $html .= '
                    <div style="padding:1.5rem;">';

            if ($relatedCat) {
                $html .= '
                        <span style="display:inline-block; padding:0.25rem 0.75rem; background:#FF4F4F; color:#FFFFFF; border-radius:9999px; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.75rem;">' . $relatedCat . '</span>';
            }

            $html .= '
                        <h3 style="font-family:\'Azo Sans\'; font-size:1.1rem; font-weight:400; color:#111827; margin:0 0 0.5rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">' . $relatedTitle . '</h3>';

            if ($relatedExcerpt) {
                $html .= '
                        <p style="color:#6b7280; font-size:0.9rem; margin:0 0 1rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">' . $relatedExcerpt . '</p>';
            }

            $html .= '
                        <div style="display:flex; align-items:center; justify-content:space-between;">
                            <span style="font-size:0.75rem; color:#9ca3af;">' . $relatedDate . '</span>
                            <span style="display:inline-flex; align-items:center; gap:0.25rem; color:#FF4F4F; font-size:0.875rem; font-weight:600;">
                                Read more
                                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </span>
                        </div>
                    </div>
                </a>';
        }

        $html .= '
            </div>
            <div style="text-align:center; margin-top:2.5rem;">
                <a href="../blog.php" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.875rem 2rem; background:#FF4F4F; color:#fff; border-radius:9999px; font-weight:700; text-decoration:none; transition:background 0.2s, transform 0.2s; box-shadow:0 4px 20px rgba(37,99,235,0.25);" onmouseover="this.style.background=\'#FF4F4F\';this.style.transform=\'scale(1.04)\'" onmouseout="this.style.background=\'#FF4F4F\';this.style.transform=\'none\'">
                    View All Posts
                    <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>
        </div>
    </section>';
    }

    $html .= '

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

