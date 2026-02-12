<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$pageData = null;
$pageId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($pageId) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    $pageData = $stmt->fetch();
    
    if (!$pageData) {
        die("Page not found");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageData ? 'Edit' : 'Add New'; ?> Page - WordPress Style</title>
    
    <!-- TinyMCE Editor (Free CDN - No API Key Required) -->
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f0f1;
            color: #1d2327;
        }
        
        /* WordPress-style Header */
        .wp-header {
            background: #fff;
            border-bottom: 1px solid #c3c4c7;
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        
        .wp-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
        }
        
        .wp-title {
            font-size: 20px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .wp-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .wp-btn {
            padding: 8px 16px;
            border-radius: 3px;
            border: 1px solid #2271b1;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .wp-btn-primary {
            background: #2271b1;
            color: white;
            border-color: #2271b1;
        }
        
        .wp-btn-primary:hover {
            background: #135e96;
            border-color: #135e96;
        }
        
        .wp-btn-secondary {
            background: #f6f7f7;
            color: #2c3338;
            border-color: #c3c4c7;
        }
        
        .wp-btn-secondary:hover {
            background: #f0f0f1;
            border-color: #8c8f94;
        }
        
        /* Main Container */
        .wp-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
        }
        
        /* Main Content Area */
        .wp-main {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        
        .wp-main-inner {
            padding: 20px;
        }
        
        .wp-field {
            margin-bottom: 20px;
        }
        
        .wp-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1d2327;
            font-size: 14px;
        }
        
        .wp-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        
        .wp-input:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        
        .wp-input-title {
            font-size: 24px;
            font-weight: 600;
            padding: 16px;
        }
        
        .wp-permalink {
            padding: 12px 16px;
            background: #f6f7f7;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            font-size: 13px;
            color: #646970;
            margin-bottom: 20px;
        }
        
        .wp-permalink strong {
            color: #1d2327;
        }
        
        /* Sidebar */
        .wp-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .wp-panel {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        
        .wp-panel-header {
            padding: 12px 16px;
            border-bottom: 1px solid #c3c4c7;
            font-weight: 600;
            font-size: 14px;
            background: #f6f7f7;
        }
        
        .wp-panel-body {
            padding: 16px;
        }
        
        .wp-panel-field {
            margin-bottom: 16px;
        }
        
        .wp-panel-field:last-child {
            margin-bottom: 0;
        }
        
        .wp-select {
            width: 100%;
            padding: 8px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .wp-select:focus {
            outline: none;
            border-color: #2271b1;
        }
        
        .wp-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .wp-checkbox input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .wp-checkbox label {
            cursor: pointer;
            font-size: 14px;
        }
        
        /* Featured Image */
        .wp-featured-image {
            border: 2px dashed #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .wp-featured-image:hover {
            border-color: #2271b1;
            background: #f6f7f7;
        }
        
        .wp-featured-image img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin-bottom: 12px;
        }
        
        .wp-featured-image-text {
            color: #2271b1;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Loading Overlay */
        .wp-loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        
        .wp-loading.active {
            display: flex;
        }
        
        .wp-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f0f0f1;
            border-top: 4px solid #2271b1;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .wp-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="wp-header">
        <div class="wp-header-inner">
            <h1 class="wp-title"><?php echo $pageData ? 'Edit Page' : 'Add New Page'; ?></h1>
            <div class="wp-actions">
                <button id="preview-btn" class="wp-btn wp-btn-secondary">👁️ Preview</button>
                <button id="save-draft-btn" class="wp-btn wp-btn-secondary">💾 Save Draft</button>
                <button id="publish-btn" class="wp-btn wp-btn-primary">🚀 Publish</button>
                <a href="index.php" class="wp-btn wp-btn-secondary">← Back</a>
            </div>
        </div>
    </div>
    
    <!-- Main Container -->
    <div class="wp-container">
        <!-- Main Content -->
        <div class="wp-main">
            <div class="wp-main-inner">
                <!-- Title -->
                <div class="wp-field">
                    <input 
                        type="text" 
                        id="page-title" 
                        class="wp-input wp-input-title" 
                        placeholder="Add title"
                        value="<?php echo $pageData ? htmlspecialchars($pageData['title']) : ''; ?>"
                    >
                </div>
                
                <!-- Permalink -->
                <div class="wp-permalink">
                    <strong>Permalink:</strong> 
                    <span id="permalink-display"><?php echo $pageData ? htmlspecialchars($pageData['slug']) : 'your-page-slug'; ?></span>
                    <button id="edit-slug-btn" style="margin-left: 8px; color: #2271b1; background: none; border: none; cursor: pointer; text-decoration: underline;">Edit</button>
                </div>
                
                <!-- Content Editor -->
                <div class="wp-field">
                    <textarea id="page-content"><?php echo $pageData ? htmlspecialchars($pageData['html_content']) : ''; ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="wp-sidebar">
            <!-- Publish Panel -->
            <div class="wp-panel">
                <div class="wp-panel-header">Publish</div>
                <div class="wp-panel-body">
                    <div class="wp-panel-field">
                        <label class="wp-label">Status</label>
                        <select id="page-status" class="wp-select">
                            <option value="draft">Draft</option>
                            <option value="published" <?php echo ($pageData && $pageData['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                    <div class="wp-panel-field">
                        <label class="wp-label">Visibility</label>
                        <select id="page-visibility" class="wp-select">
                            <option value="public">Public</option>
                            <option value="private">Private</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Template Panel -->
            <div class="wp-panel">
                <div class="wp-panel-header">Page Attributes</div>
                <div class="wp-panel-body">
                    <div class="wp-panel-field">
                        <label class="wp-label">Template</label>
                        <select id="page-template" class="wp-select">
                            <option value="default">Default Template</option>
                            <option value="full-width">Full Width</option>
                            <option value="landing">Landing Page</option>
                            <option value="blog">Blog Post</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Featured Image Panel -->
            <div class="wp-panel">
                <div class="wp-panel-header">Featured Image</div>
                <div class="wp-panel-body">
                    <div class="wp-featured-image" id="featured-image-box">
                        <div class="wp-featured-image-text">+ Set featured image</div>
                    </div>
                    <input type="file" id="featured-image-input" style="display: none;" accept="image/*">
                </div>
            </div>
            
            <!-- SEO Panel -->
            <div class="wp-panel">
                <div class="wp-panel-header">SEO Settings</div>
                <div class="wp-panel-body">
                    <div class="wp-panel-field">
                        <label class="wp-label">Meta Description</label>
                        <textarea id="meta-description" class="wp-input" rows="3" placeholder="Brief description for search engines"></textarea>
                    </div>
                    <div class="wp-panel-field">
                        <label class="wp-label">Focus Keywords</label>
                        <input type="text" id="meta-keywords" class="wp-input" placeholder="keyword1, keyword2">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="wp-loading" id="loading">
        <div class="wp-spinner"></div>
    </div>
    
    <script>
        const pageId = <?php echo $pageId ? $pageId : 'null'; ?>;
        const pageData = <?php echo $pageData ? json_encode($pageData) : 'null'; ?>;
        
        // Function to initialize TinyMCE with retry logic
        function initTinyMCE(retries = 10) {
            console.log('Attempting to initialize TinyMCE... (attempt ' + (11 - retries) + '/10)');
            
            if (typeof tinymce !== 'undefined') {
                console.log('TinyMCE library loaded successfully');
                
                tinymce.init({
                    selector: '#page-content',
                    height: 500,
                    menubar: true,
                    plugins: [
                        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                        'insertdatetime', 'media', 'table', 'help', 'wordcount'
                    ],
                    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code',
                    content_style: 'body { font-family: Inter, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif; font-size: 16px; line-height: 1.6; }',
                    branding: false,
                    promotion: false,
                    setup: function(editor) {
                        editor.on('init', function() {
                            console.log('✓ TinyMCE editor initialized successfully!');
                        });
                    }
                }).catch(function(error) {
                    console.error('TinyMCE initialization error:', error);
                });
            } else if (retries > 0) {
                console.log('TinyMCE not ready yet, retrying in 200ms...');
                setTimeout(function() {
                    initTinyMCE(retries - 1);
                }, 200);
            } else {
                console.error('❌ TinyMCE failed to load after multiple attempts. Please check:');
                console.error('1. Internet connection');
                console.error('2. CDN availability');
                console.error('3. Browser console for network errors');
                alert('TinyMCE editor failed to load. Please refresh the page or check your internet connection.');
            }
        }
        
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing TinyMCE...');
            initTinyMCE();
        
            // Auto-generate slug from title
            document.getElementById('page-title').addEventListener('input', function(e) {
                if (!pageId) {
                    const slug = e.target.value
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    document.getElementById('permalink-display').textContent = slug || 'your-page-slug';
                }
            });
            
            // Edit slug
            document.getElementById('edit-slug-btn').addEventListener('click', function() {
                const currentSlug = document.getElementById('permalink-display').textContent;
                const newSlug = prompt('Enter new slug:', currentSlug);
                if (newSlug) {
                    document.getElementById('permalink-display').textContent = newSlug;
                }
            });
            
            // Featured image upload
            document.getElementById('featured-image-box').addEventListener('click', function() {
                document.getElementById('featured-image-input').click();
            });
            
            document.getElementById('featured-image-input').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('featured-image-box').innerHTML = `
                            <img src="${e.target.result}" alt="Featured image">
                            <div class="wp-featured-image-text">Change image</div>
                        `;
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Save function
            async function savePage(status = 'draft') {
                const loading = document.getElementById('loading');
                loading.classList.add('active');
                
                try {
                    const title = document.getElementById('page-title').value;
                    const slug = document.getElementById('permalink-display').textContent;
                    const content = tinymce.get('page-content').getContent();
                    
                    if (!title || !slug) {
                        alert('Title and slug are required!');
                        loading.classList.remove('active');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('id', pageId || '');
                    formData.append('title', title);
                    formData.append('slug', slug);
                    formData.append('html_content', content);
                    formData.append('css_content', '');
                    formData.append('components', '');
                    formData.append('styles', '');
                    formData.append('status', status);
                    formData.append('template', document.getElementById('page-template').value);
                    formData.append('meta_description', document.getElementById('meta-description').value);
                    formData.append('meta_keywords', document.getElementById('meta-keywords').value);
                    
                    const response = await fetch('save_page.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(status === 'published' ? 'Page published successfully!' : 'Draft saved successfully!');
                        if (!pageId && result.id) {
                            window.location.href = 'wp-builder.php?id=' + result.id;
                        }
                    } else {
                        alert('Error saving page: ' + result.message);
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    alert('Error saving page. Check console for details.');
                } finally {
                    loading.classList.remove('active');
                }
            }
            
            // Button handlers
            document.getElementById('save-draft-btn').addEventListener('click', () => savePage('draft'));
            document.getElementById('publish-btn').addEventListener('click', () => savePage('published'));
            
            document.getElementById('preview-btn').addEventListener('click', () => {
                const content = tinymce.get('page-content').getContent();
                const previewWindow = window.open('', '_blank');
                
                const html = '<!DOCTYPE html>' +
                    '<html lang="en">' +
                    '<head>' +
                    '<meta charset="UTF-8">' +
                    '<meta name="viewport" content="width=device-width, initial-scale=1.0">' +
                    '<title>Preview</title>' +
                    '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">' +
                    '<style>' +
                    'body { font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; padding: 0; }' +
                    'main { padding: 40px; max-width: 1200px; margin: 0 auto; }' +
                    '</style>' +
                    '</head>' +
                    '<body>' +
                    '<main>' +
                    content +
                    '</main>' +
                    '<script src="../assets/components/navigation.js"><\/script>' +
                    '<script src="../assets/components/footer.js"><\/script>' +
                    '</body>' +
                    '</html>';
                
                previewWindow.document.write(html);
                previewWindow.document.close();
            });
        });
    </script>
</body>
</html>
