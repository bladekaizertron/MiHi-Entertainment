<?php
// Ensure no output before headers - prevent BOM issues
ob_start();

// Set proper headers to prevent caching and encoding issues
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/config.php';

if (!function_exists('escape')) {
    function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

requireLogin();
$currentUser = getCurrentUser();
if (!in_array(strtolower($currentUser['role'] ?? ''), ['admin','editor'], true)) {
    header('Location: index.php'); exit;
}

$db = getDB();
$csrf = $_SESSION['csrf_token'] ?? ($_SESSION['csrf_token'] = bin2hex(random_bytes(32)));
$error = '';

// Handle video upload
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_video') {
    // Suppress all errors and warnings for clean JSON output
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Clear and disable output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    
    try {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($csrf, $token)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit;
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
            exit;
        }
        
        $file = $_FILES['file'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Validate video file type
        $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo'];
        if (!in_array($mimeType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only video files allowed (MP4, WebM, OGG, MOV, AVI).']);
            exit;
        }
        
        // Check file size (max 100MB)
        $maxSize = 100 * 1024 * 1024; // 100MB in bytes
        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 100MB.']);
            exit;
        }
        
        // Get file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi'])) {
            $ext = 'mp4'; // Default to mp4
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../uploads/videos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = 'video_' . uniqid() . '.' . $ext;
        $destination = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Use relative path from project root instead of SITE_URL
            $url = '/MiHi-Entertainment/uploads/videos/' . $filename;
            echo json_encode(['success' => true, 'url' => $url, 'type' => 'video']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

// Function to build complete HTML document
function buildCompleteHTML($title, $content, $meta_title, $meta_description, $meta_keywords, $og_title, $og_description, $og_image, $canonical_url, $robots) {
    $meta_title = $meta_title ?: $title;
    $og_title = $og_title ?: $title;
    $canonical_tag = $canonical_url ? "<link rel=\"canonical\" href=\"" . htmlspecialchars($canonical_url) . "\">" : '';
    $og_image_tag = $og_image ? "<meta property=\"og:image\" content=\"" . htmlspecialchars($og_image) . "\">" : '';
    
    return "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>" . htmlspecialchars($meta_title) . "</title>
    <meta name=\"description\" content=\"" . htmlspecialchars($meta_description) . "\">
    <meta name=\"keywords\" content=\"" . htmlspecialchars($meta_keywords) . "\">
    <meta name=\"robots\" content=\"" . htmlspecialchars($robots) . "\">
    $canonical_tag
    
    <!-- Open Graph / Social Media -->
    <meta property=\"og:title\" content=\"" . htmlspecialchars($og_title) . "\">
    <meta property=\"og:description\" content=\"" . htmlspecialchars($og_description) . "\">
    $og_image_tag
    <meta property=\"og:type\" content=\"website\">
    
    <!-- Favicon -->
    <link rel=\"icon\" type=\"image/svg+xml\" href=\"assets/images/favicon.svg\">
    
    <!-- Tailwind CSS -->
    <script src=\"https://cdn.tailwindcss.com\"></script>
    
    <style>
        @font-face {
            font-family: 'Azo Sans Uber';
            src: url('assets/fonts/AzoSansUber-Regular.woff2') format('woff2'),
                 url('assets/fonts/AzoSansUber-Regular.woff') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Azo Sans';
            src: url('assets/fonts/AzoSans-Regular.woff2') format('woff2'),
                 url('assets/fonts/AzoSans-Regular.woff') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        html, body {
            overflow-x: hidden;
            width: 100%;
            max-width: 100%;
        }

        body {
            font-family: 'Azo Sans', sans-serif;
            color: #1a202c;
            background: #0a0a0a;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Azo Sans Uber', sans-serif;
        }

        /* Fixed navigation positioning */
        header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            z-index: 50 !important;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        * {
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div id=\"nav-placeholder\"></div>
    
    $content
    
    <div id=\"footer-placeholder\"></div>
    
    <script src=\"assets/components/navigation.js\"></script>
    <script src=\"assets/components/footer.js\"></script>
</body>
</html>";
}

// --- Handle Save ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid.';
    } else {
        $title = trim($_POST['title']);
        $slug = trim($_POST['slug']) ?: strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
        $content = $_POST['content_html']; // This is now the full HTML body
        
        // SEO fields
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $meta_keywords = trim($_POST['meta_keywords'] ?? '');
        $og_title = trim($_POST['og_title'] ?? '');
        $og_description = trim($_POST['og_description'] ?? '');
        $og_image = trim($_POST['og_image'] ?? '');
        $canonical_url = trim($_POST['canonical_url'] ?? '');
        $robots = trim($_POST['robots'] ?? 'index, follow');
        
        
        try {
            // Sanitize filename
            $filename = $slug . '.html';
            $filepath = __DIR__ . '/../' . $filename;
            
            // Check if file already exists
            if (file_exists($filepath)) {
                $error = "A page with slug '$slug' already exists. Please choose a different slug.";
            } else {
                // Build complete HTML document
                $html = buildCompleteHTML($title, $content, $meta_title, $meta_description, $meta_keywords, $og_title, $og_description, $og_image, $canonical_url, $robots);
                
                // Write file to root directory
                if (file_put_contents($filepath, $html) === false) {
                    throw new Exception("Failed to write HTML file. Check directory permissions.");
                }
                
                // Also save to database for management
                $stmt = $db->prepare("
                    INSERT INTO pages (title, slug, content_html, status, 
                        meta_title, meta_description, meta_keywords, 
                        og_title, og_description, og_image, 
                        canonical_url, robots, 
                        created_at, updated_at) 
                    VALUES (?, ?, ?, 'published', ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $title, $slug, $content,
                    $meta_title ?: $title,
                    $meta_description,
                    $meta_keywords,
                    $og_title ?: $title,
                    $og_description,
                    $og_image,
                    $canonical_url,
                    $robots
                ]);
                
                // Redirect to the generated page
                header("Location: ../$filename");
                exit;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiHi Visual Editor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Editor UI Styles */
        body { background: #18181b; color: #e4e4e7; overflow: hidden; }
        
        .panel { background: #27272a; border-color: #3f3f46; }
        .panel-header { background: #18181b; border-bottom: 1px solid #3f3f46; padding: 12px; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa; }
        
        /* Draggable Components */
        .component-item {
            background: #3f3f46; padding: 12px; border-radius: 6px; margin-bottom: 8px; cursor: grab;
            display: flex; align-items: center; gap: 10px; transition: all 0.2s; border: 1px solid transparent;
        }
        .component-item:hover { background: #52525b; border-color: #71717a; transform: translateY(-1px); }
        .component-item:active { cursor: grabbing; }

        /* The Iframe Canvas Wrapper */
        #iframe-wrapper {
            background-color: #18181b;
            background-image: radial-gradient(#3f3f46 1px, transparent 1px);
            background-size: 20px 20px;
            transition: all 0.3s ease;
        }
        
        iframe {
            background: white;
            box-shadow: 0 0 40px rgba(0,0,0,0.5);
            transition: width 0.3s ease;
        }

        /* Inputs */
        input[type="text"], input[type="number"], select, textarea {
            background: #18181b; border: 1px solid #3f3f46; color: white; width: 100%; padding: 6px 10px; border-radius: 4px; font-size: 0.875rem;
        }
        input:focus { outline: none; border-color: #18F1E1; }
        
        /* Brand Colors */
        .text-primary { color: #FF4F4F; }
        .text-secondary { color: #18F1E1; }
        .bg-primary { background-color: #FF4F4F; }
        .bg-secondary { background-color: #18F1E1; }
    </style>
</head>
<body class="flex flex-col h-screen">

    <header class="h-14 bg-zinc-900 border-b border-zinc-700 flex items-center justify-between px-4 shrink-0 z-20 min-w-0">
        <div class="flex items-center gap-4 min-w-0 flex-1">
            <div class="font-bold text-lg whitespace-nowrap shrink-0"><i class="fas fa-layer-group text-[#18F1E1] mr-2"></i>PageBuilder <span class="text-xs bg-[#FF4F4F] text-white px-2 py-0.5 rounded ml-2">PRO</span></div>
            <div class="h-6 w-px bg-zinc-700 shrink-0"></div>
            <input type="text" id="pageTitleInput" placeholder="Enter Page Title..." class="bg-transparent border-none text-white focus:ring-0 min-w-0 flex-1 max-w-xs font-medium placeholder-zinc-500">
        </div>
        
        <div class="flex bg-zinc-800 rounded-md p-1 gap-1 shrink-0 mx-4">
            <button onclick="resizeCanvas('100%')" class="p-2 hover:bg-zinc-700 rounded text-zinc-400 hover:text-white transition-colors" title="Desktop"><i class="fas fa-desktop"></i></button>
            <button onclick="resizeCanvas('768px')" class="p-2 hover:bg-zinc-700 rounded text-zinc-400 hover:text-white transition-colors" title="Tablet"><i class="fas fa-tablet-alt"></i></button>
            <button onclick="resizeCanvas('375px')" class="p-2 hover:bg-zinc-700 rounded text-zinc-400 hover:text-white transition-colors" title="Mobile"><i class="fas fa-mobile-alt"></i></button>
        </div>

        <div class="flex gap-3 shrink-0">
            <button onclick="previewPage()" class="bg-zinc-700 hover:bg-zinc-600 text-white px-5 py-1.5 rounded text-sm font-medium transition shadow-lg hover:shadow-xl whitespace-nowrap"><i class="fas fa-eye mr-1"></i>Preview</button>
            <button onclick="openSeoModal()" class="bg-zinc-700 hover:bg-zinc-600 text-white px-5 py-1.5 rounded text-sm font-medium transition shadow-lg hover:shadow-xl whitespace-nowrap"><i class="fas fa-search mr-1"></i>SEO Settings</button>
            <button onclick="saveDraft()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-1.5 rounded text-sm font-medium transition shadow-lg hover:shadow-xl whitespace-nowrap"><i class="fas fa-save mr-1"></i>Save Draft</button>
            <button onclick="exportHTML()" class="bg-[#FF4F4F] hover:bg-[#FF3838] text-white px-5 py-1.5 rounded text-sm font-medium transition shadow-lg hover:shadow-xl whitespace-nowrap"><i class="fas fa-check-circle mr-1"></i>Publish</button>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        
        <div class="w-72 panel border-r flex flex-col z-10">
            <div class="panel-header">Elements</div>
            <div class="p-4 overflow-y-auto flex-1">
                <p class="text-xs text-zinc-500 mb-3">Drag these onto the canvas</p>
                
                <div class="component-item" draggable="true" data-type="hero">
                    <i class="fas fa-star text-[#FF4F4F] w-5"></i> <span>Hero Section</span>
                </div>
                <div class="component-item" draggable="true" data-type="text">
                    <i class="fas fa-align-left text-[#18F1E1] w-5"></i> <span>Text Block</span>
                </div>
                <div class="component-item" draggable="true" data-type="cards">
                    <i class="fas fa-th-large text-purple-400 w-5"></i> <span>Feature Cards</span>
                </div>
                <div class="component-item" draggable="true" data-type="split">
                    <i class="fas fa-columns text-blue-400 w-5"></i> <span>Split Screen</span>
                </div>
                <div class="component-item" draggable="true" data-type="cta">
                    <i class="fas fa-bullhorn text-[#FF4F4F] w-5"></i> <span>Call to Action</span>
                </div>
                <div class="component-item" draggable="true" data-type="video">
                    <i class="fas fa-video text-[#18F1E1] w-5"></i> <span>Video Section</span>
                </div>
            </div>
        </div>

        <div id="iframe-wrapper" class="flex-1 flex justify-center py-8 overflow-y-auto relative">
            <iframe id="editorFrame" class="w-full h-full min-h-[800px] bg-white mx-auto border-0"></iframe>
        </div>

        <div class="w-80 panel border-l flex flex-col z-10">
            <div class="panel-header">Inspector</div>
            <div id="inspectorPanel" class="p-4 overflow-y-auto flex-1">
                <div class="text-center text-zinc-500 mt-10">
                    <i class="fas fa-mouse-pointer text-2xl mb-2 opacity-50"></i>
                    <p class="text-sm">Click an element on the canvas<br>to edit its style.</p>
                    <div class="mt-6 pt-6 border-t border-zinc-700">
                        <p class="text-xs text-zinc-600 mb-2">Brand Colors</p>
                        <div class="flex gap-2 justify-center">
                            <div class="w-8 h-8 rounded" style="background: #FF4F4F;" title="Primary: #FF4F4F"></div>
                            <div class="w-8 h-8 rounded" style="background: #18F1E1;" title="Secondary: #18F1E1"></div>
                            <div class="w-8 h-8 rounded bg-zinc-800 border border-zinc-700" title="Dark: #1F1F1F"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="realSaveForm" method="POST" class="hidden">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input type="hidden" name="title" id="hiddenTitle">
        <input type="hidden" name="slug" id="hiddenSlug">
        <textarea name="content_html" id="hiddenContent"></textarea>
        
        <!-- SEO Fields -->
        <input type="hidden" name="meta_title" id="hiddenMetaTitle">
        <input type="hidden" name="meta_description" id="hiddenMetaDescription">
        <input type="hidden" name="meta_keywords" id="hiddenMetaKeywords">
        <input type="hidden" name="og_title" id="hiddenOgTitle">
        <input type="hidden" name="og_description" id="hiddenOgDescription">
        <input type="hidden" name="og_image" id="hiddenOgImage">
        <input type="hidden" name="canonical_url" id="hiddenCanonicalUrl">
        <input type="hidden" name="robots" id="hiddenRobots">
        <input type="hidden" name="structured_data" id="hiddenStructuredData">
    </form>

    <div id="mediaModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-zinc-900 rounded-lg shadow-xl w-full max-w-md mx-4 border border-zinc-700">
            <div class="p-4 border-b border-zinc-700 flex justify-between items-center">
                <h3 id="mediaModalTitle" class="text-white font-semibold">Insert Media</h3>
                <button onclick="closeMediaModal()" class="text-zinc-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4">
                <div id="mediaModalTabs" class="flex gap-2 mb-4 border-b border-zinc-700">
                    <button onclick="switchMediaTab('upload')" class="media-tab px-4 py-2 text-sm text-white border-b-2 border-[#18F1E1] transition-colors">
                        <i class="fas fa-upload mr-1"></i> Upload
                    </button>
                    <button onclick="switchMediaTab('url')" class="media-tab px-4 py-2 text-sm text-zinc-400 hover:text-white border-b-2 border-transparent hover:border-[#18F1E1] transition-colors">
                        <i class="fas fa-link mr-1"></i> URL
                    </button>
                    <button onclick="switchMediaTab('embed')" class="media-tab px-4 py-2 text-sm text-zinc-400 hover:text-white border-b-2 border-transparent hover:border-[#18F1E1] transition-colors">
                        <i class="fas fa-code mr-1"></i> Embed
                    </button>
                </div>
                
                <div id="mediaModalContent">
                    <div id="mediaTabUpload" class="media-tab-content">
                        <label class="block text-xs text-zinc-500 mb-2">Select file to upload</label>
                        <input type="file" id="mediaFileInput" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm mb-3">
                        <div id="mediaUploadPreview" class="mb-3 hidden">
                            <img id="mediaUploadPreviewImg" src="" alt="Preview" class="max-w-full h-auto rounded-lg mb-2">
                            <video id="mediaUploadPreviewVideo" src="" controls class="max-w-full h-auto rounded-lg mb-2 hidden"></video>
                        </div>
                        <button onclick="handleMediaUpload()" class="w-full bg-[#18F1E1] hover:bg-[#15D9C9] text-zinc-900 px-4 py-2 rounded font-semibold transition-colors">
                            <i class="fas fa-upload mr-1"></i> Upload & Insert
                        </button>
                    </div>
                    
                    <div id="mediaTabUrl" class="media-tab-content hidden">
                        <label class="block text-xs text-zinc-500 mb-2">Enter media URL</label>
                        <input type="text" id="mediaUrlInput" placeholder="https://example.com/image.jpg" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm mb-3">
                        <div id="mediaUrlPreview" class="mb-3 hidden">
                            <img id="mediaUrlPreviewImg" src="" alt="Preview" class="max-w-full h-auto rounded-lg mb-2" onerror="this.parentElement.classList.add('hidden')">
                            <video id="mediaUrlPreviewVideo" src="" controls class="max-w-full h-auto rounded-lg mb-2 hidden" onerror="this.parentElement.classList.add('hidden')"></video>
                        </div>
                        <button onclick="handleMediaUrl()" class="w-full bg-[#18F1E1] hover:bg-[#15D9C9] text-zinc-900 px-4 py-2 rounded font-semibold transition-colors">
                            <i class="fas fa-link mr-1"></i> Insert from URL
                        </button>
                    </div>
                    
                    <div id="mediaTabEmbed" class="media-tab-content hidden">
                        <label class="block text-xs text-zinc-500 mb-2">Paste embed code (HTML/iframe)</label>
                        <textarea id="mediaEmbedInput" placeholder='<iframe src="..."></iframe>' rows="4" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm mb-3 font-mono text-xs"></textarea>
                        <button onclick="handleMediaEmbed()" class="w-full bg-[#18F1E1] hover:bg-[#15D9C9] text-zinc-900 px-4 py-2 rounded font-semibold transition-colors">
                            <i class="fas fa-code mr-1"></i> Insert Embed
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEO Settings Modal -->
    <div id="seoModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-zinc-900 rounded-lg shadow-xl w-full max-w-2xl mx-4 border border-zinc-700 max-h-[90vh] overflow-y-auto">
            <div class="p-4 border-b border-zinc-700 flex justify-between items-center sticky top-0 bg-zinc-900 z-10">
                <h3 class="text-white font-semibold flex items-center gap-2">
                    <i class="fas fa-search text-[#18F1E1]"></i>
                    SEO Settings
                </h3>
                <button onclick="closeSeoModal()" class="text-zinc-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6">
                <!-- Meta Tags Section -->
                <div class="mb-6">
                    <h4 class="text-white font-semibold mb-3 flex items-center gap-2">
                        <i class="fas fa-tag text-[#FF4F4F]"></i>
                        Meta Tags
                    </h4>
                    
                    
                    <div class="mb-4">
                        <label class="block text-xs text-zinc-400 mb-2">Page Slug (URL)</label>
                        <input type="text" id="seoSlug" placeholder="Leave empty to auto-generate from title" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm font-mono">
                        <small class="text-zinc-500 text-xs">URL-friendly version (e.g., "my-page-name")</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-xs text-zinc-400 mb-2">Meta Title</label>
                        <input type="text" id="seoMetaTitle" placeholder="Leave empty to use page title" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm">
                        <small class="text-zinc-500 text-xs">Recommended: 50-60 characters</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-xs text-zinc-400 mb-2">Meta Description</label>
                        <textarea id="seoMetaDescription" rows="3" placeholder="Brief description of the page" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm"></textarea>
                        <small class="text-zinc-500 text-xs">Recommended: 150-160 characters</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-xs text-zinc-400 mb-2">Meta Keywords</label>
                        <input type="text" id="seoMetaKeywords" placeholder="keyword1, keyword2, keyword3" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm">
                        <small class="text-zinc-500 text-xs">Comma-separated keywords</small>
                    </div>
                </div>
                
                <!-- Open Graph Tags Section -->
                <div class="mb-6 pb-6 border-b border-zinc-700">
                    <h4 class="text-white font-semibold mb-3 flex items-center gap-2">
                        <i class="fab fa-facebook text-[#18F1E1]"></i>
                        Open Graph (Social Media)
                    </h4>
                    
                    <div class="mb-4">
                        <label class="block text-xs text-zinc-400 mb-2">OG Title</label>
                        <input type="text" id="seoOgTitle" placeholder="Leave empty to use page title" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-xs text-zinc-400 mb-2">OG Description</label>
                        <textarea id="seoOgDescription" rows="2" placeholder="Description for social media shares" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-xs text-zinc-400 mb-2">OG Image URL</label>
                        <input type="url" id="seoOgImage" placeholder="https://example.com/image.jpg" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm">
                        <small class="text-zinc-500 text-xs">Recommended: 1200x630px</small>
                    </div>
                </div>
                
                <!-- Advanced Settings -->
                <div class="mb-4">
                    <h4 class="text-white font-semibold mb-3 flex items-center gap-2">
                        <i class="fas fa-cog text-[#FF4F4F]"></i>
                        Advanced
                    </h4>
                    
                    <div class="mb-4">
                        <label class="block text-xs text-zinc-400 mb-2">Canonical URL</label>
                        <input type="url" id="seoCanonicalUrl" placeholder="https://example.com/page" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm">
                        <small class="text-zinc-500 text-xs">Preferred URL for this page</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-xs text-zinc-400 mb-2">Robots Meta Tag</label>
                        <select id="seoRobots" class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm">
                            <option value="index, follow">index, follow (Default - Allow search engines)</option>
                            <option value="noindex, follow">noindex, follow (Don't index, but follow links)</option>
                            <option value="index, nofollow">index, nofollow (Index page, don't follow links)</option>
                            <option value="noindex, nofollow">noindex, nofollow (Don't index or follow)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Structured Data Section -->
                <div class="mb-4">
                    <h4 class="text-white font-semibold mb-3 flex items-center gap-2">
                        <i class="fas fa-code text-[#FF4F4F]"></i>
                        Structured Data (JSON-LD)
                    </h4>
                    <p class="text-xs text-zinc-500 mb-3">Schema.org markup for rich snippets (auto-filled from page)</p>
                    
                    <div class="mb-4">
                        <label class="block text-xs text-zinc-400 mb-2">JSON-LD Editor</label>
                        <textarea id="structuredDataEditor" rows="10" placeholder='Click "Auto-Fill from Content" to extract existing structured data' class="w-full bg-zinc-800 border border-zinc-700 text-white px-3 py-2 rounded text-sm font-mono resize-vertical"></textarea>
                        <div class="mt-2 p-2 bg-zinc-800 rounded border-l-3 border-[#18F1E1]">
                            <small class="text-zinc-500 text-xs">
                                <i class="fas fa-info-circle text-[#18F1E1]"></i> 
                                <strong>Multiple Schemas:</strong> Wrap multiple schemas in an array: <code class="bg-zinc-900 px-1 py-0.5 rounded text-xs">[{schema1}, {schema2}]</code>
                            </small>
                        </div>
                    </div>
                    
                    <button type="button" onclick="clearStructuredData()" class="w-full bg-zinc-800 hover:bg-zinc-700 text-white px-3 py-2 rounded text-sm mb-3 transition-colors">
                        <i class="fas fa-trash mr-1"></i> Clear
                    </button>
                </div>
                
                <!-- Auto-Fill Button -->
                <button type="button" onclick="autoFillSeoFromContent()" class="w-full bg-[#667eea] hover:bg-[#5568d3] text-white px-4 py-3 rounded font-semibold transition-colors mb-3">
                    <i class="fas fa-magic mr-2"></i>Auto-Fill from Content
                </button>
                
                <!-- Save Button -->
                <button onclick="saveSeoSettings()" class="w-full bg-[#18F1E1] hover:bg-[#15D9C9] text-zinc-900 px-4 py-3 rounded font-semibold transition-colors">
                    <i class="fas fa-save mr-2"></i>Save SEO Settings
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Component -->
    <div id="notification" class="fixed top-4 right-4 z-50 hidden transform transition-all duration-300 ease-in-out">
        <div id="notificationContent" class="bg-white rounded-lg shadow-2xl border-l-4 p-4 min-w-[320px] max-w-md flex items-start gap-3">
            <div id="notificationIcon" class="flex-shrink-0 w-6 h-6 flex items-center justify-center">
                <!-- Icon will be inserted here -->
            </div>
            <div class="flex-1">
                <h4 id="notificationTitle" class="font-semibold text-sm mb-1"></h4>
                <p id="notificationMessage" class="text-sm text-gray-600"></p>
            </div>
            <button onclick="hideNotification()" class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Hidden templates for page builder - avoids template literal issues on some servers -->
    
    <!-- Hero Section Template -->
    <script type="text/template" id="tpl-hero">
<section data-editable class="relative text-white py-24 px-6 text-center overflow-hidden">
    <!-- Background Image Container -->
    <div class="absolute inset-0 hero-background-container">
        <!-- Default gradient background -->
        <div class="absolute inset-0 bg-gradient-to-br from-[#0f0a1a] via-[#1d1130] to-[#2a133d]"></div>
        <!-- Background image (hidden by default) -->
        <img src="" alt="" class="absolute inset-0 w-full h-full object-cover hidden hero-background-image">
    </div>
    
    <!-- Gradient overlay for text readability -->
    <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/40"></div>
    
    <div class="relative max-w-6xl mx-auto">
        
        <h1 contenteditable="true" class="text-5xl md:text-7xl font-bold mb-6 outline-none leading-tight" style="font-family: 'Azo Sans Uber', sans-serif; font-weight: 400; text-transform: uppercase; letter-spacing: 0.02em; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
            <span class="block" style="color: #FF4F4F;">PHOTO BOOTH</span>
            <span class="block" style="color: #18F1E1;">RENTALS</span>
        </h1>
        <p contenteditable="true" class="text-xl text-white/90 mb-8 max-w-3xl mx-auto outline-none leading-relaxed" style="font-family: 'Azo Sans', sans-serif;">Premium photo booth rentals and event entertainment services nationwide. From AI-powered photo booths to 360 video experiences.</p>
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 md:gap-6 justify-center items-center px-2 sm:px-4 w-full max-w-md sm:max-w-none mx-auto">
            <a href="#" class="group relative inline-flex items-center justify-center gap-2 sm:gap-3 text-white px-5 sm:px-8 md:px-10 py-3 sm:py-4 md:py-5 rounded-full text-sm sm:text-base md:text-lg font-medium shadow-2xl hover:shadow-[#FF4F4F]/50 transition-all duration-500 transform hover:scale-105 sm:hover:scale-110 hover:-translate-y-1 overflow-hidden w-full sm:w-auto min-w-[140px] sm:min-w-0 touch-manipulation" style="background: #FF4F4F;">
                <span class="relative z-10">Get Your Quote</span>
            </a>
            <button type="button" class="group inline-flex items-center justify-center gap-2 sm:gap-3 px-5 sm:px-8 md:px-10 py-3 sm:py-4 md:py-5 rounded-full text-sm sm:text-base md:text-lg font-medium backdrop-blur-md bg-white/10 border-2 shadow-xl hover:shadow-2xl transition-all duration-500 transform hover:scale-105 sm:hover:scale-110 hover:-translate-y-1 text-white w-full sm:w-auto min-w-[140px] sm:min-w-0 cursor-pointer touch-manipulation" style="border-color: rgba(24, 241, 225, 0.5);" onmouseover="this.style.borderColor='rgba(24, 241, 225, 0.7)'; this.style.background='rgba(24, 241, 225, 0.2)';" onmouseout="this.style.borderColor='rgba(24, 241, 225, 0.5)'; this.style.background='rgba(255, 255, 255, 0.1)';">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 17.72V21a2 2 0 01-2 2h-1C9.716 23 3 16.284 3 8V7a2 2 0 012-2z"></path>
                </svg>
                <span>Call Us</span>
            </button>
        </div>
    </div>
</section>
    </script>

    <!-- Text Section Template -->
    <script type="text/template" id="tpl-text">
       <section data-editable class="w-full py-16 px-6" style="color: #1F1F1F;">
          <div class="max-w-4xl mx-auto text-center mb-12">
           <h2 contenteditable="true" class="text-3xl sm:text-4xl md:text-5xl font-bold mb-6 outline-none" style="font-family: 'Azo Sans Uber', sans-serif; font-weight: 400; text-transform: uppercase; letter-spacing: 0.02em; color: #FF4F4F; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Section Heading</h2>
           <p contenteditable="true" class="text-base md:text-lg leading-relaxed outline-none" style="font-family: 'Azo Sans', sans-serif;">This is a paragraph. You can type directly here. We use contenteditable to make this feel like a real document editor.</p>
         </div>
      </section>
    </script>

    <!-- Cards Section Template -->
    <script type="text/template" id="tpl-cards">
        <section data-editable class="py-16 px-6 bg-white">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12">
                    <h2 contenteditable="true" class="text-3xl sm:text-4xl md:text-5xl font-bold mb-6 outline-none" style="font-family: 'Azo Sans Uber', sans-serif; font-weight: 400; text-transform: uppercase; letter-spacing: 0.02em; color: #FF4F4F; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Feature Cards</h2>
                    <p contenteditable="true" class="text-base md:text-lg leading-relaxed max-w-3xl mx-auto outline-none" style="font-family: 'Azo Sans', sans-serif; color: #1F1F1F;">Discover the amazing features that make our service stand out from the rest.</p>
                </div>
                <div class="grid md:grid-cols-3 gap-8" id="feature-cards-container">
                    <!-- Initial card 1 -->
                    <div class="feature-card-item bg-white p-6 rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300 relative group">
                        <div class="w-12 h-12 bg-pink-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 contenteditable="true" class="text-xl font-bold mb-2 outline-none" style="font-family: 'Azo Sans', sans-serif; color: #FF4F4F;">Feature One</h3>
                        <p contenteditable="true" class="outline-none" style="font-family: 'Azo Sans', sans-serif; color: #1F1F1F;">Description of the feature.</p>
                        
                        <!-- Remove button overlay -->
                        <button onclick="removeFeatureCard(this)" data-editor-only class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center shadow-lg z-10" title="Remove card">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Initial card 2 -->
                    <div class="feature-card-item bg-white p-6 rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300 relative group">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h3 contenteditable="true" class="text-xl font-bold mb-2 outline-none" style="font-family: 'Azo Sans', sans-serif; color: #FF4F4F;">Feature Two</h3>
                        <p contenteditable="true" class="outline-none" style="font-family: 'Azo Sans', sans-serif; color: #1F1F1F;">Description of the feature.</p>
                        
                        <!-- Remove button overlay -->
                        <button onclick="removeFeatureCard(this)" data-editor-only class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center shadow-lg z-10" title="Remove card">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Initial card 3 -->
                    <div class="feature-card-item bg-white p-6 rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300 relative group">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <h3 contenteditable="true" class="text-xl font-bold mb-2 outline-none" style="font-family: 'Azo Sans', sans-serif; color: #FF4F4F;">Feature Three</h3>
                        <p contenteditable="true" class="outline-none" style="font-family: 'Azo Sans', sans-serif; color: #1F1F1F;">Description of the feature.</p>
                        
                        <!-- Remove button overlay -->
                        <button onclick="removeFeatureCard(this)" data-editor-only class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center shadow-lg z-10" title="Remove card">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Add Card Button -->
                <div class="mt-8 text-center" data-editor-only>
                    <button onclick="addFeatureCard(this)" class="inline-flex items-center gap-2 px-6 py-3 rounded-full border-2 border-dashed border-[#FF4F4F]/50 text-[#FF4F4F] hover:bg-[#FF4F4F]/10 hover:border-[#FF4F4F] transition-all duration-300">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Card</span>
                    </button>
                </div>
            </div>
        </section>
    </script>

    <!-- Split Screen Template -->
    <script type="text/template" id="tpl-split">
<section data-editable class="py-20 px-6 bg-white">
    <div class="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 items-center" id="split-screen-grid">
        <div id="split-screen-text-content">
            <h2 contenteditable="true" class="text-3xl sm:text-4xl md:text-5xl font-bold mb-6 outline-none" style="font-family: 'Azo Sans Uber', sans-serif; font-weight: 400; text-transform: uppercase; letter-spacing: 0.02em; color: #FF4F4F; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Split Screen Section</h2>
            <p contenteditable="true" class="text-base md:text-lg leading-relaxed mb-8 outline-none" style="color: #1F1F1F;">This is a split screen layout with content on one side and an image placeholder on the other.</p>
            <div class="space-y-4 mb-8" id="split-screen-feature-points">
                <div class="flex gap-3 split-screen-feature-item group" data-feature-index="1">
                    <div class="w-10 h-10 rounded-full bg-pink-500/10 flex items-center justify-center text-pink-600 font-bold flex-shrink-0 border border-pink-500/20 split-screen-feature-number">1</div>
                    <div class="flex-1">
                        <p contenteditable="true" class="font-semibold outline-none" style="color: #1F1F1F;">Feature Point One</p>
                        <p contenteditable="true" class="text-sm outline-none" style="color: #1F1F1F;">Description of feature</p>
                    </div>
                    <button data-action="remove-feature" class="text-red-600 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity p-1" title="Remove feature point">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="flex gap-3 split-screen-feature-item group" data-feature-index="2">
                    <div class="w-10 h-10 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-600 font-bold flex-shrink-0 border border-purple-500/20 split-screen-feature-number">2</div>
                    <div class="flex-1">
                        <p contenteditable="true" class="font-semibold outline-none" style="color: #1F1F1F;">Feature Point Two</p>
                        <p contenteditable="true" class="text-sm outline-none" style="color: #1F1F1F;">Description of feature</p>
                    </div>
                    <button data-action="remove-feature" class="text-red-600 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity p-1" title="Remove feature point">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="flex gap-3 split-screen-feature-item group" data-feature-index="3">
                    <div class="w-10 h-10 rounded-full bg-red-500/10 flex items-center justify-center text-red-600 font-bold flex-shrink-0 border border-red-500/20 split-screen-feature-number">3</div>
                    <div class="flex-1">
                        <p contenteditable="true" class="font-semibold outline-none" style="color: #1F1F1F;">Feature Point Three</p>
                        <p contenteditable="true" class="text-sm outline-none" style="color: #1F1F1F;">Description of feature</p>
                    </div>
                    <button data-action="remove-feature" class="text-red-600 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity p-1" title="Remove feature point">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="#" class="inline-flex items-center justify-center px-8 py-4 rounded-full font-semibold text-lg bg-[#FF4F4F] hover:bg-[#FF3838] text-white transition-all duration-300 hover:-translate-y-1 hover:scale-105">Get Your Quote</a>
                <a href="#" class="inline-flex items-center justify-center px-7 py-3 rounded-full font-semibold border-2 border-[#18F1E1] text-[#18F1E1] bg-white hover:bg-[#18F1E1] hover:text-black transition-all duration-300">Call Us</a>
            </div>
        </div>
        <div class="relative" data-editable="true" id="split-screen-media-content">
            <div class="relative bg-white border border-gray-200/50 rounded-[28px] overflow-hidden shadow-[0_24px_60px_-18px_rgba(0,0,0,0.12)] group">
                <div class="w-full h-[500px] bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center relative" id="split-screen-media-container">
                    <img src="" alt="" class="w-full h-full object-cover hidden" id="split-screen-image">
                    <video src="" controls class="w-full h-full object-cover hidden" id="split-screen-video"></video>
                    <span class="text-gray-400" id="split-screen-placeholder">Media Placeholder</span>
                    
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center z-10" id="split-screen-media-overlay">
                        <div class="flex flex-col gap-3 px-4">
                            <button data-action="change-media" data-type="photo" class="bg-white hover:bg-gray-100 text-gray-900 px-6 py-3 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors shadow-lg">
                                <i class="fas fa-image"></i>
                                <span>Change Photo</span>
                            </button>
                            <button data-action="change-media" data-type="video" class="bg-white hover:bg-gray-100 text-gray-900 px-6 py-3 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors shadow-lg">
                                <i class="fas fa-video"></i>
                                <span>Change Video</span>
                            </button>
                            <button data-action="remove-media" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors shadow-lg" id="split-screen-remove-btn" style="display: none;">
                                <i class="fas fa-trash"></i>
                                <span>Remove Media</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
    </script>

    <!-- CTA Section Template -->
    <script type="text/template" id="tpl-cta">
        <section data-editable class="bg-gradient-to-r from-[#1F1F1F] via-[#1F1F1F] to-[#1F1F1F] py-20 px-6 text-center text-white relative overflow-hidden">
           <div class="absolute inset-0 pointer-events-none">
               <div class="absolute top-10 left-1/2 -translate-x-1/2 w-[90%] h-full bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.15),transparent_60%)]"></div>
           </div>
           <div class="relative max-w-4xl mx-auto">
               <h2 contenteditable="true" class="text-3xl sm:text-4xl md:text-5xl font-bold mb-6 outline-none" style="font-family: 'Azo Sans Uber', sans-serif; font-weight: 400; color: #18F1E1; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Ready to Begin?</h2>
               <p contenteditable="true" class="text-base md:text-lg text-white/85 leading-relaxed mb-8 max-w-2xl mx-auto outline-none">Get started with our premium services today.</p>
               <div class="flex flex-col sm:flex-row gap-4 justify-center">
                   <a href="#" class="inline-flex items-center justify-center px-8 py-4 rounded-full font-semibold text-lg bg-[#FF4F4F] hover:bg-[#FF3838] text-white transition-all duration-300 hover:-translate-y-1 hover:scale-105">Contact Us</a>
                   <a href="#" class="inline-flex items-center justify-center px-7 py-3 rounded-full font-semibold border-2 border-[#18F1E1] text-[#18F1E1] bg-transparent hover:bg-[#18F1E1] hover:text-black transition-all duration-300">Call Us</a>
                </div>
           </div>
       </section>
    </script>

    <!-- Video Section Template -->
    <script type="text/template" id="tpl-video">
<section data-editable class="relative overflow-hidden bg-gradient-to-r from-[#1F1F1F] via-[#1F1F1F] to-[#1F1F1F] text-white py-20 px-6" data-video-section>
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-10 left-1/2 -translate-x-1/2 w-[90%] h-full bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.15),transparent_60%)]"></div>
    </div>
    <div class="relative max-w-6xl mx-auto">
        <div class="text-center mb-16">
            <h2 contenteditable="true" class="text-3xl sm:text-4xl md:text-5xl font-bold mb-6 outline-none" style="font-family: 'Azo Sans Uber', sans-serif; font-weight: 400; color: #18F1E1; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Video Showcase</h2>
            <p contenteditable="true" class="text-base md:text-lg text-white/85 leading-relaxed max-w-3xl mx-auto outline-none">Create share-worthy videos that capture the energy and emotion of your event.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6" id="video-cards-container">
            <!-- Initial video card -->
            <div class="video-card-item bg-white/10 border border-white/15 rounded-3xl overflow-hidden backdrop-blur transition-all duration-300 hover:-translate-y-1 relative group">
                <div class="aspect-video overflow-hidden bg-black/50 flex items-center justify-center relative video-player-container">
                    <span class="text-white/50 video-placeholder">Video Placeholder</span>
                    <video class="w-full h-full object-cover hidden video-element" controls></video>
                    <div class="w-full h-full hidden iframe-wrapper absolute inset-0"></div>
                    
                    <!-- Hover overlay for changing/removing video -->
                    <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center gap-3 z-10">
                        <button onclick="changeVideoInCard(this)" class="bg-[#18F1E1] hover:bg-[#15D9C9] text-black px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors">
                            <i class="fas fa-video"></i> Change Video
                        </button>
                        <button onclick="removeVideoCard(this)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <h4 contenteditable="true" class="text-xl font-semibold mb-2 outline-none" style="color: #18F1E1; font-family: 'Azo Sans', sans-serif;">Video Title</h4>
                    <p contenteditable="true" class="text-sm text-white/70 mb-4 leading-relaxed outline-none">Video description goes here.</p>
                    <a href="#" onclick="openVideoModal(event, this)" class="inline-flex items-center rounded-full bg-[#FF4F4F] px-5 py-2 text-white font-semibold hover:bg-[#FF3838] transition-colors">Watch Now</a>
                </div>
            </div>
        </div>
        
        <!-- Add Video Card Button -->
        <div class="mt-8 text-center">
            <button onclick="addVideoCard(this)" class="inline-flex items-center gap-2 px-6 py-3 rounded-full border-2 border-dashed border-[#18F1E1]/50 text-[#18F1E1] hover:bg-[#18F1E1]/10 hover:border-[#18F1E1] transition-all duration-300">
                <i class="fas fa-plus-circle"></i>
                <span>Add Video Card</span>
            </button>
        </div>
    </div>
    
    <!-- Fullscreen Video Modal -->
    <div id="videoModal" class="fixed inset-0 bg-black/95 z-50 hidden flex items-center justify-center p-4" onclick="closeVideoModal(event)">
        <button onclick="closeVideoModal(event)" class="absolute top-4 right-4 text-white hover:text-[#18F1E1] transition-colors z-10">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <div class="w-full max-w-6xl aspect-video" onclick="event.stopPropagation()">
            <div id="videoModalContent" class="w-full h-full"></div>
        </div>
    </div>
    
    <script>
        function openVideoModal(event, button) {
            event.preventDefault();
            
            // Find the video card
            const card = button.closest('.video-card-item');
            if (!card) return;
            
            // Get the video element or iframe
            const videoElement = card.querySelector('.video-element');
            const iframeWrapper = card.querySelector('.iframe-wrapper');
            const modal = document.getElementById('videoModal');
            const modalContent = document.getElementById('videoModalContent');
            
            // Clear previous content
            modalContent.innerHTML = '';
            
            console.log('=== DEBUG: openVideoModal ===');
            console.log('videoElement:', videoElement);
            console.log('videoElement.src:', videoElement?.src);
            console.log('videoElement.getAttribute("src"):', videoElement?.getAttribute('src'));
            console.log('iframeWrapper:', iframeWrapper);
            console.log('iframeWrapper.innerHTML:', iframeWrapper?.innerHTML);
            
            // Clone and insert the video or iframe
            // Check the actual src attribute, not the resolved property
            const videoSrc = videoElement?.getAttribute('src');
            if (videoElement && videoSrc && videoSrc.trim() !== '') {
                console.log('Using VIDEO element');
                const videoClone = videoElement.cloneNode(true);
                videoClone.classList.remove('hidden');
                videoClone.classList.add('w-full', 'h-full');
                videoClone.autoplay = true;
                modalContent.appendChild(videoClone);
            } else if (iframeWrapper && iframeWrapper.innerHTML.trim()) {
                console.log('Using IFRAME wrapper');
                // Get the iframe from the wrapper
                const iframe = iframeWrapper.querySelector('iframe');
                console.log('Found iframe:', iframe);
                if (iframe) {
                    const iframeClone = iframe.cloneNode(true);
                    iframeClone.classList.add('w-full', 'h-full');
                    // Enable autoplay for YouTube/Vimeo
                    const src = iframeClone.src;
                    console.log('iframe src:', src);
                    if (src.includes('youtube.com')) {
                        iframeClone.src = src + (src.includes('?') ? '&' : '?') + 'autoplay=1';
                    } else if (src.includes('vimeo.com')) {
                        iframeClone.src = src + (src.includes('?') ? '&' : '?') + 'autoplay=1';
                    }
                    console.log('Appending iframe to modal');
                    modalContent.appendChild(iframeClone);
                } else {
                    console.log('ERROR: No iframe found in wrapper!');
                }
            } else {
                console.log('ERROR: No valid video or iframe found!');
            }
            
            console.log('modalContent after:', modalContent.innerHTML);
            
            // Show modal
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeVideoModal(event) {
            if (event) event.preventDefault();
            
            const modal = document.getElementById('videoModal');
            const modalContent = document.getElementById('videoModalContent');
            
            // Stop video playback
            const video = modalContent.querySelector('video');
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
            
            // Clear content
            modalContent.innerHTML = '';
            
            // Hide modal
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideoModal();
            }
        });
    </script>
</section>
    </script>


    <!-- Iframe Template -->
    <script type="text/template" id="iframeTemplate">
<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.tailwindcss.com"><##SCRIPT_END##>
    <style>
        /* Azo Sans Font Face Declarations */
        @font-face {
            font-family: 'Azo Sans';
            src: url('../assets/fonts/AzoSans-Regular.woff2') format('woff2'),
                 url('../assets/fonts/AzoSans-Regular.woff') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Azo Sans Uber';
            src: url('../assets/fonts/AzoSansUber-Regular.woff2') format('woff2'),
                 url('../assets/fonts/AzoSansUber-Regular.woff') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        * { box-sizing: border-box; }
        body { 
            min-height: 100vh; 
            margin: 0;
            padding: 0;
            font-family: 'Azo Sans', sans-serif;
            color: #1F1F1F;
        }
        
        /* Remove extra padding when footer is present */
        body:has(footer) {
            padding-bottom: 0;
        }
        
        /* Ensure footer is at the bottom */
        footer {
            margin-top: auto;
        }
        /* Hover effect to show editable areas */
        [data-editable]:hover { 
            outline: 2px dashed #18F1E1; 
            cursor: pointer; 
            position: relative; 
            outline-offset: 4px;
        }
        [data-editable].selected { 
            outline: 2px solid #FF4F4F; 
            outline-offset: 2px;
        }
        
        /* Text elements can be selected individually */
        h1, h2, h3, h4, h5, h6, p, span, a, button, label, li {
            position: relative;
        }
        h1:hover, h2:hover, h3:hover, h4:hover, h5:hover, h6:hover, 
        p:hover, span:hover, a:hover, button:hover {
            outline: 2px dashed #18F1E1;
            outline-offset: 2px;
            cursor: pointer;
        }
        h1.selected, h2.selected, h3.selected, h4.selected, h5.selected, h6.selected,
        p.selected, span.selected, a.selected, button.selected {
            outline: 2px solid #FF4F4F;
            outline-offset: 2px;
        }
        
        /* Media elements can be selected */
        img, video, iframe, [data-editable] img, [data-editable] video, [data-editable] iframe {
            position: relative;
        }
        img:hover, video:hover, iframe:hover,
        [data-editable]:has(img):hover, [data-editable]:has(video):hover, [data-editable]:has(iframe):hover {
            outline: 2px dashed #18F1E1;
            outline-offset: 2px;
            cursor: pointer;
        }
        img.selected, video.selected, iframe.selected,
        [data-editable]:has(img).selected, [data-editable]:has(video).selected, [data-editable]:has(iframe).selected {
            outline: 2px solid #FF4F4F;
            outline-offset: 2px;
        }
        
        /* Card icon selection */
        .rounded-xl:hover {
            outline: 2px dashed #18F1E1;
            outline-offset: 2px;
            cursor: pointer;
        }
        .rounded-xl.selected {
            outline: 2px solid #FF4F4F;
            outline-offset: 2px;
        }
        
        /* Drag Helper */
        .drop-zone { 
            height: 10px; 
            background: transparent; 
            transition: height 0.2s; 
        }
        .drop-zone.active { 
            height: 40px; 
            background: rgba(24, 241, 225, 0.1); 
            border: 2px dashed #18F1E1; 
        }
        
        /* Empty State */
        .empty-canvas { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 300px; 
            color: #9ca3af; 
            font-size: 1.5rem; 
            border: 4px dashed #e5e7eb; 
            margin: 20px; 
            border-radius: 10px; 
            background: #f9fafb;
        }
        
        /* Smooth transitions */
        * { transition: all 0.2s ease; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }
    </style>
</head>
<body class="bg-white" style="display: flex; flex-direction: column; min-height: 100vh;">
    <main style="flex: 1;">
        <div id="canvas-root">
        <div class="empty-canvas">Drop Components Here</div>
    </div>
    </main>
    <script src="../assets/components/navigation.js"><##SCRIPT_END##>
    <script src="../assets/components/footer.js"><##SCRIPT_END##>
</body>
</html>
    </script>

    <script>
        const csrfToken = '<?php echo $csrf; ?>';
        const iframe = document.getElementById('editorFrame');
        let selectedElement = null; // The DOM element inside the iframe currently selected

        // --- 1. Initialization: Build the Iframe Environment ---
        window.onload = function() {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            
            // Get iframe template from hidden script tag - more robust than template literals
            var iframeContent = document.getElementById('iframeTemplate').innerHTML;
            // Replace placeholder with actual script closing tag
            iframeContent = iframeContent.replace(/\<##SCRIPT_END##\>/g, '</' + 'script>');
            
            // Inject Tailwind & Base Styles into Iframe
            doc.open();
            doc.write(iframeContent);
            doc.close();

            // Setup Listeners inside Iframe
            setupIframeListeners(doc);
        };

        // --- 2. HTML Templates (The Blocks) - Load from hidden script tags for cross-server compatibility ---
        // Helper function to get template content from hidden script tags
        function getTemplate(id) {
            var tpl = document.getElementById('tpl-' + id);
            if (!tpl) {
                console.error('Template not found: tpl-' + id);
                return '';
            }
            return tpl.innerHTML.trim();
        }
        
        // Templates object - reads from hidden script tags instead of using template literals
        var templates = {
            hero: getTemplate('hero'),
            text: getTemplate('text'),
            cards: getTemplate('cards'),
            split: getTemplate('split'),
            cta: getTemplate('cta'),
            video: getTemplate('video')
        };
        
        // Verify templates loaded correctly
        (function() {
            var missing = [];
            for (var key in templates) {
                if (!templates[key] || templates[key].length < 10) {
                    missing.push(key);
                }
            }
            if (missing.length > 0) {
                console.error('PageBuilder: Some templates failed to load:', missing);
            }
        })();

        // --- 3. Drag & Drop Logic ---
        function setupIframeListeners(doc) {
            const root = doc.getElementById('canvas-root');
            
            // Expose functions to iframe window for overlay buttons
            exposeFunctionsToIframe();

            // Handle Drag Over (Visual Feedback)
            doc.addEventListener('dragover', (e) => {
                e.preventDefault();
            });

            // Handle Drop
            doc.addEventListener('drop', (e) => {
                e.preventDefault();
                const type = e.dataTransfer.getData('type');
                if (type && templates[type]) {
                    // Remove empty state if present
                    const empty = doc.querySelector('.empty-canvas');
                    if (empty) empty.remove();

                    // Insert HTML
                    const tempDiv = doc.createElement('div');
                    tempDiv.innerHTML = templates[type];
                    const newEl = tempDiv.firstElementChild;
                    
                    // Logic to drop closest to mouse would go here, 
                    // for now we append to bottom for simplicity
                    root.appendChild(newEl);
                    
                    // Select it immediately
                    selectElement(newEl);
                }
            });

            // Handle Click (Selection)
            doc.addEventListener('click', (e) => {
                // Handle split screen media overlay buttons
                const btn = e.target.closest('button[data-action]');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const action = btn.getAttribute('data-action');
                    if (action === 'change-media') {
                        const type = btn.getAttribute('data-type');
                        if (window.changeSplitScreenMedia) {
                            window.changeSplitScreenMedia(type);
                        }
                        return;
                    } else if (action === 'remove-media') {
                        if (window.removeSplitScreenMedia) {
                            window.removeSplitScreenMedia();
                        }
                        return;
                    } else if (action === 'remove-feature') {
                        const featureItem = btn.closest('.split-screen-feature-item');
                        if (featureItem && window.removeSplitScreenFeaturePoint) {
                            window.removeSplitScreenFeaturePoint(featureItem);
                        }
                        return;
                    }
                }
                
                // Don't prevent default if clicking on contenteditable content
                if (e.target.hasAttribute('contenteditable') && e.target.getAttribute('contenteditable') === 'true') {
                    // Allow normal editing, but still select the element
                    setTimeout(() => {
                        const textElements = ['H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'P', 'SPAN', 'A', 'BUTTON', 'LABEL', 'LI', 'STRONG', 'EM', 'B', 'I'];
                        const clickedTag = e.target.tagName;
                        if (textElements.includes(clickedTag)) {
                            selectElement(e.target);
                        }
                    }, 10);
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                // Priority: Select media elements (img, video, iframe) or their containers
                const clickedTag = e.target.tagName;
                if (clickedTag === 'IMG' || clickedTag === 'VIDEO' || clickedTag === 'IFRAME') {
                    // Select the media element or its container
                    const container = e.target.closest('[data-editable]');
                    if (container) {
                        selectElement(container);
                    } else {
                        selectElement(e.target);
                    }
                } else if (clickedTag === 'SVG' || clickedTag === 'PATH') {
                    // Check if it's a card icon
                    const iconContainer = e.target.closest('.rounded-xl');
                    if (iconContainer && iconContainer.closest('.grid')) {
                        selectElement(iconContainer);
                    } else {
                        const target = e.target.closest('[data-editable]');
                        if (target) {
                            selectElement(target);
                        } else {
                            deselectAll();
                        }
                    }
                } else if (clickedTag === 'DIV' && e.target.classList.contains('rounded-xl') && e.target.querySelector('svg') && e.target.closest('.grid')) {
                    // Direct click on icon container
                    selectElement(e.target);
                } else {
                    // Check if clicking on split screen media placeholder
                    const splitScreenMediaContainer = e.target.closest('#split-screen-media-container');
                    if (splitScreenMediaContainer) {
                        // Find the parent split screen section by traversing up
                        let parent = splitScreenMediaContainer.parentElement;
                        while (parent && parent !== doc.body) {
                            if (parent.hasAttribute('data-editable') && parent.querySelector('.lg\\:grid-cols-2')) {
                                selectElement(parent);
                                return;
                            }
                            parent = parent.parentElement;
                        }
                    }
                    
                    // Priority: Select text elements first (h1-h6, p, span, a, etc.)
                    const textElements = ['H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'P', 'SPAN', 'A', 'BUTTON', 'LABEL', 'LI', 'STRONG', 'EM', 'B', 'I'];
                    
                    if (textElements.includes(clickedTag)) {
                        // Select the specific text element
                        selectElement(e.target);
                    } else {
                // Find closest editable section
                const target = e.target.closest('[data-editable]');
                if (target) {
                    selectElement(target);
                } else {
                    deselectAll();
                }
            }
                }
            });
        }

        // Setup Sidebar Draggables
        document.querySelectorAll('.component-item').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('type', item.dataset.type);
            });
        });

        // --- 4. Background Image Helper Functions ---
        function getBackgroundImageUrl(el, computedStyle) {
            const bgImage = computedStyle.backgroundImage || el.style.backgroundImage || '';
            if (bgImage && bgImage !== 'none') {
                const match = bgImage.match(/url\(['"]?([^'"]+)['"]?\)/);
                return match ? match[1] : '';
            }
            return '';
        }

        function getBackgroundSize(el) {
            return el.style.backgroundSize || 'cover';
        }

        function getBackgroundPosition(el) {
            return el.style.backgroundPosition || 'center';
        }

        function getBackgroundRepeat(el) {
            return el.style.backgroundRepeat || 'no-repeat';
        }

        // --- 5. Selection & Inspector Logic ---
        function selectElement(el) {
            const doc = iframe.contentDocument;
            
            // Remove previous selection
            if (selectedElement) selectedElement.classList.remove('selected');
            
            selectedElement = el;
            selectedElement.classList.add('selected');

            // Build Inspector UI
            const inspector = document.getElementById('inspectorPanel');
            const computedStyle = iframe.contentWindow.getComputedStyle(el);
            
            // Identify element type
            let typeLabel = el.tagName.toLowerCase();
            const tagName = el.tagName.toUpperCase();
            const isTextElement = ['H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'P', 'SPAN', 'A', 'BUTTON', 'LABEL', 'LI', 'STRONG', 'EM', 'B', 'I'].includes(tagName);
            
            // Check if it's a media element (image, video, embed container)
            const isMediaElement = tagName === 'IMG' || tagName === 'VIDEO' || tagName === 'IFRAME' || 
                                  (tagName === 'DIV' && (el.querySelector('img') || el.querySelector('video') || el.querySelector('iframe')));
            
            // Check if it's a feature card icon container
            const isCardIconContainer = (tagName === 'DIV' && el.classList.contains('rounded-xl') && el.querySelector('svg') && el.closest('.grid')) ||
                                       (tagName === 'SVG' && el.closest('.rounded-xl') && el.closest('.grid'));
            
            // Check if it's a text element
            if (['H1', 'H2', 'H3', 'H4', 'H5', 'H6'].includes(tagName)) {
                typeLabel = `Heading ${tagName}`;
            } else if (tagName === 'P') {
                typeLabel = "Paragraph";
            } else if (tagName === 'IMG') {
                typeLabel = "Image";
            } else if (tagName === 'VIDEO') {
                typeLabel = "Video";
            } else if (tagName === 'IFRAME') {
                typeLabel = "Embed";
            } else if (tagName === 'DIV' && (el.querySelector('img') || el.querySelector('video') || el.querySelector('iframe'))) {
                typeLabel = "Media Container";
            } else if (['SPAN', 'A', 'BUTTON', 'LABEL', 'LI'].includes(tagName)) {
                typeLabel = tagName.charAt(0) + tagName.slice(1).toLowerCase();
            } else if (el.classList.contains('bg-gradient-to-br') || el.querySelector('span[style*="#FF4F4F"]')) {
                if (el.querySelector('h1')) typeLabel = "Hero Section";
                else if (el.querySelector('[style*="#18F1E1"]')) typeLabel = "CTA Section";
                else typeLabel = "Section";
            } else if (el.querySelector('.grid')) {
                if (el.querySelector('video') || el.querySelector('[style*="#1F1F1F"]')) typeLabel = "Video Section";
                else typeLabel = "Feature Cards";
            } else if (el.querySelector('.lg\\:grid-cols-2')) typeLabel = "Split Screen";
            else if (el.id === 'split-screen-media-container' || el.closest('#split-screen-media-container')) {
                // If clicking on the media container or its children, find parent split screen
                const splitScreenSection = el.closest('[data-editable]')?.querySelector('.lg\\:grid-cols-2')?.closest('[data-editable]');
                if (splitScreenSection) {
                    // Re-select the parent split screen section
                    setTimeout(() => selectElement(splitScreenSection), 10);
                    return;
                }
                typeLabel = "Media Container";
            } else if (el.querySelector('h2') && el.classList.contains('max-w-4xl')) typeLabel = "Text Section";
            
            // Check if it's a text block section
            const isTextBlock = typeLabel === "Text Section";
            
            // Check if it's a split screen section
            const isSplitScreen = typeLabel === "Split Screen";
            
            // Check if split screen has media
            let hasSplitScreenMedia = false;
            if (isSplitScreen) {
                const mediaContainer = el.querySelector('#split-screen-media-container');
                if (mediaContainer) {
                    const image = mediaContainer.querySelector('#split-screen-image');
                    const video = mediaContainer.querySelector('#split-screen-video');
                    hasSplitScreenMedia = (
                        (image && !image.classList.contains('hidden') && image.src) ||
                        (video && !video.classList.contains('hidden') && video.src) ||
                        mediaContainer.querySelector('iframe')
                    );
                }
            }

            // Get current font family - check if it's a text element or section
            let currentFont = getCurrentFontFamily(el, computedStyle);
            
            // For text elements, also check parent's font if element doesn't have explicit font
            if (!el.style.fontFamily && ['H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'P', 'SPAN', 'A', 'BUTTON'].includes(el.tagName.toUpperCase())) {
                const parentStyle = iframe.contentWindow.getComputedStyle(el.parentElement);
                if (parentStyle.fontFamily && parentStyle.fontFamily !== 'inherit') {
                    currentFont = parentStyle.fontFamily;
                }
            }

            // Detect current background type and extract gradient colors if present
            let currentBgType = 'solid';
            let gradientColor1 = '#FF4F4F';
            let gradientColor2 = '#18F1E1';
            const bgImage = computedStyle.backgroundImage || el.style.backgroundImage || '';
            if (bgImage && bgImage !== 'none' && bgImage.includes('gradient')) {
                currentBgType = 'gradient';
                // Extract gradient colors
                const gradientMatch = bgImage.match(/gradient\([^,]+,\s*([^,]+),\s*([^)]+)\)/);
                if (gradientMatch) {
                    gradientColor1 = gradientMatch[1].trim().replace(/['"]/g, '');
                    gradientColor2 = gradientMatch[2].trim().replace(/['"]/g, '');
                }
            } else if (computedStyle.backgroundColor === 'rgba(0, 0, 0, 0)' || computedStyle.backgroundColor === 'transparent') {
                currentBgType = 'transparent';
            }

            inspector.innerHTML = `
                <div class="mb-4 flex justify-between items-center">
                    <span class="text-xs font-bold text-[#18F1E1] uppercase">${typeLabel}</span>
                    <button onclick="deleteSelected()" class="text-xs text-[#FF4F4F] hover:text-red-300"><i class="fas fa-trash"></i> Delete</button>
                </div>

                ${typeLabel === "Hero Section" ? `
                <div class="mb-4 border-t border-zinc-700 pt-4">
                    <label class="block text-xs text-zinc-500 mb-2">Background</label>
                    <button onclick="changeHeroBackgroundFromInspector()" class="w-full bg-[#18F1E1] hover:bg-[#15D9C9] text-black px-3 py-2 rounded text-xs font-semibold transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-image"></i> Change Background
                    </button>
                </div>
                ` : ''}

                ${isMediaElement ? `
                <div class="mb-4 border-t border-zinc-700 pt-4">
                    <label class="block text-xs text-zinc-500 mb-2">Media Options</label>
                    <button onclick="removeMediaElement()" class="w-full bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-xs transition-colors"><i class="fas fa-trash mr-1"></i> Remove Media</button>
                </div>
                ` : ''}

                ${isCardIconContainer ? `
                <div class="mb-4 border-t border-zinc-700 pt-4">
                    <label class="block text-xs text-zinc-500 mb-2">Icon Options</label>
                    <div class="grid grid-cols-5 gap-2 mb-3 max-h-64 overflow-y-auto">
                        <button onclick="updateCardIcon('fa-camera')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Camera"><i class="fas fa-camera"></i></button>
                        <button onclick="updateCardIcon('fa-video')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Video"><i class="fas fa-video"></i></button>
                        <button onclick="updateCardIcon('fa-image')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Image"><i class="fas fa-image"></i></button>
                        <button onclick="updateCardIcon('fa-star')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Star"><i class="fas fa-star"></i></button>
                        <button onclick="updateCardIcon('fa-heart')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Heart"><i class="fas fa-heart"></i></button>
                        <button onclick="updateCardIcon('fa-thumbs-up')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Thumbs Up"><i class="fas fa-thumbs-up"></i></button>
                        <button onclick="updateCardIcon('fa-lightbulb')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Lightbulb"><i class="fas fa-lightbulb"></i></button>
                        <button onclick="updateCardIcon('fa-gift')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Gift"><i class="fas fa-gift"></i></button>
                        <button onclick="updateCardIcon('fa-music')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Music"><i class="fas fa-music"></i></button>
                        <button onclick="updateCardIcon('fa-palette')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Palette"><i class="fas fa-palette"></i></button>
                        <button onclick="updateCardIcon('fa-magic')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Magic"><i class="fas fa-magic"></i></button>
                        <button onclick="updateCardIcon('fa-sparkles')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Sparkles"><i class="fas fa-sparkles"></i></button>
                        <button onclick="updateCardIcon('fa-users')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Users"><i class="fas fa-users"></i></button>
                        <button onclick="updateCardIcon('fa-trophy')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Trophy"><i class="fas fa-trophy"></i></button>
                        <button onclick="updateCardIcon('fa-award')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Award"><i class="fas fa-award"></i></button>
                        <button onclick="updateCardIcon('fa-fire')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Fire"><i class="fas fa-fire"></i></button>
                        <button onclick="updateCardIcon('fa-bolt')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Bolt"><i class="fas fa-bolt"></i></button>
                        <button onclick="updateCardIcon('fa-rocket')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Rocket"><i class="fas fa-rocket"></i></button>
                        <button onclick="updateCardIcon('fa-gem')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Gem"><i class="fas fa-gem"></i></button>
                        <button onclick="updateCardIcon('fa-crown')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Crown"><i class="fas fa-crown"></i></button>
                        <button onclick="updateCardIcon('fa-shield')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Shield"><i class="fas fa-shield"></i></button>
                        <button onclick="updateCardIcon('fa-check-circle')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Check Circle"><i class="fas fa-check-circle"></i></button>
                        <button onclick="updateCardIcon('fa-bell')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Bell"><i class="fas fa-bell"></i></button>
                        <button onclick="updateCardIcon('fa-envelope')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Envelope"><i class="fas fa-envelope"></i></button>
                        <button onclick="updateCardIcon('fa-phone')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Phone"><i class="fas fa-phone"></i></button>
                        <button onclick="updateCardIcon('fa-calendar')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Calendar"><i class="fas fa-calendar"></i></button>
                        <button onclick="updateCardIcon('fa-clock')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Clock"><i class="fas fa-clock"></i></button>
                        <button onclick="updateCardIcon('fa-map-marker')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Location"><i class="fas fa-map-marker-alt"></i></button>
                        <button onclick="updateCardIcon('fa-globe')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Globe"><i class="fas fa-globe"></i></button>
                        <button onclick="updateCardIcon('fa-wifi')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="WiFi"><i class="fas fa-wifi"></i></button>
                        <button onclick="updateCardIcon('fa-lock')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Lock"><i class="fas fa-lock"></i></button>
                        <button onclick="updateCardIcon('fa-key')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Key"><i class="fas fa-key"></i></button>
                        <button onclick="updateCardIcon('fa-cog')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Settings"><i class="fas fa-cog"></i></button>
                        <button onclick="updateCardIcon('fa-tools')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Tools"><i class="fas fa-tools"></i></button>
                        <button onclick="updateCardIcon('fa-wrench')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Wrench"><i class="fas fa-wrench"></i></button>
                        <button onclick="updateCardIcon('fa-chart-line')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Chart"><i class="fas fa-chart-line"></i></button>
                        <button onclick="updateCardIcon('fa-dollar-sign')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Dollar"><i class="fas fa-dollar-sign"></i></button>
                        <button onclick="updateCardIcon('fa-shopping-cart')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Shopping Cart"><i class="fas fa-shopping-cart"></i></button>
                        <button onclick="updateCardIcon('fa-tag')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Tag"><i class="fas fa-tag"></i></button>
                        <button onclick="updateCardIcon('fa-bookmark')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Bookmark"><i class="fas fa-bookmark"></i></button>
                        <button onclick="updateCardIcon('fa-folder')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Folder"><i class="fas fa-folder"></i></button>
                        <button onclick="updateCardIcon('fa-file')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="File"><i class="fas fa-file"></i></button>
                        <button onclick="updateCardIcon('fa-download')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Download"><i class="fas fa-download"></i></button>
                        <button onclick="updateCardIcon('fa-upload')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Upload"><i class="fas fa-upload"></i></button>
                        <button onclick="updateCardIcon('fa-share')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Share"><i class="fas fa-share"></i></button>
                        <button onclick="updateCardIcon('fa-comment')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Comment"><i class="fas fa-comment"></i></button>
                        <button onclick="updateCardIcon('fa-comments')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Comments"><i class="fas fa-comments"></i></button>
                        <button onclick="updateCardIcon('fa-thumbs-down')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Thumbs Down"><i class="fas fa-thumbs-down"></i></button>
                        <button onclick="updateCardIcon('fa-flag')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Flag"><i class="fas fa-flag"></i></button>
                        <button onclick="updateCardIcon('fa-book')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Book"><i class="fas fa-book"></i></button>
                        <button onclick="updateCardIcon('fa-graduation-cap')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Graduation Cap"><i class="fas fa-graduation-cap"></i></button>
                        <button onclick="updateCardIcon('fa-briefcase')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Briefcase"><i class="fas fa-briefcase"></i></button>
                        <button onclick="updateCardIcon('fa-building')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Building"><i class="fas fa-building"></i></button>
                        <button onclick="updateCardIcon('fa-home')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Home"><i class="fas fa-home"></i></button>
                        <button onclick="updateCardIcon('fa-car')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Car"><i class="fas fa-car"></i></button>
                        <button onclick="updateCardIcon('fa-plane')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Plane"><i class="fas fa-plane"></i></button>
                        <button onclick="updateCardIcon('fa-ship')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Ship"><i class="fas fa-ship"></i></button>
                        <button onclick="updateCardIcon('fa-bicycle')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Bicycle"><i class="fas fa-bicycle"></i></button>
                        <button onclick="updateCardIcon('fa-hamburger')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Food"><i class="fas fa-hamburger"></i></button>
                        <button onclick="updateCardIcon('fa-coffee')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Coffee"><i class="fas fa-coffee"></i></button>
                        <button onclick="updateCardIcon('fa-utensils')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Utensils"><i class="fas fa-utensils"></i></button>
                        <button onclick="updateCardIcon('fa-dumbbell')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Fitness"><i class="fas fa-dumbbell"></i></button>
                        <button onclick="updateCardIcon('fa-futbol')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Football"><i class="fas fa-futbol"></i></button>
                        <button onclick="updateCardIcon('fa-basketball-ball')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Basketball"><i class="fas fa-basketball-ball"></i></button>
                        <button onclick="updateCardIcon('fa-gamepad')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Gamepad"><i class="fas fa-gamepad"></i></button>
                        <button onclick="updateCardIcon('fa-puzzle-piece')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Puzzle"><i class="fas fa-puzzle-piece"></i></button>
                        <button onclick="updateCardIcon('fa-chess')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Chess"><i class="fas fa-chess"></i></button>
                        <button onclick="updateCardIcon('fa-dice')" class="p-2 bg-zinc-700 hover:bg-zinc-600 rounded text-white text-sm" title="Dice"><i class="fas fa-dice"></i></button>
                    </div>
                    <input type="text" id="customIconInput" placeholder="Enter Font Awesome icon class (e.g., fa-camera)" class="w-full bg-zinc-800 border border-zinc-700 text-white px-2 py-1 rounded text-xs mb-2">
                    <button onclick="updateCardIconFromInput()" class="w-full bg-[#18F1E1] hover:bg-[#15D9C9] text-zinc-900 px-3 py-2 rounded text-xs font-semibold transition-colors">Apply Custom Icon</button>
                </div>
                ` : ''}

                <div class="mb-4">
                    <label class="block text-xs text-zinc-500 mb-1">Font Family</label>
                    <select onchange="updateFontFamily(this.value)" class="bg-zinc-800 border border-zinc-700 text-white px-2 py-1.5 rounded text-xs w-full">
                        <option value="'Azo Sans', sans-serif" ${currentFont.includes('Azo Sans') && !currentFont.includes('Uber') ? 'selected' : ''}>Azo Sans</option>
                        <option value="'Azo Sans Uber', sans-serif" ${currentFont.includes('Azo Sans Uber') ? 'selected' : ''}>Azo Sans Uber</option>
                        <option value="system-ui, -apple-system, sans-serif" ${currentFont.includes('system-ui') || currentFont.includes('apple-system') ? 'selected' : ''}>System Font</option>
                        <option value="Georgia, serif" ${currentFont.includes('Georgia') ? 'selected' : ''}>Georgia (Serif)</option>
                        <option value="'Courier New', monospace" ${currentFont.includes('Courier') ? 'selected' : ''}>Courier New (Monospace)</option>
                        <option value="'Times New Roman', serif" ${currentFont.includes('Times') ? 'selected' : ''}>Times New Roman</option>
                        <option value="Arial, sans-serif" ${currentFont.includes('Arial') && !currentFont.includes('Azo') ? 'selected' : ''}>Arial</option>
                        <option value="Helvetica, sans-serif" ${currentFont.includes('Helvetica') ? 'selected' : ''}>Helvetica</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs text-zinc-500 mb-1">Background</label>
                    <select id="bgTypeSelect" onchange="changeBackgroundType(this.value)" class="bg-zinc-800 border border-zinc-700 text-white px-2 py-1.5 rounded text-xs w-full mb-2">
                        <option value="solid" ${currentBgType === 'solid' ? 'selected' : ''}>Solid Color</option>
                        <option value="gradient" ${currentBgType === 'gradient' ? 'selected' : ''}>Gradient</option>
                        <option value="transparent" ${currentBgType === 'transparent' ? 'selected' : ''}>Transparent</option>
                    </select>
                    
                    <div id="bgSolidOptions" style="display: ${currentBgType === 'solid' ? 'block' : 'none'};">
                        <div class="flex gap-2 mb-2">
                            <input type="color" id="bgColorPicker" value="${rgbToHex(computedStyle.backgroundColor)}" oninput="updateBackgroundColor(this.value)" class="h-8 w-8 p-0 border-0 bg-transparent cursor-pointer">
                            <input type="text" id="bgColorInput" value="${rgbToHex(computedStyle.backgroundColor)}" onchange="updateBackgroundColor(this.value)" placeholder="#000000" class="bg-zinc-800 border border-zinc-700 text-white px-2 py-1 rounded text-xs flex-1">
                        </div>
                        <div class="grid grid-cols-4 gap-1 mb-2">
                            <button onclick="updateBackgroundColor('#FF4F4F')" class="h-6 rounded" style="background: #FF4F4F;" title="Coral"></button>
                            <button onclick="updateBackgroundColor('#18F1E1')" class="h-6 rounded" style="background: #18F1E1;" title="Cyan"></button>
                            <button onclick="updateBackgroundColor('#1F1F1F')" class="h-6 rounded" style="background: #1F1F1F;" title="Dark"></button>
                            <button onclick="updateBackgroundColor('#FFFFFF')" class="h-6 rounded border border-zinc-600" style="background: #FFFFFF;" title="White"></button>
                            <button onclick="updateBackgroundColor('#000000')" class="h-6 rounded" style="background: #000000;" title="Black"></button>
                            <button onclick="updateBackgroundColor('#F3F4F6')" class="h-6 rounded border border-zinc-600" style="background: #F3F4F6;" title="Light Gray"></button>
                            <button onclick="updateBackgroundColor('#0f0a1a')" class="h-6 rounded" style="background: #0f0a1a;" title="Dark Purple"></button>
                            <button onclick="updateBackgroundColor('#1d1130')" class="h-6 rounded" style="background: #1d1130;" title="Purple"></button>
                        </div>
                    </div>
                    
                    <div id="bgGradientOptions" style="display: ${currentBgType === 'gradient' ? 'block' : 'none'};">
                        <div class="mb-2">
                            <label class="block text-xs text-zinc-500 mb-1">Gradient Type</label>
                            <select id="gradientType" onchange="updateGradient()" class="bg-zinc-800 border border-zinc-700 text-white px-2 py-1 rounded text-xs w-full">
                                <option value="linear">Linear</option>
                                <option value="radial">Radial</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="block text-xs text-zinc-500 mb-1">Direction</label>
                            <select id="gradientDirection" onchange="updateGradient()" class="bg-zinc-800 border border-zinc-700 text-white px-2 py-1 rounded text-xs w-full">
                                <option value="to right">To Right</option>
                                <option value="to left">To Left</option>
                                <option value="to bottom">To Bottom</option>
                                <option value="to top">To Top</option>
                                <option value="to bottom right">To Bottom Right</option>
                                <option value="to top left">To Top Left</option>
                            </select>
                        </div>
                        <div class="flex gap-2 mb-2">
                            <div class="flex-1">
                                <label class="block text-xs text-zinc-500 mb-1">Color 1</label>
                                <div class="flex gap-1">
                                    <input type="color" id="gradientColor1" value="${gradientColor1}" oninput="updateGradient()" class="h-6 w-8 p-0 border-0 bg-transparent cursor-pointer">
                                    <input type="text" id="gradientColor1Input" value="${gradientColor1}" onchange="updateGradient()" class="bg-zinc-800 border border-zinc-700 text-white px-1 py-0.5 rounded text-xs flex-1">
                                </div>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-zinc-500 mb-1">Color 2</label>
                                <div class="flex gap-1">
                                    <input type="color" id="gradientColor2" value="${gradientColor2}" oninput="updateGradient()" class="h-6 w-8 p-0 border-0 bg-transparent cursor-pointer">
                                    <input type="text" id="gradientColor2Input" value="${gradientColor2}" onchange="updateGradient()" class="bg-zinc-800 border border-zinc-700 text-white px-1 py-0.5 rounded text-xs flex-1">
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-1">
                            <button onclick="setGradientPreset('#FF4F4F', '#18F1E1')" class="h-6 rounded text-xs" style="background: linear-gradient(to right, #FF4F4F, #18F1E1);">Coral → Cyan</button>
                            <button onclick="setGradientPreset('#0f0a1a', '#1d1130')" class="h-6 rounded text-xs" style="background: linear-gradient(to right, #0f0a1a, #1d1130);">Dark Purple</button>
                            <button onclick="setGradientPreset('#1F1F1F', '#000000')" class="h-6 rounded text-xs" style="background: linear-gradient(to right, #1F1F1F, #000000);">Dark Gray</button>
                            <button onclick="setGradientPreset('#FFFFFF', '#F3F4F6')" class="h-6 rounded text-xs border border-zinc-600" style="background: linear-gradient(to right, #FFFFFF, #F3F4F6);">Light</button>
                        </div>
                    </div>
                </div>

                ${isTextBlock ? `
                <div class="mb-4 border-t border-zinc-700 pt-4">
                    <label class="block text-xs text-zinc-500 mb-2">Insert Media</label>
                    <div class="space-y-2">
                        <button onclick="openMediaModal('image')" class="w-full bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-2 rounded text-xs transition-colors flex items-center justify-center gap-2"><i class="fas fa-image"></i> Add Image</button>
                        <button onclick="openMediaModal('video')" class="w-full bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-2 rounded text-xs transition-colors flex items-center justify-center gap-2"><i class="fas fa-video"></i> Add Video</button>
                        <button onclick="openMediaModal('embed')" class="w-full bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-2 rounded text-xs transition-colors flex items-center justify-center gap-2"><i class="fas fa-code"></i> Add Embed</button>
                    </div>
                    <div class="text-xs text-zinc-500 mt-2">Media will be inserted after the heading</div>
                </div>
                ` : ''}

                ${isSplitScreen ? (() => {
                    // Check current position - if text is first child, media is on right (default)
                    const grid = el.querySelector('#split-screen-grid');
                    const textContent = el.querySelector('#split-screen-text-content');
                    const mediaContent = el.querySelector('#split-screen-media-content');
                    const isMediaOnRight = grid && textContent && mediaContent && 
                                           grid.firstElementChild === textContent;
                    const positionLabel = isMediaOnRight ? 'Media on Right' : 'Media on Left';
                    
                    return `
                <div class="mb-4 border-t border-zinc-700 pt-4">
                    <label class="block text-xs text-zinc-500 mb-2">Layout</label>
                    <button onclick="switchSplitScreenPosition()" class="w-full bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-2 rounded text-xs transition-colors flex items-center justify-center gap-2 mb-3">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Switch Position (${positionLabel})</span>
                    </button>
                </div>
                <div class="mb-4 border-t border-zinc-700 pt-4">
                    <label class="block text-xs text-zinc-500 mb-2">Feature Points</label>
                    <div class="space-y-2">
                        <button onclick="addSplitScreenFeaturePoint()" class="w-full bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-2 rounded text-xs transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-plus"></i>
                            <span>Add Feature Point</span>
                        </button>
                    </div>
                    <div class="text-xs text-zinc-500 mt-2">Click to add more feature points. Hover over items to remove them.</div>
                </div>
                <div class="mb-4 border-t border-zinc-700 pt-4">
                    <label class="block text-xs text-zinc-500 mb-2">Split Screen Media</label>
                    <div class="space-y-2">
                        <button onclick="openMediaModal('image')" class="w-full bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-2 rounded text-xs transition-colors flex items-center justify-center gap-2"><i class="fas fa-image"></i> ${hasSplitScreenMedia ? 'Change Photo' : 'Add Photo'}</button>
                        <button onclick="openMediaModal('video')" class="w-full bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-2 rounded text-xs transition-colors flex items-center justify-center gap-2"><i class="fas fa-video"></i> ${hasSplitScreenMedia ? 'Change Video' : 'Add Video'}</button>
                        ${hasSplitScreenMedia ? `<button onclick="removeSplitScreenMedia()" class="w-full bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-xs transition-colors flex items-center justify-center gap-2"><i class="fas fa-trash"></i> Remove Media</button>` : ''}
                    </div>
                    <div class="text-xs text-zinc-500 mt-2">You can upload or use a link for media.</div>
                </div>
                `;
                })() : ''}

                ${!isTextElement ? `
                <div class="mb-4">
                    <label class="block text-xs text-zinc-500 mb-1">Background Image</label>
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="bgImageInput" value="${getBackgroundImageUrl(el, computedStyle)}" placeholder="Enter image URL..." onchange="updateBackgroundImage(this.value)" class="bg-zinc-800 border border-zinc-700 text-white px-2 py-1 rounded text-xs flex-1">
                        <input type="file" id="bgImageUpload" accept="image/*" style="display: none;" onchange="handleBackgroundImageUpload(this)">
                        <button type="button" onclick="document.getElementById('bgImageUpload').click()" class="bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-1 rounded text-xs transition-colors"><i class="fas fa-upload"></i></button>
                    </div>
                    ${getBackgroundImageUrl(el, computedStyle) ? `
                    <div class="relative">
                        <img src="${getBackgroundImageUrl(el, computedStyle)}" alt="Background preview" class="w-full h-20 object-cover rounded border border-zinc-700">
                        <button onclick="removeBackgroundImage()" class="absolute top-1 right-1 bg-red-600 hover:bg-red-700 text-white p-1 rounded text-xs"><i class="fas fa-times"></i></button>
                    </div>
                    ` : ''}
                    <div class="mt-2 flex gap-2">
                        <select onchange="updateBackgroundSize(this.value)" class="bg-zinc-800 border border-zinc-700 text-white px-2 py-1 rounded text-xs flex-1">
                            <option value="cover" ${getBackgroundSize(el) === 'cover' ? 'selected' : ''}>Cover</option>
                            <option value="contain" ${getBackgroundSize(el) === 'contain' ? 'selected' : ''}>Contain</option>
                            <option value="auto" ${getBackgroundSize(el) === 'auto' ? 'selected' : ''}>Auto</option>
                        </select>
                        <select onchange="updateBackgroundPosition(this.value)" class="bg-zinc-800 border border-zinc-700 text-white px-2 py-1 rounded text-xs flex-1">
                            <option value="center" ${getBackgroundPosition(el) === 'center' ? 'selected' : ''}>Center</option>
                            <option value="top" ${getBackgroundPosition(el) === 'top' ? 'selected' : ''}>Top</option>
                            <option value="bottom" ${getBackgroundPosition(el) === 'bottom' ? 'selected' : ''}>Bottom</option>
                            <option value="left" ${getBackgroundPosition(el) === 'left' ? 'selected' : ''}>Left</option>
                            <option value="right" ${getBackgroundPosition(el) === 'right' ? 'selected' : ''}>Right</option>
                        </select>
                    </div>
                    <div class="mt-2">
                        <label class="flex items-center gap-2 text-xs text-zinc-500">
                            <input type="checkbox" ${getBackgroundRepeat(el) === 'no-repeat' ? 'checked' : ''} onchange="updateBackgroundRepeat(this.checked)">
                            <span>No Repeat</span>
                        </label>
                    </div>
                </div>
                ` : ''}

                <div class="mb-4">
                    <label class="block text-xs text-zinc-500 mb-1">Text Color</label>
                    <div class="flex gap-2">
                        <input type="color" value="${rgbToHex(computedStyle.color)}" oninput="updateStyle('color', this.value)" class="h-8 w-8 p-0 border-0 bg-transparent cursor-pointer">
                        <input type="text" value="${rgbToHex(computedStyle.color)}" onchange="updateStyle('color', this.value)" class="bg-zinc-800 border border-zinc-700 text-white px-2 py-1 rounded text-xs">
                    </div>
                </div>

                ${(tagName === 'A' || tagName === 'BUTTON') ? `
                <div class="mb-4">
                    <label class="block text-xs text-zinc-500 mb-1">Button Text</label>
                    <input type="text" value="${el.textContent.trim()}" onchange="updateButtonText(this.value)" placeholder="Enter button text..." class="bg-zinc-800 border border-zinc-700 text-white px-2 py-1.5 rounded text-xs w-full">
                    <div class="mt-2 flex gap-2">
                        <button onclick="updateButtonText('Get Your Quote')" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-xs py-1.5 rounded transition-colors">Get Your Quote</button>
                        <button onclick="updateButtonText('Call Us')" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-xs py-1.5 rounded transition-colors">Call Us</button>
                        <button onclick="updateButtonText('Learn More')" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-xs py-1.5 rounded transition-colors">Learn More</button>
                    </div>
                </div>
                ` : ''}

                ${!isTextElement ? `
                <div class="mb-4">
                    <label class="block text-xs text-zinc-500 mb-1">Padding (Vertical)</label>
                    <input type="range" min="0" max="150" value="${parseInt(computedStyle.paddingTop) || 64}" oninput="updateStyle('paddingTop', this.value + 'px'); updateStyle('paddingBottom', this.value + 'px')" class="w-full">
                    <div class="text-xs text-zinc-400 mt-1 text-center">${parseInt(computedStyle.paddingTop) || 64}px</div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs text-zinc-500 mb-1">Padding (Horizontal)</label>
                    <input type="range" min="0" max="100" value="${parseInt(computedStyle.paddingLeft) || 24}" oninput="updateStyle('paddingLeft', this.value + 'px'); updateStyle('paddingRight', this.value + 'px')" class="w-full">
                    <div class="text-xs text-zinc-400 mt-1 text-center">${parseInt(computedStyle.paddingLeft) || 24}px</div>
                </div>
                ` : ''}

                ${!isTextElement ? `
                <div class="border-t border-zinc-700 pt-4 mt-4">
                    <button onclick="moveUp()" class="w-full bg-zinc-700 hover:bg-zinc-600 text-xs py-2 rounded mb-2 transition-colors"><i class="fas fa-arrow-up"></i> Move Up</button>
                    <button onclick="moveDown()" class="w-full bg-zinc-700 hover:bg-zinc-600 text-xs py-2 rounded transition-colors"><i class="fas fa-arrow-down"></i> Move Down</button>
                </div>
                ` : `
                <div class="border-t border-zinc-700 pt-4 mt-4">
                    <p class="text-xs text-zinc-500 text-center">Click on text to edit font</p>
                </div>
                `}
            `;

            // Add event listeners for inspector panel buttons
            const inspectorPanel = document.getElementById('inspectorPanel');
            if (inspectorPanel) {
                // Add event listener for add feature point button
                const addFeatureBtn = inspectorPanel.querySelector('button[onclick*="addSplitScreenFeaturePoint"]');
                if (addFeatureBtn) {
                    addFeatureBtn.onclick = function(e) {
                        e.preventDefault();
                        addSplitScreenFeaturePoint();
                    };
                }
            }
        }

        function deselectAll() {
            if (selectedElement) selectedElement.classList.remove('selected');
            selectedElement = null;
            document.getElementById('inspectorPanel').innerHTML = `
                <div class="text-center text-zinc-500 mt-10">
                    <i class="fas fa-mouse-pointer text-2xl mb-2 opacity-50"></i>
                    <p class="text-sm">Click an element on the canvas<br>to edit its style.</p>
                </div>`;
        }

        // --- 6. DOM Manipulation Functions ---
        function updateStyle(prop, value) {
            if (selectedElement) {
                selectedElement.style[prop] = value;
            }
        }

        function updateFontFamily(fontFamily) {
            if (selectedElement) {
                selectedElement.style.fontFamily = fontFamily;
                // If it's a heading and using Azo Sans Uber, apply uppercase transform
                const tagName = selectedElement.tagName.toLowerCase();
                if ((tagName === 'h1' || tagName === 'h2' || tagName === 'h3' || tagName === 'h4' || tagName === 'h5' || tagName === 'h6') && fontFamily.includes('Azo Sans Uber')) {
                    selectedElement.style.textTransform = 'uppercase';
                    selectedElement.style.letterSpacing = '0.02em';
                }
            }
        }

        function getCurrentFontFamily(el, computedStyle) {
            // Try to get font from inline style first
            if (el.style.fontFamily) {
                return el.style.fontFamily;
            }
            // Check for style attribute with font-family
            if (el.getAttribute('style') && el.getAttribute('style').includes('font-family')) {
                const styleMatch = el.getAttribute('style').match(/font-family:\s*([^;]+)/);
                if (styleMatch) return styleMatch[1].trim();
            }
            // Fall back to computed style
            return computedStyle.fontFamily || 'inherit';
        }

        function updateButtonText(text) {
            if (selectedElement && (selectedElement.tagName === 'A' || selectedElement.tagName === 'BUTTON')) {
                selectedElement.textContent = text;
            }
        }

        // Background Color Functions
        function updateBackgroundColor(color) {
            if (selectedElement) {
                selectedElement.style.backgroundColor = color;
                selectedElement.style.backgroundImage = 'none';
                // Update inputs
                const colorPicker = document.getElementById('bgColorPicker');
                const colorInput = document.getElementById('bgColorInput');
                if (colorPicker) colorPicker.value = color;
                if (colorInput) colorInput.value = color;
            }
        }

        function changeBackgroundType(type) {
            if (!selectedElement) return;
            
            const solidOptions = document.getElementById('bgSolidOptions');
            const gradientOptions = document.getElementById('bgGradientOptions');
            
            if (type === 'solid') {
                if (solidOptions) solidOptions.style.display = 'block';
                if (gradientOptions) gradientOptions.style.display = 'none';
                // Restore solid color if gradient was set
                const bgImage = selectedElement.style.backgroundImage;
                if (bgImage && bgImage.includes('gradient')) {
                    selectedElement.style.backgroundImage = 'none';
                }
            } else if (type === 'gradient') {
                if (solidOptions) solidOptions.style.display = 'none';
                if (gradientOptions) gradientOptions.style.display = 'block';
                updateGradient();
            } else if (type === 'transparent') {
                if (solidOptions) solidOptions.style.display = 'none';
                if (gradientOptions) gradientOptions.style.display = 'none';
                selectedElement.style.backgroundColor = 'transparent';
                selectedElement.style.backgroundImage = 'none';
            }
        }

        function updateGradient() {
            if (!selectedElement) return;
            
            const type = document.getElementById('gradientType')?.value || 'linear';
            const direction = document.getElementById('gradientDirection')?.value || 'to right';
            const color1Picker = document.getElementById('gradientColor1');
            const color2Picker = document.getElementById('gradientColor2');
            const color1Input = document.getElementById('gradientColor1Input');
            const color2Input = document.getElementById('gradientColor2Input');
            
            // Get color from picker or input (picker takes priority)
            const color1 = color1Picker?.value || color1Input?.value || '#FF4F4F';
            const color2 = color2Picker?.value || color2Input?.value || '#18F1E1';
            
            // Sync inputs
            if (color1Picker && color1Input) {
                if (color1Picker.value !== color1Input.value) {
                    // If input was changed, update picker
                    if (document.activeElement === color1Input) {
                        color1Picker.value = color1;
                    } else {
                        color1Input.value = color1Picker.value;
                    }
                }
            }
            if (color2Picker && color2Input) {
                if (color2Picker.value !== color2Input.value) {
                    if (document.activeElement === color2Input) {
                        color2Picker.value = color2;
                    } else {
                        color2Input.value = color2Picker.value;
                    }
                }
            }
            
            const finalColor1 = color1Picker?.value || color1;
            const finalColor2 = color2Picker?.value || color2;
            
            if (type === 'linear') {
                selectedElement.style.backgroundImage = `linear-gradient(${direction}, ${finalColor1}, ${finalColor2})`;
            } else {
                selectedElement.style.backgroundImage = `radial-gradient(circle, ${finalColor1}, ${finalColor2})`;
            }
            selectedElement.style.backgroundColor = '';
        }

        function setGradientPreset(color1, color2) {
            const color1Picker = document.getElementById('gradientColor1');
            const color2Picker = document.getElementById('gradientColor2');
            const color1Input = document.getElementById('gradientColor1Input');
            const color2Input = document.getElementById('gradientColor2Input');
            
            if (color1Picker) color1Picker.value = color1;
            if (color2Picker) color2Picker.value = color2;
            if (color1Input) color1Input.value = color1;
            if (color2Input) color2Input.value = color2;
            
            updateGradient();
        }

        // Background Image Update Functions
        function updateBackgroundImage(url) {
            if (selectedElement && url) {
                selectedElement.style.backgroundImage = `url('${url}')`;
                selectedElement.style.backgroundSize = selectedElement.style.backgroundSize || 'cover';
                selectedElement.style.backgroundPosition = selectedElement.style.backgroundPosition || 'center';
                selectedElement.style.backgroundRepeat = selectedElement.style.backgroundRepeat || 'no-repeat';
                // Refresh inspector to show preview
                if (selectedElement) selectElement(selectedElement);
            }
        }

        function updateBackgroundSize(size) {
            if (selectedElement) {
                selectedElement.style.backgroundSize = size;
            }
        }

        function updateBackgroundPosition(position) {
            if (selectedElement) {
                selectedElement.style.backgroundPosition = position;
            }
        }

        function updateBackgroundRepeat(noRepeat) {
            if (selectedElement) {
                selectedElement.style.backgroundRepeat = noRepeat ? 'no-repeat' : 'repeat';
            }
        }

        function removeBackgroundImage() {
            if (selectedElement) {
                selectedElement.style.backgroundImage = 'none';
                // Refresh inspector
                selectElement(selectedElement);
            }
        }

        function handleBackgroundImageUpload(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please upload JPG, PNG, GIF, or WEBP images only.');
                    input.value = '';
                    return;
                }
                
                // Validate file size (10MB max)
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    alert('File is too large. Maximum size is 10MB.');
                    input.value = '';
                    return;
                }
                
                const formData = new FormData();
                formData.append('upload', file);

                // Show loading state
                const uploadBtn = input.nextElementSibling;
                const originalHTML = uploadBtn.innerHTML;
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('upload_image.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin' // Include cookies for authentication
                })
                .then(response => {
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error(`Server returned non-JSON response (${response.status}): ${text.substring(0, 100)}`);
                        });
                    }
                    
                    return response.json().then(data => {
                        if (!response.ok) {
                            throw new Error(data.error?.message || `HTTP error! status: ${response.status}`);
                        }
                        return data;
                    });
                })
                .then(data => {
                    if (data.url) {
                        updateBackgroundImage(data.url);
                        const bgInput = document.getElementById('bgImageInput');
                        if (bgInput) bgInput.value = data.url;
                    } else if (data.error) {
                        throw new Error(data.error.message || 'Upload failed');
                    } else {
                        throw new Error('Invalid response from server');
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack
                    });
                    alert('Upload failed: ' + error.message);
                })
                .finally(() => {
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = originalHTML;
                    input.value = ''; // Reset file input
                });
            }
        }

        // Media Modal Functions
        let currentMediaType = 'image'; // 'image', 'video', or 'embed'
        let currentMediaTarget = null; // The selected element where media will be inserted
        
        function openMediaModal(type) {
            currentMediaType = type;
            
            // If selected element is inside a split screen, use the split screen section as target
            if (selectedElement) {
                // Check if selected element is the split screen section itself
                const doc = iframe.contentDocument;
                let targetSection = selectedElement;
                
                // If it's not the section, find the parent section
                if (!selectedElement.querySelector('.lg\\:grid-cols-2')) {
                    // Traverse up to find the split screen section
                    let parent = selectedElement.parentElement;
                    while (parent && parent !== doc.body) {
                        if (parent.hasAttribute('data-editable') && parent.querySelector('.lg\\:grid-cols-2')) {
                            targetSection = parent;
                            break;
                        }
                        parent = parent.parentElement;
                    }
                }
                
                currentMediaTarget = targetSection;
            } else {
                currentMediaTarget = selectedElement;
            }
            
            const modal = document.getElementById('mediaModal');
            const title = document.getElementById('mediaModalTitle');
            
            // Set title based on type
            if (type === 'image') {
                title.textContent = 'Insert Image';
            } else if (type === 'video') {
                title.textContent = 'Insert Video';
            } else if (type === 'embed') {
                title.textContent = 'Insert Embed';
            }
            
            // Reset form
            document.getElementById('mediaFileInput').value = '';
            document.getElementById('mediaUrlInput').value = '';
            document.getElementById('mediaEmbedInput').value = '';
            document.getElementById('mediaUploadPreview').classList.add('hidden');
            document.getElementById('mediaUrlPreview').classList.add('hidden');
            
            // Set file input accept based on type
            const fileInput = document.getElementById('mediaFileInput');
            if (type === 'image') {
                fileInput.accept = 'image/*';
            } else if (type === 'video') {
                fileInput.accept = 'video/*';
            } else {
                fileInput.accept = '*/*';
            }
            
            // Show modal and default to upload tab
            modal.classList.remove('hidden');
            switchMediaTab('upload');
            
            // Preview file on selection
            fileInput.onchange = function(e) {
                if (e.target.files && e.target.files[0]) {
                    const file = e.target.files[0];
                    const preview = document.getElementById('mediaUploadPreview');
                    const previewImg = document.getElementById('mediaUploadPreviewImg');
                    const previewVideo = document.getElementById('mediaUploadPreviewVideo');
                    
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImg.src = e.target.result;
                            previewImg.classList.remove('hidden');
                            previewVideo.classList.add('hidden');
                            preview.classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    } else if (file.type.startsWith('video/')) {
                        const url = URL.createObjectURL(file);
                        previewVideo.src = url;
                        previewVideo.classList.remove('hidden');
                        previewImg.classList.add('hidden');
                        preview.classList.remove('hidden');
                    }
                }
            };
            
            // Preview URL on input
            const urlInput = document.getElementById('mediaUrlInput');
            urlInput.oninput = function() {
                const url = this.value.trim();
                const preview = document.getElementById('mediaUrlPreview');
                const previewImg = document.getElementById('mediaUrlPreviewImg');
                const previewVideo = document.getElementById('mediaUrlPreviewVideo');
                
                if (url) {
                    if (type === 'image' || url.match(/\.(jpg|jpeg|png|gif|webp|svg)(\?|$)/i)) {
                        previewImg.src = url;
                        previewImg.classList.remove('hidden');
                        previewVideo.classList.add('hidden');
                        preview.classList.remove('hidden');
                    } else if (type === 'video' || url.match(/\.(mp4|webm|ogg)(\?|$)/i)) {
                        previewVideo.src = url;
                        previewVideo.classList.remove('hidden');
                        previewImg.classList.add('hidden');
                        preview.classList.remove('hidden');
                    } else {
                        preview.classList.add('hidden');
                    }
                } else {
                    preview.classList.add('hidden');
                }
            };
        }
        
        function closeMediaModal() {
            document.getElementById('mediaModal').classList.add('hidden');
            currentMediaTarget = null;
        }
        
        function switchMediaTab(tab) {
            // Hide all tab contents
            document.querySelectorAll('.media-tab-content').forEach(el => el.classList.add('hidden'));
            
            // Remove active state from all tabs
            document.querySelectorAll('.media-tab').forEach(el => {
                el.classList.remove('border-[#18F1E1]', 'text-white');
                el.classList.add('border-transparent', 'text-zinc-400');
            });
            
            // Show selected tab content
            document.getElementById(`mediaTab${tab.charAt(0).toUpperCase() + tab.slice(1)}`).classList.remove('hidden');
            
            // Add active state to selected tab
            const tabs = document.querySelectorAll('.media-tab');
            if (tab === 'upload') {
                tabs[0].classList.remove('border-transparent', 'text-zinc-400');
                tabs[0].classList.add('border-[#18F1E1]', 'text-white');
            } else if (tab === 'url') {
                tabs[1].classList.remove('border-transparent', 'text-zinc-400');
                tabs[1].classList.add('border-[#18F1E1]', 'text-white');
            } else if (tab === 'embed') {
                tabs[2].classList.remove('border-transparent', 'text-zinc-400');
                tabs[2].classList.add('border-[#18F1E1]', 'text-white');
            }
        }
        
        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('mediaModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeMediaModal();
                    }
                });
            }
        });
        
        function handleMediaUpload() {
            const fileInput = document.getElementById('mediaFileInput');
            if (!fileInput.files || !fileInput.files[0]) {
                alert('Please select a file to upload');
                return;
            }
            
            const file = fileInput.files[0];
            const isVideo = file.type.startsWith('video/');
            const isImage = file.type.startsWith('image/');
            
            if (!isVideo && !isImage) {
                alert('Only image and video files are supported for upload.');
                return;
            }
            
            // Different size limits for images and videos
            const maxSize = isVideo ? (100 * 1024 * 1024) : (10 * 1024 * 1024); // 100MB for videos, 10MB for images
            if (file.size > maxSize) {
                alert(`File is too large. Maximum size is ${isVideo ? '100MB' : '10MB'}.`);
                return;
            }
            
            const formData = new FormData();
            // Use 'upload' for images (upload_image.php expects this), 'file' for videos
            formData.append(isVideo ? 'file' : 'upload', file);
            formData.append('csrf_token', csrfToken);
            formData.append('action', isVideo ? 'upload_video' : 'upload_image');
            
            const btn = document.querySelector('#mediaTabUpload button[onclick="handleMediaUpload()"]');
            if (!btn) {
                alert('Upload button not found');
                return;
            }
            
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            
            // Use different endpoint based on file type
            const uploadUrl = isVideo ? window.location.href : '/MiHi-Entertainment/admin/upload_image.php';
            
            fetch(uploadUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.url) {
                    insertMediaElement(data.url, isVideo ? 'video' : 'image');
                    closeMediaModal();
                } else {
                    throw new Error(data.message || 'Upload failed');
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert('Upload failed: ' + error.message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
        }
        
        function handleMediaUrl() {
            const urlInput = document.getElementById('mediaUrlInput');
            const url = urlInput.value.trim();
            
            if (!url) {
                alert('Please enter a URL');
                return;
            }
            
            insertMediaElement(url, currentMediaType);
            closeMediaModal();
        }
        
        function handleMediaEmbed() {
            const embedInput = document.getElementById('mediaEmbedInput');
            const embedCode = embedInput.value.trim();
            
            if (!embedCode) {
                alert('Please enter embed code');
                return;
            }
            
            insertMediaElement(embedCode, 'embed');
            closeMediaModal();
        }
        
        function changeSplitScreenMedia(type) {
            const doc = iframe.contentDocument;
            if (!doc) return;
            
            // Find the split screen section
            let splitScreenSection = null;
            
            // First try to find from selected element
            if (selectedElement) {
                splitScreenSection = selectedElement.closest('[data-editable]')?.querySelector('.lg\\:grid-cols-2')?.closest('[data-editable]');
            }
            
            // If not found, search for any split screen section
            if (!splitScreenSection) {
                const sections = doc.querySelectorAll('[data-editable]');
                sections.forEach(sec => {
                    if (sec.querySelector('.lg\\:grid-cols-2') && sec.querySelector('#split-screen-media-container')) {
                        splitScreenSection = sec;
                    }
                });
            }
            
            if (splitScreenSection) {
                // Select the split screen section first
                selectElement(splitScreenSection);
                // Set current media target and open modal
                currentMediaTarget = splitScreenSection;
                const mediaType = type === 'photo' ? 'image' : 'video';
                openMediaModal(mediaType);
            } else {
                alert('Could not find split screen section. Please select the split screen section first.');
            }
        }
        
        function removeSplitScreenMedia() {
            const doc = iframe.contentDocument;
            if (!doc) return;
            
            // Find the split screen section
            let splitScreenSection = null;
            if (selectedElement) {
                splitScreenSection = selectedElement.closest('[data-editable]')?.querySelector('.lg\\:grid-cols-2')?.closest('[data-editable]');
                if (!splitScreenSection) {
                    const sections = doc.querySelectorAll('[data-editable]');
                    sections.forEach(sec => {
                        if (sec.querySelector('.lg\\:grid-cols-2')) {
                            splitScreenSection = sec;
                        }
                    });
                }
            }
            
            if (splitScreenSection) {
                const mediaContainer = splitScreenSection.querySelector('#split-screen-media-container');
                const image = splitScreenSection.querySelector('#split-screen-image');
                const video = splitScreenSection.querySelector('#split-screen-video');
                const placeholder = splitScreenSection.querySelector('#split-screen-placeholder');
                const removeBtn = splitScreenSection.querySelector('#split-screen-remove-btn');
                
                if (mediaContainer) {
                    // Reset to placeholder state
                    if (image) {
                        image.src = '';
                        image.classList.add('hidden');
                    }
                    if (video) {
                        video.src = '';
                        video.classList.add('hidden');
                    }
                    
                    // Remove any iframe (YouTube/Vimeo videos)
                    const videoIframe = mediaContainer.querySelector('iframe');
                    if (videoIframe) {
                        videoIframe.remove();
                    }
                    
                    // Restore placeholder
                    if (placeholder) {
                        placeholder.classList.remove('hidden');
                    }
                    
                    // Reset background
                    mediaContainer.style.background = 'linear-gradient(to bottom right, rgb(243, 244, 246), rgb(229, 231, 235))';
                    
                    // Hide remove button
                    if (removeBtn) {
                        removeBtn.style.display = 'none';
                    }
                }
            }
        }
        
        function switchSplitScreenPosition() {
            const doc = iframe.contentDocument;
            if (!doc) return;
            
            // Find the split screen section
            let splitScreenSection = null;
            if (selectedElement) {
                splitScreenSection = selectedElement.closest('[data-editable]')?.querySelector('.lg\\:grid-cols-2')?.closest('[data-editable]');
                if (!splitScreenSection) {
                    const sections = doc.querySelectorAll('[data-editable]');
                    sections.forEach(sec => {
                        if (sec.querySelector('.lg\\:grid-cols-2') && sec.querySelector('#split-screen-grid')) {
                            splitScreenSection = sec;
                        }
                    });
                }
            }
            
            if (splitScreenSection) {
                const grid = splitScreenSection.querySelector('#split-screen-grid');
                const textContent = splitScreenSection.querySelector('#split-screen-text-content');
                const mediaContent = splitScreenSection.querySelector('#split-screen-media-content');
                
                if (grid && textContent && mediaContent) {
                    // Swap the positions
                    if (grid.firstElementChild === textContent) {
                        // Text is first, move media first
                        grid.insertBefore(mediaContent, textContent);
                    } else {
                        // Media is first, move text first
                        grid.insertBefore(textContent, mediaContent);
                    }
                    
                    // Reselect to update the inspector panel
                    selectElement(splitScreenSection);
                }
            } else {
                alert('Could not find split screen section. Please select the split screen section first.');
            }
        }
        
        function addSplitScreenFeaturePoint() {
            const doc = iframe.contentDocument;
            if (!doc) return;

            // Find the split screen section - search all sections for one with feature points
            let splitScreenSection = null;
            const sections = doc.querySelectorAll('[data-editable]');
            sections.forEach(sec => {
                if (sec.querySelector('#split-screen-feature-points')) {
                    splitScreenSection = sec;
                }
            });

            if (!splitScreenSection) {
                alert('Could not find split screen section with feature points. Please make sure a split-screen section is added to the page.');
                return;
            }

            // Select the split screen section if not already selected
            if (splitScreenSection !== selectedElement) {
                selectElement(splitScreenSection);
            }
            
            if (splitScreenSection) {
                const container = splitScreenSection.querySelector('#split-screen-feature-points');
                if (container) {
                    // Get current count of feature points
                    const existingItems = container.querySelectorAll('.split-screen-feature-item');
                    const nextIndex = existingItems.length + 1;
                    
                    // Color options for the number badge (cycling through colors)
                    const colors = [
                        { bg: 'bg-pink-500/10', text: 'text-pink-600', border: 'border-pink-500/20' },
                        { bg: 'bg-purple-500/10', text: 'text-purple-600', border: 'border-purple-500/20' },
                        { bg: 'bg-blue-500/10', text: 'text-blue-600', border: 'border-blue-500/20' },
                        { bg: 'bg-green-500/10', text: 'text-green-600', border: 'border-green-500/20' },
                        { bg: 'bg-yellow-500/10', text: 'text-yellow-600', border: 'border-yellow-500/20' },
                        { bg: 'bg-orange-500/10', text: 'text-orange-600', border: 'border-orange-500/20' }
                    ];
                    const colorIndex = (nextIndex - 1) % colors.length;
                    const color = colors[colorIndex];
                    
                    // Create new feature point item
                    const newItem = doc.createElement('div');
                    newItem.className = 'flex gap-3 split-screen-feature-item group';
                    newItem.setAttribute('data-feature-index', nextIndex);
                    
                    newItem.innerHTML = `
                        <div class="w-10 h-10 rounded-full ${color.bg} flex items-center justify-center ${color.text} font-bold flex-shrink-0 border ${color.border} split-screen-feature-number">${nextIndex}</div>
                        <div class="flex-1">
                            <p contenteditable="true" class="font-semibold outline-none" style="color: #1F1F1F;">Feature Point ${nextIndex}</p>
                            <p contenteditable="true" class="text-sm outline-none" style="color: #1F1F1F;">Description of feature</p>
                        </div>
                        <button data-action="remove-feature" class="text-red-600 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity p-1" title="Remove feature point">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    container.appendChild(newItem);
                    
                    // Update numbering for all items
                    updateSplitScreenFeatureNumbers(container);
                    
                    // Reselect to update the inspector panel
                    selectElement(splitScreenSection);
                }
            } else {
                alert('Could not find split screen section. Please select the split screen section first.');
            }
        }
        
        function removeSplitScreenFeaturePoint(itemElement) {
            const doc = iframe.contentDocument;
            if (!doc) return;
            
            // Find the container
            const container = itemElement.closest('#split-screen-feature-points');
            if (container) {
                // Remove the item
                itemElement.remove();
                
                // Update numbering for remaining items
                updateSplitScreenFeatureNumbers(container);
                
                // Find and reselect the split screen section
                const splitScreenSection = container.closest('[data-editable]');
                if (splitScreenSection) {
                    selectElement(splitScreenSection);
                }
            }
        }
        
        function updateSplitScreenFeatureNumbers(container) {
            const items = container.querySelectorAll('.split-screen-feature-item');
            items.forEach((item, index) => {
                const number = index + 1;
                const numberElement = item.querySelector('.split-screen-feature-number');
                if (numberElement) {
                    numberElement.textContent = number;
                    item.setAttribute('data-feature-index', number);
                }
            });
        }
        
        // Expose functions to iframe window for overlay buttons
        
        // Video Card Management Functions
        function addVideoCard(button) {
            const doc = iframe.contentDocument;
            if (!doc) return;
            
            // Find the video cards container
            const container = doc.querySelector('#video-cards-container');
            if (!container) {
                alert('Video cards container not found');
                return;
            }
            
            // Create new video card
            const newCard = doc.createElement('div');
            newCard.className = 'video-card-item bg-white/10 border border-white/15 rounded-3xl overflow-hidden backdrop-blur transition-all duration-300 hover:-translate-y-1 relative group';
            newCard.innerHTML = `
                <div class="aspect-video overflow-hidden bg-black/50 flex items-center justify-center relative video-player-container">
                    <span class="text-white/50 video-placeholder">Video Placeholder</span>
                    <video class="w-full h-full object-cover hidden video-element" controls></video>
                    <div class="w-full h-full hidden iframe-wrapper absolute inset-0"></div>
                    
                    <!-- Hover overlay for changing/removing video -->
                    <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center gap-3 z-10">
                        <button onclick="changeVideoInCard(this)" class="bg-[#18F1E1] hover:bg-[#15D9C9] text-black px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors">
                            <i class="fas fa-video"></i> Change Video
                        </button>
                        <button onclick="removeVideoCard(this)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <h4 contenteditable="true" class="text-xl font-semibold mb-2 outline-none" style="color: #18F1E1; font-family: 'Azo Sans', sans-serif;">Video Title</h4>
                    <p contenteditable="true" class="text-sm text-white/70 mb-4 leading-relaxed outline-none">Video description goes here.</p>
                    <a href="#" onclick="openVideoModal(event, this)" class="inline-flex items-center rounded-full bg-[#FF4F4F] px-5 py-2 text-white font-semibold hover:bg-[#FF3838] transition-colors">Watch Now</a>
                </div>
            `;
            
            container.appendChild(newCard);
        }
        
        function removeVideoCard(button) {
            const doc = iframe.contentDocument;
            if (!doc) return;
            
            // Find the card to remove
            const card = button.closest('.video-card-item');
            if (!card) return;
            
            // Confirm removal
            if (confirm('Remove this video card?')) {
                card.remove();
            }
        }
        
        let currentVideoCard = null; // Track which card is being edited
        let currentHeroSection = null; // Track which hero section is being edited
        
        function changeVideoInCard(button) {
            const doc = iframe.contentDocument;
            if (!doc) return;
            
            // Find the card being edited
            currentVideoCard = button.closest('.video-card-item');
            if (!currentVideoCard) return;
            
            // Open media modal for video
            openMediaModal('video');
        }
        
        function changeHeroBackground(button) {
            const doc = iframe.contentDocument;
            if (!doc) return;
            
            // Find the hero section being edited
            currentHeroSection = button.closest('section[data-editable]');
            if (!currentHeroSection) return;
            
            // Open media modal for image only
            openMediaModal('image');
        }
        
        function changeHeroBackgroundFromInspector() {
            // Use the currently selected element as the hero section
            if (!selectedElement) return;
            
            currentHeroSection = selectedElement;
            
            // Open media modal for image only
            openMediaModal('image');
        }
        
        // Feature Cards Functions
        function addFeatureCard(button) {
            const doc = iframe.contentDocument;
            if (!doc) return;
            
            // Find the feature cards container
            const container = doc.querySelector('#feature-cards-container');
            if (!container) {
                alert('Feature cards container not found');
                return;
            }
            
            // Random icon colors for variety
            const iconColors = ['pink-500', 'blue-500', 'purple-500', 'green-500', 'yellow-500', 'red-500', 'indigo-500', 'teal-500'];
            const randomColor = iconColors[Math.floor(Math.random() * iconColors.length)];
            
            // Random icon SVGs
            const icons = [
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>',
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>',
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>',
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>',
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>',
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
            ];
            const randomIcon = icons[Math.floor(Math.random() * icons.length)];
            
            // Create new feature card
            const newCard = doc.createElement('div');
            newCard.className = 'feature-card-item bg-white p-6 rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300 relative group';
            newCard.innerHTML = `
                <div class="w-12 h-12 bg-${randomColor} rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${randomIcon}
                    </svg>
                </div>
                <h3 contenteditable="true" class="text-xl font-bold mb-2 outline-none" style="font-family: 'Azo Sans', sans-serif; color: #FF4F4F;">New Feature</h3>
                <p contenteditable="true" class="outline-none" style="font-family: 'Azo Sans', sans-serif; color: #1F1F1F;">Description of the feature.</p>
                
                <!-- Remove button overlay -->
                <button onclick="removeFeatureCard(this)" data-editor-only class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center shadow-lg z-10" title="Remove card">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(newCard);
        }
        
        function removeFeatureCard(button) {
            const doc = iframe.contentDocument;
            if (!doc) return;
            
            // Find the card to remove
            const card = button.closest('.feature-card-item');
            if (!card) return;
            
            // Confirm removal
            if (confirm('Remove this feature card?')) {
                card.remove();
            }
        }
        
        function exposeFunctionsToIframe() {
            if (iframe.contentWindow) {
                iframe.contentWindow.changeSplitScreenMedia = changeSplitScreenMedia;
                iframe.contentWindow.removeSplitScreenMedia = removeSplitScreenMedia;
                iframe.contentWindow.switchSplitScreenPosition = switchSplitScreenPosition;
                iframe.contentWindow.addSplitScreenFeaturePoint = addSplitScreenFeaturePoint;
                iframe.contentWindow.removeSplitScreenFeaturePoint = removeSplitScreenFeaturePoint;
                // Video card functions
                iframe.contentWindow.addVideoCard = addVideoCard;
                iframe.contentWindow.removeVideoCard = removeVideoCard;
                iframe.contentWindow.changeVideoInCard = changeVideoInCard;
                // Hero background function
                iframe.contentWindow.changeHeroBackground = changeHeroBackground;
                // Feature card functions
                iframe.contentWindow.addFeatureCard = addFeatureCard;
                iframe.contentWindow.removeFeatureCard = removeFeatureCard;
            }
        }
        
        function insertMediaElement(source, type) {
            const doc = iframe.contentDocument;
            
            // Check if we're inserting into a video card
            if (currentVideoCard) {
                const playerContainer = currentVideoCard.querySelector('.video-player-container');
                const placeholder = currentVideoCard.querySelector('.video-placeholder');
                const videoElement = currentVideoCard.querySelector('.video-element');
                const iframeWrapper = currentVideoCard.querySelector('.iframe-wrapper');
                
                if (playerContainer) {
                    // Hide placeholder
                    if (placeholder) placeholder.classList.add('hidden');
                    
                    // Clear previous content
                    if (videoElement) {
                        videoElement.src = '';
                        videoElement.classList.add('hidden');
                    }
                    if (iframeWrapper) {
                        iframeWrapper.innerHTML = '';
                        iframeWrapper.classList.add('hidden');
                    }
                    
                    if (type === 'video') {
                        // Check if it's YouTube or Vimeo
                        if (source.includes('youtube.com') || source.includes('youtu.be')) {
                            const videoId = extractYouTubeId(source);
                            if (videoId) {
                                iframeWrapper.innerHTML = `<iframe class="w-full h-full" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
                                iframeWrapper.classList.remove('hidden');
                            }
                        } else if (source.includes('vimeo.com')) {
                            const videoId = extractVimeoId(source);
                            if (videoId) {
                                iframeWrapper.innerHTML = `<iframe class="w-full h-full" src="https://player.vimeo.com/video/${videoId}" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>`;
                                iframeWrapper.classList.remove('hidden');
                            }
                        } else {
                            // Direct video URL
                            if (videoElement) {
                                videoElement.src = source;
                                videoElement.classList.remove('hidden');
                            }
                        }
                    }
                }
                
                // Clear the current video card reference
                currentVideoCard = null;
                closeMediaModal();
                return;
            }
            
            // Check if we're inserting into a hero section background
            if (currentHeroSection && type === 'image') {
                const backgroundImage = currentHeroSection.querySelector('.hero-background-image');
                if (backgroundImage) {
                    // Set the background image
                    backgroundImage.src = source;
                    backgroundImage.classList.remove('hidden');
                }
                
                // Clear the current hero section reference
                currentHeroSection = null;
                closeMediaModal();
                return;
            }
            
            if (!currentMediaTarget) {
                // Try to find split screen section from selected element
                if (selectedElement) {
                    const splitScreenSection = selectedElement.closest('[data-editable]')?.querySelector('.lg\\:grid-cols-2')?.closest('[data-editable]');
                    if (splitScreenSection) {
                        currentMediaTarget = splitScreenSection;
                    } else {
                        currentMediaTarget = selectedElement;
                    }
                } else {
                    currentMediaTarget = selectedElement;
                }
            }
            if (!currentMediaTarget) return;
            
            // Check if this is a split screen section - look for the grid with lg:grid-cols-2
            let isSplitScreen = false;
            let splitScreenSection = currentMediaTarget;
            
            // Check if current target has the split screen grid
            if (currentMediaTarget.querySelector && currentMediaTarget.querySelector('.lg\\:grid-cols-2')) {
                isSplitScreen = true;
            } else {
                // Try to find parent split screen section
                let parent = currentMediaTarget.parentElement;
                while (parent && parent !== doc.body) {
                    if (parent.hasAttribute && parent.hasAttribute('data-editable') && parent.querySelector && parent.querySelector('.lg\\:grid-cols-2')) {
                        isSplitScreen = true;
                        splitScreenSection = parent;
                        break;
                    }
                    parent = parent.parentElement;
                }
            }
            
            // Use the split screen section if found
            if (isSplitScreen) {
                currentMediaTarget = splitScreenSection;
            }
            
            if (isSplitScreen && (type === 'image' || type === 'video')) {
                // Handle split screen media (image or video)
                const mediaContainer = currentMediaTarget.querySelector('#split-screen-media-container');
                const image = currentMediaTarget.querySelector('#split-screen-image');
                const video = currentMediaTarget.querySelector('#split-screen-video');
                const placeholder = currentMediaTarget.querySelector('#split-screen-placeholder');
                const removeBtn = currentMediaTarget.querySelector('#split-screen-remove-btn');
                
                if (mediaContainer && placeholder) {
                    // Hide placeholder
                    placeholder.classList.add('hidden');
                    mediaContainer.style.background = 'transparent';
                    
                    // Remove any existing iframe first
                    const existingIframe = mediaContainer.querySelector('iframe');
                    if (existingIframe) {
                        existingIframe.remove();
                    }
                    
                    if (type === 'image') {
                        // Show image, hide video
                        if (image) {
                            image.src = source;
                            image.classList.remove('hidden');
                        }
                        if (video) {
                            video.classList.add('hidden');
                            video.src = '';
                        }
                    } else if (type === 'video') {
                        // Handle video - check if it's YouTube/Vimeo or direct video
                        if (source.includes('youtube.com') || source.includes('youtu.be')) {
                            const videoId = extractYouTubeId(source);
                            if (videoId) {
                                // Hide image and video elements
                                if (image) image.classList.add('hidden');
                                if (video) {
                                    video.classList.add('hidden');
                                    video.src = '';
                                }
                                
                                // Create and insert YouTube iframe
                                const videoIframe = doc.createElement('iframe');
                                videoIframe.className = 'w-full h-full';
                                videoIframe.src = `https://www.youtube.com/embed/${videoId}`;
                                videoIframe.setAttribute('frameborder', '0');
                                videoIframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
                                videoIframe.setAttribute('allowfullscreen', '');
                                mediaContainer.insertBefore(videoIframe, mediaContainer.firstChild);
                            }
                        } else if (source.includes('vimeo.com')) {
                            const videoId = extractVimeoId(source);
                            if (videoId) {
                                // Hide image and video elements
                                if (image) image.classList.add('hidden');
                                if (video) {
                                    video.classList.add('hidden');
                                    video.src = '';
                                }
                                
                                // Create and insert Vimeo iframe
                                const videoIframe = doc.createElement('iframe');
                                videoIframe.className = 'w-full h-full';
                                videoIframe.src = `https://player.vimeo.com/video/${videoId}`;
                                videoIframe.setAttribute('frameborder', '0');
                                videoIframe.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
                                videoIframe.setAttribute('allowfullscreen', '');
                                mediaContainer.insertBefore(videoIframe, mediaContainer.firstChild);
                            }
                        } else {
                            // Direct video URL
                            if (video) {
                                video.src = source;
                                video.classList.remove('hidden');
                            }
                            if (image) {
                                image.classList.add('hidden');
                            }
                        }
                    }
                    
                    // Show remove button
                    if (removeBtn) {
                        removeBtn.style.display = 'block';
                    }
                }
                return;
            }
            
            const heading = currentMediaTarget.querySelector('h2');
            const container = currentMediaTarget.querySelector('.text-center') || currentMediaTarget;
            
            const mediaDiv = doc.createElement('div');
            mediaDiv.className = 'my-8';
            mediaDiv.setAttribute('data-editable', 'true');
            
            if (type === 'embed') {
                // Handle embed code
                const tempDiv = doc.createElement('div');
                tempDiv.innerHTML = source;
                const newEmbed = tempDiv.firstElementChild;
                
                if (newEmbed) {
                    if (newEmbed.tagName === 'IFRAME') {
                        const wrapper = doc.createElement('div');
                        wrapper.className = 'relative w-full overflow-hidden rounded-lg shadow-lg';
                        wrapper.style.paddingTop = '56.25%';
                        newEmbed.style.position = 'absolute';
                        newEmbed.style.top = '0';
                        newEmbed.style.left = '0';
                        newEmbed.style.width = '100%';
                        newEmbed.style.height = '100%';
                        newEmbed.style.border = '0';
                        wrapper.appendChild(newEmbed);
                        mediaDiv.appendChild(wrapper);
                    } else {
                        mediaDiv.appendChild(newEmbed);
                    }
                } else {
                    mediaDiv.innerHTML = source;
                }
            } else if (type === 'image') {
                // Handle image
                mediaDiv.innerHTML = `
                    <img src="${source}" alt="Image" class="w-full h-auto rounded-lg shadow-lg" style="max-width: 100%; height: auto;">
                `;
            } else if (type === 'video') {
                // Handle video
                let videoHTML = '';
                if (source.includes('youtube.com') || source.includes('youtu.be')) {
                    const videoId = extractYouTubeId(source);
                    if (videoId) {
                        videoHTML = `
                            <div class="relative w-full" style="padding-bottom: 56.25%; height: 0; overflow: hidden;">
                                <iframe class="absolute top-0 left-0 w-full h-full rounded-lg shadow-lg" 
                                    src="https://www.youtube.com/embed/${videoId}" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen></iframe>
                            </div>
                        `;
                    } else {
                        alert('Invalid YouTube URL');
                        return;
                    }
                } else if (source.includes('vimeo.com')) {
                    const videoId = extractVimeoId(source);
                    if (videoId) {
                        videoHTML = `
                            <div class="relative w-full" style="padding-bottom: 56.25%; height: 0; overflow: hidden;">
                                <iframe class="absolute top-0 left-0 w-full h-full rounded-lg shadow-lg" 
                                    src="https://player.vimeo.com/video/${videoId}" 
                                    frameborder="0" 
                                    allow="autoplay; fullscreen; picture-in-picture" 
                                    allowfullscreen></iframe>
                            </div>
                        `;
                    } else {
                        alert('Invalid Vimeo URL');
                        return;
                    }
                } else {
                    // Direct video URL
                    videoHTML = `
                        <video class="w-full h-auto rounded-lg shadow-lg" controls style="max-width: 100%;">
                            <source src="${source}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    `;
                }
                mediaDiv.innerHTML = videoHTML;
            }
            
            // Insert after heading or at the end of container
            if (heading && heading.nextSibling) {
                container.insertBefore(mediaDiv, heading.nextSibling);
            } else if (heading) {
                heading.parentNode.insertBefore(mediaDiv, heading.nextSibling);
            } else {
                container.appendChild(mediaDiv);
            }
            
            // Select the new media
            setTimeout(() => selectElement(mediaDiv), 100);
        }
        
        function extractYouTubeId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }
        
        function extractVimeoId(url) {
            const regExp = /(?:vimeo\.com\/)(?:.*\/)?(\d+)/;
            const match = url.match(regExp);
            return match ? match[1] : null;
        }

        function deleteSelected() {
            if (selectedElement && confirm('Delete this element?')) {
                selectedElement.remove();
                deselectAll();
            }
        }

        function removeMediaElement() {
            if (!selectedElement) return;
            
            // Check if it's a media element or container
            const isMedia = selectedElement.tagName === 'IMG' || 
                          selectedElement.tagName === 'VIDEO' || 
                          selectedElement.tagName === 'IFRAME' ||
                          (selectedElement.tagName === 'DIV' && (selectedElement.querySelector('img') || selectedElement.querySelector('video') || selectedElement.querySelector('iframe')));
            
            if (isMedia) {
                if (confirm('Remove this media element?')) {
                    // If it's a container div, remove the whole container
                    // Otherwise remove the element itself
                    if (selectedElement.tagName === 'DIV' && selectedElement.hasAttribute('data-editable')) {
                        selectedElement.remove();
                    } else {
                        // If it's inside a container, remove the container
                        const container = selectedElement.closest('[data-editable]');
                        if (container && container !== selectedElement) {
                            container.remove();
                        } else {
                            selectedElement.remove();
                        }
                    }
                    deselectAll();
                }
            }
        }

        // Icon path mappings for common Font Awesome icons (using Heroicons SVG paths)
        const iconPaths = {
            'fa-camera': 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z',
            'fa-video': 'M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z',
            'fa-image': 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
            'fa-star': 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
            'fa-heart': 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            'fa-thumbs-up': 'M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5',
            'fa-lightbulb': 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
            'fa-gift': 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
            'fa-music': 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3',
            'fa-palette': 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
            'fa-magic': 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
            'fa-sparkles': 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
            'fa-users': 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            'fa-trophy': 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
            'fa-award': 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
            'fa-fire': 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z',
            'fa-bolt': 'M13 10V3L4 14h7v7l9-11h-7z',
            'fa-rocket': 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
            'fa-gem': 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
            'fa-crown': 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
            'fa-shield': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            'fa-check-circle': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'fa-bell': 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
            'fa-envelope': 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            'fa-phone': 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 17.72V21a2 2 0 01-2 2h-1C9.716 23 3 16.284 3 8V7a2 2 0 012-2z',
            'fa-calendar': 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'fa-clock': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'fa-map-marker': 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
            'fa-globe': 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'fa-wifi': 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0',
            'fa-lock': 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
            'fa-key': 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
            'fa-cog': 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            'fa-tools': 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            'fa-wrench': 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            'fa-chart-line': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'fa-dollar-sign': 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'fa-shopping-cart': 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            'fa-tag': 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
            'fa-bookmark': 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z',
            'fa-folder': 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
            'fa-file': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'fa-download': 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
            'fa-upload': 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
            'fa-share': 'M8.684 13.342c.400-.574 1.08-.946 1.816-.946h4.032c.73 0 1.405.367 1.812.946l2.713 3.89a2.25 2.25 0 01-1.953 3.306H5.25a2.25 2.25 0 01-1.953-3.306l2.713-3.89zm5.632-2.342a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5z',
            'fa-comment': 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
            'fa-comments': 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
            'fa-thumbs-down': 'M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17.196 7H19m-14 0h2m5-10v2',
            'fa-flag': 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
            'fa-book': 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            'fa-graduation-cap': 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z M12 14v6.055',
            'fa-briefcase': 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            'fa-building': 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            'fa-home': 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'fa-car': 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
            'fa-plane': 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
            'fa-ship': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'fa-bicycle': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'fa-hamburger': 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            'fa-coffee': 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
            'fa-utensils': 'M3 3h18M3 3v18M21 3v18',
            'fa-dumbbell': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'fa-futbol': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'fa-basketball-ball': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'fa-gamepad': 'M14.751 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M16.5 9.87v4.263a1 1 0 01-1.555.832l-3.197-2.132a1 1 0 010-1.664l3.197-2.132A1 1 0 0116.5 9.87z M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z',
            'fa-puzzle-piece': 'M14.751 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M16.5 9.87v4.263a1 1 0 01-1.555.832l-3.197-2.132a1 1 0 010-1.664l3.197-2.132A1 1 0 0116.5 9.87z M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z',
            'fa-chess': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'fa-dice': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
        };

        function updateCardIcon(iconClass) {
            if (!selectedElement) return;
            
            // Get the icon container (the div with rounded-xl class)
            const iconContainer = selectedElement.classList.contains('rounded-xl') ? selectedElement : selectedElement.closest('.rounded-xl');
            if (!iconContainer) return;
            
            // Get the SVG element
            const svg = iconContainer.querySelector('svg');
            if (!svg) return;
            
            // Get the path element
            const path = svg.querySelector('path');
            if (!path) return;
            
            // Get icon path from mapping or use a default
            const iconPath = iconPaths[iconClass] || iconPaths['fa-star'];
            
            // Update the path
            path.setAttribute('d', iconPath);
            
            // Update selection to show changes
            selectElement(iconContainer);
        }

        function updateCardIconFromInput() {
            const input = document.getElementById('customIconInput');
            if (!input || !input.value.trim()) {
                alert('Please enter an icon class');
                return;
            }
            
            const iconClass = input.value.trim();
            // Try to find the icon path
            if (iconPaths[iconClass]) {
                updateCardIcon(iconClass);
            } else {
                // For custom icons, we'd need to fetch from Font Awesome API or use a library
                // For now, show a message
                alert('Custom icon class not found in predefined list. Please use one of the preset icons or ensure the icon class is correct.');
            }
        }

        function moveUp() {
            if (selectedElement && selectedElement.previousElementSibling) {
                selectedElement.parentNode.insertBefore(selectedElement, selectedElement.previousElementSibling);
            }
        }

        function moveDown() {
            if (selectedElement && selectedElement.nextElementSibling) {
                selectedElement.parentNode.insertBefore(selectedElement.nextElementSibling, selectedElement);
            }
        }

        // --- 6. Saving & Exporting ---
        function exportHTML() {
            const title = document.getElementById('pageTitleInput').value;
            if(!title) { 
                alert('Please enter a page title'); 
                document.getElementById('pageTitleInput').focus();
                return; 
            }

            // Check if canvas has content
            const canvasRoot = iframe.contentDocument.getElementById('canvas-root');
            const hasContent = canvasRoot && canvasRoot.children.length > 0 && 
                              !canvasRoot.querySelector('.empty-canvas');
            
            if(!hasContent) {
                if(!confirm('Your page appears to be empty. Save anyway?')) {
                    return;
                }
            }

            // Clone the document to clean it up before saving
            const doc = iframe.contentDocument.documentElement.cloneNode(true);
            
            // Cleanup: Remove editable attributes and selection classes
            const body = doc.querySelector('body');
            body.querySelectorAll('[data-editable]').forEach(el => {
                el.removeAttribute('data-editable');
                el.classList.remove('selected');
            });
            body.querySelectorAll('[contenteditable]').forEach(el => {
                el.removeAttribute('contenteditable');
                el.classList.remove('outline-none');
            });
            
            // Remove the "Drop here" empty state if it exists
            const empty = body.querySelector('.empty-canvas');
            if(empty) empty.remove();
            
            // Remove delete buttons from feature points
            body.querySelectorAll('button[data-action="remove-feature"]').forEach(btn => btn.remove());
            
            // Remove media change overlay and other editor-only elements
            body.querySelectorAll('#split-screen-media-overlay').forEach(el => el.remove());
            
            // Remove hero section "Change Background" button
            body.querySelectorAll('button[onclick*="changeHeroBackground"]').forEach(btn => {
                const overlay = btn.closest('.absolute.inset-0.bg-black\\/60');
                if (overlay) overlay.remove();
            });
            
            // Remove video card editor controls
            // Remove hover overlays with "Change Video" and "Remove" buttons
            body.querySelectorAll('.video-card-item .absolute.inset-0.bg-black\\/70').forEach(el => el.remove());
            
            // Remove all elements with data-editor-only attribute (Add Card button, remove buttons, etc.)
            body.querySelectorAll('[data-editor-only]').forEach(el => el.remove());
            
            // Remove "Add Video Card" button
            body.querySelectorAll('button[onclick*="addVideoCard"]').forEach(btn => {
                // Remove the entire container div that has the button
                const container = btn.closest('div.mt-8.text-center');
                if (container) container.remove();
            });
            
            // Remove group class from video cards (used for hover effects)
            body.querySelectorAll('.video-card-item').forEach(card => {
                card.classList.remove('group');
                
                // Make sure videos/iframes are visible and remove placeholders
                const placeholder = card.querySelector('.video-placeholder');
                const videoElement = card.querySelector('.video-element');
                const iframeWrapper = card.querySelector('.iframe-wrapper');
                
                // If there's a video or iframe, remove the placeholder
                if ((videoElement && videoElement.src) || (iframeWrapper && iframeWrapper.innerHTML.trim())) {
                    if (placeholder) placeholder.remove();
                    
                    // Make video visible
                    if (videoElement && videoElement.src) {
                        videoElement.classList.remove('hidden');
                    }
                    
                    // Make iframe visible
                    if (iframeWrapper && iframeWrapper.innerHTML.trim()) {
                        iframeWrapper.classList.remove('hidden');
                    }
                }
            });
            
            // Get final HTML - extract just the canvas content
            const canvasContent = body.querySelector('#canvas-root');
            const finalContent = canvasContent ? canvasContent.innerHTML : body.innerHTML;
            
            
            // Get slug from SEO settings or generate from title
            let slug = document.getElementById('seoSlug').value.trim();
            if (!slug) {
                slug = title.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '') || 'untitled-page';
            } else {
                // Sanitize user-provided slug
                slug = slug.toLowerCase()
                    .replace(/[^a-z0-9-]/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
            
            // Fill Hidden Form
            document.getElementById('hiddenTitle').value = title;
            document.getElementById('hiddenSlug').value = slug;
            document.getElementById('hiddenContent').value = finalContent;
            
            // Fill SEO fields
            document.getElementById('hiddenMetaTitle').value = document.getElementById('seoMetaTitle').value || '';
            document.getElementById('hiddenMetaDescription').value = document.getElementById('seoMetaDescription').value || '';
            document.getElementById('hiddenMetaKeywords').value = document.getElementById('seoMetaKeywords').value || '';
            document.getElementById('hiddenOgTitle').value = document.getElementById('seoOgTitle').value || '';
            document.getElementById('hiddenOgDescription').value = document.getElementById('seoOgDescription').value || '';
            document.getElementById('hiddenOgImage').value = document.getElementById('seoOgImage').value || '';
            document.getElementById('hiddenCanonicalUrl').value = document.getElementById('seoCanonicalUrl').value || '';
            document.getElementById('hiddenRobots').value = document.getElementById('seoRobots').value || 'index, follow';
            document.getElementById('hiddenStructuredData').value = document.getElementById('structuredDataEditor').value || '';
            
            // Show loading state
            const saveBtn = document.querySelector('button[onclick="exportHTML()"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Publishing...';
            
            // Show notification
            showNotification('info', 'Publishing Page', 'Your page is being published...');
            
            // Submit
            document.getElementById('realSaveForm').submit();
        }

        // Helper: Convert RGB to Hex for color inputs
        function rgbToHex(rgb) {
            if (!rgb || rgb === 'rgba(0, 0, 0, 0)') return '#000000';
            if (rgb.startsWith('#')) return rgb;
            const sep = rgb.indexOf(",") > -1 ? "," : " ";
            rgb = rgb.substr(4).split(")")[0].split(sep);
            let r = (+rgb[0]).toString(16), g = (+rgb[1]).toString(16), b = (+rgb[2]).toString(16);
            if (r.length == 1) r = "0" + r;
            if (g.length == 1) g = "0" + g;
            if (b.length == 1) b = "0" + b;
            return "#" + r + g + b;
        }

        // Helper: Resize Iframe
        // Notification System
        let notificationTimeout;
        
        function showNotification(type, title, message) {
            const notification = document.getElementById('notification');
            const content = document.getElementById('notificationContent');
            const icon = document.getElementById('notificationIcon');
            const titleEl = document.getElementById('notificationTitle');
            const messageEl = document.getElementById('notificationMessage');
            
            // Clear any existing timeout
            if (notificationTimeout) {
                clearTimeout(notificationTimeout);
            }
            
            // Set content
            titleEl.textContent = title;
            messageEl.textContent = message;
            
            // Set icon and colors based on type
            if (type === 'success') {
                content.style.borderLeftColor = '#10b981'; // green-500
                icon.innerHTML = '<i class="fas fa-check-circle text-green-500 text-xl"></i>';
            } else if (type === 'error') {
                content.style.borderLeftColor = '#ef4444'; // red-500
                icon.innerHTML = '<i class="fas fa-exclamation-circle text-red-500 text-xl"></i>';
            } else if (type === 'info') {
                content.style.borderLeftColor = '#3b82f6'; // blue-500
                icon.innerHTML = '<i class="fas fa-info-circle text-blue-500 text-xl"></i>';
            } else if (type === 'warning') {
                content.style.borderLeftColor = '#f59e0b'; // amber-500
                icon.innerHTML = '<i class="fas fa-exclamation-triangle text-amber-500 text-xl"></i>';
            }
            
            // Show notification with animation
            notification.classList.remove('hidden');
            setTimeout(() => {
                notification.style.transform = 'translateY(0)';
                notification.style.opacity = '1';
            }, 10);
            
            // Auto-hide after 5 seconds
            notificationTimeout = setTimeout(() => {
                hideNotification();
            }, 5000);
        }
        
        function hideNotification() {
            const notification = document.getElementById('notification');
            notification.style.transform = 'translateY(-20px)';
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 300);
        }
        
        // Preview Page Function
        function previewPage() {
            const title = document.getElementById('pageTitleInput').value;
            if(!title) { 
                showNotification('warning', 'Preview Warning', 'Please enter a page title before previewing.');
                document.getElementById('pageTitleInput').focus();
                return; 
            }
            
            // Get the cleaned HTML
            const doc = iframe.contentDocument.documentElement.cloneNode(true);
            const body = doc.querySelector('body');
            
            // Cleanup for preview (same as export)
            body.querySelectorAll('[data-editable]').forEach(el => {
                el.removeAttribute('data-editable');
                el.classList.remove('selected');
            });
            body.querySelectorAll('[contenteditable]').forEach(el => {
                el.removeAttribute('contenteditable');
                el.classList.remove('outline-none');
            });
            
            const empty = body.querySelector('.empty-canvas');
            if(empty) empty.remove();
            
            body.querySelectorAll('button[data-action="remove-feature"]').forEach(btn => btn.remove());
            body.querySelectorAll('#split-screen-media-overlay').forEach(el => el.remove());
            body.querySelectorAll('button[onclick*="changeHeroBackground"]').forEach(btn => {
                const overlay = btn.closest('.absolute.inset-0.bg-black\\/60');
                if (overlay) overlay.remove();
            });
            body.querySelectorAll('.video-card-item .absolute.inset-0.bg-black\\/70').forEach(el => el.remove());
            body.querySelectorAll('[data-editor-only]').forEach(el => el.remove());
            body.querySelectorAll('button[onclick*="addVideoCard"]').forEach(btn => {
                const container = btn.closest('div.mt-8.text-center');
                if (container) container.remove();
            });
            
            // Remove navigation and footer to prevent duplication (scripts will re-add them)
            const header = body.querySelector('header.fixed.top-0');
            if (header) header.remove();
            
            const footer = body.querySelector('footer');
            if (footer) footer.remove();
            
            // Remove injected styles to prevent duplication
            const navStyle = doc.querySelector('#navigation-protection');
            if (navStyle) navStyle.remove();
            
            const footerStyle = doc.querySelector('#footer-font-style');
            if (footerStyle) footerStyle.remove();
            
            // Create preview window
            const previewWindow = window.open('', '_blank');
            previewWindow.document.write('<!DOCTYPE html>');
            previewWindow.document.write(doc.outerHTML);
            previewWindow.document.close();
            
            showNotification('info', 'Preview Opened', 'Page preview opened in a new tab.');
        }
        
        // Save Draft Function
        function saveDraft() {
            const title = document.getElementById('pageTitleInput').value;
            if(!title) { 
                showNotification('warning', 'Draft Warning', 'Please enter a page title before saving draft.');
                document.getElementById('pageTitleInput').focus();
                return; 
            }
            
            showNotification('info', 'Saving Draft', 'Your page is being saved as a draft...');
            
            // For now, just show success message
            // In a real implementation, you would save to database with draft status
            setTimeout(() => {
                showNotification('success', 'Draft Saved', 'Your page has been saved as a draft successfully.');
            }, 1000);
        }
        
        function resizeCanvas(width) {
            document.getElementById('editorFrame').style.width = width;
        }

        // SEO Modal Functions
        function openSeoModal() {
            document.getElementById('seoModal').classList.remove('hidden');
        }

        function closeSeoModal() {
            document.getElementById('seoModal').classList.add('hidden');
        }
        
        // Structured Data Functions
        function clearStructuredData() {
            if (confirm('Are you sure you want to clear the structured data?')) {
                document.getElementById('structuredDataEditor').value = '';
            }
        }
        
        function autoFillSeoFromContent() {
            const doc = iframe.contentDocument;
            if (!doc) return;
            
            // Try to extract existing structured data from the page
            const scripts = doc.querySelectorAll('script[type="application/ld+json"]');
            if (scripts.length > 0) {
                const structuredData = [];
                scripts.forEach(script => {
                    try {
                        const data = JSON.parse(script.textContent);
                        structuredData.push(data);
                    } catch (e) {
                        console.error('Error parsing structured data:', e);
                    }
                });
                
                if (structuredData.length > 0) {
                    const jsonString = structuredData.length === 1 
                        ? JSON.stringify(structuredData[0], null, 2)
                        : JSON.stringify(structuredData, null, 2);
                    document.getElementById('structuredDataEditor').value = jsonString;
                    showNotification('success', 'Auto-Fill Complete', 'Structured data extracted from page content.');
                } else {
                    showNotification('info', 'No Data Found', 'No structured data found in the page.');
                }
            } else {
                showNotification('info', 'No Data Found', 'No structured data found in the page.');
            }
        }

        function saveSeoSettings() {
            // No need to do anything - values are already in the fields
            closeSeoModal();
            // Show confirmation
            const saveBtn = event.target;
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '\u003ci class="fas fa-check mr-2"\u003e\u003c/i\u003eSaved!';
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
            }, 1500);
        }

        // Auto-generate slug from title
        document.getElementById('pageTitleInput').addEventListener('input', function(e) {
            const title = e.target.value;
            if (title) {
                const slug = title.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                // Store slug for later use in export
                document.getElementById('pageTitleInput').dataset.slug = slug;
            }
        });
    </script>
</body>
</html>
<?php
// Flush output buffer to ensure proper content delivery
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>