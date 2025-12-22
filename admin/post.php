<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();

// Handle AJAX search request
if (isset($_GET['search']) && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        $searchTerm = trim($_GET['search']);
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        if (empty($searchTerm)) {
            echo json_encode(['posts' => [], 'total' => 0, 'totalPages' => 0, 'currentPage' => 1]);
            exit;
        }
        
        $searchPattern = '%' . $searchTerm . '%';
        
        // Get total count
        $countStmt = $db->prepare("
            SELECT COUNT(*) 
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.title LIKE :search1 
               OR p.content LIKE :search2
               OR u.username LIKE :search3
               OR c.name LIKE :search4
               OR p.status LIKE :search5
        ");
        $countStmt->execute([
            'search1' => $searchPattern,
            'search2' => $searchPattern,
            'search3' => $searchPattern,
            'search4' => $searchPattern,
            'search5' => $searchPattern
        ]);
        $totalPosts = $countStmt->fetchColumn();
        $totalPages = ceil($totalPosts / $perPage);
        
        // Get paginated posts
        $stmt = $db->prepare("
            SELECT p.*, u.username as author, c.name as category_name
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.title LIKE :search1 
               OR p.content LIKE :search2
               OR u.username LIKE :search3
               OR c.name LIKE :search4
               OR p.status LIKE :search5
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':search1', $searchPattern, PDO::PARAM_STR);
        $stmt->bindValue(':search2', $searchPattern, PDO::PARAM_STR);
        $stmt->bindValue(':search3', $searchPattern, PDO::PARAM_STR);
        $stmt->bindValue(':search4', $searchPattern, PDO::PARAM_STR);
        $stmt->bindValue(':search5', $searchPattern, PDO::PARAM_STR);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'posts' => $posts,
            'total' => (int)$totalPosts,
            'totalPages' => (int)$totalPages,
            'currentPage' => (int)$page
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total count
$totalPosts = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);

// Get posts
$posts = $db->query("
    SELECT p.*, u.username as author, c.name as category_name
    FROM posts p
    LEFT JOIN users u ON p.author_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - MiHi CMS</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>All Posts</h1>
            <a href="create.php" class="btn btn-primary">Create New Post</a>
        </div>
        
        <div style="margin-bottom: 20px;">
            <input type="text" id="searchBox" placeholder="Search posts by title, category, author, or status..." 
                   style="width: 100%; max-width: 500px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                   autocomplete="off">
        </div>
        
        <table class="data-table" id="postsTable">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                <tr>
                    <td><?php echo escape($post['title']); ?></td>
                    <td><?php echo escape($post['category_name'] ?? 'Uncategorized'); ?></td>
                    <td><?php echo escape($post['author']); ?></td>
                    <td><span class="badge badge-<?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span></td>
                    <td><?php echo $post['views']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                        <a href="delete.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination" id="paginationContainer">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        (function() {
            const searchBox = document.getElementById('searchBox');
            const table = document.getElementById('postsTable');
            const tbody = table.querySelector('tbody');
            const originalTbody = tbody.innerHTML;
            const pagination = document.getElementById('paginationContainer');
            const originalPagination = pagination ? pagination.outerHTML : '';
            let searchTimeout;
            let currentSearchTerm = '';
            let currentSearchPage = 1;
            let searchPaginationData = null;
            
            if (!searchBox || !table) return;
            
            function renderPagination(totalPages, currentPage, searchTerm) {
                if (!pagination) return;
                
                if (totalPages <= 1) {
                    pagination.style.display = 'none';
                    return;
                }
                
                pagination.style.display = '';
                let paginationHTML = '';
                
                for (let i = 1; i <= totalPages; i++) {
                    const activeClass = i === currentPage ? 'active' : '';
                    paginationHTML += `<a href="#" class="${activeClass}" data-page="${i}">${i}</a>`;
                }
                
                pagination.innerHTML = paginationHTML;
                
                // Add click handlers
                pagination.querySelectorAll('a').forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const page = parseInt(this.getAttribute('data-page'));
                        if (page !== currentPage) {
                            currentSearchPage = page;
                            performSearch(searchTerm, page);
                        }
                    });
                });
            }
            
            function renderPosts(posts, paginationInfo) {
                if (posts.length === 0) {
                    tbody.innerHTML = '<tr class="no-results"><td colspan="7" style="text-align: center; padding: 20px; color: #999;">No posts found matching your search.</td></tr>';
                    if (pagination) pagination.style.display = 'none';
                    return;
                }
                
                tbody.innerHTML = posts.map(function(post) {
                    const categoryName = post.category_name || 'Uncategorized';
                    const createdDate = new Date(post.created_at).toLocaleDateString('en-US', { 
                        month: 'short', 
                        day: 'numeric', 
                        year: 'numeric' 
                    });
                    const statusCapitalized = post.status.charAt(0).toUpperCase() + post.status.slice(1);
                    
                    return `
                        <tr>
                            <td>${escapeHtml(post.title)}</td>
                            <td>${escapeHtml(categoryName)}</td>
                            <td>${escapeHtml(post.author || 'Unknown')}</td>
                            <td><span class="badge badge-${post.status}">${statusCapitalized}</span></td>
                            <td>${post.views || 0}</td>
                            <td>${createdDate}</td>
                            <td>
                                <a href="edit.php?id=${post.id}" class="btn btn-sm btn-edit">Edit</a>
                                <a href="delete.php?id=${post.id}" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    `;
                }).join('');
                
                // Render pagination if we have pagination info
                if (paginationInfo && paginationInfo.totalPages > 0) {
                    renderPagination(paginationInfo.totalPages, paginationInfo.currentPage, currentSearchTerm);
                } else if (pagination) {
                    pagination.style.display = 'none';
                }
            }
            
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            function performSearch(searchTerm, page = 1) {
                if (!searchTerm.trim()) {
                    // Restore original content if search is cleared
                    currentSearchTerm = '';
                    currentSearchPage = 1;
                    tbody.innerHTML = originalTbody;
                    if (pagination && originalPagination) {
                        pagination.outerHTML = originalPagination;
                        // Re-get reference after restore
                        const newPagination = document.getElementById('paginationContainer');
                        if (newPagination) newPagination.style.display = '';
                    }
                    return;
                }
                
                currentSearchTerm = searchTerm;
                currentSearchPage = page;
                
                // Show loading state
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #999;">Searching...</td></tr>';
                if (pagination) pagination.style.display = 'none';
                
                fetch(`?search=${encodeURIComponent(searchTerm)}&page=${page}&ajax=1`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        if (!data.posts) {
                            throw new Error('Invalid response format');
                        }
                        renderPosts(data.posts, {
                            total: data.total || 0,
                            totalPages: data.totalPages || 0,
                            currentPage: data.currentPage || 1
                        });
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #d00;">Error: ' + escapeHtml(error.message || 'Please try again.') + '</td></tr>';
                        if (pagination) pagination.style.display = 'none';
                    });
            }
            
            searchBox.addEventListener('input', function(e) {
                const searchTerm = e.target.value.trim();
                
                // Reset to page 1 when search term changes
                if (searchTerm !== currentSearchTerm) {
                    currentSearchPage = 1;
                }
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                // Debounce search - wait 300ms after user stops typing
                searchTimeout = setTimeout(function() {
                    performSearch(searchTerm, 1);
                }, 300);
            });
            
            // Handle Escape key to clear search
            searchBox.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    searchBox.value = '';
                    performSearch('');
                }
            });
        })();
    </script>
</body>
</html>

