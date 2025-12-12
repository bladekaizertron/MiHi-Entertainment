<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$role = strtolower(trim($currentUser['role'] ?? ''));
if (!in_array($role, ['admin','editor'], true)) {
	header('Location: index.php');
	exit;
}

$db = getDB();
$error = '';
$success = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

// CSRF
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Load existing
$page = [
	'title' => '',
	'slug' => '',
	'content_html' => '',
	'custom_css' => '',
	'status' => 'draft',
];
$originalSlug = '';
if ($isEdit) {
	try {
		$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
		$stmt->execute([$id]);
		$found = $stmt->fetch();
		if ($found) {
			$page = $found;
			$originalSlug = $found['slug'];
		}
	} catch (Throwable $e) {
		$error = 'Failed to load page. Ensure the pages table exists.';
	}
}

function slugify($text) {
	$text = strtolower(trim($text));
	$text = preg_replace('/[^a-z0-9-]+/', '-', $text);
	$text = preg_replace('/-+/', '-', $text);
	return trim($text, '-');
}

function renderStaticPageHtml($pageData) {
	$cssHref = SITE_URL . '/assets/css/style.css';
	$title = htmlspecialchars($pageData['title'] ?? '', ENT_QUOTES, 'UTF-8');
	$content = $pageData['content_html'] ?? '';
	$customCss = $pageData['custom_css'] ?? '';
	ob_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $title; ?></title>
	<link rel="stylesheet" href="<?php echo $cssHref; ?>">
	<style>
		.page-container { max-width: 1280px; margin: 0 auto; padding: 40px 20px 80px; }
		@media (min-width: 1536px) { .page-container { max-width: 1440px; } }
		.hero-section { border-radius: 24px; padding: 48px; margin-bottom: 32px; position: relative; overflow: hidden; background: #f8fafc; color: #0f172a; }
		.hero-section.dark { background: #0f172a; color: #f8fafc; }
		.hero-section.hero-image { background-size: cover; background-position: center; color: #ffffff; }
		.hero-section .eyebrow { text-transform: uppercase; letter-spacing: .15em; font-size: 12px; margin-bottom: 10px; opacity: .75; }
		.hero-section h1 { font-size: clamp(2.2rem, 4vw, 3.5rem); margin-bottom: 16px; }
		.hero-section p { font-size: 1.1rem; max-width: 640px; }
		.hero-section .hero-btn { display: inline-flex; align-items: center; padding: 12px 22px; border-radius: 999px; background: #FF4F4F; color: #fff; text-decoration: none; margin-top: 20px; font-weight: 600; }
		.text-image { display: flex; gap: 28px; align-items: center; padding: 36px; border-radius: 20px; background: #f8fafc; margin-bottom: 32px; flex-wrap: wrap; }
		.text-image.reverse { flex-direction: row-reverse; }
		.text-image img { width: 42%; border-radius: 18px; object-fit: cover; flex: 1 1 320px; }
		.text-image .text { flex: 1 1 320px; }
		.text-image h3 { margin-top: 0; }
		.text-image .btn-primary { margin-top: 16px; display: inline-flex; padding: 10px 18px; border-radius: 10px; background:#111827; color:#fff; text-decoration:none; }
		.feature-section { margin: 40px 0; }
		.feature-section h3 { margin-bottom: 12px; }
		.feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
		.feature-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; background: #fff; }
		.feature-card h4 { margin-top: 0; margin-bottom: 8px; }
		.page-content p { line-height: 1.7; margin-bottom: 16px; }
		.page-content h1, .page-content h2, .page-content h3 { margin-top: 24px; }
		@media (max-width: 768px) {
			.hero-section { padding: 32px 24px; }
			.text-image { padding: 24px; }
			.text-image img { width: 100%; }
		}
	</style>
	<?php if (!empty($customCss)): ?>
	<style><?php echo $customCss; ?></style>
	<?php endif; ?>
</head>
<body>
	<main class="page-container">
		<article class="page-content">
			<?php echo $content; ?>
		</article>
	</main>
</body>
</html>
<?php
	return ob_get_clean();
}

function writeStaticPage($pageData) {
	$dir = __DIR__ . '/..';
	$path = $dir . '/' . $pageData['slug'] . '.html';
	file_put_contents($path, renderStaticPageHtml($pageData));
}

function deleteStaticPage($slug) {
	$path = __DIR__ . '/../' . $slug . '.html';
	if (is_file($path)) {
		unlink($path);
	}
}

	$action = $_POST['action'] ?? '';
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'export_static') {
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($csrf, $token)) {
		$error = 'Security token invalid.';
	} else {
		$title = trim($_POST['title'] ?? '');
		$slug = trim($_POST['slug'] ?? '');
		$content = $_POST['content_html'] ?? '';
		$customCss = $_POST['custom_css'] ?? '';
		$status = in_array($_POST['status'] ?? 'draft', ['draft','published'], true) ? $_POST['status'] : 'draft';
		if ($title === '') {
			$error = 'Title is required.';
		} else {
			if ($slug === '') $slug = slugify($title);
			// Ensure slug does not conflict with existing static file outside of this page
			$staticPath = __DIR__ . '/../' . $slug . '.html';
			if (file_exists($staticPath) && (!$isEdit || $slug !== $originalSlug)) {
				$error = 'A static file with that slug already exists. Please choose a different slug.';
			} else {
			// Unique slug
			$check = $db->prepare("SELECT id FROM pages WHERE slug = ? AND id <> ?");
			try {
				$check->execute([$slug, $id]);
				if ($check->fetch()) {
					$error = 'Slug already exists.';
				} else {
					if ($isEdit) {
						$stmt = $db->prepare("UPDATE pages SET title = ?, slug = ?, content_html = ?, custom_css = ?, status = ?, published_at = CASE WHEN ? = 'published' AND published_at IS NULL THEN NOW() ELSE published_at END WHERE id = ?");
						$stmt->execute([$title, $slug, $content, $customCss ?: null, $status, $status, $id]);
						$success = 'Page updated.';
					} else {
						$stmt = $db->prepare("INSERT INTO pages (title, slug, content_html, custom_css, status, published_at) VALUES (?, ?, ?, ?, ?, ?)");
						$publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
						$stmt->execute([$title, $slug, $content, $customCss ?: null, $status, $publishedAt]);
						$id = (int)$db->lastInsertId();
						$isEdit = true;
						$success = 'Page created.';
					}
					// reload
					$page = ['title'=>$title,'slug'=>$slug,'content_html'=>$content,'custom_css'=>$customCss,'status'=>$status];
					$pageDataForExport = [
						'title' => $title,
						'slug' => $slug,
						'content_html' => $content,
						'custom_css' => $customCss
					];
					if ($status === 'published') {
						writeStaticPage($pageDataForExport);
					} else {
						deleteStaticPage($slug);
					}
					if ($originalSlug && $originalSlug !== $slug) {
						deleteStaticPage($originalSlug);
					}
				}
			} catch (Throwable $e) {
				$error = 'Save failed. Ensure the pages table exists.';
			}
			}
		}
	}
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'export_static') {
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($csrf, $token)) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Invalid token']);
		exit;
	}
	$pageId = (int)($_POST['page_id'] ?? 0);
	if ($pageId <= 0) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Missing page id']);
		exit;
	}
	$stmt = $db->prepare("SELECT title, slug, content_html, custom_css FROM pages WHERE id = ? LIMIT 1");
	$stmt->execute([$pageId]);
	$pageRow = $stmt->fetch();
	if (!$pageRow) {
		http_response_code(404);
		echo json_encode(['success' => false, 'message' => 'Page not found']);
		exit;
	}
	$html = renderStaticPageHtml($pageRow);
	header('Content-Type: application/json');
	echo json_encode(['success' => true, 'html' => $html, 'slug' => $pageRow['slug']]);
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $isEdit ? 'Edit Page' : 'New Page'; ?> - Admin</title>
	<link rel="stylesheet" href="../assets/css/admin.css">
	<style>
		.admin-content { padding: 24px; }
		.form-grid { display:grid; grid-template-columns: 2fr 1fr; gap:16px; }
		.card { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; }
		.form-group { margin-bottom:12px; }
		label { display:block; font-size:13px; color:#374151; margin-bottom:6px; }
		input[type="text"], select, textarea { width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; outline:none; }
		textarea { min-height: 280px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
		.actions { display:flex; gap:8px; }
		.btn { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#ffffff; cursor:pointer; text-decoration:none; }
		.btn:hover { background:#f8fafc; }
		.preview { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; position:sticky; top:24px; max-height:calc(100vh - 120px); display:flex; flex-direction:column; }
		.preview-header { padding:8px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280; font-size:12px; background:#f8fafc; display:flex; justify-content:space-between; align-items:center; }
		.preview-body { padding:16px; overflow:auto; flex:1; }
		/* Modal Popup for Live Preview */
		.preview-modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.75); backdrop-filter:blur(4px); }
		.preview-modal.active { display:flex; align-items:center; justify-content:center; }
		.preview-modal-content { background:#ffffff; border-radius:16px; width:95%; max-width:1400px; height:90vh; max-height:900px; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation:modalSlideIn 0.3s ease-out; }
		@keyframes modalSlideIn { from { opacity:0; transform:scale(0.95) translateY(-20px); } to { opacity:1; transform:scale(1) translateY(0); } }
		.preview-modal-header { padding:16px 20px; border-bottom:1px solid #e5e7eb; background:#f8fafc; display:flex; justify-content:space-between; align-items:center; border-radius:16px 16px 0 0; }
		.preview-modal-header h3 { margin:0; font-size:16px; font-weight:600; color:#111827; }
		.preview-modal-close { background:none; border:none; font-size:24px; color:#6b7280; cursor:pointer; padding:0; width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:6px; transition:all 0.2s; }
		.preview-modal-close:hover { background:#e5e7eb; color:#111827; }
		.preview-modal-body { padding:24px; overflow:auto; flex:1; background:#ffffff; }
		.preview-modal-actions { padding:12px 20px; border-top:1px solid #e5e7eb; background:#f8fafc; display:flex; gap:8px; justify-content:flex-end; border-radius:0 0 16px 16px; }
		/* Blocks */
		.builder-toolbar { margin-bottom:16px; }
		.builder-toolbar h4 { margin:0 0 8px; font-size:13px; color:#64748b; letter-spacing:.04em; text-transform:uppercase; }
		.block-palette { display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:8px; }
		.block-palette button { display:flex; align-items:center; gap:6px; padding:10px 12px; border-radius:10px; border:1px dashed #cbd5e1; background:#f8fafc; cursor:pointer; font-size:13px; color:#0f172a; transition:all .2s ease; }
		.block-palette button:hover { border-color:#FF4F4F; background:#fff5f5; color:#991b1b; }
		.preset-gallery { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-bottom:20px; }
		.preset-card { border:1px solid #e5e7eb; border-radius:12px; padding:12px; background:#fff; display:flex; flex-direction:column; gap:8px; }
		.preset-card h5 { margin:0; font-size:14px; }
		.preset-card p { margin:0; font-size:12px; color:#6b7280; }
		.preset-card button { align-self:flex-start; }
		.blocks-toolbar { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
		.btn-sm { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:8px; border:1px solid #e5e7eb; background:#ffffff; cursor:pointer; font-size:12px; }
		.btn-sm:hover { background:#f8fafc; }
		.block-item { border:1px solid #e5e7eb; border-radius:10px; padding:12px; margin-bottom:10px; background:#fff; transition:box-shadow .15s ease; }
		.block-item:hover { box-shadow:0 6px 12px rgba(15,23,42,.08); }
		.block-item.collapsed .block-fields { display:none; }
		.block-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; gap:8px; }
		.block-title { font-size:13px; color:#374151; font-weight:600; }
		.block-actions { display:flex; gap:6px; }
		.block-fields { display:grid; gap:8px; }
		.row-2 { display:grid; grid-template-columns: 1fr 1fr; gap:8px; }
		.helper-note { font-size:12px; color:#6b7280; margin-bottom:8px; }
		/* Drag in preview */
		.preview-block { border:1px dashed #e5e7eb; padding:10px; border-radius:8px; margin-bottom:10px; background:#fff; cursor:move; }
		.preview-block.drag-over { background:#f8fafc; border-color:#93c5fd; }
		/* preview section styles */
		.hero-section { border-radius:20px; padding:48px; position:relative; overflow:hidden; }
		.hero-section.dark { background:#0f172a; color:#f8fafc; }
		.hero-section.light { background:#f8fafc; color:#0f172a; }
		.hero-section .hero-btn { display:inline-flex; align-items:center; padding:10px 18px; border-radius:999px; background:#FF4F4F; color:#fff; text-decoration:none; margin-top:16px; }
		.text-image { display:flex; gap:24px; align-items:center; padding:32px; border-radius:16px; background:#f8fafc; }
		.text-image .text { flex:1; }
		.text-image.reverse { flex-direction:row-reverse; }
		.text-image img { width:45%; border-radius:16px; object-fit:cover; flex:1; }
		.feature-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; }
		.feature-card { border:1px solid #e5e7eb; border-radius:12px; padding:16px; background:#fff; }
	</style>
</head>
<body>
	<?php include __DIR__ . '/includes/header.php'; ?>
	<div class="admin-content">
		<div class="actions" style="margin-bottom:12px;">
			<a class="btn" href="pages.php">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 18l-6-6 6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<span>Back to Pages</span>
			</a>
			<?php if ($isEdit): ?>
			<button type="button" class="btn" onclick="exportStaticPage()" title="Generate and download static HTML">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<span>Export Static HTML</span>
			</button>
			<?php endif; ?>
		</div>

		<?php if ($error): ?>
			<div class="alert alert-error" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:10px 12px;border-radius:8px;margin-bottom:14px;">&nbsp;<?php echo escape($error); ?></div>
		<?php endif; ?>
		<?php if ($success): ?>
			<div class="alert alert-success" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:10px 12px;border-radius:8px;margin-bottom:14px;">&nbsp;<?php echo escape($success); ?></div>
		<?php endif; ?>

		<form method="POST" action="">
			<input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
			<div class="form-grid">
				<div class="card">
					<div class="helper-note">Use the block editor to design from scratch, or edit the HTML directly below.</div>
					<div class="builder-toolbar">
						<h4>Block Palette</h4>
						<div class="block-palette">
							<button type="button" onclick="addBlock('hero')">✨ Hero Section</button>
							<button type="button" onclick="addBlock('heading')">H Heading</button>
							<button type="button" onclick="addBlock('paragraph')">¶ Paragraph</button>
							<button type="button" onclick="addBlock('textImage')">🖼 Text + Image</button>
							<button type="button" onclick="addBlock('featureGrid')">⭐ Feature Grid</button>
							<button type="button" onclick="addBlock('image')">🖼 Standalone Image</button>
							<button type="button" onclick="addBlock('button')">🔘 Button</button>
							<button type="button" onclick="addBlock('divider')">— Divider</button>
							<button type="button" onclick="addBlock('html')">&lt;/&gt; Custom HTML</button>
						</div>
					</div>
					<div class="builder-toolbar">
						<h4>MiHi Presets</h4>
						<div class="preset-gallery">
							<div class="preset-card">
								<h5>🏠 Homepage Overview</h5>
								<p>Complete brand introduction with signature services and nationwide coverage.</p>
								<button type="button" class="btn-sm" onclick="applyPreset('mihiHome')">Use preset</button>
							</div>
							<div class="preset-card">
								<h5>📸 Photo Booth Services</h5>
								<p>Comprehensive photo booth lineup with AI, 360°, and classic options.</p>
								<button type="button" class="btn-sm" onclick="applyPreset('photoBooths')">Use preset</button>
							</div>
							<div class="preset-card">
								<h5>🎥 Video Booth Experiences</h5>
								<p>Showcase cinematic video booths including GlamBot and bullet-time.</p>
								<button type="button" class="btn-sm" onclick="applyPreset('videoBooths')">Use preset</button>
							</div>
							<div class="preset-card">
								<h5>✨ Complete Event Solutions</h5>
								<p>Highlight A/V services, event decor, and themed experiences.</p>
								<button type="button" class="btn-sm" onclick="applyPreset('eventServices')">Use preset</button>
							</div>
							<div class="preset-card">
								<h5>📍 Locations Page</h5>
								<p>Nationwide coverage with popular service locations.</p>
								<button type="button" class="btn-sm" onclick="applyPreset('locations')">Use preset</button>
							</div>
						</div>
					</div>
					<div id="blocks-container"></div>
					<hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0;">
					<div class="form-group">
						<label for="title">Title</label>
						<input type="text" id="title" name="title" value="<?php echo escape($page['title']); ?>" required>
					</div>
					<div class="form-group">
						<label for="slug">Slug</label>
						<input type="text" id="slug" name="slug" value="<?php echo escape($page['slug']); ?>" placeholder="auto-from-title">
					</div>
					<div class="form-group">
						<label for="content_html">Content (HTML allowed)</label>
						<textarea id="content_html" name="content_html" oninput="updatePreview()"><?php echo htmlspecialchars($page['content_html'] ?? '', ENT_NOQUOTES, 'UTF-8'); ?></textarea>
					</div>
					<div class="form-group">
						<label for="custom_css">Custom CSS</label>
						<textarea id="custom_css" name="custom_css" style="min-height:140px;" oninput="updatePreview()"><?php echo htmlspecialchars($page['custom_css'] ?? '', ENT_NOQUOTES, 'UTF-8'); ?></textarea>
					</div>
					<div class="actions">
						<button class="btn" type="submit" onclick="document.getElementById('status').value='draft'">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 7h18M3 12h18M3 17h18" stroke-width="2" stroke-linecap="round"/></svg>
							<span>Save Draft</span>
						</button>
						<button class="btn" type="submit" onclick="document.getElementById('status').value='published'">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12l5 5L20 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							<span>Publish</span>
						</button>
						<input type="hidden" id="status" name="status" value="<?php echo escape($page['status']); ?>">
						<?php if ($isEdit): ?>
						<a class="btn" href="../page.php?slug=<?php echo urlencode($page['slug']); ?>" target="_blank">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 3h7v7m-1-6L10 14M5 5h5M5 10v9h9v-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							<span>View</span>
						</a>
						<?php endif; ?>
					</div>
				</div>
				<div class="preview" style="display:none;">
					<div class="preview-header">
						<span>Live Preview</span>
						<button type="button" class="btn-sm" onclick="togglePreviewBackground()">Toggle BG</button>
					</div>
					<div class="preview-body" id="preview-body"><?php echo $page['content_html']; ?></div>
				</div>
				<div style="text-align:center; padding:20px; border:2px dashed #e5e7eb; border-radius:12px; background:#f8fafc;">
					<button type="button" class="btn" onclick="openPreviewModal()" style="background:#FF4F4F; color:#fff; padding:12px 24px; font-size:14px; font-weight:600;">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right:8px;"><path d="M14 3h7v7M5 5h5M5 10v9h9v-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						Open Live Preview
					</button>
					<p style="margin-top:12px; font-size:12px; color:#6b7280;">Click to view your page in a full-screen popup</p>
				</div>
			</div>
			
			<!-- Preview Modal Popup -->
			<div id="preview-modal" class="preview-modal" onclick="if(event.target===this) closePreviewModal()">
				<div class="preview-modal-content">
					<div class="preview-modal-header">
						<h3>Live Preview</h3>
						<div style="display:flex; gap:8px; align-items:center;">
							<button type="button" class="btn-sm" onclick="togglePreviewBackground()" style="font-size:12px;">Toggle BG</button>
							<button type="button" class="preview-modal-close" onclick="closePreviewModal()" title="Close (Esc)">×</button>
						</div>
					</div>
					<div class="preview-modal-body" id="preview-modal-body"><?php echo $page['content_html']; ?></div>
					<div class="preview-modal-actions">
						<button type="button" class="btn-sm" onclick="closePreviewModal()">Close</button>
					</div>
				</div>
			</div>
			</div>
		</form>
	</div>
	<script>
	const csrfToken = '<?php echo $csrf; ?>';
	const currentPageId = <?php echo $isEdit ? (int)$id : 'null'; ?>;

	function updatePreview() {
		var html = document.getElementById('content_html').value;
		var css = document.getElementById('custom_css').value;
		var body = document.getElementById('preview-body');
		var modalBody = document.getElementById('preview-modal-body');
		// If using blocks, render with wrappers for drag; otherwise, fallback to raw html
		if (blocks.length > 0) {
			renderPreviewFromBlocks();
		} else {
			var content = html + (css ? ('<style>' + css + '</style>') : '');
			body.innerHTML = content;
			if (modalBody) modalBody.innerHTML = content;
		}
	}
	
	function openPreviewModal() {
		updatePreview(); // Ensure preview is up to date
		document.getElementById('preview-modal').classList.add('active');
		document.body.style.overflow = 'hidden';
	}
	
	function closePreviewModal() {
		document.getElementById('preview-modal').classList.remove('active');
		document.body.style.overflow = '';
	}
	
	// Close modal on Escape key
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape') {
			closePreviewModal();
		}
	});

	// Simple block editor
	const blockDefaults = {
		heading: { type:'heading', level:'h2', text:'Heading text', align:'left' },
		paragraph: { type:'paragraph', text:'Lorem ipsum dolor sit amet, consectetur adipiscing elit.' },
		image: { type:'image', src:'', alt:'', width:'', align:'center' },
		button: { type:'button', label:'Learn more', href:'#', style:'primary', align:'left' },
		divider: { type:'divider' },
		html: { type:'html', markup:'<div class="custom-section">Your HTML here</div>' },
		hero: { type:'hero', eyebrow:'Our Services', title:'Create unforgettable experiences', subtitle:'Design sections quickly with the block builder.', buttonText:'Get Started', buttonUrl:'#', background:'light', align:'left', backgroundImage:'' },
		textImage: { type:'textImage', title:'Tell your story', body:'Pair rich text with imagery to highlight key moments.', image:'', imagePosition:'right', background:'#f8fafc', buttonText:'Read More', buttonUrl:'#' },
		featureGrid: { type:'featureGrid', title:'Why choose us', intro:'Delivering wow-worthy experiences with every event.', columns:3, items:"Concept + Design|Collaborate with our creative team to plan remarkable experiences.\nPremium Talent|Connect with world-class performers and entertainers.\nOn-Site Execution|Relax while we manage every detail flawlessly." }
	};

	const presets = {
		mihiHome: [
			{ type:'hero', eyebrow:'MIHI ENTERTAINMENT', title:'PREMIUM PHOTO & VIDEO BOOTH RENTALS NATIONWIDE', subtitle:'Transform your event with cutting-edge AI experiences, cinematic 360° video booths, and unforgettable activations. From weddings to corporate events, we deliver studio-quality production coast to coast.', buttonText:'GET STARTED', buttonUrl:'/contact-us.html', background:'dark', align:'center' },
			{ type:'featureGrid', title:'SIGNATURE PHOTO BOOTH EXPERIENCES', intro:'Elevate your event with our most popular booth rentals.', columns:3, items:"AI Photo Booth|Transform guests into superheroes, celebrities, or fantasy characters with cutting-edge AI technology.\n360° Video Booth|Cinematic slow-motion videos captured from every angle with full 360° rotation.\nGreen Screen Booth|Transport guests anywhere with custom backgrounds and professional green screen technology.\nGlamBot Video Booth|Automated cinematic slow-motion videos with red-carpet treatment.\nMosaic Wall|Build your event story photo by photo with interactive displays.\nRoaming Booth|The party comes to your guests with our mobile photo booth experience." },
			{ type:'textImage', title:'NATIONWIDE COVERAGE', body:'From Denver to Las Vegas, Boston to Los Angeles—MiHi Entertainment delivers premium photo booth rentals and event services across the United States. Our professional team ensures flawless setup and unforgettable experiences at every location.', image:'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', imagePosition:'right', background:'#1F1F1F', buttonText:'VIEW LOCATIONS', buttonUrl:'/locations' },
			{ type:'button', label:'EXPLORE ALL SERVICES', href:'/booth-services.html', style:'primary', align:'center' }
		],
		photoBooths: [
			{ type:'hero', eyebrow:'PHOTO BOOTH RENTALS', title:'CREATE UNFORGETTABLE MOMENTS', subtitle:'From AI-powered transformations to classic photo prints, our photo booths are rated #1 for weddings, corporate events, and parties nationwide.', buttonText:'GET PRICING', buttonUrl:'/contact-us.html', background:'dark', align:'center' },
			{ type:'featureGrid', title:'PREMIUM PHOTO BOOTH OPTIONS', intro:'Choose from our complete lineup of photo booth experiences.', columns:3, items:"AI Photo Booth|Transform guests into superheroes, celebrities, or fantasy characters.\n360° Photo Booth|Capture stunning 360° rotating photos with instant prints.\nGreen Screen Booth|Custom backgrounds and professional green screen technology.\nMosaic Wall|Interactive photo displays that build your event story.\nRoaming Booth|Mobile photo booth that goes where your guests are.\nClassic Photo Booth|Traditional photo booth with instant prints and custom overlays." },
			{ type:'textImage', title:'WHY CHOOSE MIHI PHOTO BOOTHS', body:'MiHi Entertainment sets the gold standard for photo booth rentals. Our state-of-the-art equipment, professional attendants, and customizable options ensure every detail is perfect. Rated #1 in customer satisfaction with seamless setup and unforgettable experiences.', image:'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f', imagePosition:'left', background:'#1F1F1F', buttonText:'VIEW PHOTO BOOTHS', buttonUrl:'/product/photo-booth.html' },
			{ type:'button', label:'BOOK YOUR PHOTO BOOTH', href:'/contact-us.html', style:'primary', align:'center' }
		],
		videoBooths: [
			{ type:'hero', eyebrow:'VIDEO BOOTH EXPERIENCES', title:'CINEMATIC SLOW-MOTION VIDEO BOOTHS', subtitle:'Give your event the red carpet treatment with high-speed GlamBot clips, bullet-time arrays, and fully branded video experiences.', buttonText:'VIEW VIDEO BOOTHS', buttonUrl:'/video-booths.html', background:'dark', align:'center' },
			{ type:'featureGrid', title:'SIGNATURE VIDEO BOOTH ACTIVATIONS', intro:'Professional video production for events of all sizes.', columns:3, items:"360° Video Booth|Cinematic slow-motion videos from every angle with full rotation.\nGlamBot Video Booth|Automated red-carpet slow-motion clips with dramatic flair.\nBullet-Time Array|Matrix-style multi-camera freeze-frame experiences.\nVideo Testimonial Studio|Collect authentic guest reactions with branded backdrops.\nSlow-Motion Booth|High-speed footage with cinematic quality and instant delivery." },
			{ type:'textImage', title:'FULLY BRANDED VIDEO EXPERIENCES', body:'Every video includes your custom branding, logos, music, and effects. Our professional team handles setup, operation, and instant delivery of high-quality video content that your guests will share for years to come.', image:'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f', imagePosition:'right', background:'#1F1F1F', buttonText:'SEE VIDEO SAMPLES', buttonUrl:'/video-booths.html' },
			{ type:'button', label:'BOOK A VIDEO BOOTH', href:'/contact-us.html', style:'primary', align:'center' }
		],
		eventServices: [
			{ type:'hero', eyebrow:'COMPLETE EVENT SOLUTIONS', title:'AUDIO, VISUAL, DECOR & THEMED EXPERIENCES', subtitle:'Transform corporate, social, holiday, and casino events with MiHi\'s comprehensive A/V rentals, event decor, and immersive themed experiences.', buttonText:'EXPLORE SERVICES', buttonUrl:'/av-services', background:'dark', align:'left' },
			{ type:'featureGrid', title:'A/V PRODUCTION SERVICES', intro:'Professional audio-visual solutions for any venue size.', columns:3, items:"Audio Services|Crisp sound systems, wireless microphones, and professional mixing.\nVisual Services|LED screens, projectors, digital signage, and video walls.\nLighting Services|Dynamic lighting effects, stage lighting, and mood-setting ambiance.\nEvent Stages|Professional staging solutions for presentations and performances." },
			{ type:'featureGrid', title:'EVENT DECOR & RENTALS', intro:'Special effects, themed decor, and interactive experiences.', columns:3, items:"Special Effects|Sparkular displays, confetti cannons, champagne walls, and fog machines.\nEvent Decor|Ceiling fabric, shimmer walls, themed lounges, and custom signage.\nGame Rentals|Claw machines, VR headsets, money booth experiences, and arcade games.\nCasino Rentals|Professional casino setups with tables, dealers, and themed experiences." },
			{ type:'textImage', title:'IMMERSIVE THEMED EXPERIENCES', body:'From Western saloons to luxe holiday parties, trade show installations to corporate activations—choose from our curated themed sets or work with our team to create custom builds that perfectly match your event vision.', image:'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', imagePosition:'left', background:'#1F1F1F', buttonText:'VIEW THEMES', buttonUrl:'/event-decor' }
		],
		locations: [
			{ type:'hero', eyebrow:'NATIONWIDE COVERAGE', title:'PHOTO BOOTH RENTALS ACROSS AMERICA', subtitle:'MiHi Entertainment delivers premium photo booth and event services to major cities nationwide. Find our services in Denver, Las Vegas, Los Angeles, Boston, and 50+ locations.', buttonText:'FIND YOUR LOCATION', buttonUrl:'/locations', background:'dark', align:'center' },
			{ type:'featureGrid', title:'POPULAR SERVICE LOCATIONS', intro:'We serve events in major metropolitan areas across the United States.', columns:3, items:"Denver, Colorado|Premium photo booth rentals for weddings and corporate events.\nLas Vegas, Nevada|Casino parties, trade shows, and high-end event activations.\nLos Angeles, California|Red-carpet events, celebrity parties, and luxury experiences.\nBoston, Massachusetts|Corporate events, weddings, and social gatherings.\nNew York, New York|High-profile events, product launches, and exclusive parties." },
			{ type:'textImage', title:'SERVING EVENTS NATIONWIDE', body:'No matter where your event is located, MiHi Entertainment brings the same level of professionalism, quality equipment, and unforgettable experiences. Our nationwide network ensures consistent service excellence from coast to coast.', image:'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', imagePosition:'right', background:'#1F1F1F', buttonText:'VIEW ALL LOCATIONS', buttonUrl:'/locations' }
		]
	};

	function createBlock(type) {
		var template = blockDefaults[type];
		if (!template) return { type:type };
		var clone = JSON.parse(JSON.stringify(template));
		clone.id = 'block-' + Date.now() + '-' + Math.floor(Math.random()*1000);
		clone.collapsed = false;
		return clone;
	}

	var blocks = [];
	var dragSrcIndex = null;
	var previewDark = false;

	function addBlock(type) {
		var block = createBlock(type);
		if (!block) return;
		blocks.push(block);
		renderBlocksEditor();
		syncBlocksToHtml();
	}

	function applyPreset(key) {
		if (!presets[key]) return;
		if (blocks.length > 0 && !confirm('Replace current layout with this preset?')) {
			return;
		}
		blocks = presets[key].map(function(b) {
			var template = createBlock(b.type);
			return Object.assign(template, b);
		});
		renderBlocksEditor();
		syncBlocksToHtml();
		window.scrollTo({ top: document.querySelector('.builder-toolbar').offsetTop - 20, behavior: 'smooth' });
	}

	function moveBlock(idx, dir) {
		var newIdx = idx + dir;
		if (newIdx < 0 || newIdx >= blocks.length) return;
		var temp = blocks[idx];
		blocks[idx] = blocks[newIdx];
		blocks[newIdx] = temp;
		renderBlocksEditor();
		syncBlocksToHtml();
	}

	function deleteBlock(idx) {
		if (!confirm('Remove this block?')) return;
		blocks.splice(idx, 1);
		renderBlocksEditor();
		syncBlocksToHtml();
	}

	function duplicateBlock(idx) {
		var copy = JSON.parse(JSON.stringify(blocks[idx]));
		copy.collapsed = blocks[idx].collapsed || false;
		blocks.splice(idx + 1, 0, copy);
		renderBlocksEditor();
		syncBlocksToHtml();
	}

	function toggleCollapse(idx) {
		blocks[idx].collapsed = !blocks[idx].collapsed;
		renderBlocksEditor();
	}

	function onFieldChange(idx, field, value) {
		blocks[idx][field] = value;
		syncBlocksToHtml();
	}

	function renderBlocksEditor() {
		var cont = document.getElementById('blocks-container');
		cont.innerHTML = '';
		blocks.forEach(function(b, i) {
			var wrapper = document.createElement('div');
			wrapper.className = 'block-item' + (b.collapsed ? ' collapsed' : '');
			var head = document.createElement('div');
			head.className = 'block-head';
			var title = document.createElement('div');
			title.className = 'block-title';
			title.textContent = (b.type.charAt(0).toUpperCase() + b.type.slice(1));
			var actions = document.createElement('div');
			actions.className = 'block-actions';
			actions.innerHTML = '' +
				'<button type="button" class="btn-sm" onclick="moveBlock('+i+', -1)">↑</button>' +
				'<button type="button" class="btn-sm" onclick="moveBlock('+i+', 1)">↓</button>' +
				'<button type="button" class="btn-sm" onclick="duplicateBlock('+i+')">Duplicate</button>' +
				'<button type="button" class="btn-sm" onclick="toggleCollapse('+i+')">'+(b.collapsed ? 'Expand' : 'Collapse')+'</button>' +
				'<button type="button" class="btn-sm" onclick="deleteBlock('+i+')">Delete</button>';
			head.appendChild(title);
			head.appendChild(actions);

			var fields = document.createElement('div');
			fields.className = 'block-fields';

			if (b.type === 'heading') {
				fields.innerHTML = '' +
					'<div class="row-2">' +
					'  <div><label>Level</label><select onchange="onFieldChange('+i+', \'level\', this.value)">' +
					['h1','h2','h3','h4','h5','h6'].map(function(l){return '<option '+(b.level===l?'selected':'')+' value="'+l+'">'+l.toUpperCase()+'</option>';}).join('') +
					'  </select></div>' +
					'  <div><label>Align</label><select onchange="onFieldChange('+i+', \'align\', this.value)">' +
					['left','center','right'].map(function(a){return '<option '+(b.align===a?'selected':'')+' value="'+a+'">'+a+'</option>';}).join('') +
					'</select></div>' +
					'</div>' +
					'<div><label>Text</label><input type="text" value="'+escapeHtml(b.text)+'" oninput="onFieldChange('+i+', \'text\', this.value)"></div>';
			} else if (b.type === 'paragraph') {
				fields.innerHTML = '' +
					'<div><label>Text</label><textarea oninput="onFieldChange('+i+', \'text\', this.value)">'+escapeHtml(b.text)+'</textarea></div>';
			} else if (b.type === 'image') {
				fields.innerHTML = '' +
					'<div class="row-2"><div><label>Image URL</label><input type="text" value="'+escapeHtml(b.src)+'" oninput="onFieldChange('+i+', \'src\', this.value)"></div>' +
					'<div><label>Alt text</label><input type="text" value="'+escapeHtml(b.alt)+'" oninput="onFieldChange('+i+', \'alt\', this.value)"></div></div>' +
					'<div class="row-2"><div><label>Width (e.g. 600px or 50%)</label><input type="text" value="'+escapeHtml(b.width)+'" oninput="onFieldChange('+i+', \'width\', this.value)"></div>' +
					'<div><label>Align</label><select onchange="onFieldChange('+i+', \'align\', this.value)">' +
					['left','center','right'].map(function(a){return '<option '+(b.align===a?'selected':'')+' value="'+a+'">'+a+'</option>';}).join('') +
					'</select></div></div>';
			} else if (b.type === 'button') {
				fields.innerHTML = '' +
					'<div class="row-2"><div><label>Label</label><input type="text" value="'+escapeHtml(b.label)+'" oninput="onFieldChange('+i+', \'label\', this.value)"></div>' +
					'<div><label>Link</label><input type="text" value="'+escapeHtml(b.href)+'" oninput="onFieldChange('+i+', \'href\', this.value)"></div></div>' +
					'<div class="row-2"><div><label>Style</label><select onchange="onFieldChange('+i+', \'style\', this.value)">' +
					['primary','secondary','link'].map(function(s){return '<option '+(b.style===s?'selected':'')+' value="'+s+'">'+s+'</option>';}).join('') +
					'</select></div>' +
					'<div><label>Align</label><select onchange="onFieldChange('+i+', \'align\', this.value)">' +
					['left','center','right'].map(function(a){return '<option '+(b.align===a?'selected':'')+' value="'+a+'">'+a+'</option>';}).join('') +
					'</select></div></div>';
			} else if (b.type === 'divider') {
				fields.innerHTML = '<div class="helper-note">A horizontal line divider.</div>';
			} else if (b.type === 'html') {
				fields.innerHTML = '<div><label>Custom HTML</label><textarea oninput="onFieldChange('+i+', \'markup\', this.value)">'+escapeHtml(b.markup || '')+'</textarea></div>';
			} else if (b.type === 'hero') {
				fields.innerHTML = '' +
					'<div class="row-2"><div><label>Eyebrow</label><input type="text" value="'+escapeHtml(b.eyebrow||'')+'" oninput="onFieldChange('+i+', \'eyebrow\', this.value)"></div>' +
					'<div><label>Align</label><select onchange="onFieldChange('+i+', \'align\', this.value)">' +
					['left','center'].map(function(a){return '<option '+((b.align||'left')===a?'selected':'')+' value="'+a+'">'+a+'</option>';}).join('') +
					'</select></div></div>' +
					'<div><label>Title</label><textarea oninput="onFieldChange('+i+', \'title\', this.value)">'+escapeHtml(b.title||'')+'</textarea></div>' +
					'<div><label>Subtitle</label><textarea oninput="onFieldChange('+i+', \'subtitle\', this.value)">'+escapeHtml(b.subtitle||'')+'</textarea></div>' +
					'<div class="row-2"><div><label>Button Text</label><input type="text" value="'+escapeHtml(b.buttonText||'')+'" oninput="onFieldChange('+i+', \'buttonText\', this.value)"></div>' +
					'<div><label>Button URL</label><input type="text" value="'+escapeHtml(b.buttonUrl||'')+'" oninput="onFieldChange('+i+', \'buttonUrl\', this.value)"></div></div>' +
					'<div class="row-2"><div><label>Background Style</label><select onchange="onFieldChange('+i+', \'background\', this.value)">' +
					['light','dark','image'].map(function(a){return '<option '+((b.background||'light')===a?'selected':'')+' value="'+a+'">'+a+'</option>';}).join('') +
					'</select></div>' +
					'<div><label>Background Image (if image)</label><input type="text" value="'+escapeHtml(b.backgroundImage||'')+'" oninput="onFieldChange('+i+', \'backgroundImage\', this.value)"></div></div>';
			} else if (b.type === 'textImage') {
				fields.innerHTML = '' +
					'<div class="row-2"><div><label>Title</label><input type="text" value="'+escapeHtml(b.title||'')+'" oninput="onFieldChange('+i+', \'title\', this.value)"></div>' +
					'<div><label>Image Position</label><select onchange="onFieldChange('+i+', \'imagePosition\', this.value)">' +
					['left','right'].map(function(a){return '<option '+((b.imagePosition||'right')===a?'selected':'')+' value="'+a+'">'+a+'</option>';}).join('') +
					'</select></div></div>' +
					'<div><label>Body</label><textarea oninput="onFieldChange('+i+', \'body\', this.value)">'+escapeHtml(b.body||'')+'</textarea></div>' +
					'<div class="row-2"><div><label>Image URL</label><input type="text" value="'+escapeHtml(b.image||'')+'" oninput="onFieldChange('+i+', \'image\', this.value)"></div>' +
					'<div><label>Background</label><input type="text" value="'+escapeHtml(b.background||'#f8fafc')+'" oninput="onFieldChange('+i+', \'background\', this.value)"></div></div>' +
					'<div class="row-2"><div><label>Button Text</label><input type="text" value="'+escapeHtml(b.buttonText||'')+'" oninput="onFieldChange('+i+', \'buttonText\', this.value)"></div>' +
					'<div><label>Button URL</label><input type="text" value="'+escapeHtml(b.buttonUrl||'')+'" oninput="onFieldChange('+i+', \'buttonUrl\', this.value)"></div></div>';
			} else if (b.type === 'featureGrid') {
				fields.innerHTML = '' +
					'<div><label>Title</label><input type="text" value="'+escapeHtml(b.title||'')+'" oninput="onFieldChange('+i+', \'title\', this.value)"></div>' +
					'<div><label>Intro</label><textarea oninput="onFieldChange('+i+', \'intro\', this.value)">'+escapeHtml(b.intro||'')+'</textarea></div>' +
					'<div class="row-2"><div><label>Columns</label><select onchange="onFieldChange('+i+', \'columns\', this.value)">' +
					[2,3,4].map(function(a){return '<option '+((parseInt(b.columns,10)||3)===a?'selected':'')+' value="'+a+'">'+a+'</option>';}).join('') +
					'</select></div>' +
					'<div><label>Items (one per line: Title|Description)</label><textarea oninput="onFieldChange('+i+', \'items\', this.value)">'+escapeHtml(b.items||'')+'</textarea></div></div>';
			}

			wrapper.appendChild(head);
			wrapper.appendChild(fields);
			cont.appendChild(wrapper);
		});
	}

	function escapeHtml(s) {
		return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\"/g,'&quot;').replace(/'/g,'&#039;');
	}

	function blocksToHtmlArray() {
		return blocks.map(function(b) {
			if (b.type === 'heading') {
				var tag = b.level || 'h2';
				var alignStyle = b.align && b.align !== 'left' ? ' style="text-align:'+b.align+';"' : '';
				return '<'+tag+alignStyle+'>'+escapeHtml(b.text)+'</'+tag+'>';
			}
			if (b.type === 'paragraph') {
				return '<p>'+escapeHtml(b.text)+'</p>';
			}
			if (b.type === 'image') {
				var styles = [];
				if (b.width) styles.push('width:'+b.width);
				var wrapStyle = '';
				if (b.align === 'center') wrapStyle = 'text-align:center;';
				if (b.align === 'right') wrapStyle = 'text-align:right;';
				var imgTag = '<img src="'+escapeHtml(b.src)+'" alt="'+escapeHtml(b.alt)+'"'+(styles.length?' style="'+styles.join(';')+'"':'')+'>';
				return '<div style="'+wrapStyle+'">'+imgTag+'</div>';
			}
			if (b.type === 'button') {
				var cls = (b.style==='secondary') ? 'btn-secondary' : (b.style==='link' ? 'btn-link' : 'btn-primary');
				var wrapStyle = '';
				if (b.align === 'center') wrapStyle = 'text-align:center;';
				if (b.align === 'right') wrapStyle = 'text-align:right;';
				return '<div style="'+wrapStyle+'"><a href="'+escapeHtml(b.href)+'" class="'+cls+'">'+escapeHtml(b.label)+'</a></div>';
			}
			if (b.type === 'divider') {
				return '<hr>';
			}
			if (b.type === 'html') {
				return b.markup || '';
			}
			if (b.type === 'hero') {
				var heroClass = 'hero-section '+(b.background || 'light')+' align-'+(b.align || 'left');
				var style = '';
				if ((b.background || '') === 'image' && b.backgroundImage) {
					style = ' style="background-image:url('+escapeHtml(b.backgroundImage)+');background-size:cover;background-position:center;"';
					heroClass += ' hero-image';
				}
				return '<section class="'+heroClass+'"'+style+'>' +
					(b.eyebrow ? '<p class="eyebrow">'+escapeHtml(b.eyebrow)+'</p>' : '') +
					(b.title ? '<h1>'+escapeHtml(b.title)+'</h1>' : '') +
					(b.subtitle ? '<p>'+escapeHtml(b.subtitle)+'</p>' : '') +
					(b.buttonText ? '<a href="'+escapeHtml(b.buttonUrl||'#')+'" class="hero-btn">'+escapeHtml(b.buttonText)+'</a>' : '') +
					'</section>';
			}
			if (b.type === 'textImage') {
				var reverse = (b.imagePosition || 'right') === 'left' ? ' reverse' : '';
				var bg = b.background ? ' style="background:'+escapeHtml(b.background)+';"' : '';
				var buttonHtml = b.buttonText ? '<a href="'+escapeHtml(b.buttonUrl||'#')+'" class="btn-primary">'+escapeHtml(b.buttonText)+'</a>' : '';
				var imageHtml = b.image ? '<img src="'+escapeHtml(b.image)+'" alt="'+escapeHtml(b.title||'')+'">' : '';
				return '<section class="text-image'+reverse+'"'+bg+'>'+imageHtml+'<div class="text">'+
					(b.title ? '<h3>'+escapeHtml(b.title)+'</h3>' : '') +
					(b.body ? '<p>'+escapeHtml(b.body)+'</p>' : '') +
					buttonHtml +
					'</div></section>';
			}
			if (b.type === 'featureGrid') {
				var cols = parseInt(b.columns, 10) || 3;
				var items = (b.items || '').split('\n').filter(Boolean).map(function(line){
					var parts = line.split('|');
					return { title: parts[0] ? parts[0].trim() : '', desc: parts[1] ? parts[1].trim() : '' };
				});
				var cards = items.map(function(item){
					return '<div class="feature-card"><h4>'+escapeHtml(item.title)+'</h4><p>'+escapeHtml(item.desc)+'</p></div>';
				}).join('');
				return '<section class="feature-section"><h3>'+escapeHtml(b.title || '')+'</h3><p>'+escapeHtml(b.intro || '')+'</p><div class="feature-grid columns-'+cols+'">'+cards+'</div></section>';
			}
			return '';
		});
	}

	function syncBlocksToHtml() {
		var html = blocksToHtmlArray().join('\n');
		var textarea = document.getElementById('content_html');
		textarea.value = html;
		renderPreviewFromBlocks();
	}

	function renderPreviewFromBlocks() {
		var body = document.getElementById('preview-body');
		var modalBody = document.getElementById('preview-modal-body');
		var pieces = blocksToHtmlArray();
		var wrapped = pieces.map(function(piece, idx){
			return '<div class="preview-block" draggable="true" data-index="'+idx+'">'+piece+'</div>';
		}).join('');
		var css = document.getElementById('custom_css').value;
		var content = wrapped + (css ? ('<style>' + css + '</style>') : '');
		body.innerHTML = content;
		if (modalBody) modalBody.innerHTML = content;
		attachDnD();
	}

	function attachDnD() {
		var body = document.getElementById('preview-body');
		var items = body.querySelectorAll('.preview-block');
		items.forEach(function(el){
			el.addEventListener('dragstart', function(e){
				dragSrcIndex = parseInt(el.getAttribute('data-index'));
				e.dataTransfer.effectAllowed = 'move';
				e.dataTransfer.setData('text/plain', dragSrcIndex);
			});
			el.addEventListener('dragover', function(e){
				e.preventDefault();
				el.classList.add('drag-over');
			});
			el.addEventListener('dragleave', function(){
				el.classList.remove('drag-over');
			});
			el.addEventListener('drop', function(e){
				e.preventDefault();
				el.classList.remove('drag-over');
				var targetIdx = parseInt(el.getAttribute('data-index'));
				var src = dragSrcIndex;
				if (isNaN(src) || isNaN(targetIdx) || src === targetIdx) return;
				var item = blocks[src];
				blocks.splice(src, 1);
				blocks.splice(targetIdx, 0, item);
				renderBlocksEditor();
				syncBlocksToHtml();
			});
		});
	}

	// Minimal styles for buttons used by blocks (preview only)
	document.addEventListener('DOMContentLoaded', function() {
		var css = '.btn-primary{display:inline-block;background:#FF4F4F;color:#fff;padding:10px 14px;border-radius:8px;text-decoration:none} .btn-primary:hover{background:#FF6347} .btn-secondary{display:inline-block;background:#f3f4f6;color:#111827;padding:10px 14px;border-radius:8px;text-decoration:none;border:1px solid #e5e7eb} .btn-secondary:hover{background:#e5e7eb} .btn-link{color:#FF4F4F;text-decoration:underline}';
		var style = document.createElement('style');
		style.innerHTML = css;
		document.head.appendChild(style);
	});

	function togglePreviewBackground() {
		previewDark = !previewDark;
		var body = document.getElementById('preview-body');
		var modalBody = document.getElementById('preview-modal-body');
		var bg = previewDark ? '#0f172a' : '#ffffff';
		var color = previewDark ? '#f8fafc' : '#111827';
		if (body) {
			body.style.background = bg;
			body.style.color = color;
		}
		if (modalBody) {
			modalBody.style.background = bg;
			modalBody.style.color = color;
		}
	}

	function exportStaticPage() {
		if (!currentPageId) {
			alert('Please save this page first.');
			return;
		}
		var formData = new FormData();
		formData.append('csrf_token', csrfToken);
		formData.append('page_id', currentPageId);
		formData.append('action', 'export_static');
		fetch(window.location.href, {
			method: 'POST',
			body: formData
		})
		.then(function(res) { return res.json(); })
		.then(function(data) {
			if (!data.success) {
				alert(data.message || 'Export failed.');
				return;
			}
			var blob = new Blob([data.html], { type: 'text/html' });
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url;
			a.download = (data.slug || 'page') + '.html';
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
		})
		.catch(function() {
			alert('Export failed. Please try again.');
		});
	}
	</script>
</body>
</html>


