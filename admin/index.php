<?php
// index.php - Dashboard Main Content
$pageTitle = "Dashboard";
require_once __DIR__ . '/incs/header.php';

$adminId = $_SESSION['admin_id'] ?? null;
if (!$adminId) {
    header("Location: login");
    exit;
}
require_once __DIR__ . '/services/DashboardService.php';
require_once __DIR__ . '/services/ActivityLogService.php';

$db = $pdo;

$stats = DashboardService::getStats($db);

$dashboard = [
    'summary' => 'Here is your latest platform overview.',
    'stats' => $stats,
    'charts' => [],
    'recentEnrollments' => DashboardService::getRecentEnrollments($db),
    'recentMessages' => DashboardService::getRecentMessages($db, 7),
    'activities' => []
];

$statusPercentages = DashboardService::getStatusPercentages($stats);
$monthly = DashboardService::getMonthlyEnrollmentData($db);
$programs = DashboardService::getProgramDistribution($db);
$weeklyGrowth = DashboardService::getWeeklyGrowth($db);
$topCountry = DashboardService::getTopCountry($db);
$countriesReached = DashboardService::getCountriesReached($db);
$rawActivities = ActivityLogService::getRecentActivities($db, 5);

$dashboard['activities'] = array_map(
    fn($activity) => [
        'type' => $activity['action'],
        'action' => $activity['admin_name'] . ': ' . $activity['description'],
        'time' => $activity['created_at']
    ],
    $rawActivities
);

$dashboard['charts'] = [
    'monthlyLabels' => $monthly['labels'],
    'monthlyCounts' => $monthly['counts'],
    'programLabels' => $programs['labels'],
    'programCounts' => $programs['counts']
];

$dashboard['metrics'] = [
    'weeklyGrowth' => $weeklyGrowth,
    'topCountry' => $topCountry,
    'countriesReached' => $countriesReached,
];

$inquiryTypes = [
    'tutoring' => [
        'icon' => 'fas fa-graduation-cap',
        'label' => 'Tutoring',
        'color' => 'blue'
    ],
    'teacher-training' => [
        'icon' => 'fas fa-chalkboard-teacher',
        'label' => 'Training',
        'color' => 'indigo'
    ],
    'admissions' => [
        'icon' => 'fas fa-door-open',
        'label' => 'Admissions',
        'color' => 'green'
    ],
    'technical' => [
        'icon' => 'fas fa-terminal',
        'label' => 'Technical',
        'color' => 'purple'
    ],
    'feedback' => [
        'icon' => 'fas fa-star',
        'label' => 'Feedback',
        'color' => 'orange'
    ],
    'general' => [
        'icon' => 'fas fa-comment',
        'label' => 'General',
        'color' => 'gray'
    ]
];
?>
<link rel="stylesheet" href="assets/css/index.css?<?= filemtime('assets/css/index.css') ?>">


