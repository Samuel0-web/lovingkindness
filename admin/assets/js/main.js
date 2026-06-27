
// ========== THEME SYSTEM ==========
window.applyTheme = (theme) => {
    const html = document.documentElement;

    // System theme
    if (theme === 'system') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        html.setAttribute(
            'data-theme',
            prefersDark ? 'dark' : 'light'
        );

        return;
    }

    // Manual theme
    html.setAttribute('data-theme', theme);
};

// Load saved theme globally
const savedTheme = localStorage.getItem('theme') || 'system';
applyTheme(savedTheme);

// Listen for system theme changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'system') { applyTheme('system'); }
});

const sidebar = document.getElementById('sidebar');
const collapseBtn = document.getElementById('collapseBtn');
const menuBtn = document.getElementById('menuBtn');
const overlay = document.getElementById('sidebarOverlay');

// ========== DESKTOP COLLAPSE/EXPAND ==========
if (collapseBtn) {
    const icon = collapseBtn.querySelector('i');
    
    collapseBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        
        if (sidebar.classList.contains('collapsed')) {
            icon.classList.remove('bi-layout-sidebar');
            icon.classList.add('bi-layout-sidebar-inset');
        } else {
            icon.classList.remove('bi-layout-sidebar-inset');
            icon.classList.add('bi-layout-sidebar');
        }
    });
    
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true') {
        sidebar.classList.add('collapsed');
        icon.classList.remove('bi-layout-sidebar');
        icon.classList.add('bi-layout-sidebar-inset');
    }
}

// ========== MOBILE MENU TOGGLE ==========
if (menuBtn && overlay) {
    menuBtn.addEventListener('click', () => {
        sidebar.classList.add('mobile-open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
    
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        sidebar.style.transform = '';
        overlay.style.opacity = '';
    });
}

// ========== FLUID SWIPE TO CLOSE (ONLY) ==========
let touchStartX = 0;
let touchCurrentX = 0;
let isSwiping = false;
let hasMoved = false;

sidebar.addEventListener('touchstart', (e) => {
    if (!sidebar.classList.contains('mobile-open')) return;
    touchStartX = e.touches[0].clientX;
    isSwiping = true;
    hasMoved = false;
    sidebar.style.transition = 'none';
}, { passive: true });

sidebar.addEventListener('touchmove', (e) => {
    if (!isSwiping || !sidebar.classList.contains('mobile-open')) return;
    touchCurrentX = e.touches[0].clientX;
    const deltaX = touchCurrentX - touchStartX;
    
    // Only consider it a swipe if moved more than 5px
    if (Math.abs(deltaX) > 5) { hasMoved = true; }
    
    // Only allow swipe to the left (negative delta)
    if (deltaX < 0) {
        const swipePercent = Math.max(deltaX, -280);
        sidebar.style.transform = `translateX(${swipePercent}px)`;
        overlay.style.opacity = `${1 + (swipePercent / 280)}`;
    }
});

document.getElementById('logoutForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const ok = await UI.confirm({
        title: 'Logout',
        message: 'Are you sure you want to log out?',
        type: 'warning',
        confirmText: 'Yes, Logout',
        cancelText: 'Stay Logged In',
        showWarning: false
    });

    if (ok) {
        this.submit(); // continue to PHP logout
    }
});

sidebar.addEventListener('touchend', (e) => {
    if (!isSwiping) return;
    isSwiping = false;
    sidebar.style.transition = '';
    
    // Only close if it was an actual swipe (hasMoved) AND swiped more than 50px
    if (hasMoved) {
        const deltaX = touchCurrentX - touchStartX;
        
        // If swiped more than 50px to the left, close the sidebar
        if (deltaX < -50) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            sidebar.style.transform = '';
            overlay.style.opacity = '';
        } else {
            // Snap back to open position
            sidebar.style.transform = '';
            overlay.style.opacity = '';
        }
    } else {
        // It was a tap, not a swipe - do nothing, keep sidebar open
        sidebar.style.transform = '';
        overlay.style.opacity = '';
    }
    
    // Reset values
    touchStartX = 0;
    touchCurrentX = 0;
    hasMoved = false;
});

// ========== CLOSE SIDEBAR ON RESIZE ==========
window.addEventListener('resize', () => {
    if (window.innerWidth > 768 && sidebar.classList.contains('mobile-open')) {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        sidebar.style.transform = '';
        overlay.style.opacity = '';
    }
});

// ========== CLOSE SIDEBAR WITH ESCAPE KEY ==========
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        sidebar.style.transform = '';
        overlay.style.opacity = '';
    }
});

const centre = document.getElementById('notificationCentre');
const bell = document.getElementById('notificationBell');
const dropdown = document.getElementById('notificationDropdown');
const unreadBadge = document.querySelectorAll('.unreadBadge');
const notificationsList = document.getElementById('notificationsList');
const emptyState = document.getElementById('emptyState');
const markAllReadBtn = document.getElementById('markAllReadBtn');
const viewAllBtn = document.getElementById('viewAllBtn');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
let notifications = [];

