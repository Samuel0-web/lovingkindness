<?php
$pageTitle = "Notification Centre";
require_once __DIR__ . "/incs/header.php";

$adminId = $_SESSION['admin_id'] ?? null;
if (!$adminId) {
    header("Location: login");
    exit;
}
?>
<link rel="stylesheet" href="assets/css/notification.css?v=<?= filemtime('assets/css/notification.css') ?>">

<div class="nc__container">
    <div class="nc__toolbar">
        <div class="nc__search-row">
            <div class="nc__search">
                <i class="bi bi-search nc__search-icon"></i>
                <input type="text" class="nc__search-input" id="ncSearch" placeholder="Search your inbox..." aria-label="Search notifications">
                <div class="nc__search-shortcut">
                    <i class="bi bi-command"></i> K
                </div>
            </div>

            <div class="uiDropdown nc__group-dropdown">
                <button type="button" class="uiDropdownTrigger nc__group-trigger"
                    id="ncGroupDropdown" data-value=""
                >
                    All Time
                    <i class="bi bi-chevron-down"></i>
                </button>

                <div class="uiDropdownMenu">
                    <button type="button" data-value="all">All Time</button>
                    <button type="button" data-value="today">Today</button>
                    <button type="button" data-value="yesterday">Yesterday</button>
                    <button type="button" data-value="last-7-days">Last 7 Days</button>
                    <button type="button" data-value="earlier-month">Earlier This Month</button>
                    <button type="button" data-value="older">Older</button>
                </div>
            </div>
        </div>

        <!-- Bulk actions -->
        <div class="nc__bulk-actions">
            <button id="ncMarkAllRead" class="nc__bulk-btn nc__bulk-btn--primary">
                <i class="bi bi-envelope-open"></i>
                Mark all read
            </button>

            <button id="ncDeleteAll" class="nc__bulk-btn nc__bulk-btn--danger">
                <i class="bi bi-trash3"></i>
                Delete all
            </button>
        </div>
        
        <div class="nc__filters" id="ncFilters">
            <button class="nc__filter nc__filter--active" data-filter="all">All</button>
            <button class="nc__filter" data-filter="unread">Unread</button>
            <button class="nc__filter" data-filter="enrollments">Enrollments</button>
            <button class="nc__filter" data-filter="messages">Messages</button>
            <button class="nc__filter" data-filter="system">System</button>
        </div>
    </div>

    <div class="nc__feed" id="ncFeed"></div>

    <div class="nc__empty" id="ncEmpty" hidden>
        <div class="nc__empty-icon">
            <i class="bi bi-inbox"></i>
        </div>
        <h3 class="nc__empty-title" id="ncEmptyTitle">You're all caught up</h3>
        <p class="nc__empty-text" id="ncEmptyText">Your inbox is beautifully clean. Kick back, relax, or check out the latest course metrics.</p>
    </div>
</div>

<script>
window.csrfToken = <?= json_encode($_SESSION['csrf_token']) ?>;
</script>
<script src="assets/js/notification.js?<?= filemtime('assets/js/notification.js') ?>"></script>
<?php require_once __DIR__ . "/incs/footer.php" ?>