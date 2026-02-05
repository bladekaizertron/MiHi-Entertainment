<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$message = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    try {
        if ($action === 'create' || $action === 'update') {
            $label = trim($_POST['label'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
            $sort_order = (int)($_POST['sort_order'] ?? 0);
            $is_header = isset($_POST['is_header']) ? 1 : 0;
            
            if (empty($label)) throw new Exception("Label is required.");
            
            if ($action === 'create') {
                $stmt = $db->prepare("INSERT INTO navigation_items (label, url, description, parent_id, sort_order, is_header) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$label, $url, $description, $parent_id, $sort_order, $is_header]);
                $message = 'Item created.';
            } else {
                if ($parent_id == $id) throw new Exception("Item cannot be its own parent.");
                $stmt = $db->prepare("UPDATE navigation_items SET label=?, url=?, description=?, parent_id=?, sort_order=?, is_header=? WHERE id=?");
                $stmt->execute([$label, $url, $description, $parent_id, $sort_order, $is_header, $id]);
                $message = 'Item updated.';
            }
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM navigation_items WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Item and children deleted.';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
    
    if ($action !== '') {
        header("Location: navigation.php?message=" . urlencode($message));
        exit;
    }
}

// Fetch Items
$items = $db->query("SELECT * FROM navigation_items ORDER BY sort_order ASC, label ASC")->fetchAll(PDO::FETCH_ASSOC);

function buildTree(array $elements, $parentId = null) {
    $branch = array();
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildTree($elements, $element['id']);
            if ($children) $element['children'] = $children;
            $branch[] = $element;
        }
    }
    return $branch;
}
$tree = buildTree($items);

function flattenTreeForSelect($tree, $level = 0, &$flat = []) {
    foreach ($tree as $item) {
        $item['_level'] = $level;
        $flat[] = $item;
        if (!empty($item['children'])) {
            flattenTreeForSelect($item['children'], $level + 1, $flat);
        }
    }
    return $flat;
}
$flatList = [];
flattenTreeForSelect($tree, 0, $flatList);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Manager - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .menu-builder {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .menu-tree-root { padding: 0; margin: 0; list-style: none; }
        
        /* Node Container */
        .menu-node {
            border-bottom: 1px solid #f1f5f9;
        }
        
        /* Row Content */
        .menu-row {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: #fff;
            transition: background 0.2s;
        }
        .menu-row:hover { background: #f8fafc; }
        
        /* Root items styling highlight */
        .root-node > .menu-node > .menu-row {
            background: #f1f5f9;
            font-weight: 600;
        }
        
        /* Indent handling via nested lists */
        .menu-children {
            display: none; /* Collapsed by default */
            padding-left: 32px;
            border-left: 2px solid #e2e8f0;
            margin-left: 20px;
        }
        .menu-children.open { display: block; }
        
        /* Toggle Button */
        .toggle-btn {
            width: 24px; height: 24px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 8px;
            color: #64748b;
            transition: all 0.2s;
        }
        .toggle-btn:hover { background: #e2e8f0; color: #0f172a; }
        .toggle-btn.empty { opacity: 0.2; pointer-events: none; }
        .toggle-btn svg { transition: transform 0.2s; }
        .toggle-btn.rotated svg { transform: rotate(90deg); } /* Rotated when open */
        
        .item-label { color: var(--text-primary); margin-right: 12px; }
        .item-url { font-size: 12px; color: #94a3b8; font-weight: 400; flex: 1; }
        .badge-header { background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-size: 10px; text-transform: uppercase; font-weight: 700; margin-left: 8px; }

        .actions { display: flex; gap: 4px; opacity: 0.6; transition: opacity 0.2s; }
        .menu-row:hover .actions { opacity: 1; }
        .btn-icon { padding: 6px; border-radius: 4px; border: none; cursor: pointer; background: transparent; color: #64748b; }
        .btn-icon:hover { background: #e2e8f0; color: var(--primary-color); }

        /* Modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000;
            display: none; align-items: center; justify-content: center;
            backdrop-filter: blur(2px);
        }
        .modal-overlay.active { display: flex; }
        .modal-card {
            background: #fff; width: 100%; max-width: 500px; padding: 32px;
            border-radius: var(--radius-lg); box-shadow: var(--shadow-xl);
            animation: slideUp 0.3s ease-out;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Navigation Manager</h1>
            <button onclick="openModal('create')" class="btn btn-primary">
                <span>+ Add Menu Item</span>
            </button>
        </div>
        
        <?php if (!empty($_GET['message'])): ?>
            <div class="alert alert-info"><?php echo escape($_GET['message']); ?></div>
        <?php endif; ?>

        <div class="menu-builder">
            <div style="padding: 16px; border-bottom: 2px solid var(--border-color); background: var(--light-bg); display:flex; justify-content:space-between; align-items:center;">
                <span style="font-weight: bold; color: var(--text-secondary); text-transform: uppercase; font-size: 12px; letter-spacing: 1px;">Menu Structure</span>
                <button onclick="expandAll()" class="btn-sm btn-secondary" style="font-size:11px;">Expand All</button>
            </div>
            
            <?php 
            function renderTreeNodes($nodes, $level = 0) {
                if (empty($nodes)) return;
                
                // Add class for root nodes for specific styling
                $ulClass = ($level === 0) ? 'menu-tree-root' : 'menu-children';
                
                // Root is visible, children are hidden by default -> handled by 'open' class logic below
                // Actually we just return the list of items. The container UL logic is handled by parent.
                
                foreach ($nodes as $item) {
                    $hasChildren = !empty($item['children']);
                    $isRoot = ($level === 0);
                    
                    echo '<div class="menu-node ' . ($isRoot ? 'root-node' : '') . '">';
                    
                    // ROW
                    echo '<div class="menu-row">';
                        // Toggle Button
                        if ($hasChildren) {
                            // If it's root, maybe expand by default? No, user said "too long to scroll". Collapsed by default.
                            echo '<div class="toggle-btn" onclick="toggleNode(this, '.$item['id'].')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                            </div>';
                        } else {
                            echo '<div class="toggle-btn empty">●</div>';
                        }
                        
                        // Content
                        echo '<span class="item-label">' . escape($item['label']) . '</span>';
                        if ($item['is_header']) echo '<span class="badge-header">Header</span>';
                        if (!empty($item['url'])) echo '<span class="item-url">' . escape($item['url']) . '</span>';
                        
                        // Actions
                        echo '<div class="actions">';
                             echo '<span style="font-size:11px; margin-right:8px; display:flex; align-items:center;">Ord: '.$item['sort_order'].'</span>';
                             echo '<button class="btn-icon" onclick="openModal(\'update\', '.htmlspecialchars(json_encode($item)).')" title="Edit"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>';
                             echo '<button class="btn-icon" style="color:var(--e-color);" onclick="deleteItem('.$item['id'].')" title="Delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>';
                        echo '</div>'; // End Actions
                        
                    echo '</div>'; // End Row
                    
                    // CHILDREN (Hidden by default)
                    if ($hasChildren) {
                        echo '<div id="children-'.$item['id'].'" class="menu-children">';
                        renderTreeNodes($item['children'], $level + 1);
                        echo '</div>';
                    }
                    
                    echo '</div>'; // End Node
                }
            }
            
            if (empty($tree)) {
                echo '<div style="padding: 32px; text-align: center; color: var(--text-secondary);">No menu items found.</div>';
            } else {
                echo '<div class="menu-tree-root">';
                renderTreeNodes($tree);
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Same Modal as before -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-card">
            <h2 id="modalTitle" style="margin-bottom: 24px; font-size: 24px;">Add Menu Item</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="itemId" value="">
                
                <div class="form-group">
                    <label>Label</label>
                    <input type="text" name="label" id="itemLabel" required>
                </div>
                
                <div class="form-group">
                    <label>URL</label>
                    <input type="text" name="url" id="itemUrl">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="itemDescription" style="min-height:60px;"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Parent Item</label>
                        <select name="parent_id" id="itemParent">
                            <option value="">-- Top Level --</option>
                            <?php foreach ($flatList as $opt): ?>
                                <option value="<?php echo $opt['id']; ?>">
                                    <?php echo str_repeat('&nbsp;&nbsp;', $opt['_level']); ?><?php echo escape($opt['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Order</label>
                        <input type="number" name="sort_order" id="itemSort" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_header" id="itemIsHeader">
                        <span>Is Group Header?</span>
                    </label>
                </div>
                
                <div class="form-actions" style="justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script>
        // Toggle Logic
        function toggleNode(btn, id) {
            const children = document.getElementById('children-' + id); // Corrected ID selection
            if(children) {
                children.classList.toggle('open');
                btn.classList.toggle('rotated');
            }
        }
        
        function expandAll() {
            document.querySelectorAll('.menu-children').forEach(el => el.classList.add('open'));
            document.querySelectorAll('.toggle-btn').forEach(el => el.classList.add('rotated'));
        }

        // Modal Logic
        const modal = document.getElementById('editModal');
        
        function openModal(mode, item = null) {
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');
            
            if (mode === 'create') {
                title.textContent = 'Add Menu Item';
                action.value = 'create';
                document.getElementById('itemId').value = '';
                document.getElementById('itemLabel').value = '';
                document.getElementById('itemUrl').value = '';
                document.getElementById('itemDescription').value = '';
                document.getElementById('itemParent').value = '';
                document.getElementById('itemSort').value = '0';
                document.getElementById('itemIsHeader').checked = false;
            } else {
                title.textContent = 'Edit Menu Item';
                action.value = 'update';
                document.getElementById('itemId').value = item.id;
                document.getElementById('itemLabel').value = item.label;
                document.getElementById('itemUrl').value = item.url;
                document.getElementById('itemDescription').value = item.description || '';
                document.getElementById('itemParent').value = item.parent_id || '';
                document.getElementById('itemSort').value = item.sort_order;
                document.getElementById('itemIsHeader').checked = item.is_header == 1; 
            }
            modal.classList.add('active');
        }
        
        function closeModal() {
            modal.classList.remove('active');
        }
        
        function deleteItem(id) {
            if (confirm('Delete this item and all its sub-items?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
