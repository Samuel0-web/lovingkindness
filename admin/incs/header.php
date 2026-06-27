<?php
require __DIR__ . '/../config/db.php';

// 🔐 Protect route
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    header("Location: login");
    exit;
}

// Fetch admin data
$stmt = $pdo->prepare("SELECT name, email, profile_picture, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

$adminName = $admin['name'] ?? 'Administrator';
$adminEmail = $admin['email'] ?? '';
$adminAvatar = $admin['profile_picture'] ?? '';
$adminRole = $admin['role'] ?? 'admin';

// Detect current route for active links
$currentRoute = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (empty($currentRoute) || $currentRoute === 'admin') {
    $currentRoute = 'dashboard';
}

// Get time-based greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good Morning";
    $greetingIcon = "fas fa-sun";
} elseif ($hour < 18) {
    $greeting = "Good Afternoon";
    $greetingIcon = "fas fa-cloud-sun";
} else {
    $greeting = "Good Evening";
    $greetingIcon = "fas fa-moon";
}

$pageTitle = $pageTitle ?? ucfirst($currentRoute);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="dark light">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <title><?= ucfirst($currentRoute) ?> | Admin Panel</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/base.css?<?= filemtime('assets/css/base.css') ?>">
    <link rel="stylesheet" href="assets/css/global.css?<?= filemtime('assets/css/global.css') ?>">
    <link rel="stylesheet" href="assets/css/ui.css?<?= filemtime('assets/css/ui.css') ?>">
</head>
<body>
<div class="admin-layout">

    <!-- SIDEBAR - REDESIGNED -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-inner">
            <div class="sidebar-header">
                <a href="dashboard" class="logo">
                    <span class="logo-wrapper">
                        <img src="../img/logo.jpg" alt="Logo" class="logo-img">
                    </span>
                    <div class="logo-text">
                        <span class="logo-title">Loving Kindness</span>
                        <span class="logo-subtitle">Admin Portal</span>
                    </div>
                </a>
                <button id="collapseBtn" class="collapse-btn">
                    <i class="bi bi-layout-sidebar"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-label">MAIN</div>
                    <a href="dashboard" class="nav-link <?= $currentRoute === 'dashboard' ? 'active' : '' ?>">
                        <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                        <span class="nav-text">Dashboard</span>
                        <?php if ($currentRoute === 'dashboard'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                    <a href="users" class="nav-link <?= $currentRoute === 'users' ? 'active' : '' ?>">
                        <div class="nav-icon"><i class="fas fa-users"></i></div>
                        <span class="nav-text">Users</span>
                        <?php if ($currentRoute === 'users'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                    <a href="enrollments" class="nav-link <?= $currentRoute === 'enrollments' ? 'active' : '' ?>">
                        <div class="nav-icon"><i class="fas fa-graduation-cap"></i></div>
                        <span class="nav-text">Enrollments</span>
                        <span class="nav-badge" id="enrollmentBadge"></span>
                        <?php if ($currentRoute === 'enrollments'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                    <a href="inbox" class="nav-link <?= $currentRoute === 'inbox' ? 'active' : '' ?>">
                        <div class="nav-icon"><i class="fas fa-envelope"></i></div>
                        <span class="nav-text">Messages</span>
                        <span class="nav-badge warning" id="messageBadge"></span>
                        <?php if ($currentRoute === 'inbox'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-label">SYSTEM</div>
                    <a href="settings" class="nav-link <?= $currentRoute === 'settings' ? 'active' : '' ?>">
                        <div class="nav-icon"><i class="fas fa-cog"></i></div>
                        <span class="nav-text">Settings</span>
                        <?php if ($currentRoute === 'settings'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                    <a href="backup" class="nav-link <?= $currentRoute === 'backup' ? 'active' : '' ?>">
                        <div class="nav-icon"><i class="fas fa-database"></i></div>
                        <span class="nav-text">Backup</span>
                        <?php if ($currentRoute === 'backup'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <a href="profile" class="user-card">
                    <div class="user-avatar">
                        <img src="<?= $adminAvatar 
                        ? htmlspecialchars($adminAvatar) . '?t=' . time() 
                        : 'https://ui-avatars.com/api/?background=0f5b3e&color=fff&name=' . urlencode($adminName) ?>" alt="<?= htmlspecialchars($adminName) ?>" class="avatar-img">
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($adminName) ?></div>
                        <div class="user-role">
                            <?=
                                $adminRole === 'owner' ? 'Owner' :
                                ($adminRole === 'admin' ? 'Administrator' : ucfirst($adminRole))
                            ?>
                        </div>
                    </div>
                </a>
                <form method="POST" action="logout" id="logoutForm">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                    >
                    <button class="logout-btn" type="submit">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <header class="admin-header">
            <button id="menuBtn" class="menu-btn">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header-left">
                <div class="breadcrumb">
                    <span class="breadcrumb-item">Admin</span>
                    <i class="fas fa-chevron-right"></i>
                    <span class="breadcrumb-item active"><?= ucfirst($currentRoute) ?></span>
                </div>
                <div class="greeting-section">
                    <i class="<?= $greetingIcon ?> greeting-icon"></i>
                    <div class="greeting-text">
                        <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? ucfirst($currentRoute)); ?></h1>
                        <p class="page-greeting">
                            <span><?= $greeting ?>,</span>
                            <strong><?= htmlspecialchars($adminName) ?>!</strong>
                        </p>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <div class="notification__centre" id="notificationCentre">
                    <div class="notification" id="notificationBell" role="button" tabindex="0" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="badge unreadBadge"></span>
                    </div>

                    <!-- Dropdown panel injected here -->
                    <div class="notification__dropdown" id="notificationDropdown">
                        <!-- Header -->
                        <div class="dropdown__header">
                            <div class="notif__header__left">
                                <h3>Notifications</h3>
                            </div>
                            <button class="mark__read__btn" id="markAllReadBtn">Mark all as read</button>
                        </div>

                        <!-- Scrollable list container -->
                        <div class="notifications__list" id="notificationsList">
                            <!-- Dynamically filled with JS -->
                        </div>

                        <!-- Empty state (hidden by default) -->
                        <div class="empty__state" id="emptyState">
                            <i class="fas fa-bell"></i>
                            <p>No notifications yet</p>
                            <small>New activity will appear here.</small>
                        </div>

                        <!-- Footer -->
                        <div class="dropdown__footer">
                            <button class="view__all__btn" id="viewAllBtn">
                                View All Notifications <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mobile__notification">
                    <a href="notifications" class="notification">
                        <i class="fas fa-bell"></i>
                        <span class="badge unreadBadge">0</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="main-content">