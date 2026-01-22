<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$role = strtolower(trim($currentUser['role'] ?? ''));
if (!in_array($role, ['admin', 'editor'], true)) {
	header('Location: index.php');
	exit;
}

$db = getDB();
$error = '';
$success = '';

// Helper function to convert PHP ini size to bytes
function return_bytes($val)
{
	$val = trim($val);
	$last = strtolower($val[strlen($val) - 1]);
	$val = (int) $val;
	switch ($last) {
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}
	return $val;
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$postMaxBytes = return_bytes(ini_get('post_max_size') ?: '0');
	$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? $_SERVER['HTTP_CONTENT_LENGTH'] ?? 0);
	$postLimitExceeded = $postMaxBytes > 0 && $contentLength > $postMaxBytes;
	$postMaxLabel = ini_get('post_max_size') ?: 'Unknown';

	$token = $_POST['csrf_token'] ?? '';
	if ($token === '') {
		if ($postLimitExceeded) {
			$limitMB = $postMaxBytes ? round($postMaxBytes / (1024 * 1024), 1) . 'MB' : $postMaxLabel;
			$error = 'The upload probably exceeds the server post_max_size limit (' . $limitMB . '). Please upload a smaller PDF or ask your host to increase that limit.';
		} else {
			$error = 'Security token invalid.';
		}
	} elseif (!hash_equals($csrf, $token)) {
		$error = 'Security token invalid.';
	} else {
		$title = trim($_POST['title'] ?? '');
		$slug = trim($_POST['slug'] ?? '');

		if (empty($title)) {
			$error = 'Title is required.';
		} elseif (empty($slug)) {
			$error = 'Slug is required.';
		} elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
			$error = 'Slug can only contain lowercase letters, numbers, and hyphens.';
		} else {
			// Check if table exists first
			try {
				// Check if slug already exists
				$check = $db->prepare("SELECT id FROM flipbooks WHERE slug = ?");
				$check->execute([$slug]);
				if ($check->fetch()) {
					$error = 'A flipbook with this slug already exists.';
				} else {
					$tableExists = true;
				}
			} catch (PDOException $e) {
				if (strpos($e->getMessage(), "doesn't exist") !== false) {
					$error = 'The flipbooks table does not exist. Please <a href="create_flipbooks_table.php">run the database migration</a> first.';
					$tableExists = false;
				} else {
					$error = 'Database error: ' . $e->getMessage();
					$tableExists = false;
				}
			}

			if ($tableExists) {
				// Handle PDF upload
				$pdfPath = null;
				if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
					$file = $_FILES['pdf_file'];

					// Validate file type
					$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
					if ($ext !== 'pdf') {
						$error = 'Only PDF files are allowed.';
					} else {
						// Check PHP upload limits (important for GoDaddy)
						$phpUploadMax = ini_get('upload_max_filesize');
						$phpPostMax = ini_get('post_max_size');
						$phpUploadMaxBytes = return_bytes($phpUploadMax);
						$phpPostMaxBytes = return_bytes($phpPostMax);
						$serverMaxSize = min($phpUploadMaxBytes, $phpPostMaxBytes);

						// Use smaller of: 1GB or server limit (but at least 10MB)
						$maxSize = min(1024 * 1024 * 1024, $serverMaxSize ?: (1024 * 1024 * 1024));
						if ($maxSize < 10 * 1024 * 1024) {
							$maxSize = 10 * 1024 * 1024; // Minimum 10MB
						}
						if ($file['size'] > $maxSize) {
							$maxSizeMB = round($maxSize / (1024 * 1024), 1);
							$error = 'File is too large. Maximum size is ' . $maxSizeMB . 'MB (Server limit: ' . $phpUploadMax . ').';
						} else {
							// Create upload directory if it doesn't exist
							$baseDir = __DIR__ . '/../flipbook/';
							$uploadDir = $baseDir . 'uploads/';

							// Create base flipbook directory if it doesn't exist
							if (!file_exists($baseDir)) {
								// Try 0755 first (more secure), then 0777 (for shared hosting like GoDaddy)
								if (!@mkdir($baseDir, 0755, true)) {
									if (!@mkdir($baseDir, 0777, true)) {
										$error = 'Failed to create flipbook directory. Please <a href="setup_flipbook_dirs.php">run the setup script</a> or create the directory manually via FTP/cPanel.';
									} else {
										@chmod($baseDir, 0777);
									}
								} else {
									@chmod($baseDir, 0755);
									// If not writable, try 0777 (for GoDaddy/shared hosting)
									if (!is_writable($baseDir)) {
										@chmod($baseDir, 0777);
									}
								}
							}

							// Create uploads directory if it doesn't exist
							if (empty($error) && !file_exists($uploadDir)) {
								// Try 0755 first, then 0777 for shared hosting
								if (!@mkdir($uploadDir, 0755, true)) {
									if (!@mkdir($uploadDir, 0777, true)) {
										$error = 'Failed to create uploads directory. Please <a href="setup_flipbook_dirs.php">run the setup script</a> or create the directory manually via FTP/cPanel.';
									} else {
										@chmod($uploadDir, 0777);
									}
								} else {
									@chmod($uploadDir, 0755);
									// If not writable, try 0777 (for GoDaddy/shared hosting)
									if (!is_writable($uploadDir)) {
										@chmod($uploadDir, 0777);
									}
								}
							}

							// Check if directory is writable
							if (empty($error) && !is_writable($uploadDir)) {
								// Try to fix permissions - try 0777 for shared hosting
								@chmod($uploadDir, 0777);
								if (!is_writable($uploadDir)) {
									$error = 'Upload directory is not writable. Please <a href="setup_flipbook_dirs.php">run the setup script</a> or set permissions to 755/777 via FTP/cPanel File Manager.';
								}
							}

							if (empty($error)) {
								// Generate unique filename
								$filename = 'flipbook_' . uniqid() . '_' . preg_replace('/[^a-z0-9.-]/i', '_', $file['name']);
								$destination = $uploadDir . $filename;

								if (@move_uploaded_file($file['tmp_name'], $destination)) {
									@chmod($destination, 0644);
									$pdfPath = 'flipbook/uploads/' . $filename;
								} else {
									$lastError = error_get_last();
									$errorMsg = 'Failed to upload PDF file.';
									if ($lastError) {
										$errorMsg .= ' Error: ' . $lastError['message'];
									}
									$error = $errorMsg;
								}
							}
						}
					}
				} else {
					$error = 'Please upload a PDF file.';
				}

				if (empty($error) && $pdfPath) {
					try {
						// Insert into database
						$stmt = $db->prepare("INSERT INTO flipbooks (title, slug, pdf_path, created_at) VALUES (?, ?, ?, NOW())");
						$stmt->execute([$title, $slug, $pdfPath]);

						// Generate HTML file
						generateFlipbookPage($slug, $title, $pdfPath);

						$success = 'Flipbook created successfully!';
						// Redirect to flipbooks list
						header('Location: flipbooks.php?success=1');
						exit;
					} catch (PDOException $e) {
						// If table doesn't exist, show error
						if (strpos($e->getMessage(), "doesn't exist") !== false) {
							$error = 'Flipbooks table does not exist. Please run the database migration first.';
						} else {
							$error = 'Database error: ' . $e->getMessage();
						}
						// Clean up uploaded file if database insert failed
						if ($pdfPath && file_exists(__DIR__ . '/../' . $pdfPath)) {
							@unlink(__DIR__ . '/../' . $pdfPath);
						}
					}
				}
			}
		}
	}
}

