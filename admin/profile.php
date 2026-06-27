<?php
$pageTitle = "My Profile";
require_once __DIR__ . '/incs/header.php';

$adminId = $_SESSION['admin_id'] ?? null;
if (!$adminId) {
    header("Location: login");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header("Location: logout");
    exit;
}

$fullName  = $admin['name'] ?? '';
$email     = $admin['email'] ?? '';
$avatar    = $admin['profile_picture'] ?? '';
$createdAt = $admin['created_at'] ?? date('Y-m-d H:i:s');
$lastLogin = $admin['last_login'] ?? $admin['last_active'] ?? date('Y-m-d H:i:s');
$memberSince = date('F Y', strtotime($createdAt));
$initials = strtoupper(substr($fullName, 0, 1));
if (strpos($fullName, ' ') !== false) {
    $parts = explode(' ', $fullName);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
}
?>

<link rel="stylesheet" href="assets/css/profile.css?<?= filemtime('assets/css/profile.css') ?>">

<div class="profileAppRoot">
    <!-- DESKTOP LAYOUT: Sidebar + Main Content -->
    <div class="profileDesktopLayout">
        <!-- Sidebar -->
        <aside class="profileSidebar">
            <div class="profileSidebarUser">
                <div class="profileAvatarWrapper" id="desktopAvatarBtn">
                    <div class="profileAvatarLarge">
                        <?php if ($avatar): ?>
                            <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="avatar">
                        <?php else: ?>
                            <span><?= $initials ?></span>
                        <?php endif; ?>
                    </div>
                    <button class="profileAvatarEditTrigger" type="button">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <h2><?= htmlspecialchars($fullName) ?></h2>
                <p class="profileSidebarEmail"><?= htmlspecialchars($email) ?></p>
                <div class="profileSidebarBadge">
                    <i class="far fa-calendar-alt"></i>
                    <span>Joined <?= $memberSince ?></span>
                </div>
            </div>
            <nav class="profileNavList">
                <a href="#" class="profileNavItem active" data-section="profile">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="#" class="profileNavItem" data-section="security">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security</span>
                </a>
                <a href="#" class="profileNavItem" data-section="activity">
                    <i class="fas fa-chart-line"></i>
                    <span>Activity</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="profileMainContent">
            <!-- Profile Section -->
            <div class="profileSettingsSection active" id="section-profile">
                <div class="profileSectionHeader">
                    <h1>Profile Information</h1>
                    <p>Update your personal details</p>
                </div>
                <div class="profileSettingsGroup">
                    <div class="profileSettingRow">
                        <div class="profileSettingLabel">
                            <label>Full Name</label>
                        </div>
                        <div class="profileSettingValue">
                            <span class="display-name"><?= htmlspecialchars($fullName) ?></span>
                            <button class="profileInlineEditBtn edit-name-btn">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            <div class="profileInlineEditForm" style="display:none;">
                                <input type="text" class="name-input" value="<?= htmlspecialchars($fullName) ?>">
                                <div class="profileInlineActions">
                                    <button class="save-name-btn profileBtnPrimarySmall">Save</button>
                                    <button class="cancel-edit-btn profileBtnSecondarySmall">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="profileSettingRow">
                        <div class="profileSettingLabel">
                            <label>Email Address</label>
                        </div>
                        <div class="profileSettingValue">
                            <span class="display-email"><?= htmlspecialchars($email) ?></span>
                            <button class="profileInlineEditBtn edit-email-btn">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            <div class="profileInlineEditForm" style="display:none;">
                                <input type="email" class="email-input" value="<?= htmlspecialchars($email) ?>">
                                <div class="profileInlineActions">
                                    <button class="save-email-btn profileBtnPrimarySmall">Save</button>
                                    <button class="cancel-edit-btn profileBtnSecondarySmall">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Section -->
            <div class="profileSettingsSection" id="section-security">
                <div class="profileSectionHeader">
                    <h1>Security</h1>
                    <p>Manage your password and security settings</p>
                </div>
                <div class="profileSettingsGroup">
                    <div class="profileSettingRow vertical">
                        <div class="profileSettingLabel">
                            <label>Change Password</label>
                            <span class="profileSettingHint">Choose a strong password</span>
                        </div>
                        <div class="profileSettingValueFull">
                            <div class="profilePasswordForm">
                                <div class="profilePasswordField">
                                    <input class="formInput" type="password"
                                        id="deskCurrentPwd"
                                        name="current_pwd_secure"
                                        placeholder="Current password"
                                        autocomplete="current-password"
                                        readonly onfocus="this.removeAttribute('readonly');">
                                    <button class="profilePasswordToggle" type="button">
                                        <i class="fas fa-eye-slash"></i>
                                    </button>
                                </div>
                                <div class="profilePasswordField">
                                    <input class="formInput" type="password" id="deskNewPwd" placeholder="New password" autocomplete="new-password">
                                    <button class="profilePasswordToggle" type="button">
                                        <i class="fas fa-eye-slash"></i>
                                    </button>
                                </div>
                                <div class="profileStrengthBar" id="deskStrengthBar">
                                    <div class="profileStrengthFill"></div>
                                </div>
                                <div class="profilePasswordField">
                                    <input class="formInput" type="password" id="deskConfirmPwd" placeholder="Confirm new password" autocomplete="new-password">
                                    <button class="profilePasswordToggle" type="button">
                                        <i class="fas fa-eye-slash"></i>
                                    </button>
                                </div>
                                <button class="profileBtnPrimary" id="deskUpdatePwdBtn">Update Password</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Section -->
            <div class="profileSettingsSection" id="section-activity">
                <div class="profileSectionHeader">
                    <h1>Activity Log</h1>
                    <p>Recent account activity</p>
                </div>
                <div class="profileSettingsGroup">
                    <div class="profileActivityTimeline">
                        <div class="profileTimelineItem">
                            <div class="profileTimelineIcon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div class="profileTimelineDetails">
                                <strong>Login</strong>
                                <span><?= date('M d, Y H:i', strtotime($lastLogin)) ?></span>
                                <p>Successful login from Chrome on Windows</p>
                            </div>
                        </div>
                        <div class="profileTimelineItem">
                            <div class="profileTimelineIcon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <div class="profileTimelineDetails">
                                <strong>Profile Update</strong>
                                <span>2 days ago</span>
                                <p>Profile information was modified</p>
                            </div>
                        </div>
                        <div class="profileTimelineItem">
                            <div class="profileTimelineIcon">
                                <i class="fas fa-key"></i>
                            </div>
                            <div class="profileTimelineDetails">
                                <strong>Password Change</strong>
                                <span>30 days ago</span>
                                <p>Password was successfully changed</p>
                            </div>
                        </div>
                        <div class="profileTimelineItem">
                            <div class="profileTimelineIcon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="profileTimelineDetails">
                                <strong>Account Created</strong>
                                <span><?= $memberSince ?></span>
                                <p>Account registration completed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MOBILE LAYOUT: Accordion Style -->
    <div class="profileMobileContainer">
        <!-- Sticky Header -->
        <div class="profileMobileHeader">
            <div class="profileMobileAvatar" id="mobileAvatarBtn">
                <div class="profileMobileAvatarImg">
                    <?php if ($avatar): ?>
                        <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="avatar">
                    <?php else: ?>
                        <span><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <button class="profileMobileAvatarEdit" type="button">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
            <div class="profileMobileUserInfo">
                <h1><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <!-- Accordion Sections -->
        <div class="profileAccordionList">
            <!-- Personal Info Accordion -->
            <div class="profileAccordionItem">
                <button class="profileAccordionHeader">
                    <div class="profileAccordionTitle">
                        <i class="fas fa-user-circle"></i>
                        <span>Personal Information</span>
                    </div>
                    <i class="fas fa-chevron-down profileAccordionIcon"></i>
                </button>
                <div class="profileAccordionContent">
                    <div class="profileMobileSettingRow">
                        <label>Full Name</label>
                        <div class="profileMobileValueGroup">
                            <span id="mobileDisplayName"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                            <button class="profileMobileEditTrigger" id="mobileEditNameBtn">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                        </div>
                        <div class="profileMobileEditForm" id="mobileNameEditForm">
                            <input type="text" id="mobileNameInput" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="profileMobileEditActions">
                                <button id="mobileSaveNameBtn" class="profileBtnPrimarySmall">Save</button>
                                <button id="mobileCancelNameBtn" class="profileBtnSecondarySmall">Cancel</button>
                            </div>
                        </div>
                    </div>
                    <div class="profileMobileSettingRow">
                        <label>Email Address</label>
                        <div class="profileMobileValueGroup">
                            <span id="mobileDisplayEmail"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span>
                            <button class="profileMobileEditTrigger" id="mobileEditEmailBtn">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                        </div>
                        <div class="profileMobileEditForm" id="mobileEmailEditForm">
                            <input type="email" id="mobileEmailInput" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="profileMobileEditActions">
                                <button id="mobileSaveEmailBtn" class="profileBtnPrimarySmall">Save</button>
                                <button id="mobileCancelEmailBtn" class="profileBtnSecondarySmall">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Accordion -->
            <div class="profileAccordionItem">
                <button class="profileAccordionHeader">
                    <div class="profileAccordionTitle">
                        <i class="fas fa-shield-alt"></i>
                        <span>Security</span>
                    </div>
                    <i class="fas fa-chevron-down profileAccordionIcon"></i>
                </button>
                <div class="profileAccordionContent">
                    <div class="profileMobilePasswordForm">
                        <div class="profilePasswordField">
                            <input class="formInput" type="password" id="mobileCurrentPwd" placeholder="Current password">
                            <button class="profilePasswordToggle" type="button">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                        <div class="profilePasswordField">
                            <input class="formInput" type="password" id="mobileNewPwd" placeholder="New password">
                            <button class="profilePasswordToggle" type="button">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                        <div class="profileStrengthBar" id="mobileStrengthBar">
                            <div class="profileStrengthFill"></div>
                        </div>
                        <div class="profilePasswordField">
                            <input class="formInput" type="password" id="mobileConfirmPwd" placeholder="Confirm password">
                            <button class="profilePasswordToggle" type="button">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                        <button class="profileBtnPrimary" id="mobileUpdatePwdBtn">Update Password</button>
                    </div>
                </div>
            </div>

            <!-- Activity Accordion -->
            <div class="profileAccordionItem">
                <button class="profileAccordionHeader">
                    <div class="profileAccordionTitle">
                        <i class="fas fa-chart-line"></i>
                        <span>Activity</span>
                    </div>
                    <i class="fas fa-chevron-down profileAccordionIcon"></i>
                </button>
                <div class="profileAccordionContent">
                    <div class="profileMobileTimeline">
                        <div class="profileMobileTimelineItem">
                            <div class="profileTimelineDot"></div>
                            <div>
                                <strong>Last Login</strong>
                                <span><?= date('M d, Y H:i', strtotime($lastLogin)) ?></span>
                            </div>
                        </div>
                        <div class="profileMobileTimelineItem">
                            <div class="profileTimelineDot"></div>
                            <div>
                                <strong>Member Since</strong>
                                <span><?= $memberSince ?></span>
                            </div>
                        </div>
                        <div class="profileMobileTimelineItem">
                            <div class="profileTimelineDot"></div>
                            <div>
                                <strong>Profile Updated</strong>
                                <span>Recently</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Avatar Modal -->
