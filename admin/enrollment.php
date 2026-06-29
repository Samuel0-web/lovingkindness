<?php
$pageTitle = "Enrollment Management";
require_once __DIR__ . '/incs/header.php';

$adminId = $_SESSION['admin_id'] ?? null;
if (!$adminId) {
    header("Location: login");
    exit;
}

$search = trim($_GET['search'] ?? '');
?>

<link rel="stylesheet" href="assets/css/enrollment.css?<?= filemtime('assets/css/enrollment.css') ?>">

<div class="enrollmentManager">
    <div class="managerContainer">
        <div class="controlDock" id="controlDock">
            <div class="dockSearch">
                <i class="fas fa-search"></i>
                <input type="text" class="formInput" id="searchInput" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
                <kbd class="searchShortcut">&#8984;K</kbd>
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

        <div class="bulkActionBar" id="bulkActionBar">
            <span class="bulkActionBar__count" id="bulkCount"><i class="fas fa-check-circle"></i> 0 selected</span>
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

        <div class="enrollmentGrid">
            <div class="emptyState">
                <svg class="loadingSpinner" viewBox="25 25 50 50">
                    <circle class="path" cx="50" cy="50" r="20"></circle>
                </svg>
            </div>
        </div>

        <div class="paginationBar--ajax" id="paginationBar"></div>
    </div>
</div>

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