// Function to generate flipbook HTML page
function generateFlipbookPage($slug, $title, $pdfPath)
{
	$template = <<<'HTML'
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>{{TITLE}} - MiHi Entertainment</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  <!-- Flipbook StyleSheet -->
  <link href="flipbook/components/css/dflip.min.css" rel="stylesheet" type="text/css">
  <!-- Icons Stylesheet -->
  <link href="flipbook/components/css/themify-icons.min.css" rel="stylesheet" type="text/css">
  <style>
    html,
    body {
      background: #f5f5f5 !important;
      margin: 0;
      padding: 0;
      overflow: hidden;
      width: 100%;
      height: 100%;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
    }

    /* Flipbook container - full screen */
    .flipbook-page-container {
      padding: 0;
      margin: 0;
      width: 100vw;
      height: 100vh;
      background: #f5f5f5;
      display: flex;
      justify-content: center;
      align-items: center;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
    }

    .container {
      background: transparent !important;
      background-color: transparent !important;
      width: 100%;
      height: 100%;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .flipbook-wrapper {
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 0;
      margin: 0;
    }

    ._df_container,
    ._df_book,
    .df_container,
    #df_manual_book {
      background: transparent !important;
      background-color: transparent !important;
      width: 100% !important;
      max-width: 100% !important;
      margin: 0 auto !important;
    }

    /* Ensure flipbook container has proper sizing - full screen */
    #df_manual_book {
      width: 100% !important;
      max-width: 100% !important;
      height: 100% !important;
      min-height: 100vh !important;
    }

    /* Fix for flipbook internal elements */
    #df_manual_book ._df_container,
    #df_manual_book .df_container {
      width: 100% !important;
      max-width: 100% !important;
      margin: 0 auto !important;
    }

    /* Ensure flipbook canvas and pages are properly sized - full screen */
    #df_manual_book canvas,
    #df_manual_book ._df_canvas,
    #df_manual_book .df_canvas {
      width: 100% !important;
      max-width: 100% !important;
      height: 100% !important;
      max-height: 100vh !important;
    }

    /* Prevent overflow */
    * {
      box-sizing: border-box;
    }
    
    /* Disable flipbook loading animations */
    ._df_container,
    ._df_book,
    .df_container,
    #df_manual_book,
    ._df_container *,
    ._df_book *,
    .df_container *,
    #df_manual_book * {
      animation: none !important;
      transition: none !important;
    }
    
    /* Initially hide the flipbook to prevent animation */
    #df_manual_book {
      visibility: hidden;
    }

    /* Responsive adjustments - maintain full screen */
    @media (max-width: 768px) {
      .flipbook-page-container {
        padding: 0;
      }

      .container {
        padding: 0;
      }

      #df_manual_book {
        min-height: 100vh !important;
        height: 100vh !important;
      }
    }

    @media (max-width: 480px) {
      #df_manual_book {
        min-height: 100vh !important;
        height: 100vh !important;
      }

      .container {
        padding: 0;
      }
    }
		.upload-overlay {
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0, 0, 0, 0.7);
			z-index: 9999;
			align-items: center;
			justify-content: center;
			flex-direction: column;
			gap: 20px;
		}
		.upload-overlay.active {
			display: flex;
		}
		.upload-spinner {
			width: 50px;
			height: 50px;
			border: 4px solid rgba(255, 255, 255, 0.3);
			border-top-color: #667eea;
			border-radius: 50%;
			animation: spin 1s linear infinite;
		}
		@keyframes spin {
			to { transform: rotate(360deg); }
		}
		.upload-text {
			color: #ffffff;
			font-size: 18px;
			font-weight: 500;
		}
		.upload-progress {
			color: #ffffff;
			font-size: 14px;
			opacity: 0.8;
		}
		.progress-bar-container {
			width: 300px;
			max-width: 90%;
			height: 8px;
			background: rgba(255, 255, 255, 0.2);
			border-radius: 10px;
			overflow: hidden;
			margin-top: 10px;
		}
		.progress-bar {
			height: 100%;
			background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
			border-radius: 10px;
			width: 0%;
			transition: width 0.3s ease;
			position: relative;
			overflow: hidden;
		}
		.progress-bar::after {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			bottom: 0;
			right: 0;
			background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
			animation: shimmer 2s infinite;
		}
		@keyframes shimmer {
			0% { transform: translateX(-100%); }
			100% { transform: translateX(100%); }
		}
		.progress-percentage {
			color: #ffffff;
			font-size: 16px;
			font-weight: 600;
			margin-top: 8px;
		}
		.file-info {
			margin-top: 8px;
			padding: 8px 12px;
			background: #f3f4f6;
			border-radius: 6px;
			font-size: 13px;
			color: #6b7280;
		}
  </style>