<div class="profileModalOverlay" id="avatarModal">
    <div class="profileModalContainer">
        <div class="profileModalHeader">
            <h3>Change Profile Picture</h3>
            <button class="profileModalClose" id="closeAvatarModalBtn">&times;</button>
        </div>
        <div class="profileModalBody">
            <div class="profileAvatarPreview">
                <div class="profilePreviewCircle">
                    <?php if ($avatar): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" id="avatarPreviewImg">
                    <?php else: ?>
                        <span id="avatarPreviewInitials"><?= $initials ?></span>
                    <?php endif; ?>
                </div>
                <label class="profileUploadBtn">
                    <i class="fas fa-cloud-upload-alt"></i> Choose Image
                    <input type="file" id="avatarFileInput" accept="image/*" hidden>
                </label>
                <?php if ($avatar): ?>
                    <button class="profileBtnDanger" id="removeAvatarBtn" <?= !$avatar ? 'disabled' : '' ?>>
                        <i class="fas fa-trash-alt"></i> Remove Image
                    </button>
                <?php endif; ?>
                <p class="profileUploadHint">JPG, PNG up to 2MB</p>
            </div>
        </div>
        <div class="profileModalFooter">
            <button class="profileBtnSecondary" id="cancelAvatarBtn">Cancel</button>
            <button class="profileBtnPrimary" id="saveAvatarBtn" disabled>Save Changes</button>
        </div>
    </div>
