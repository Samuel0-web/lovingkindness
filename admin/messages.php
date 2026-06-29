<?php
$pageTitle = 'Support Inbox';

$adminId = $_SESSION['admin_id'] ?? null;
if (!$adminId) {
    header("Location: login");
    exit;
}

// Get filters
$statusFilter = $_GET['status'] ?? 'all';
$inquiryFilter = $_GET['inquiry'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Build WHERE clause
$where = ["deleted_at IS NULL"];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

if ($inquiryFilter !== 'all') {
    $where[] = "inquiry_type = ?";
    $params[] = $inquiryFilter;
}

if ($search !== '') {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Fetch messages
$stmt = $pdo->prepare("
    SELECT id, full_name, subject, inquiry_type, status, created_at, message FROM contact_messages 
    {$whereClause}
    ORDER BY FIELD(status, 'unread', 'read', 'replied'), created_at DESC
");
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'unread') as unread,
        SUM(status = 'read') as read_count,
        SUM(status = 'replied') as replied,
        SUM(status = 'archived') as archived
    FROM contact_messages
    WHERE deleted_at IS NULL
");
$countStmt->execute();
$counts = $countStmt->fetch(PDO::FETCH_ASSOC);

require_once __DIR__ . '/incs/header.php';
?>

<link rel="stylesheet" href="assets/css/messages.css?<?= filemtime('assets/css/messages.css') ?>">

<div class="inboxContainer">
    <!-- LEFT PANEL - CONVERSATION LIST -->
    <div class="conversationList" id="conversationList">
        <div class="inboxHeader">
            <div class="inboxActions">
                <div class="searchWrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" class="formInput" id="searchInput" placeholder="Search conversations..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filterGroup">
                    <div class="uiDropdown" id="statusFilterDropdown" data-value="<?= $statusFilter ?>">
                        <button class="uiDropdownTrigger">
                            <?= ucfirst($statusFilter === 'all' ? 'All' : $statusFilter) ?>
                            <i class="fas fa-chevron-down"></i>
                        </button>

                        <div class="uiDropdownMenu">
                            <button data-value="all">All</button>
                            <button data-value="unread">Unread</button>
                            <button data-value="read">Read</button>
                            <button data-value="replied">Replied</button>
                            <button data-value="archived">Archived</button>
                        </div>
                    </div>
                    <div class="uiDropdown" id="inquiryFilterDropdown" data-value="<?= htmlspecialchars($inquiryFilter) ?>">
                        <button class="uiDropdownTrigger">
                            <?= $inquiryFilter === 'all' ? 'All types' : ucfirst($inquiryFilter) ?>
                            <i class="fas fa-chevron-down"></i>
                        </button>

                        <div class="uiDropdownMenu">
                            <button data-value="all">All types</button>
                            <button data-value="tutoring">Tutoring</button>
                            <button data-value="teacher-training">Training</button>
                            <button data-value="admissions">Admissions</button>
                            <button data-value="technical">Technical</button>
                            <button data-value="feedback">Feedback</button>
                            <button data-value="general">General</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="conversationStream" id="conversationStream">
            <?php if (empty($messages)): ?>
                <div class="emptyState">
                    <div class="emptyIcon">
                        <i class="fas fa-comments"></i>
                    </div>

                    <h3>No messages yet</h3>
                    <p>Contact form submissions and inquiries will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): 
                    $inquiry = getInquiryTypeDetails($message['inquiry_type']);
                ?>
                <?php
                    // Add this ABOVE the foreach loop:
                    $statusBadgeIcons = [
                        'unread'   => 'fas fa-circle',
                        'read'     => 'fas fa-check',
                        'replied'  => 'fas fa-reply',
                        'archived' => 'fas fa-archive',
                        'spam'     => 'fas fa-ban',
                    ];
                ?>
                    <div class="conversationItem <?= $message['status'] === 'unread' ? 'unread' : '' ?>" data-id="<?= $message['id'] ?>">
                        <div class="conversationAvatar">
                            <div class="avatarInitials">
                                <?= getInitials($message['full_name']) ?>
                            </div>
                        </div>
                        <div class="conversationContent">
                            <div class="conversationHeader">
                                <span class="senderName"><?= htmlspecialchars($message['full_name']) ?></span>
                                <span class="conversationTime"><?= formatConversationDate($message['created_at']) ?></span>
                            </div>
                            <div class="conversationBadgeRow">
                                <span class="inquiryBadge badge-<?= $inquiry['color'] ?>">
                                    <i class="<?= $inquiry['icon'] ?>"></i>
                                    <span><?= $inquiry['label'] ?></span>
                                </span>
                                <span class="statusBadge status-<?= $message['status'] ?>">
                                    <i class="<?= $statusBadgeIcons[$message['status']] ?? 'fas fa-circle' ?>"></i>
                                    <span><?= ucfirst($message['status']) ?></span>
                                </span>
                            </div>
                            <div class="conversationSubject"><?= htmlspecialchars($message['subject'] ?? '') ?></div>
                            <div class="conversationPreview"><?= htmlspecialchars(cleanPreviewText($message['message'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- RIGHT PANEL - CONVERSATION DETAIL -->
    <div class="conversationDetail" id="conversationDetail">
        <div class="detailPlaceholder">
            <i class="fas fa-comment-dots"></i>
            <p>Select a conversation</p>
        </div>
    </div>
</div>

<script>
window.CSRF_TOKEN = "<?= csrf_token() ?>";
</script>
<script src="assets/js/messages.js?<?= filemtime('assets/js/messages.js') ?>"></script>

<?php require __DIR__ . '/incs/footer.php'; ?>