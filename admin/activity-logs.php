<!-- activity.php — Refactored markup with description-first hierarchy -->
<?php
$pageTitle = "Audit Trail";
require_once __DIR__ . "/incs/header.php";
require_once __DIR__ . "/services/ActivityLogService.php";

require_once __DIR__ . "/config/db.php";

$limit = 100;
$totalLogs = ActivityLogService::getTotalLogs($pdo);
$admins = ActivityLogService::getAdmins($pdo);
$actions = ActivityLogService::getActions($pdo);
$groups = ActivityLogService::getGroupedAuditLogs($pdo, $limit, 0);
?>
<link rel="stylesheet" href="assets/css/activity.css?<?= filemtime("assets/css/activity.css") ?>">

<div class="audit">
    <!-- Toolbar -->
    <div class="audit__toolbar">
        <div class="audit__search">
            <i class="fas fa-search audit__search-icon"></i>
            <input type="text" class="audit__search-input" id="js-audit-search"
                placeholder="Search by actor, entity, description, or IP..." autocomplete="off"
            >
            <button type="button" class="audit__search-clear" id="js-search-clear"
                aria-label="Clear search" hidden
            >
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="audit__filters">
            <div class="uiDropdown audit__dropdown">
                <button type="button" class="uiDropdownTrigger" id="js-filter-admin" data-value="">
                    All <i class="fas fa-chevron-down"></i>
                </button>

                <div class="uiDropdownMenu">
                    <button type="button" data-value="">All</button>
                    
                    <?php foreach ($admins as $admin): ?>
                        <button type="button" data-value="<?= htmlspecialchars($admin) ?>">
                            <?= htmlspecialchars($admin) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="uiDropdown audit__dropdown">
                <button type="button" class="uiDropdownTrigger" id="js-filter-action" data-value="">
                    All <i class="fas fa-chevron-down"></i>
                </button>

                <div class="uiDropdownMenu">
                    <button type="button" data-value="">All</button>
                    
                    <?php foreach ($actions as $action): ?>
                        <button type="button" data-value="<?= htmlspecialchars($action) ?>">
                            <?= ucfirst(htmlspecialchars($action)) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="button" class="audit__btn audit__btn--clear" id="js-clear-filters"
                hidden
            >
                Clear
            </button>
            <button class="audit__btn" id="js-audit-export">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Continuous Feed -->
    <div class="audit__panel">
        <?php include __DIR__ . '/partials/activity-items.php'; ?>
    </div>
    <?php if ($totalLogs > $limit): ?>
        <div class="audit__footer">
            <button
                id="js-load-more"
                class="audit__btn audit__btn--load"
                data-offset="<?= $limit ?>"
            >
                Load More
            </button>
        </div>
    <?php endif; ?>
</div>

<script src="assets/js/activity.js?<?= filemtime("assets/js/activity.js") ?>"></script>
<?php require_once __DIR__ . "/incs/footer.php"; ?>