if (notificationsList) {
    notificationsList.addEventListener('click', async (e) => {
        const card = e.target.closest('.notif__card');
        if (!card) { return; }
        const id = Number(card.dataset.id);
        const url = card.dataset.url;

        if (card.classList.contains('unread')) {
            card.classList.remove('unread');
            const currentCount = Number(unreadBadge[0]?.textContent || 0);
            updateNotificationBadges(Math.max(0, currentCount - 1));
            await markRead(id);
        }
        window.location.href = url;
    });
}

const escapeHtmls = (str = '') => str.replace(/[&<>"']/g, match => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
})[match]);

function getNotificationIcon(type) {
    switch (type) {
        case 'new_enrollment':
            return 'fa-graduation-cap';

        case 'new_contact_message':
            return 'fa-envelope';

        default:
            return 'fa-bell';
    }
}

function getNotificationUrl(notification) {
    switch (notification.type) {
        case 'new_enrollment':
            return 'enrollments';

        case 'new_contact_message':
            return 'inbox';

        default:
            return 'notifications';
    }
}

function createTime(dateString) {
    const now = new Date();
    const date = new Date(dateString);

    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);
    if (seconds < 60) return 'Just now';

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;

    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;

    const days = Math.floor(hours / 24);
    if (days < 30) return `${days}d ago`;

    return date.toLocaleDateString();
}

function openDropdown() { dropdown.classList.add('active'); }
function closeDropdown() { dropdown.classList.remove('active'); }
function toggleDropdown() { dropdown.classList.toggle('active'); }

bell.addEventListener('click', async (e) => {
    e.stopPropagation();
    toggleDropdown();
    if (dropdown.classList.contains('active')) { await loadNotifications(); }
});

document.addEventListener('click', (e) => {
    if (!centre.contains(e.target)) { closeDropdown(); }
});

function updateNotificationBadges(count) {
    unreadBadge.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    });
}

async function loadUnseenCount() {
    try {
        const response = await fetch('api/notifications?action=unseenCount');
        const data = await response.json();
        if (!data.ok) return;
        const count = Number(data.count) || 0;
        updateNotificationBadges(count);
        markAllReadBtn.style.display = count > 0 ? '' : 'none';
    } catch (error) {
        console.error(error);
    }
}

bell.addEventListener('keydown', async (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleDropdown();
        if (dropdown.classList.contains('active')) { await loadNotifications(); }
    }
});

async function loadNotifications() {
    try {
        const response = await fetch('api/notifications?action=recent&limit=8');
        const data = await response.json();
        if (!data.ok) return;
        notifications = data.notifications || [];
        renderNotifications();
    } catch (error) {
        console.error(error);
    }
}

async function markRead(id) {
    try {
        await fetch('api/notifications?action=markRead', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ id })
            
        });

    } catch (error) {
        console.error(error);
    }
}

function renderNotifications() {
    if (!notifications.length) {
        notificationsList.innerHTML = '';
        emptyState.style.display = 'flex';
        return;
    }

    emptyState.style.display = 'none';

    notificationsList.innerHTML = notifications.map(notification => `
        <div class="notif__card ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}"
            data-url="${getNotificationUrl(notification)}"
        >

            <div class="notif__icon">
                <i class="fas ${getNotificationIcon(notification.type)}"></i>
            </div>

            <div class="notif__content">
                <div class="notif__title">
                    <span>${escapeHtmls(notification.title)}</span>
                    <span class="notif__time">${createTime(notification.created_at)}</span>
                </div>

                <div class="notif__message">${escapeHtmls(notification.message)}</div>
            </div>
        </div>
    `).join('');
}

/*
|--------------------------------------------------------------------------
| Buttons
|--------------------------------------------------------------------------
*/

viewAllBtn.addEventListener('click', () => { window.location.href = 'notifications';});

/*
|--------------------------------------------------------------------------
| Init
|--------------------------------------------------------------------------
*/

loadUnseenCount();

function startGlobalSSE() {
    const source = new EventSource('api/global-stream');

    source.addEventListener('unread_notifications', async event => {
        const data = JSON.parse(event.data);
        updateNotificationBadges(data.count);
        if (dropdown.classList.contains('active')) { await loadNotifications(); }
    });

    source.addEventListener('pending_enrollments', event => {
        const data = JSON.parse(event.data);
        const badge = document.getElementById('enrollmentBadge');

        if (badge) {
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? '' : 'none';
        }
    });

    source.addEventListener('unread_messages', event => {
        const data = JSON.parse(event.data);
        const badge = document.getElementById('messageBadge');
        if (!badge) return;
        badge.textContent = data.count;
        badge.style.display = data.count > 0 ? '' : 'none';
    });

    source.onerror = () => {
        source.close();
        setTimeout(startGlobalSSE, 5000);
    };
}

startGlobalSSE();