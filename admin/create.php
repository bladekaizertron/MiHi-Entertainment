<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$errors = [];
$success = false;

// Get categories and tags
$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$tags = $db->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = trim($_POST['excerpt'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $status = $_POST['status'] ?? 'draft';
    $published_at = ($status === 'published' && !empty($_POST['published_at'])) ? $_POST['published_at'] : null;
    
    // SEO fields
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $meta_keywords = trim($_POST['meta_keywords'] ?? '');
    $og_title = trim($_POST['og_title'] ?? '');
    $og_description = trim($_POST['og_description'] ?? '');
    $og_image = trim($_POST['og_image'] ?? '');
    $canonical_url = trim($_POST['canonical_url'] ?? '');
    $robots = trim($_POST['robots'] ?? 'index, follow');
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    if (empty($content)) {
        $errors[] = 'Content is required';
    }
    
    // Generate slug if not provided
    if (empty($slug)) {
        $slug = generateSlug($title);
    } else {
        $slug = generateSlug($slug);
    }
    
    // Check if slug exists
    $stmt = $db->prepare("SELECT id FROM posts WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        $slug .= '-' . time();
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Handle featured image
            $featured_image = trim($_POST['featured_image'] ?? '');
            
            // Insert post
            $stmt = $db->prepare("
                INSERT INTO posts (title, slug, content, excerpt, featured_image, category_id, author_id, status, published_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title,
                $slug,
                $content,
                $excerpt,
                $featured_image ?: null,
                $category_id,
                $_SESSION['user_id'],
                $status,
                $published_at
            ]);
            
            $post_id = $db->lastInsertId();
            
            // Insert SEO metadata
            $stmt = $db->prepare("
                INSERT INTO seo_metadata (post_id, meta_title, meta_description, meta_keywords, og_title, og_description, og_image, canonical_url, robots)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $post_id,
                $meta_title ?: $title,
                $meta_description ?: $excerpt,
                $meta_keywords,
                $og_title ?: $title,
                $og_description ?: $excerpt,
                $og_image,
                $canonical_url,
                $robots
            ]);
            
            // Insert tags
            if (!empty($_POST['tags'])) {
                $tagIds = array_map('intval', $_POST['tags']);
                $stmt = $db->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                foreach ($tagIds as $tagId) {
                    $stmt->execute([$post_id, $tagId]);
                }
            }
            
            $db->commit();
            
            // Generate static HTML page for the post (only if published)
            require_once __DIR__ . '/../includes/generate_post_page.php';
            $pageGenerated = generatePostPage($post_id);
            if ($pageGenerated) {
                // Success - page generated
            } else if ($status === 'published') {
                // Error generating page for published post
                error_log("Failed to generate HTML page for post ID: " . $post_id);
            }
            
            $success = true;
            header('Location: edit.php?id=' . $post_id . '&success=1');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Error creating post: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - CMS Blog Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Create New Post</h1>
            <a href="index.php" class="btn btn-secondary">Return to Dashboard</a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="post-form" id="postForm" action="" onsubmit="return validateForm()">
            <div class="form-row">
                <div class="form-group form-group-large">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required value="<?php echo escape($_POST['title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" value="<?php echo escape($_POST['slug'] ?? ''); ?>">
                    <small>Leave empty to auto-generate from title</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo escape($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="draft" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'draft') ? 'selected' : ''; ?>>Draft (Not visible on blog)</option>
                        <option value="published" <?php echo (isset($_POST['status']) && $_POST['status'] === 'published') ? 'selected' : ''; ?>>Published (Visible on blog)</option>
                    </select>
                    <small>Only published posts appear on the public blog page</small>
                </div>
                
                <div class="form-group">
                    <label for="published_at">Publish Date</label>
                    <input type="datetime-local" id="published_at" name="published_at" value="<?php echo escape($_POST['published_at'] ?? date('Y-m-d\TH:i')); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt" rows="3"><?php echo escape($_POST['excerpt'] ?? ''); ?></textarea>
                <small>Short description for previews and meta descriptions</small>
            </div>
            
            <div class="form-group">
                <label for="featured_image">Thumbnail Image</label>
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <input type="text" id="featured_image" name="featured_image" value="<?php echo escape($_POST['featured_image'] ?? ''); ?>" placeholder="Enter image URL or upload" style="flex: 1;">
                    <input type="file" id="thumbnail_upload" accept="image/*" style="display: none;">
                    <button type="button" id="upload_thumbnail_btn" class="btn btn-secondary" style="white-space: nowrap;">Upload Image</button>
                </div>
                <small>Enter image URL or click "Upload Image" to upload directly</small>
                <div id="thumbnail_preview" style="margin-top: 10px;">
                    <?php if (!empty($_POST['featured_image'])): ?>
                        <img src="<?php echo escape($_POST['featured_image']); ?>" alt="Thumbnail preview" style="max-width: 300px; height: auto; border-radius: 8px; border: 2px solid #e2e8f0;">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="content">Content *</label>
                <textarea id="content" name="content"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Tags</label>
                <div class="checkbox-group">
                    <?php foreach ($tags as $tag): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>"
                                <?php echo (isset($_POST['tags']) && in_array($tag['id'], $_POST['tags'])) ? 'checked' : ''; ?>>
                            <?php echo escape($tag['name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="seo-section">
                <h2>SEO Settings</h2>
                
                <div class="form-group">
                    <label for="meta_title">Meta Title</label>
                    <input type="text" id="meta_title" name="meta_title" value="<?php echo escape($_POST['meta_title'] ?? ''); ?>">
                    <small>Leave empty to use post title</small>
                </div>
                
                <div class="form-group">
                    <label for="meta_description">Meta Description</label>
                    <textarea id="meta_description" name="meta_description" rows="2"><?php echo escape($_POST['meta_description'] ?? ''); ?></textarea>
                    <small>Recommended: 150-160 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="meta_keywords">Meta Keywords</label>
                    <input type="text" id="meta_keywords" name="meta_keywords" value="<?php echo escape($_POST['meta_keywords'] ?? ''); ?>">
                    <small>Comma-separated keywords</small>
                </div>
                
                <div class="form-group">
                    <label for="og_title">Open Graph Title</label>
                    <input type="text" id="og_title" name="og_title" value="<?php echo escape($_POST['og_title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="og_description">Open Graph Description</label>
                    <textarea id="og_description" name="og_description" rows="2"><?php echo escape($_POST['og_description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="og_image">Open Graph Image URL</label>
                    <input type="url" id="og_image" name="og_image" value="<?php echo escape($_POST['og_image'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="canonical_url">Canonical URL</label>
                    <input type="url" id="canonical_url" name="canonical_url" value="<?php echo escape($_POST['canonical_url'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="robots">Robots Meta</label>
                    <input type="text" id="robots" name="robots" value="<?php echo escape($_POST['robots'] ?? 'index, follow'); ?>">
                    <small>e.g., "index, follow" or "noindex, nofollow"</small>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Post</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Initialize TinyMCE
        document.addEventListener('DOMContentLoaded', function() {
            tinymce.init({
                selector: '#content',
                height: 500,
                plugins: 'preview importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap quickbars emoticons',
                menubar: 'file edit view insert format tools table help',
                toolbar: 'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl',
                toolbar_sticky: true,
                
                // Image Upload Configuration
                images_upload_url: 'upload_image.php',
                automatic_uploads: true,
                file_picker_types: 'image',
                
                // Custom Handler to adapt to your PHP backend
                images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.withCredentials = false;
                    xhr.open('POST', 'upload_image.php');

                    xhr.upload.onprogress = (e) => {
                        progress(e.loaded / e.total * 100);
                    };

                    xhr.onload = () => {
                        if (xhr.status === 403) {
                            reject({ message: 'HTTP Error: ' + xhr.status, remove: true });
                            return;
                        }

                        if (xhr.status < 200 || xhr.status >= 300) {
                            reject('HTTP Error: ' + xhr.status);
                            return;
                        }

                        let json;
                        try {
                            json = JSON.parse(xhr.responseText);
                        } catch (e) {
                            reject('Invalid JSON: ' + xhr.responseText);
                            return;
                        }

                        if (!json || typeof json.url !== 'string') {
                            reject('Invalid JSON: ' + xhr.responseText);
                            return;
                        }

                        resolve(json.url);
                    };

                    xhr.onerror = () => {
                        reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
                    };

                    const formData = new FormData();
                    // We use 'upload' as the key to match common PHP uploader expectations
                    formData.append('upload', blobInfo.blob(), blobInfo.filename());

                    xhr.send(formData);
                }),
                
                // Enable image captions
                image_caption: true,
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }'
            });
        });

        // Thumbnail upload functionality (This remains separate from the main editor)
        document.getElementById('upload_thumbnail_btn').addEventListener('click', function() {
            document.getElementById('thumbnail_upload').click();
        });
        
        document.getElementById('thumbnail_upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validate file type
            if (!file.type.match('image.*')) {
                alert('Please select an image file');
                return;
            }
            
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File is too large. Maximum size is 5MB.');
                return;
            }
            
            // Show loading
            const btn = document.getElementById('upload_thumbnail_btn');
            const originalText = btn.textContent;
            btn.textContent = 'Uploading...';
            btn.disabled = true;
            
            // Upload file
            const formData = new FormData();
            formData.append('thumbnail', file);
            
            fetch('upload_thumbnail.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.textContent = originalText;
                btn.disabled = false;
                
                if (data.url) {
                    // Set the URL in the input field
                    document.getElementById('featured_image').value = data.url;
                    
                    // Show preview
                    const preview = document.getElementById('thumbnail_preview');
                    preview.innerHTML = '<img src="' + data.url + '" alt="Thumbnail preview" style="max-width: 300px; height: auto; border-radius: 8px; border: 2px solid #e2e8f0;">';
                } else {
                    alert('Upload failed: ' + (data.error?.message || 'Unknown error'));
                }
            })
            .catch(error => {
                btn.textContent = originalText;
                btn.disabled = false;
                console.error('Upload error:', error);
                alert('Failed to upload image. Please try again.');
            });
        });
        
        // Update preview when URL changes
        document.getElementById('featured_image').addEventListener('input', function() {
            const url = this.value.trim();
            const preview = document.getElementById('thumbnail_preview');
            
            if (url) {
                const img = document.createElement('img');
                img.src = url;
                img.alt = 'Thumbnail preview';
                img.style.cssText = 'max-width: 300px; height: auto; border-radius: 8px; border: 2px solid #e2e8f0;';
                img.onerror = function() {
                    preview.innerHTML = '<span style="color: #dc3545;">Invalid image URL</span>';
                };
                preview.innerHTML = '';
                preview.appendChild(img);
            } else {
                preview.innerHTML = '';
            }
        });
        
        // Form validation function
        function validateForm() {
            const title = document.getElementById('title').value.trim();
            
            if (!title) {
                alert('Please enter a title');
                document.getElementById('title').focus();
                return false;
            }
            
            // Sync TinyMCE content to textarea before checking
            tinymce.triggerSave();
            
            const content = document.getElementById('content').value.trim();
            if (!content) {
                alert('Please enter content');
                // Focus the editor
                tinymce.get('content').focus();
                return false;
            }
            
            return true;
        }
        
        // Auto-generate slug from title
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            if (titleInput) {
                titleInput.addEventListener('input', function() {
                    const slugInput = document.getElementById('slug');
                    if (slugInput && !slugInput.value) {
                        slugInput.value = this.value.toLowerCase()
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-+|-+$/g, '');
                    }
                });
            }
        });
    </script>
</body>
</html>