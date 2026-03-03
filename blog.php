<?php
require_once __DIR__ . '/config/config.php';

$db = getDB();

// Get pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Category filter
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : '';
$categories = $db->query("SELECT id, name, slug FROM categories ORDER BY name")->fetchAll();
// Prepare pill vs dropdown categories
$maxPills = 10;
$pillCategories = array_slice($categories, 0, $maxPills);
$moreCategories = array_slice($categories, $maxPills);
if ($categorySlug !== '') {
    // Ensure active category appears as a pill
    $activeIndex = -1;
    foreach ($categories as $idx => $c) {
        if ($c['slug'] === $categorySlug) { $activeIndex = $idx; break; }
    }
    if ($activeIndex >= $maxPills) {
        // Move active into pills (replace last), and remove from more
        $active = $categories[$activeIndex];
        // Avoid duplicate if already included
        $exists = false;
        foreach ($pillCategories as $c) { if ($c['slug'] === $active['slug']) { $exists = true; break; } }
        if (!$exists) {
            // Replace last pill with active
            if (!empty($pillCategories)) {
                array_pop($pillCategories);
            }
            $pillCategories[] = $active;
            // Rebuild moreCategories excluding active
            $moreCategories = [];
            foreach ($categories as $i => $c) {
                if ($i >= $maxPills && $c['slug'] !== $active['slug']) {
                    $moreCategories[] = $c;
                }
            }
        }
    }
}