</head>

<body>

  <div class="flipbook-page-container">
    <div class="container">
      <div class="flipbook-wrapper">
        <!--Normal FLipbook-->
        <div class="_df_book" height="1000" webgl="true" source="{{PDF_PATH}}" id="df_manual_book"
          style="background: transparent !important; width: 100%; max-width: 100%;">
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery  -->
  <script src="flipbook/components/js/libs/jquery.min.js" type="text/javascript"></script>
  <!-- Flipbook main Js file -->
  <script src="flipbook/components/js/dflip.min.js" type="text/javascript"></script>
  <script>
    // Wait for flipbook to load, then show it instantly
    $(document).ready(function() {
      // Use jQuery to check if flipbook is initialized
      var checkFlipbook = setInterval(function() {
        var flipbook = $('#df_manual_book').find('._df_container');
        if (flipbook.length > 0) {
          clearInterval(checkFlipbook);
          document.getElementById('df_manual_book').style.visibility = 'visible';
          
          // Notify parent window that flipbook is loaded (if in iframe)
          if (window.parent && window.parent !== window) {
            window.parent.postMessage('flipbookLoaded', '*');
          }
        }
      }, 100);
      
      // Timeout safety after 2 seconds
      setTimeout(function() {
        clearInterval(checkFlipbook);
        document.getElementById('df_manual_book').style.visibility = 'visible';
        
        // Notify parent window that flipbook is loaded (if in iframe)
        if (window.parent && window.parent !== window) {
          window.parent.postMessage('flipbookLoaded', '*');
        }
      }, 2000);
    });
  </script>