</div>

<!-- Avatar Bottom Drawer for Mobile -->
<div class="profileDrawer" id="avatarDrawer">
    <div class="profileDrawerBackdrop" id="avatarDrawerBackdrop"></div>
    <div class="profileDrawerContent">
        <div class="profileDrawerStickyArea">
            <div class="profileDrawerHandle"></div>
            <div class="profileDrawerHeader">
                <h3 class="profileDrawerTitle">Change Profile Picture</h3>
                <button type="button" class="profileDrawerCloseBtn" id="closeAvatarDrawerBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="profileDrawerBody" id="avatarDrawerBody">
            <div class="profileAvatarPreview">
                <div class="profilePreviewCircle">
                    <?php if ($avatar): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" id="avatarDrawerPreviewImg">
                    <?php else: ?>
                        <span id="avatarDrawerPreviewInitials"><?= $initials ?></span>
                    <?php endif; ?>
                </div>
                <label class="profileUploadBtn">
                    <i class="fas fa-cloud-upload-alt"></i> Choose Image
                    <input type="file" id="avatarDrawerFileInput" accept="image/*" hidden>
                </label>
                <?php if ($avatar): ?>
                    <button class="profileBtnDanger" id="removeAvatarDrawerBtn">
                        <i class="fas fa-trash-alt"></i> Remove Image
                    </button>
                <?php endif; ?>
                <p class="profileUploadHint">JPG, PNG up to 2MB</p>
            </div>
        </div>
        <div class="profileDrawerFooter">
            <button class="profileBtnSecondary" id="cancelAvatarDrawerBtn">Cancel</button>
            <button class="profileBtnPrimary" id="saveAvatarDrawerBtn" disabled>Save Changes</button>
        </div>
    </div>
</div>

<script>
window.APP = {
    name: <?= json_encode($fullName) ?>,
    email: <?= json_encode($email) ?>,
    initials: <?= json_encode($initials) ?>,
    csrf: <?= json_encode(csrf_token()) ?>
};
</script>

<script src="assets/js/profile.js?<?= filemtime('assets/js/profile.js') ?>"></script>

<?php require_once __DIR__ . '/incs/footer.php'; ?>