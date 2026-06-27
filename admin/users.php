<?php
$pageTitle = "Manage Users";
require_once __DIR__ . '/incs/header.php';

$currentUserId = $_SESSION['admin_id'];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("
    SELECT id, name, email, profile_picture, last_login, created_at, role
    FROM users
    ORDER BY 
        (id = :currentUserId) DESC,
        created_at ASC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':currentUserId', $currentUserId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$start = $offset + 1;
$end = min($offset + $limit, $totalUsers);

function safe_url($url) {
    if (!$url) return '';

    // allow relative paths (uploads/avatar.jpg)
    if (!preg_match('#^https?://#', $url)) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    // allow valid external URLs
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    return '';
}

function timeAgo($datetime) {
    if (!$datetime) return 'Never';

    $time = time() - strtotime($datetime);

    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time / 60) . ' mins ago';
    if ($time < 86400) return floor($time / 3600) . ' hrs ago';
    if ($time < 604800) return floor($time / 86400) . ' days ago';

    return date('M d, Y \a\t h:i A', strtotime($datetime));
}
?>
<link rel="stylesheet" href="assets/css/users.css?<?= filemtime('assets/css/users.css') ?>">

<div class="um-container">
    <!-- Add User Button & Search -->
    <div class="um-actions-bar">
        <button class="um-btn-add" id="addUserBtn">
            <i class="fas fa-plus"></i>
            Add New User
        </button>
        <div class="um-search">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name or email...">
        </div>
    </div>

    <!-- Empty State (shown only when search has no results) -->
    <div class="um-empty-state" id="umEmptyState">
        <div class="empty-icon">
            <i class="fas fa-search"></i>
        </div>
        <h3 id="emptyStateTitle">No results found</h3>
        <p>Try a different search term.</p>
    </div>

    <!-- Users Table (Desktop) -->
    <div class="um-table-wrapper">
        <table class="um-table">
            <thead>
                <tr>
                    <?php if ($_SESSION['role'] === 'owner'): ?>
                        <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                    <?php else: ?>
                        <th style="width: 40px;"></th>
                    <?php endif; ?>
                    <th>User</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th>Last Login</th>
                    <th style="width: 80px;">Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php foreach ($users as $user): ?>
                <tr data-user-id="<?= $user['id'] ?>">
                    <td>
                        <?php if ($user['id'] != $_SESSION['admin_id'] && $_SESSION['role'] === 'owner'): ?>
                            <input type="checkbox" class="user-checkbox" value="<?= $user['id'] ?>">
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="um-user-cell">
                            <div class="um-avatar">
                                <img src="<?= $user['profile_picture'] 
                                    ? safe_url($user['profile_picture']) . '?t=' . time() 
                                    : 'https://ui-avatars.com/api/?background=0f5b3e&color=fff&name=' . urlencode($user['name']) ?>" alt="<?= htmlspecialchars($user['name']) ?>" class="avatar-img">
                            </div>
                            <span class="um-user-name">
                                <?= htmlspecialchars($user['name']) ?>
                                <?php if ($user['role'] === 'owner'): ?>
                                    <span class="badge-owner">Owner</span>
                                <?php elseif ($user['id'] == $_SESSION['admin_id']): ?>
                                    <span class="badge-you">You</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <?= $user['last_login'] ? timeAgo($user['last_login']) : 'Never' ?>
                    </td>
                    <td>
                        <div class="um-action-btns">
                            <?php if ($user['id'] != $_SESSION['admin_id'] && $_SESSION['role'] === 'owner'): ?>
                                <button class="um-action-btn edit" data-id="<?= $user['id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="um-action-btn delete" data-id="<?= $user['id'] ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php else: ?>
                                <span style="font-size:12px; color:#888;">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Users Cards (Mobile) -->
    <div class="um-cards-container" id="usersCards">
        <?php foreach ($users as $user): ?>
        <div class="um-card" data-user-id="<?= $user['id'] ?>">
            <div class="um-card-checkbox">
                <?php if ($user['id'] != $_SESSION['admin_id'] && $_SESSION['role'] === 'owner'): ?>
                    <input type="checkbox" class="user-checkbox" value="<?= $user['id'] ?>">
                <?php endif; ?>
            </div>
            <div class="um-card-user">
                <div class="um-card-avatar">
                    <img src="<?= $user['profile_picture'] ? safe_url($user['profile_picture']) . '?t=' . time() : 'https://ui-avatars.com/api/?background=0f5b3e&color=fff&name=' . urlencode($user['name']) ?>" alt="<?= htmlspecialchars($user['name']) ?>" class="avatar-img">
                </div>
                <div class="um-card-name">
                    <?= htmlspecialchars($user['name']) ?>
                    <?php if ($user['role'] === 'owner'): ?>
                        <span class="badge-owner">Owner</span>
                    <?php elseif ($user['id'] == $_SESSION['admin_id']): ?>
                        <span class="badge-you">You</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="um-card-details">
                <div class="um-card-detail">
                    <span class="um-detail-label">Email:</span>
                    <span class="um-detail-value"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="um-card-detail">
                    <span class="um-detail-label">Joined:</span>
                    <span class="um-detail-value"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                </div>
                <div class="um-card-detail">
                <span class="um-detail-label">Last Login:</span>
                    <span class="um-detail-value">
                        <?= $user['last_login'] ? timeAgo($user['last_login']) : 'Never' ?>
                    </span>
                </div>
            </div>
            <div class="um-card-actions">
                    <?php if ($user['id'] != $_SESSION['admin_id'] && $_SESSION['role'] === 'owner'): ?>
                    <button class="um-action-btn edit" data-id="<?= $user['id'] ?>">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="um-action-btn delete" data-id="<?= $user['id'] ?>">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                <?php else: ?>
                    <span style="font-size:12px; color:#888;">—</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination - only show if needed -->
    <?php if ($totalUsers > $limit): ?>
    <div class="um-pagination">
        <div class="um-pagination-info">
            Showing <span id="showingStart"><?= $start ?></span> - 
            <span id="showingEnd"><?= $end ?></span> of 
            <span id="totalCount"><?= $totalUsers ?></span> users
        </div>
        <div class="um-pagination-controls">
            <button class="um-page-btn" id="prevPage" disabled>
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="um-page-numbers" id="pageNumbers">
                <button class="um-page-num active">1</button>
            </div>
            <button class="um-page-btn" id="nextPage" disabled>
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bulk Action Bar (only appears when items are selected) -->
    <div class="bulk-action-bar" id="bulkActionBar">
        <div class="selected-info">
            <i class="fas fa-check-circle"></i>
            <span id="selectedCount">0</span> users selected
        </div>
        <button class="bulk-delete-btn" id="bulkDeleteBtn">
            <i class="fas fa-trash-alt"></i>
            Delete Selected
        </button>
        <button class="cancel-bulk-btn" id="cancelBulkBtn">
            <i class="fas fa-times"></i>
            Cancel
        </button>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="um-modal" id="userModal">
    <div class="um-modal-content"></div>
</div>

<!-- Bottom Drawer for Mobile -->
<div class="um-drawer" id="userDrawer">
    <div class="drawerBackdrop" id="drawerBackdrop"></div>
    <div class="drawerContent">
        <div class="drawerStickyArea">
            <div class="drawerHandle"></div>
            <div class="drawerHeader">
                <h3 class="drawerTitle">Add New User</h3>
                <button type="button" class="drawerCloseBtn" id="closeDrawerBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="drawerBody" id="drawerBody">
            <!-- Form content will be injected here by JS -->
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/incs/footer.php'; ?>

<script>
window.APP = {
    currentUserId: <?= $_SESSION['admin_id'] ?>,
    currentUserRole: "<?= $_SESSION['role'] ?>",
    totalUsers: <?= $totalUsers ?>,
    currentPage: <?= $page ?>,
    perPage: <?= $limit ?>,
    csrfToken: "<?= htmlspecialchars(csrf_token()) ?>"
};
</script>

<script src="assets/js/users.js?v=<?= filemtime('assets/js/users.js') ?>"></script>