</body>

</html>
HTML;

	$html = str_replace('{{TITLE}}', htmlspecialchars($title, ENT_QUOTES, 'UTF-8'), $template);
	$html = str_replace('{{PDF_PATH}}', htmlspecialchars($pdfPath, ENT_QUOTES, 'UTF-8'), $html);

	$outputFile = __DIR__ . '/../' . $slug . '.html';
	file_put_contents($outputFile, $html);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Create Flipbook - Admin</title>
	<link rel="stylesheet" href="../assets/css/admin.css">
	<style>
		.admin-content {
			padding: 24px;
		}

		.page-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 24px;
		}

		.page-header h1 {
			margin: 0;
			font-size: 24px;
			font-weight: 600;
			color: #111827;
		}

		.btn {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 8px 16px;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			background: #ffffff;
			cursor: pointer;
			text-decoration: none;
			font-size: 14px;
			transition: all 0.2s;
		}

		.btn:hover {
			background: #f8fafc;
		}

		.btn-primary {
			background: #667eea;
			color: #ffffff;
			border-color: #667eea;
		}

		.btn-primary:hover {
			background: #5568d3;
		}

		.btn-secondary {
			background: #6b7280;
			color: #ffffff;
			border-color: #6b7280;
		}

		.btn-secondary:hover {
			background: #4b5563;
		}

		.alert {
			padding: 12px 16px;
			border-radius: 8px;
			margin-bottom: 16px;
		}

		.alert-error {
			background: #fee2e2;
			color: #991b1b;
			border: 1px solid #fecaca;
		}

		.form-container {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 10px;
			padding: 24px;
			max-width: 800px;
		}

		.form-group {
			margin-bottom: 20px;
		}

		.form-group label {
			display: block;
			margin-bottom: 8px;
			font-weight: 500;
			color: #111827;
			font-size: 14px;
		}

		.form-group .required {
			color: #ef4444;
		}

		.form-control {
			width: 100%;
			padding: 10px 12px;
			border: 1px solid #d1d5db;
			border-radius: 8px;
			font-size: 14px;
			transition: border-color 0.2s;
		}

		.form-control:focus {
			outline: none;
			border-color: #667eea;
			box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
		}

		.form-group small {
			display: block;
			margin-top: 6px;
			color: #6b7280;
			font-size: 12px;
		}

		.form-actions {
			display: flex;
			gap: 12px;
			margin-top: 24px;
			padding-top: 24px;
			border-top: 1px solid #e5e7eb;
		}

		.upload-overlay {
			display: none !important;
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0, 0, 0, 0.85);
			z-index: 99999;
			align-items: center;
			justify-content: center;
			flex-direction: column;
			gap: 20px;
		}

		.upload-overlay.active {
			display: flex !important;
		}

		.upload-spinner {
			width: 50px;
			height: 50px;
			border: 4px solid rgba(255, 255, 255, 0.3);
			border-top-color: #667eea;
			border-radius: 50%;
			animation: spin 1s linear infinite;
		}

		@keyframes spin {
			to {
				transform: rotate(360deg);
			}
		}

		.upload-text {
			color: #ffffff;
			font-size: 18px;
			font-weight: 500;
		}

		.upload-progress {
			color: #ffffff;
			font-size: 14px;
			opacity: 0.8;
		}

		.progress-bar-container {
			width: 300px;
			max-width: 90%;
			height: 8px;
			background: rgba(255, 255, 255, 0.2);
			border-radius: 10px;
			overflow: hidden;
			margin-top: 10px;
		}

		.progress-bar {
			height: 100%;
			background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
			border-radius: 10px;
			width: 0%;
			transition: width 0.3s ease;
			position: relative;
			overflow: hidden;
		}

		.progress-bar::after {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			bottom: 0;
			right: 0;
			background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
			animation: shimmer 2s infinite;
		}

		@keyframes shimmer {
			0% {
				transform: translateX(-100%);
			}

			100% {
				transform: translateX(100%);
			}
		}

		.progress-percentage {
			color: #ffffff;
			font-size: 16px;
			font-weight: 600;
			margin-top: 8px;
		}

		.file-info {
			margin-top: 8px;
			padding: 8px 12px;
			background: #f3f4f6;
			border-radius: 6px;
			font-size: 13px;
			color: #6b7280;
		}
	</style>