// Get total published posts (with optional category filter)
if ($categorySlug !== '') {
    $stmtTotal = $db->prepare("
        SELECT COUNT(*)
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'published' AND c.slug = ?
    ");
    $stmtTotal->execute([$categorySlug]);
    $totalPosts = (int)$stmtTotal->fetchColumn();
} else {
    $totalPosts = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
}
$totalPages = max(1, (int)ceil($totalPosts / $perPage));

// Get published posts (with optional category filter)
if ($categorySlug !== '') {
    $stmt = $db->prepare("
        SELECT p.*, u.username as author, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'published' AND c.slug = ?
        ORDER BY p.published_at DESC, p.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute([$categorySlug]);
    $posts = $stmt->fetchAll();
} else {
    $posts = $db->query("
        SELECT p.*, u.username as author, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'published'
        ORDER BY p.published_at DESC, p.created_at DESC
        LIMIT $perPage OFFSET $offset
    ")->fetchAll();
}

// Get site settings
$siteName = getSetting('site_name', 'MiHi Blogs');
$siteDescription = getSetting(
    'site_description',
    'Discover the latest trends and insights in the world of event entertainment with MiHi Entertainment\'s blog.'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($siteName); ?> - <?php echo escape($siteDescription); ?></title>
    <meta name="description" content="<?php echo escape($siteDescription); ?>">
    <meta name="keywords" content="<?php echo escape(getSetting('default_meta_keywords', 'blog, articles')); ?>">
    
    <meta property="og:title" content="<?php echo escape($siteName); ?>">
    <meta property="og:description" content="<?php echo escape($siteDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Azo Sans Font Face Declarations */
        @font-face {
            font-family: 'Azo Sans';
            src: url('assets/fonts/AzoSans-Regular.woff2') format('woff2'),
                 url('assets/fonts/AzoSans-Regular.woff') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Azo Sans Uber';
            src: url('assets/fonts/AzoSansUber-Regular.woff2') format('woff2'),
                 url('assets/fonts/AzoSansUber-Regular.woff') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        /* Brand Guide: Body Text - Azo Sans Regular */
        /* Only apply to main content, not navigation/footer */
        body { 
            overflow-x: hidden;
            background: #ffffff;
        }
        
        main {
            font-family: 'Azo Sans', sans-serif; 
            color: #1F1F1F;
        }

        main p {
            font-family: 'Azo Sans', sans-serif;
            color: #1F1F1F;
        }

        /* Brand Guide: Headers - Azo Sans Uber (Uppercase) */
        /* Only apply to headers inside main content, not navigation/footer */
        main h1, main h2, main h3, main h4, main h5, main h6 { 
            font-family: 'Azo Sans Uber', sans-serif;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            line-height: 1.2;
            color: #FF4F4F;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .hero-h1 {
            font-family: 'Azo Sans Uber', sans-serif;
            text-transform: uppercase;
        }

        .hero-p {
            font-family: 'Azo Sans', sans-serif;
        }

        /* Brand Guide: CTAs use Coral (#FF4F4F) */
        main .btn-primary { 
            background: #FF4F4F;
            color: #ffffff;
            transition: all 0.3s ease; 
        }
        main .btn-primary:hover { 
            background: #e63939;
            transform: translateY(-2px); 
            box-shadow: 0 20px 40px rgba(255, 79, 79, 0.4);
        }
        
        /* Gradient Backgrounds */
        .gradient-hero {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #f1f3f5 100%);
            position: relative;
            overflow: hidden;
        }
        .gradient-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 79, 79, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 80% 50%, rgba(24, 241, 225, 0.08) 0%, transparent 50%);
            animation: pulse 8s ease-in-out infinite;
        }
        
        .gradient-dark {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        
        .gradient-card {
            background: linear-gradient(135deg, rgba(255, 79, 79, 0.05) 0%, rgba(24, 241, 225, 0.05) 100%);
            border: 1px solid rgba(255, 79, 79, 0.15);
        }
        
        /* Animations */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 1s ease-out;
        }

        /* Section Padding */
        .section-padding { 
            padding: 5rem 0; 
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        @media (min-width: 768px) { 
            .section-padding { 
                padding: 8rem 0; 
            } 
        }
        
        /* Ensure all sections are properly contained - ONLY for main content */
        main section {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            position: relative;
        }
        /* Main content area - ensure proper spacing */
        main {
            position: relative;
            width: 100%;
            max-width: 100vw;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }
        
        /* Scope page CSS to only affect main content, not navigation/footer */
        main * {
            box-sizing: border-box;
        }
        
        /* Ensure body doesn't overflow */
        body {
            overflow-x: hidden;
            max-width: 100vw;
        }
        
        /* Exclude header and footer containers from overflow restrictions */
        header .container,
        footer .container {
            overflow: visible !important;
        }
        
        /* Prevent text size adjustment on mobile - exclude components */
        *:not(header):not(footer):not(header *):not(footer *) {
            -webkit-text-size-adjust: 100%;
            -moz-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }
        
        /* Category bar styles - Modern Redesign */
        .category-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            width: 100%;
            max-width: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 1.5rem;
            margin-bottom: 3rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        /* Ensure containers don't overflow - ONLY for main content */
        main .container {
            width: 100%;
            max-width: 100%;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        @media (min-width: 640px) {
            main .container {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            main .container {
                padding-left: 2rem;
                padding-right: 2rem;
            }
        }

        /* Category pills - Modern styling */
        .category-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .category-pills a {
            font-family: 'Azo Sans', sans-serif;
            padding: 0.625rem 1.25rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .category-pills a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .category-pills a:hover::before {
            left: 100%;
        }

        .category-pills a:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(255, 79, 79, 0.25);
        }
        
        .category-pills a.active {
            background: linear-gradient(135deg, #FF4F4F 0%, #e63939 100%);
            color: #ffffff;
            border-color: #FF4F4F;
            box-shadow: 0 4px 15px rgba(255, 79, 79, 0.4);
        }
        
        .category-pills a:not(.active) {
            background: #ffffff;
            color: #1F1F1F;
            border-color: rgba(255, 79, 79, 0.2);
        }
        
        .category-pills a:not(.active):hover {
            background: #FF4F4F;
            color: #ffffff;
            border-color: #FF4F4F;
        }

        /* Category select dropdown styling */
        .cat-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            font-family: 'Azo Sans', sans-serif;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 20 20' fill='none' stroke='%23FF4F4F' stroke-width='2'><path d='M5 7l5 5 5-5'/></svg>");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px 12px;
            transition: all 0.3s ease;
        }

        .cat-select:hover {
            background-color: #FF4F4F;
            color: white;
            border-color: #FF4F4F;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 20 20' fill='none' stroke='%23ffffff' stroke-width='2'><path d='M5 7l5 5 5-5'/></svg>");
        }

        /* Feature Cards - Premium Redesign */
        .blog-card {
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            border: 1px solid rgba(255, 79, 79, 0.08);
            border-radius: 1.5rem;
            overflow: hidden;
            color: #1F1F1F;
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.06);
        }
        
        .blog-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FF4F4F 0%, #18F1E1 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
        }
        
        .blog-card:hover::before {
            transform: scaleX(1);
        }
        
        .blog-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(255, 79, 79, 0.15), 0 8px 24px rgba(0, 0, 0, 0.1);
            border-color: rgba(255, 79, 79, 0.25);
        }

        .blog-card-image-wrapper {
            position: relative;
            overflow: hidden;
            width: 100%;
            height: 260px;
            min-height: 260px;
            max-height: 260px;
            flex-shrink: 0;
            background: linear-gradient(135deg, rgba(255, 79, 79, 0.05) 0%, rgba(24, 241, 225, 0.05) 100%);
        }
        
        .blog-card-image-wrapper::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 1;
        }
        
        .blog-card:hover .blog-card-image-wrapper::after {
            opacity: 1;
        }

        .blog-card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .blog-card:hover .blog-card-image {
            transform: scale(1.12);
        }
        
        .blog-card-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        
        .blog-card-category {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-radius: 9999px;
            margin-bottom: 0.875rem;
            background: linear-gradient(135deg, #FF4F4F 0%, #e63939 100%);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(255, 79, 79, 0.25);
            align-self: flex-start;
        }
        
        .blog-card-title {
            font-size: 1rem;
            font-weight: 200;
            line-height: 1.4;
            margin-bottom: 0.75rem;
            color: #1F1F1F;
            transition: color 0.3s ease;
            font-family: 'Azo Sans', sans-serif;
        }
        
        .blog-card-title a {
            color: inherit;
            text-decoration: none;
        }
        
        .blog-card-title a:hover {
            color: #FF4F4F;
        }
        
        .blog-card-meta {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            font-size: 0.8125rem;
            color: #6B7280;
            margin-bottom: 0.875rem;
            flex-wrap: wrap;
        }
        
        .blog-card-meta svg {
            width: 0.875rem;
            height: 0.875rem;
            flex-shrink: 0;
        }
        
        .blog-card-excerpt {
            color: #4B5563;
            line-height: 1.65;
            margin-bottom: 1.25rem;
            font-size: 0.9375rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 4.5rem;
        }
        
        .blog-card-read-more {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: #FF4F4F;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 0.5rem;
            text-decoration: none;
        }
        
        /* Ensure grid items don't stretch */
        .grid > .blog-card {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        
        /* Ensure consistent card sizing in grid */
        @media (min-width: 768px) {
            .grid.grid-cols-2 > .blog-card {
                width: 100%;
            }
        }
        
        @media (min-width: 1024px) {
            .grid.grid-cols-3 > .blog-card {
                width: 100%;
            }
        }
        
        .blog-card-read-more svg {
            width: 1rem;
            height: 1rem;
            transition: transform 0.3s ease;
        }
        
        .blog-card-read-more:hover {
            gap: 0.625rem;
            color: #e63939;
        }
        
        .blog-card-read-more:hover svg {
            transform: translateX(2px);
        }

        /* Pagination styles - Modern Design */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 4rem;
            margin-bottom: 4rem;
            flex-wrap: wrap;
        }

        .pagination-link {
            padding: 0.875rem 1.5rem;
            border-radius: 0.75rem;
            text-decoration: none;
            color: #1F1F1F;
            background: #ffffff;
            border: 2px solid rgba(255, 79, 79, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Azo Sans', sans-serif;
            font-weight: 600;
            font-size: 0.875rem;
            min-width: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .pagination-link:hover {
            background: linear-gradient(135deg, #FF4F4F 0%, #e63939 100%);
            color: #ffffff;
            border-color: #FF4F4F;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(255, 79, 79, 0.35);
        }

        .pagination-link.active {
            background: linear-gradient(135deg, #FF4F4F 0%, #e63939 100%);
            color: #ffffff;
            border-color: #FF4F4F;
            box-shadow: 0 4px 20px rgba(255, 79, 79, 0.4);
            transform: scale(1.05);
        }
        
        .pagination-ellipsis {
            padding: 0.875rem 0.5rem;
            color: #6B7280;
        }
    </style>
</head>
<body style="background: #ffffff; color: #1F1F1F; overflow-x: hidden; max-width: 100vw;">
    <main role="main" style="width: 100%; max-width: 100vw; overflow-x: hidden;">
        <section class="relative h-[70vh] min-h-[500px] flex items-center justify-center overflow-hidden" style="background-image: linear-gradient(135deg, rgba(255, 79, 79, 0.1) 0%, rgba(24, 241, 225, 0.1) 100%), url('assets/images/blogs/hero.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; padding-top: 80px;">
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/50 to-black/70"></div>
            <div class="absolute inset-0" style="background: radial-gradient(circle at 30% 50%, rgba(255, 79, 79, 0.15) 0%, transparent 50%), radial-gradient(circle at 70% 50%, rgba(24, 241, 225, 0.15) 0%, transparent 50%);"></div>
            <div class="relative z-10 text-center text-white px-4 sm:px-6 max-w-6xl mx-auto">
                <div class="inline-block mb-6 px-4 py-2 rounded-full backdrop-blur-md bg-white/10 border border-white/20">
                    <span class="text-sm font-semibold tracking-wider uppercase" style="color: #18F1E1;">Latest Insights</span>
                </div>
                <h1 class="hero-h1 text-5xl sm:text-6xl md:text-7xl lg:text-8xl xl:text-9xl mb-6 leading-tight" style="font-weight: 400; text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);">
                    <span style="color: #FF4F4F;">MIHI</span> <span style="color: #18F1E1;">BLOGS</span>
                </h1>
                <p class="hero-p text-xl sm:text-2xl md:text-3xl mb-10 text-white/95 max-w-4xl mx-auto leading-relaxed font-light" style="text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);">
                    Discover the latest trends, insights, and stories from the world of event entertainment
                </p>
                <div class="flex flex-wrap items-center justify-center gap-6 mt-8 text-white/80">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full animate-pulse" style="background: #FF4F4F;"></div>
                        <span class="text-sm font-medium">Event Tips</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full animate-pulse" style="background: #18F1E1; animation-delay: 0.2s;"></div>
                        <span class="text-sm font-medium">Industry News</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full animate-pulse" style="background: #FF4F4F; animation-delay: 0.4s;"></div>
                        <span class="text-sm font-medium">Success Stories</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-padding gradient-dark bg-white">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
                <div class="text-center mb-20">
                    <div class="inline-block mb-6 px-6 py-2 rounded-full" style="background: linear-gradient(135deg, rgba(255, 79, 79, 0.1) 0%, rgba(24, 241, 225, 0.1) 100%); border: 1px solid rgba(255, 79, 79, 0.2);">
                        <span class="text-sm font-bold uppercase tracking-wider" style="color: #FF4F4F;">Explore Our Content</span>
                    </div>
                    <h2 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl mb-6" style="color: #FF4F4F;">
                        ALL MIHI ENTERTAINMENT <span style="color: #18F1E1;">BLOGS</span>
                    </h2>
                    <div class="w-32 h-1.5 mx-auto mb-4 rounded-full" style="background: linear-gradient(to right, #FF4F4F, #18F1E1); box-shadow: 0 4px 15px rgba(255, 79, 79, 0.3);"></div>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        Stay updated with the latest insights, tips, and stories from the event entertainment industry
                    </p>
                </div>

                <div class="category-bar sticky top-20 z-20">
                    <div class="category-pills">
                        <a href="blog.php" 
                           class="<?php echo $categorySlug === '' ? 'active' : ''; ?>">
                            All Posts
                        </a>
                        <?php foreach ($pillCategories as $cat): ?>
                            <a href="blog.php?category=<?php echo urlencode($cat['slug']); ?>" 
                               class="<?php echo ($categorySlug === $cat['slug']) ? 'active' : ''; ?>">
                                <?php echo escape($cat['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($moreCategories)): ?>
                    <form method="get" class="category-select-desktop ml-auto hidden md:block">
                        <select name="category" class="cat-select px-4 py-2 pr-8 rounded-full border border-[#FF4F4F]/20 bg-white text-[#1F1F1F] text-sm font-medium cursor-pointer transition-all duration-300" onchange="this.form.submit()">
                            <option value="" <?php echo $categorySlug === '' ? 'selected' : ''; ?>>More</option>
                            <?php foreach ($moreCategories as $cat): ?>
                                <option value="<?php echo escape($cat['slug']); ?>" <?php echo ($categorySlug === $cat['slug']) ? 'selected' : ''; ?>>
                                    <?php echo escape($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($page > 1): ?>
                            <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                        <?php endif; ?>
                    </form>
                    <?php endif; ?>
                    
                    <form method="get" class="category-select-mobile ml-auto md:hidden">
                        <select name="category" class="cat-select px-4 py-2 pr-8 rounded-full border border-[#FF4F4F]/20 bg-white text-[#1F1F1F] text-sm font-medium cursor-pointer transition-all duration-300" onchange="this.form.submit()">
                            <option value="" <?php echo $categorySlug === '' ? 'selected' : ''; ?>>All categories…</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo escape($cat['slug']); ?>" <?php echo ($categorySlug === $cat['slug']) ? 'selected' : ''; ?>>
                                    <?php echo escape($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($page > 1): ?>
                            <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (empty($posts)): ?>
                    <div class="text-center py-20">
                        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full mb-6" style="background: linear-gradient(135deg, rgba(255, 79, 79, 0.1) 0%, rgba(24, 241, 225, 0.1) 100%);">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #FF4F4F; opacity: 0.5;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold mb-3" style="color: #FF4F4F;">No Posts Found</h3>
                        <p class="text-lg mb-6" style="color: #1F1F1F; opacity: 0.7;">Check back soon for new content!</p>
                        <a href="blog.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-full font-semibold transition-all duration-300" style="background: #FF4F4F; color: #ffffff;">
                            View All Posts
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 lg:gap-8 animate-fade-in-up" style="grid-auto-rows: auto;">
                        <?php foreach ($posts as $index => $post): ?>
                            <article class="blog-card" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                <div class="blog-card-image-wrapper">
                                    <?php if (!empty($post['featured_image'])): 
                                        // FIX: Clean the path if it starts with "../"
                                        $imagePath = $post['featured_image'];
                                        if (strpos($imagePath, '../') === 0) {
                                            $imagePath = substr($imagePath, 3); // Removes the first 3 chars (../)
                                        }
                                    ?>
                                        <a href="post/<?php echo escape($post['slug']); ?>.html" class="block h-full">
                                            <img src="<?php echo escape($imagePath); ?>" 
                                                 alt="<?php echo escape($post['title']); ?>" 
                                                 class="blog-card-image">
                                        </a>
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #FF4F4F; opacity: 0.3;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="blog-card-content">
                                    <?php if (!empty($post['category_name'])): ?>
                                        <span class="blog-card-category">
                                            <?php echo escape($post['category_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <h2 class="blog-card-title">
                                        <a href="post/<?php echo escape($post['slug']); ?>.html">
                                            <?php echo escape($post['title']); ?>
                                        </a>
                                    </h2>
                                    
                                    <div class="blog-card-meta">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <?php echo escape($post['author']); ?>
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <?php echo date('F j, Y', strtotime($post['published_at'] ?: $post['created_at'])); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($post['excerpt']): ?>
                                        <p class="blog-card-excerpt">
                                            <?php echo escape($post['excerpt']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <a href="post/<?php echo escape($post['slug']); ?>.html" class="blog-card-read-more">
                                        Read More
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $categorySlug !== '' ? '&amp;category=' . urlencode($categorySlug) : ''; ?>" 
                               class="pagination-link">
                                ← Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?page=1<?php echo $categorySlug !== '' ? '&amp;category=' . urlencode($categorySlug) : ''; ?>" 
                               class="pagination-link">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $categorySlug !== '' ? '&amp;category=' . urlencode($categorySlug) : ''; ?>" 
                               class="pagination-link <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $totalPages; ?><?php echo $categorySlug !== '' ? '&amp;category=' . urlencode($categorySlug) : ''; ?>" 
                               class="pagination-link"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $categorySlug !== '' ? '&amp;category=' . urlencode($categorySlug) : ''; ?>" 
                               class="pagination-link">
                                Next →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <script src="assets/components/navigation.js"></script>
    
    <script src="assets/components/footer.js"></script>
    
    <script src="assets/components/back-to-top.js"></script>
    
</body>
</html>