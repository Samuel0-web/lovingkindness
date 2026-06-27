<?php
$pageTitle = "Enrollment Management";
require_once __DIR__ . '/incs/header.php';

$adminId = $_SESSION['admin_id'] ?? null;
if (!$adminId) {
    header("Location: login");
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Filters
$program = $_GET['program'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = [];
$params = [];

if ($program) {
    $where[] = "program = ?";
    $params[] = $program;
}
if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}
if ($search) {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Fetch enrollments
$stmt = $pdo->prepare("
    SELECT * FROM enrollments 
    $whereClause 
    ORDER BY created_at DESC 
    LIMIT " . (int)$limit . "
    OFFSET " . (int)$offset
);
$stmt->execute($params);
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="assets/css/enrollment.css?<?= filemtime('assets/css/enrollment.css') ?>">

<div class="enrollmentManager">
    <div class="managerContainer">

        <!-- Floating Control Dock -->
        <div class="controlDock" id="controlDock">
            <div class="dockSearch">
                <i class="fas fa-search"></i>
                <input type="text" class="formInput" id="searchInput" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
                <kbd class="searchShortcut">⌘K</kbd>
            </div>
            <div class="dockFilters">
                <div class="uiDropdown" id="programDropdown">
                    <button class="uiDropdownTrigger programsFilter" data-value="">
                        All Programs
                        <i class="fas fa-chevron-down"></i>
                    </button>

                    <div class="uiDropdownMenu">
                        <button data-value="">All Programs</button>
                        <button data-value="tutoring">Tutoring</button>
                        <button data-value="teacher_training">Teacher Training</button>
                    </div>
                </div>
                <div class="uiDropdown" id="statusDropdown">
                    <button class="uiDropdownTrigger" data-value="">
                        All Statuses
                        <i class="fas fa-chevron-down"></i>
                    </button>

                    <div class="uiDropdownMenu">
                        <button data-value="">All Statuses</button>
                        <button data-value="pending">Pending</button>
                        <button data-value="contacted">Contacted</button>
                        <button data-value="consultation_booked">Consultation</button>
                        <button data-value="enrolled">Enrolled</button>
                        <button data-value="rejected">Rejected</button>
                    </div>
                </div>
                <button class="dockReset" id="resetFiltersBtn">
                    <i class="fas fa-times"></i>
                    <span>Reset</span>
                </button>
            </div>
        </div>

        <!-- Bulk Action Bar -->
        <div class="bulkActionBar" id="bulkActionBar">
            <span class="bulkActionBar__count" id="bulkCount">✓ 0 selected</span>
            <button type="button" class="bulkActionBar__clear" id="bulkClearBtn">Clear Selection</button>
            <button type="button" class="bulkActionBar__clear" id="bulkSelectAllBtn">Select All</button>
            <div class="uiDropdown" id="bulkStatusDropdown">
                <button type="button" class="uiDropdownTrigger bulkActionBar__dropdown" data-value="">
                    Change Status
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="uiDropdownMenu">
                    <button data-value="pending">Pending</button>
                    <button data-value="contacted">Contacted</button>
                    <button data-value="consultation_booked">Consultation</button>
                    <button data-value="enrolled">Enrolled</button>
                    <button data-value="rejected">Rejected</button>
                </div>
            </div>
            <span class="bulkActionBar__spacer"></span>
            <button type="button" class="bulkActionBar__delete" id="bulkDeleteBtn">Delete</button>
        </div>

        <!-- Enrollment Grid -->
        <div class="enrollmentGrid">
            <?php if (empty($enrollments)): ?>
                <div class="emptyState">
                    <div class="emptyIcon">
                        <i class="fas fa-user-graduate"></i>
                    </div>

                    <h3>No enrollments yet</h3>

                    <p>
                        New enrollment submissions will appear here automatically.
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($enrollments as $enrollment): 
                    $initials = strtoupper(substr($enrollment['full_name'], 0, 1));
                    if (strpos($enrollment['full_name'], ' ') !== false) {
                        $parts = explode(' ', $enrollment['full_name']);
                        $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
                    }
                    $createdDate = new DateTime($enrollment['created_at']);
                    $now = new DateTime();
                    $interval = $createdDate->diff($now);
                    $timeAgo = '';
                    if ($interval->days > 7) {
                        $timeAgo = $createdDate->format('M d, Y');
                    } elseif ($interval->days > 0) {
                        $timeAgo = $interval->days . ' days ago';
                    } elseif ($interval->h > 0) {
                        $timeAgo = $interval->h . ' hours ago';
                    } else {
                        $timeAgo = 'Just now';
                    }
                ?>
                <div class="enrollmentCard" data-id="<?= $enrollment['id'] ?>">
                    <label class="bulkCheckbox" data-id="<?= $enrollment['id'] ?>">
                        <input type="checkbox" class="bulkCheckbox__input" data-id="<?= $enrollment['id'] ?>">
                        <span class="bulkCheckbox__visual">
                            <i class="fas fa-check"></i>
                        </span>
                    </label>
                    <div class="cardHeader">
                        <div class="enrolleeInfo">
                            <div class="enrolleeAvatar"><?= $initials ?></div>
                            <div class="enrolleeDetails">
                                <h4><?= htmlspecialchars($enrollment['full_name']) ?></h4>
                                <span class="enrolleeId">#<?= $enrollment['id'] ?></span>
                            </div>
                        </div>
                        <div class="cardActions">
                            <button class="actionIcon viewBtn" data-id="<?= $enrollment['id'] ?>" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="actionIcon deleteBtn" data-id="<?= $enrollment['id'] ?>" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="cardBody">
                        <div class="infoRow">
                            <span class="infoLabel">Program</span>
                            <span class="programBadge <?= $enrollment['program'] == 'tutoring' ? 'tutoring' : 'training' ?>">
                                <?= $enrollment['program'] == 'tutoring' ? 'Tutoring' : 'Teacher Training' ?>
                            </span>
                        </div>
                        <div class="infoRow">
                            <span class="infoLabel">Contact</span>
                            <div class="contactInfo">
                                <span class="contactEmail"><?= htmlspecialchars($enrollment['email']) ?></span>
                                <span class="contactPhone"><?= htmlspecialchars($enrollment['phone']) ?></span>
                            </div>
                        </div>
                        <?php if ($enrollment['program'] == 'tutoring'): ?>
                        <div class="infoRow">
                            <span class="infoLabel">Student</span>
                            <span class="infoValue"><?= htmlspecialchars($enrollment['student_name']) ?> (Grade <?= htmlspecialchars($enrollment['grade']) ?>)</span>
                        </div>
                        <div class="infoRow">
                            <span class="infoLabel">Subject</span>
                            <span class="infoValue"><?= htmlspecialchars($enrollment['subject']) ?></span>
                        </div>
                        <?php else: ?>
                        <div class="infoRow">
                            <span class="infoLabel">Note</span>
                            <span class="infoValue"><?= htmlspecialchars(substr($enrollment['additional_info'] ?? 'No additional info', 0, 60)) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="infoRow">
                            <span class="infoLabel">Schedule</span>
                            <span class="scheduleValue"><?= htmlspecialchars($enrollment['preferred_time']) ?></span>
                        </div>
                    </div>
                    <div class="cardFooter">
                        <div class="statusWrapper">
                            <div class="uiDropdown statusDropdown">
                                <button class="uiDropdownTrigger statusTrigger"
                                    data-id="<?= $enrollment['id'] ?>"
                                    data-current="<?= $enrollment['status'] ?>"
                                    data-value="<?= $enrollment['status'] ?>"
                                >
                                    <?= ucfirst(str_replace('_', ' ', $enrollment['status'])) ?>
                                    <i class="fas fa-chevron-down"></i>
                                </button>

                                <div class="uiDropdownMenu">
                                    <button data-value="pending">Pending</button>
                                    <button data-value="contacted">Contacted</button>
                                    <button data-value="consultation_booked">Consultation</button>
                                    <button data-value="enrolled">Enrolled</button>
                                    <button data-value="rejected">Rejected</button>
                                </div>
                            </div>
                        </div>
                        <div class="cardMeta">
                            <span class="dateChip">
                                <i class="far fa-calendar-alt"></i>
                                <?= $timeAgo ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="paginationBar">
                <div class="paginationInfo">
                    Showing <strong><?= $offset + 1 ?></strong> – <strong><?= min($offset + $limit, $total) ?></strong> of <strong><?= number_format($total) ?></strong>
                </div>
                <div class="paginationControls">
                    <a href="?page=<?= max(1, $page - 1) ?>&program=<?= urlencode($program) ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>" 
                       class="pageBtn <?= $page <= 1 ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <span class="pageIndicator"><?= $page ?> / <?= $totalPages ?></span>
                    <a href="?page=<?= min($totalPages, $page + 1) ?>&program=<?= urlencode($program) ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>" 
                       class="pageBtn <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Premium Modal (Desktop) -->
<div class="premiumModal" id="detailModal">
    <div class="modalBackdrop"></div>
    <div class="modalPanel">
        <div class="modalHandle"></div>
        <div class="modalHead">
            <h3>Enrollment Details</h3>
            <div class="detailNav">
                <button class="detailNavBtn" id="modalPrevBtn" disabled title="Previous">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="detailNavBtn" id="modalNextBtn" disabled title="Next">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="modalCloseBtn" id="closeModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="modalBody" id="modalBody"></div>
        <div class="modalFoot">
            <button class="modalSecondaryBtn" id="closeModalFooterBtn">Close</button>
        </div>
    </div>
</div>

<!-- Bottom Drawer (Mobile/Tablet) -->
<div class="bottomDrawer" id="bottomDrawer">
    <div class="drawerStickyArea">
        <div class="drawerHandle"></div>
        <div class="drawerHeader">
            <h3>Enrollment Details</h3>
            <div class="detailNav">
                <button class="detailNavBtn" id="drawerPrevBtn" disabled title="Previous">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="detailNavBtn" id="drawerNextBtn" disabled title="Next">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="drawerCloseBtn" id="closeDrawerBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="drawerBody" id="drawerBody"></div>
    <div class="drawerFooter">
        <button class="drawerSecondaryBtn" id="closeDrawerFooterBtn">Close</button>
    </div>
</div>

<script>
    window.csrfToken = <?= json_encode(csrf_token()) ?>;
</script>

<script src="assets/js/enrollment.js?<?= filemtime('assets/js/enrollment.js') ?>"></script>

<?php require_once __DIR__ . '/incs/footer.php'; ?>