</head>

<body>
		<?php include __DIR__ . '/includes/header.php'; ?>

	<div class="page-header">
		<h1>Create New Flipbook</h1>
		<a href="flipbooks.php" class="btn btn-secondary">Back to Flipbooks</a>
	</div>

		<?php if ($error): ?>
		<div class="alert alert-error"><?php echo $error; ?></div>
		<?php endif; ?>

	<form method="POST" enctype="multipart/form-data" class="form-container">
		<input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

		<div class="form-group">
			<label for="title">Title <span class="required">*</span></label>
			<input type="text" id="title" name="title" value="<?php echo escape($_POST['title'] ?? ''); ?>" required
				class="form-control">
			<small>This will be used as the page title.</small>
		</div>

		<div class="form-group">
			<label for="slug">Slug <span class="required">*</span></label>
			<input type="text" id="slug" name="slug" value="<?php echo escape($_POST['slug'] ?? ''); ?>" required
				class="form-control" pattern="[a-z0-9-]+">
			<small>URL-friendly identifier (lowercase letters, numbers, and hyphens only). Example:
				product-guide-2024</small>
		</div>

		<div class="form-group">
			<label for="pdf_file">PDF File <span class="required">*</span></label>
			<input type="file" id="pdf_file" name="pdf_file" accept=".pdf" required class="form-control">
			<small>Maximum file size: 1GB. Only PDF files are allowed.</small>
		</div>

		<div class="form-actions">
			<button type="submit" class="btn btn-primary">Create Flipbook</button>
			<a href="flipbooks.php" class="btn btn-secondary">Cancel</a>
		</div>
	</form>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const titleInput = document.getElementById('title');
			const slugInput = document.getElementById('slug');
			const form = document.querySelector('form.form-container');
			const uploadOverlay = document.getElementById('upload-overlay');
			const uploadProgress = document.getElementById('upload-progress');
			const progressBar = document.getElementById('progress-bar');
			const progressPercentage = document.getElementById('progress-percentage');
			const fileInput = document.getElementById('pdf_file');
			const formContainer = document.querySelector('.form-container');
			const pageHeader = document.querySelector('.page-header');

			// Auto-generate slug from title
			if (titleInput && slugInput) {
				titleInput.addEventListener('input', function () {
					if (!slugInput.value || slugInput.dataset.manual !== 'true') {
						const slug = titleInput.value
							.toLowerCase()
							.replace(/[^a-z0-9]+/g, '-')
							.replace(/^-+|-+$/g, '');
						slugInput.value = slug;
					}
				});

				// Mark slug as manually edited
				slugInput.addEventListener('input', function () {
					slugInput.dataset.manual = 'true';
				});
			}

			// Show file info when file is selected
			if (fileInput) {
				fileInput.addEventListener('change', function (e) {
					const file = e.target.files[0];
					if (file) {
						const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
						let fileInfo = document.querySelector('.file-info');
						if (!fileInfo) {
							fileInfo = document.createElement('div');
							fileInfo.className = 'file-info';
							fileInput.parentElement.appendChild(fileInfo);
						}
						fileInfo.textContent = `Selected: ${file.name} (${fileSizeMB} MB)`;
					}
				});
			}

			// Show upload animation on form submit
			if (form) {
				form.addEventListener('submit', function (e) {
					const title = titleInput ? titleInput.value.trim() : '';
					const slug = slugInput ? slugInput.value.trim() : '';
					const file = fileInput ? fileInput.files[0] : null;

					// Basic validation
					if (!title || !slug || !file) {
						return; // Let browser validation handle it
					}

					// Hide form and header, show only upload overlay
					if (formContainer) {
						formContainer.style.display = 'none';
					}
					if (pageHeader) {
						pageHeader.style.display = 'none';
					}

					// Show overlay
					uploadOverlay.classList.add('active');
					progressBar.style.width = '0%';
					progressPercentage.textContent = '0%';

					// Update progress text based on file size
					const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
					if (file.size > 10 * 1024 * 1024) {
						uploadProgress.textContent = `Uploading ${fileSizeMB} MB file... This may take a moment`;
					} else {
						uploadProgress.textContent = `Uploading ${fileSizeMB} MB file...`;
					}

					// Disable form to prevent double submission
					const submitBtn = form.querySelector('button[type="submit"]');
					if (submitBtn) {
						submitBtn.disabled = true;
						submitBtn.textContent = 'Uploading...';
					}

					// Simulate progress (since we can't track real progress with form submission)
					let progress = 0;
					const progressInterval = setInterval(function () {
						progress += Math.random() * 15; // Increment by random amount
						if (progress > 90) {
							progress = 90; // Don't go to 100% until upload completes
						}
						progressBar.style.width = progress + '%';
						progressPercentage.textContent = Math.round(progress) + '%';
					}, 200);

					// Store interval to clear it if needed
					form.dataset.progressInterval = progressInterval;
				});
			}
		});
	</script>

	<div id="upload-overlay" class="upload-overlay">
		<div class="upload-spinner"></div>
		<div class="upload-text">Uploading PDF...</div>
		<div class="progress-bar-container">
			<div class="progress-bar" id="progress-bar"></div>
		</div>
		<div class="progress-percentage" id="progress-percentage">0%</div>
		<div class="upload-progress" id="upload-progress">Please wait while we process your file</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const form = document.querySelector('form.form-container');
			const uploadOverlay = document.getElementById('upload-overlay');
			const uploadProgress = document.getElementById('upload-progress');
			const progressBar = document.getElementById('progress-bar');
			const progressPercentage = document.getElementById('progress-percentage');
			const fileInput = document.getElementById('pdf_file');

			// Show file info when file is selected
			fileInput.addEventListener('change', function (e) {
				const file = e.target.files[0];
				if (file) {
					const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
					let fileInfo = document.querySelector('.file-info');
					if (!fileInfo) {
						fileInfo = document.createElement('div');
						fileInfo.className = 'file-info';
						fileInput.parentElement.appendChild(fileInfo);
					}
					fileInfo.textContent = `Selected: ${file.name} (${fileSizeMB} MB)`;
				}
			});

			// Show upload animation on form submit
			if (form) {
				form.addEventListener('submit', function (e) {
					const title = document.getElementById('title').value.trim();
					const slug = document.getElementById('slug').value.trim();
					const file = fileInput.files[0];

					// Basic validation
					if (!title || !slug || !file) {
						return; // Let browser validation handle it
					}

					// Show overlay
					uploadOverlay.classList.add('active');
					progressBar.style.width = '0%';
					progressPercentage.textContent = '0%';

					// Update progress text based on file size
					const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
					if (file.size > 10 * 1024 * 1024) {
						uploadProgress.textContent = `Uploading ${fileSizeMB} MB file... This may take a moment`;
					} else {
						uploadProgress.textContent = `Uploading ${fileSizeMB} MB file...`;
					}

					// Disable form to prevent double submission
					const submitBtn = form.querySelector('button[type="submit"]');
					submitBtn.disabled = true;
					submitBtn.textContent = 'Uploading...';

					// Simulate progress (since we can't track real progress with form submission)
					// For real progress tracking, you'd need to convert to AJAX upload
					let progress = 0;
					const progressInterval = setInterval(function () {
						progress += Math.random() * 15; // Increment by random amount
						if (progress > 90) {
							progress = 90; // Don't go to 100% until upload completes
						}
						progressBar.style.width = progress + '%';
						progressPercentage.textContent = Math.round(progress) + '%';
					}, 200);

					// Store interval to clear it if needed
					form.dataset.progressInterval = progressInterval;
				});
			}
		});
	</script>

		<?php include __DIR__ . '/includes/footer.php'; ?>
</body>

</html>