<div class="dashboard-content">
    
    <!-- SECTION 1: Dashboard Hero -->
    <div class="hero-section">
        <div class="hero-text">
            <h1>Welcome back, <?= htmlspecialchars($adminName) ?></h1>
            <p class="hero-date" id="currentDate"></p>
            <p class="hero-summary"><?= htmlspecialchars($dashboard['summary']) ?></p>
        </div>
        <div class="hero-actions">
            <button class="action-btn secondary" id="refreshDashboardBtn">
                <i class="fas fa-sync-alt"></i>
                Refresh
            </button>
        </div>
    </div>

    <!-- SECTION 2: Statistics Grid (KPI Cards) -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Users</span>
                <span class="stat-value" data-key="totalUsers" data-target="<?= htmlspecialchars($dashboard['stats']['totalUsers']) ?>">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-inbox"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Inquiries</span>
                <span class="stat-value" data-key="totalMessages" data-target="<?= htmlspecialchars($dashboard['stats']['totalMessages']) ?>">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-info">
                <span class="stat-label">New Enrollment (30d)</span>
                <span class="stat-value" data-key="newEnrollmentsThisMonth" data-target="<?= htmlspecialchars($dashboard['stats']['newEnrollmentsThisMonth']) ?>">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Enrollments</span>
                <span class="stat-value" data-key="totalEnrollments" data-target="<?= htmlspecialchars($dashboard['stats']['totalEnrollments']) ?>">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <span class="stat-label">Enrollment Success Rate</span>
                <span class="stat-value" data-key="conversionRate" data-target="<?= htmlspecialchars($dashboard['stats']['conversionRate']) ?>">0</span>
                <span class="stat-unit">%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-envelope"></i></div>
            <div class="stat-info">
                <span class="stat-label">Unread Messages</span>
                <span class="stat-value" data-key="unreadMessages" data-target="<?= htmlspecialchars($dashboard['stats']['unreadMessages']) ?>">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <span class="stat-label">Weekly Growth</span>
                <?php $growth = $dashboard['metrics']['weeklyGrowth']['growth']; ?>

                <span class="stat-value">
                    <?= htmlspecialchars($growth) ?>%
                    <?php if ($growth > 0): ?>
                        <i class="fas fa-arrow-up" style="color: var(--brand-green);"></i>
                    <?php elseif ($growth < 0): ?>
                        <i class="fas fa-arrow-down" style="color: #c30000;"></i>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-globe"></i></div>
            <div class="stat-info">
                <span class="stat-label">Top Country</span>
                <span class="stat-value">
                    <?= htmlspecialchars($dashboard['metrics']['topCountry']['country']) ?>
                </span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-earth-europe"></i></div>
            <div class="stat-info">
                <span class="stat-label">Countries Reached</span>
                <span class="stat-value" data-key="countriesReached" data-target="<?= htmlspecialchars($dashboard['metrics']['countriesReached']) ?>">
                    0
                </span>
            </div>
        </div>
    </div>

    <!-- SECTION 3: Enrollment Analytics (Charts) -->
    <div class="analytics-row">
        <div class="chart-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-line"></i> Enrollment Trends</h3>
                <span class="subtitle">Monthly growth overview</span>
            </div>
            <canvas id="trendChart"></canvas>
        </div>
        <div class="chart-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Program Distribution</h3>
                <span class="subtitle">Tutoring vs Teacher Training</span>
            </div>
            <canvas id="distributionChart"></canvas>
        </div>
    </div>

    <!-- SECTION 4: Enrollment Status Breakdown -->
    <div class="status-breakdown">
        <div class="section-title">
            <h2><i class="fas fa-chart-simple"></i> Enrollment Status Overview</h2>
            <span class="badge">Live</span>
        </div>
        <div class="status-cards">
            <div class="status-item pending">
                <div class="status-header">
                    <span><i class="fas fa-clock"></i> Pending</span>
                    <span class="count"><?= number_format($dashboard['stats']['pendingEnrollments']) ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress" data-width="<?= $statusPercentages['pending'] ?>"></div>
                </div>
            </div>
            <div class="status-item contacted">
                <div class="status-header">
                    <span><i class="fas fa-phone-alt"></i> Contacted</span>
                    <span class="count"><?= number_format($dashboard['stats']['contactedEnrollments']) ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress" data-width="<?= $statusPercentages['contacted'] ?>"></div>
                </div>
            </div>
            <div class="status-item booked">
                <div class="status-header">
                    <span><i class="fas fa-calendar-check"></i> Consultation Booked</span>
                    <span class="count"><?= number_format($dashboard['stats']['consultationBooked']) ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress" data-width="<?= $statusPercentages['consultation_booked'] ?>"></div>
                </div>
            </div>
            <div class="status-item enrolled">
                <div class="status-header">
                    <span><i class="fas fa-check-circle"></i> Enrolled</span>
                    <span class="count"><?= number_format($dashboard['stats']['enrolledStudents']) ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress" data-width="<?= $statusPercentages['enrolled'] ?>"></div>
                </div>
            </div>
            <div class="status-item rejected">
                <div class="status-header">
                    <span><i class="fas fa-times-circle"></i> Rejected</span>
                    <span class="count"><?= number_format($dashboard['stats']['rejectedEnrollments']) ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress" data-width="<?= $statusPercentages['rejected'] ?>"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 5: Message Overview -->
    <div class="message-overview">
        <h2><i class="fas fa-inbox"></i> Message Center Summary</h2>
        <div class="message-stats">
            <div class="msg-card unread">
                <i class="fas fa-envelope-open-text"></i>
                <span>Unread</span>
                <strong data-target="<?= $dashboard['stats']['unreadMessages'] ?>">0</strong>
            </div>
            <div class="msg-card replied">
                <i class="fas fa-reply-all"></i>
                <span>Replied</span>
                <strong data-target="<?= $dashboard['stats']['repliedMessages'] ?>">0</strong>
            </div>
        </div>
    </div>

    <!-- SECTION 6: Recent Enrollments -->
    <div class="recent-section">
        <div class="section-header">
            <h2><i class="fas fa-table-list"></i> Recent Enrollments</h2>
        </div>
        
        <?php if (empty($dashboard['recentEnrollments'])): ?>
        <div class="empty-state">
            <i class="fas fa-clipboard-list empty-icon"></i>
            <h3>No enrollments yet</h3>
            <p>When students submit enrollment requests, they will appear here.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="modern-table" id="enrollmentsTable">
                <thead>
                    <tr><th>Name</th><th>Program</th><th>Country</th><th>Status</th><th>Date</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($dashboard['recentEnrollments'] as $enr): ?>
                    <?php 
                        $status = preg_replace('/[^a-z0-9_-]/i', '', $enr['status']);
                        $programKey = strtolower(str_replace('-', '_', trim($enr['program'])));

                        $programIcons = [
                            'tutoring' => 'fa-chalkboard-user',
                            'teacher_training' => 'fa-graduation-cap'
                        ];

                        $programIcon = $programIcons[$programKey] ?? 'fa-book';
                    ?>
                    <tr class="dashboardEnrollmentRow" data-id="<?= $enr['id'] ?>"data-search="<?= htmlspecialchars(strtolower($enr['full_name'] . ' ' . $enr['country'] . ' ' . $enr['program'])) ?>">
                        <td data-label="Name"><i class="fas fa-user"></i> <?= htmlspecialchars($enr['full_name']) ?></td>
                        <td data-label="Program">
                            <span class="program-badge">
                                <i class="fas <?= $programIcon ?>"></i>
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $enr['program']))) ?>
                            </span>
                        </td>
                        <td data-label="Country"><i class="fas fa-globe"></i> <?= htmlspecialchars($enr['country']) ?></td>
                        <td data-label="Status">
                            <span class="status-badge <?= htmlspecialchars($status) ?>">
                                <?php
                                $statusIcons = [
                                    'pending' => 'fa-clock',
                                    'contacted' => 'fa-phone-alt',
                                    'consultation_booked' => 'fa-calendar-check',
                                    'enrolled' => 'fa-check-circle',
                                    'rejected' => 'fa-times-circle'
                                ];
                                $icon = $statusIcons[$status] ?? 'fa-circle';
                                ?>
                                <i class="fas <?= $icon ?>"></i>
                                <?= ucwords(str_replace('_', ' ', $status)) ?>
                            </span>
                        </td>
                        <td data-label="Date"><i class="fas fa-calendar-alt"></i> <?= date('M d, Y', strtotime($enr['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- SECTION 7 & 8: Recent Messages + Activity Timeline -->
    <div class="dual-layout">
        <div class="messages-feed">
            <h2><i class="fas fa-message"></i> Recent Messages</h2>
            <?php if (empty($dashboard['recentMessages'])): ?>
            <div class="empty-state small">
                <i class="fas fa-inbox empty-icon"></i>
                <p>No messages yet</p>
            </div>
            <?php else: ?>
            <div class="feed-list">
                <?php foreach ($dashboard['recentMessages'] as $msg): ?>
                    <?php
                        $status = preg_replace('/[^a-z0-9_-]/i', '', $msg['status'] ?? 'read');
                        $inquiryType = trim(strtolower($msg['inquiry_type'] ?? 'general'));
                        $inquiry = $inquiryTypes[$inquiryType] ?? $inquiryTypes['general'];
                    ?>
                    <div class="feed-item <?= $status === 'unread' ? 'unread-highlight' : '' ?>" data-message-id="<?= $msg['id'] ?>">
                        <div class="feed-avatar"><?= isset($msg['full_name']) ? getInitials($msg['full_name']) : 'U' ?></div>
                        <div class="feed-content">
                            <div class="feed-name">
                                <?= htmlspecialchars($msg['full_name'] ?? 'Unknown') ?>
                                <span class="feed-type feed-type-<?= $inquiry['color'] ?>">
                                    <i class="<?= $inquiry['icon'] ?>"></i>
                                    <?= htmlspecialchars($inquiry['label']) ?>
                                </span>
                            </div>
                            <div class="feed-subject"><?= htmlspecialchars($msg['subject'] ?? 'No subject') ?></div>
                            <div class="feed-meta">
                                <i class="far fa-clock"></i> <?= date('M d, H:i', strtotime($msg['created_at'] ?? 'now')) ?>
                                <span class="feed-status-dot <?= htmlspecialchars($status) ?>"></span>
                                <?= ucfirst(htmlspecialchars($status)) ?>
                            </div>
                        </div>
                        <a class="feed-action-link"><i class="fas fa-chevron-right"></i></a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="activity-timeline">
            <div class="timeline-head">
                <h2><i class="fas fa-history"></i> Admin Activity</h2>
                <a href="activity" class="timeline-view">View all <i class="fas fa-chevron-right"></i></a>
            </div>
            <?php if (empty($dashboard['activities'])): ?>
            <div class="empty-state small">
                <i class="fas fa-history empty-icon"></i>
                <p>No recent activity</p>
            </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($dashboard['activities'] as $activity): ?>
                        <?php $type = preg_replace('/[^a-z0-9_-]/i', '', $activity['type'] ?? 'enrollment'); ?>
                        <div class="timeline-node">
                            <div class="timeline-icon <?= htmlspecialchars($type) ?>">
                                <?php
                                    $typeIcons = [
                                        'create'      => 'fa-user-plus',
                                        'update'      => 'fa-user-pen',
                                        'delete'      => 'fa-user-minus',

                                        'enrollment'  => 'fa-user-graduate',
                                        'message'     => 'fa-envelope',
                                        'status'      => 'fa-exchange-alt',
                                    ];
                                ?>
                                <i class="fas <?= $typeIcons[$type] ?? 'fa-bell' ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-bubble">
                                    <p class="timeline-action"><?= $activity['action'] ?? 'Activity recorded' ?></p>
                                    <span class="timeline-time">
                                        <i class="far fa-clock"></i> 
                                        <?= htmlspecialchars($activity['time'] ?? date('Y-m-d H:i:s')) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 9: Quick Actions -->
    <div class="quick-actions-grid">
        <a href="enrollments" class="quick-card">
            <div class="quick-icon"><i class="fas fa-clipboard-list"></i></div>
            <h4>View Enrollments</h4>
            <p>Manage all enrollment requests</p>
        </a>
        <a href="inbox" class="quick-card">
            <div class="quick-icon"><i class="fas fa-inbox"></i></div>
            <h4>Open Inbox</h4>
            <p>Respond to new inquiries</p>
        </a>
        <a href="users" class="quick-card">
            <div class="quick-icon"><i class="fas fa-user-cog"></i></div>
            <h4>Manage Admins</h4>
            <p>Add or remove admin users</p>
        </a>
        <a href="reports" class="quick-card">
            <div class="quick-icon"><i class="fas fa-download"></i></div>
            <h4>Export Reports</h4>
            <p>Download analytics data</p>
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
window.dashboardData = {
    monthlyEnrollments: <?= json_encode($dashboard['charts']['monthlyCounts'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    monthlyLabels: <?= json_encode($dashboard['charts']['monthlyLabels'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    programDistribution: <?= json_encode($dashboard['charts']['programCounts'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    programLabels: <?= json_encode($dashboard['charts']['programLabels'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    statusPercentages: {
        pending: <?= $statusPercentages['pending'] ?>,
        contacted: <?= $statusPercentages['contacted'] ?>,
        consultation_booked: <?= $statusPercentages['consultation_booked'] ?>,
        enrolled: <?= $statusPercentages['enrolled'] ?>,
        rejected: <?= $statusPercentages['rejected'] ?>
    }
};
</script>
<script src="assets/js/index.js?<?= filemtime('assets/js/index.js') ?>"></script>
<?php include __DIR__ . '/incs/footer.php'; ?>