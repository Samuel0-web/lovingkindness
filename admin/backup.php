<?php
$pageTitle = 'Backup & System Recovery';
require_once __DIR__ . '/incs/header.php';
?>
<link rel="stylesheet" href="assets/css/backup.css?<?= filemtime('assets/css/backup.css') ?>">

<main class="fluid-backup-workspace">
    
    <!-- Minimalist, Breathable Header -->
    <header class="workspace-header">
        <div class="brand-context">
            <h1 class="main-title">System Recovery</h1>
            <p class="meta-subtitle">Last backup was completed 4 hours ago</p>
        </div>
        
        <div class="search-container">
            <div class="global-search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search parameters..." aria-label="Search backups">
            </div>
        </div>
    </header>

    <!-- Main Stack Content Area -->
    <div class="workspace-stack">
        
        <!-- Section 1: Massive Visual Storage Indicator -->
        <section class="spacious-card storage-hero">
            <div class="storage-meta-metrics">
                <div class="metric-block">
                    <span class="metric-value">1.2 <span class="metric-unit">TB</span></span>
                    <span class="metric-label">Space Occupied</span>
                </div>
                <div class="metric-block standard-align-right">
                    <span class="metric-value secondary">2.0 <span class="metric-unit">TB</span></span>
                    <span class="metric-label">Total Assigned</span>
                </div>
            </div>
            
            <div class="minimal-progress-track">
                <div class="minimal-progress-bar" style="width: 60%"></div>
            </div>

            <div class="pill-badge-row">
                <span class="security-badge"><i class="fas fa-shield-alt"></i> Fully Protected</span>
                <span class="security-badge"><i class="fas fa-lock"></i> AES-256 Bit Encryption</span>
            </div>
        </section>

        <!-- Section 2: Automation Parameters (Stacked Form Layout) -->
        <section class="spacious-card configuration-stack">
            <h2 class="section-card-title"><i class="fas fa-calendar-alt"></i> Automation Engine</h2>
            
            <div class="segmented-control">
                <input type="radio" name="freq" id="freqDaily" checked>
                <label for="freqDaily">Daily</label>
                
                <input type="radio" name="freq" id="freqWeekly">
                <label for="freqWeekly">Weekly</label>
                
                <input type="radio" name="freq" id="freqCustom">
                <label for="freqCustom">Manual</label>
            </div>

            <div class="form-row-split">
                <div class="field-container">
                    <label class="input-context-label">Execution Time</label>
                    <select class="premium-select" aria-label="Select Execution Time">
                        <option>2:00 AM (Deep Night)</option>
                        <option>4:00 AM (Early Dawn)</option>
                    </select>
                </div>
                <div class="field-container">
                    <label class="input-context-label">Target Timezone</label>
                    <select class="premium-select" aria-label="Select Timezone">
                        <option>Coordinated Universal Time (UTC)</option>
                    </select>
                </div>
            </div>

            <div class="directory-picker-zone">
                <label class="input-context-label">Target Directories</label>
                <div class="fluid-checkbox-list">
                    <label class="spacious-checkbox">
                        <input type="checkbox" checked>
                        <span class="box-text">Core Application Database</span>
                    </label>
                    <label class="spacious-checkbox">
                        <input type="checkbox" checked>
                        <span class="box-text">Static Assets & Media Repositories</span>
                    </label>
                    <label class="spacious-checkbox">
                        <input type="checkbox" checked>
                        <span class="box-text">System Configuration Files</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- Section 3: Ledger List (Spacious Row Components) -->
        <section class="spacious-card ledger-section">
            <div class="ledger-header-ui">
                <h2 class="section-card-title"><i class="fas fa-history"></i> Backup History Log</h2>
                <div class="minimal-filter">
                    <span>Filter: Last 30 days</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>

            <div class="ledger-list-container">
                <!-- Log Item 1 -->
                <div class="ledger-row-item">
                    <div class="ledger-main-meta">
                        <span class="ledger-id">BKP-2026-001</span>
                        <span class="ledger-timestamp">June 1, 2026 — 02:00:00 UTC</span>
                    </div>
                    <div class="ledger-secondary-meta">
                        <span class="ledger-size">245 MB</span>
                        <span class="status-pill status-success">Success</span>
                    </div>
                    <div class="ledger-row-actions">
                        <button class="action-row-btn" title="Restore Point"><i class="fas fa-undo-alt"></i></button>
                        <button class="action-row-btn" title="Download Archive"><i class="fas fa-download"></i></button>
                    </div>
                </div>

                <!-- Log Item 2 -->
                <div class="ledger-row-item">
                    <div class="ledger-main-meta">
                        <span class="ledger-id">BKP-2026-002</span>
                        <span class="ledger-timestamp">May 31, 2026 — 02:00:00 UTC</span>
                    </div>
                    <div class="ledger-secondary-meta">
                        <span class="ledger-size">512 MB</span>
                        <span class="status-pill status-warning">Warning</span>
                    </div>
                    <div class="ledger-row-actions">
                        <button class="action-row-btn" title="Restore Point"><i class="fas fa-undo-alt"></i></button>
                        <button class="action-row-btn" title="Download Archive"><i class="fas fa-download"></i></button>
                    </div>
                </div>
            </div>
        </section>

    </div>

    <!-- Sticky Bottom Mobile Controller Container -->
    <div class="sticky-action-footer">
        <button class="master-backup-trigger-btn">
            <i class="fas fa-cloud-upload-alt"></i>
            <span>Initialize Backup Run</span>
        </button>
    </div>

</main>

<script src="assets/js/backup.js?<?= filemtime('assets/js/backup.js') ?>"></script>
<?php require_once __DIR__ . '/incs/footer.php'; ?>