<?php
$pageTitle = "Settings";
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

// Simple settings (these would come from database in real implementation)
$maintenanceMode = false;
$emailNotifications = true;
$twoFactor = false;
?>

<link rel="stylesheet" href="assets/css/settings.css?<?= filemtime('assets/css/settings.css') ?>">

<div class="settingsPage">
    <div class="settingsContainer">
        <div class="settingsContent">
            <!-- General Section -->
            <div class="settingsCard">
                <div class="settingsCardHeader">
                    <h2>General</h2>
                </div>
                <div class="settingsRow">
                    <div class="settingsLabel">
                        <label>Turn off website temporarily</label>
                        <span class="settingsHint">When turned on, visitors will see a maintenance page. Only you can access the site.</span>
                    </div>
                    <div class="settingsControl">
                        <label class="settingsToggle">
                            <input type="checkbox" id="maintenanceMode" <?= $maintenanceMode ? 'checked' : '' ?>>
                            <span class="settingsToggleSlider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Preferences Section -->
            <div class="settingsCard">
                <div class="settingsCardHeader">
                    <h2>Preferences</h2>
                </div>
                <div class="settingsRow">
                    <div class="settingsLabel">
                        <label>Get email alerts</label>
                        <span class="settingsHint">Receive notifications about important activity</span>
                    </div>
                    <div class="settingsControl">
                        <label class="settingsToggle">
                            <input type="checkbox" id="emailNotifications" <?= $emailNotifications ? 'checked' : '' ?>>
                            <span class="settingsToggleSlider"></span>
                        </label>
                    </div>
                </div>
                <div class="settingsDivider"></div>
                <div class="settingsRow">
                    <div class="settingsLabel">
                        <label>Color scheme</label>
                        <span class="settingsHint">Choose light, dark, or match your device</span>
                    </div>
                    <div class="settingsControl">
                        <div class="settingsThemeOptions">
                            <button type="button" class="settingsThemeBtn" data-theme="light">
                                <i class="fas fa-sun"></i>
                                <span>Light</span>
                            </button>
                            <button type="button" class="settingsThemeBtn" data-theme="dark">
                                <i class="fas fa-moon"></i>
                                <span>Dark</span>
                            </button>
                            <button type="button" class="settingsThemeBtn" data-theme="system">
                                <i class="fas fa-desktop"></i>
                                <span>System</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Section -->
            <div class="settingsCard">
                <div class="settingsCardHeader">
                    <h2>Security</h2>
                </div>
                <div class="settingsRow">
                    <div class="settingsLabel">
                        <label>Extra login protection</label>
                        <span class="settingsHint">Requires a code from your phone in addition to your password</span>
                    </div>
                    <div class="settingsControl">
                        <label class="settingsToggle">
                            <input type="checkbox" id="twoFactor" <?= $twoFactor ? 'checked' : '' ?>>
                            <span class="settingsToggleSlider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Danger Zone Section -->
            <div class="settingsCard settingsCardDanger">
                <div class="settingsRow">
                    <div class="settingsLabel">
                        <label>Delete account</label>
                        <span class="settingsHint">Permanently remove your admin account and all associated data.</span>
                    </div>
                    <div class="settingsControl">
                        <button class="settingsBtnDanger" id="deleteAccountBtn">Delete Account</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/settings.js?<?= filemtime('assets/js/settings.js') ?>"></script>

<?php require_once __DIR__ . '/incs/footer.php'; ?>
