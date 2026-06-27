// ========== SETTINGS PAGE SCRIPT ==========
// Uses global UI.toast and UI.confirm from ui.js

const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => document.querySelectorAll(selector);

// ========== SAVE SETTINGS ==========
const saveSetting = async (key, value) => {
    // Mock API call - replace with your actual endpoint
    console.log(`Saving ${key}: ${value}`);
};

// ========== TOGGLE HANDLERS ==========
const maintenanceToggle = $('#maintenanceMode');
if (maintenanceToggle) {
    maintenanceToggle.addEventListener('change', (e) => {
        saveSetting('maintenance_mode', e.target.checked);
    });
}

const emailToggle = $('#emailNotifications');
if (emailToggle) {
    emailToggle.addEventListener('change', (e) => {
        saveSetting('email_notifications', e.target.checked);
    });
}

const twoFactorToggle = $('#twoFactor');
if (twoFactorToggle) {
    twoFactorToggle.addEventListener('change', (e) => {
        saveSetting('two_factor', e.target.checked);
    });
}


const setTheme = (theme) => {
    localStorage.setItem('theme', theme);

    applyTheme(theme);

    saveSetting('theme', theme);
};

// Theme buttons - ONLY JavaScript handles the active class
const themeBtns = $$('.settingsThemeBtn');
themeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const theme = btn.dataset.theme;
        themeBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        setTheme(theme);
    });
});

const currentTheme = localStorage.getItem('theme') || 'system';

themeBtns.forEach(btn => {
    if (btn.dataset.theme === currentTheme) {
        btn.classList.add('active');
    }
});

// ========== DELETE ACCOUNT ==========
const deleteAccount = async () => {
    const confirmed = await UI.confirmDelete(
        'This will permanently delete your account and all data. You will not be able to recover anything.',
        'Delete Account?'
    );
    
    if (confirmed) {
        UI.toastWarning('Account deletion requested');
    }
};

$('#deleteAccountBtn')?.addEventListener('click', deleteAccount);