document.addEventListener('DOMContentLoaded', async () => {

    const feed = document.getElementById('ncFeed');
    const filters = document.getElementById('ncFilters');
    const searchInput = document.getElementById('ncSearch');
    const emptyState = document.getElementById('ncEmpty');
    const emptyTitle = document.getElementById('ncEmptyTitle');
    const emptyText = document.getElementById('ncEmptyText');
    const groupDropdown = document.getElementById('ncGroupDropdown');
    let notifications = [];
    let activeFilter = 'all';
    let searchQuery = '';
    let activeDateGroup = 'all';
    let notificationStream = null;
    let reconnectTimer = null;

    document.querySelectorAll('.uiDropdownMenu button').forEach(btn => {
        btn.addEventListener('click', () => {
            activeDateGroup = btn.dataset.value;
            groupDropdown.dataset.value = activeDateGroup;
            groupDropdown.innerHTML = `
                ${btn.textContent}
                <i class="bi bi-chevron-down"></i>
            `;
            render();
            
        });

    });

    const dateGroupLabels = {
        today: 'Today',
        yesterday: 'Yesterday',
        'last-7-days': 'Last 7 Days',
        'earlier-month': 'Earlier This Month',
        older: 'Older'
    };

    function getDateGroup(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        const diffDays = Math.floor((today - date) / 86400000);

        if (date >= today) { return 'today'; }
        if (date >= yesterday) { return 'yesterday'; }
        if (diffDays <= 7) { return 'last-7-days'; }
        if (date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear()) {
            return 'earlier-month';
        }
        return 'older';
    }

    function normalizeType(n) {
        const t = (n.type || '').toLowerCase();

        if (t.includes('enroll')) return 'enrollment';
        if (t.includes('message') || t.includes('contact')) return 'message';
        if (t.includes('system')) return 'system';

        return 'system';
    }

    function navigateNotification(notification) {
        const type = normalizeType(notification);

        switch (type) {
            case 'enrollment':
                window.location.href = 'enrollments';
                break;

            case 'message':
                window.location.href =
                    `inbox?message=${notification.entity_id}`;
                break;
        }
    }

    function formatNotificationTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        if (diff < 60) { return 'Just now'; }
        if (diff < 3600) { return `${Math.floor(diff / 60)}m ago`; }
        if (diff < 86400) { return `${Math.floor(diff / 3600)}h ago`; }

        return date.toLocaleDateString(undefined, {
            day: 'numeric',
            month: 'short'
        });
    }

    await loadNotifications();
    const result = await api('markSeen');

    if (result.ok) {
        document.querySelectorAll('.unreadBadge').forEach(badge => {
            badge.textContent = '0';
            badge.style.display = 'none';
        });
    }

    initNotificationStream();
    setInterval(render, 60000);

    async function loadNotifications() {
        const res = await fetch('api/notifications?action=list');
        const data = await res.json();
        notifications = data.notifications || [];
        render();
    }

    async function api(action, body = null) {
        const res = await fetch(`api/notifications?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.csrfToken
            },
            body: body ? JSON.stringify(body) : null
        });

        return await res.json();
    }

    function getLatestNotificationId() {
        if (!notifications.length) { return 0; }
        return Math.max(...notifications.map(n => Number(n.id)));
    }

    function initNotificationStream() {
        if (notificationStream) { notificationStream.close(); }

        notificationStream = new EventSource(
            `api/notification-stream?last_id=${getLatestNotificationId()}`
        );

        notificationStream.addEventListener('notification', async e => {
            const notification = JSON.parse(e.data);
            const exists = notifications.some(n => Number(n.id) === Number(notification.id));
            if (exists) { return; }
            notifications.unshift(notification);
            render();
        });

        notificationStream.onerror = () => {
            notificationStream.close();
            if (reconnectTimer) { return; }

            reconnectTimer = setTimeout(() => {
                reconnectTimer = null;
                initNotificationStream();
            }, 5000);
        };
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            loadNotifications();
            api('markSeen');
        }
    });

    function render() {
        let items = notifications.filter(n => {
            const matchesFilter = (() => {
                if (activeFilter === 'all') return true;
                if (activeFilter === 'unread') { return !Number(n.is_read); }

                const type = normalizeType(n);

                if (activeFilter === 'enrollments') return type === 'enrollment';
                if (activeFilter === 'messages') return type === 'message';
                if (activeFilter === 'system') return type === 'system';

                return true;
            })();

            const text = (n.title + ' ' + n.message).toLowerCase();
            const matchesSearch = text.includes(searchQuery);
            const dateGroup = getDateGroup(n.created_at);
            const matchesDate = activeDateGroup === 'all' || dateGroup === activeDateGroup;
            return (matchesFilter && matchesSearch && matchesDate);
        });

        if (!items.length) {
            feed.innerHTML = '';
            emptyState.hidden = false;
            emptyState.style.display = '';

            const hasFilters = activeFilter !== 'all' || activeDateGroup !== 'all' ||
                searchQuery.length > 0;

            if (notifications.length === 0) {
                emptyTitle.textContent = "You're all caught up";
                emptyText.textContent = "Your inbox is beautifully clean.";
            } else if (hasFilters) {
                emptyTitle.textContent = 'No matching notifications';
                emptyText.textContent = 'Try adjusting your filters or search query.';
            } else {
                emptyTitle.textContent = 'No notifications';
                emptyText.textContent = 'There are no notifications available.';
            }

            return;
        }

        emptyState.hidden = true;
        emptyState.style.display = 'none';
        let html = '';
        let currentGroup = null;
        items.forEach(n => {
            const group = getDateGroup(n.created_at);

            if (group !== currentGroup) {
                html += `
                    <div class="nc__date-group-header">
                        ${dateGroupLabels[group]}
                    </div>
                `;
                currentGroup = group;
            }

            html += createCardHtml(n);
        });
        feed.innerHTML = html;
    }

    function createCardHtml(n) {
        const unread = !Number(n.is_read);
        const typeMap = {
            enrollment: {
                icon: 'mortarboard',
                avatarClass: 'enrollments',
                tagClass: 'enrollments',
                label: 'Enrollment'
            },

            message: {
                icon: 'chat-left-text',
                avatarClass: 'messages',
                tagClass: 'messages',
                label: 'Message'
            },

            system: {
                icon: 'cpu',
                avatarClass: 'system',
                tagClass: 'system',
                label: 'System'
            }
        };

        const meta = typeMap[normalizeType(n)] ?? typeMap.system;

        return `
            <div class="nc__card ${unread ? 'nc__card--unread' : ''}" data-id="${n.id}"
                data-category="${n.type}" data-status="${unread ? 'unread' : 'read'}"
            >
                <div class="nc__mobile-top">
                    <div class="nc__avatar nc__avatar--${meta.avatarClass}">
                        <i class="bi bi-${meta.icon}"></i>
                    </div>

                    <div class="nc__actions--mobile">
                        <button class="nc__action-btn nc__menu-trigger" aria-label="More options">
                            <i class="bi bi-three-dots"></i>
                        </button>

                        <div class="nc__dropdown">
                            <button class="nc__dropdown-item nc__action-view">
                                <i class="bi bi-eye"></i>
                                View
                            </button>

                            <button class="nc__dropdown-item nc__action-toggle-read">
                                <i class="bi bi-envelope-open"></i>
                                ${unread ? 'Mark Read' : 'Mark Unread'}
                            </button>

                            <button
                                class="nc__dropdown-item nc__dropdown-item--danger nc__action-delete"
                            >
                                <i class="bi bi-trash3"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div class="nc__content">
                    <div class="nc__header">
                        <div class="nc__title-wrapper">
                            <h3 class="nc__title">
                                ${escapeHtml(n.title)}
                            </h3>
                            ${unread ? '<div class="nc__indicator"></div>' : ''}
                        </div>

                        <div class="nc__meta-actions">
                            <span class="nc__time">${formatNotificationTime(n.created_at)}</span>
                            <div class="nc__actions nc__actions--desktop">
                                <button class="nc__action-btn nc__action-view" title="View"
                                    aria-label="View"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>

                                <button class="nc__action-btn nc__action-toggle-read"
                                    title="${unread ? 'Mark read' : 'Mark unread'}"
                                    aria-label="${unread ? 'Mark read' : 'Mark unread'}"
                                >
                                    <i class="bi bi-${unread ? 'envelope-open' : 'envelope'}"></i>
                                </button>

                                <button
                                    class="nc__action-btn nc__action-btn--danger nc__action-delete"
                                    title="Delete" aria-label="Delete"
                                >
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="nc__text">${escapeHtml(n.message)}</p>

                    <div class="nc__tags">
                        <span class="nc__tag nc__tag--${meta.tagClass}">${meta.label}</span>
                    </div>
                </div>
            </div>
        `;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    filters.addEventListener('click', e => {
        const button = e.target.closest('.nc__filter');
        if (!button) return;

        document.querySelectorAll('.nc__filter').forEach(btn =>
            btn.classList.remove(
                'nc__filter--active'
            )
        );

        button.classList.add('nc__filter--active');
        activeFilter = button.dataset.filter;
        render();
    });

    searchInput.addEventListener('input', e => {
        searchQuery = e.target.value.trim().toLowerCase();
        render();
    });

    feed.addEventListener('click', async e => {
        const card = e.target.closest('.nc__card');
        if (!card) return;
        const id = Number(card.dataset.id);
        const notification = notifications.find(n => Number(n.id) === id);
        if (!notification) return;

        if (e.target.closest('.nc__action-view')) {
            if (!Number(notification.is_read)) {
                const result = await api('markRead', { id });

                if (result.ok) {
                    notification.is_read = 1;
                }
            }

            navigateNotification(notification);
            return;
        }

        if (e.target.closest('.nc__action-toggle-read')) {
            const action = Number(notification.is_read) ? 'markUnread' : 'markRead';
            const result = await api(action, { id });
            if (!result.ok) { return; }
            notification.is_read = notification.is_read ? 0 : 1;
            render();
            return;
        }

        if (e.target.closest('.nc__action-delete')) {
            const confirmed = await window.UI.confirmDelete('Delete this notification?');
            if (!confirmed) { return; }
            const result = await api('delete', { id });
            if (!result.ok) { return; }
            notifications = notifications.filter(n => Number(n.id) !== id);
            render();
            return;
        }

        if (e.target.closest('.nc__menu-trigger')) {
            document.querySelectorAll('.nc__dropdown--active').forEach(dropdown => {
                dropdown.classList.remove('nc__dropdown--active');
            });

            const dropdown = card.querySelector('.nc__dropdown');
            dropdown.classList.add('nc__dropdown--active');
            return;
        }
    });

    document.addEventListener('click', e => {
        if (e.target.closest('.nc__actions--mobile')) { return; }
        document.querySelectorAll('.nc__dropdown--active').forEach(dropdown => {
            dropdown.classList.remove(
                'nc__dropdown--active'
            );
        });
    });

    document.getElementById('ncMarkAllRead').addEventListener('click', async () => {
        const result = await api('markAllRead');
        if (!result.ok) { return; }
        notifications.forEach(n => {n.is_read = 1;});
        render();
    });

    document.getElementById('ncDeleteAll').addEventListener('click', async () => {
        const confirmed = await window.UI.confirmDelete(
            'Delete all notifications? This cannot be undone.',
            'Delete All Notifications'
        );

        if (!confirmed) { return; }
        const result = await api('deleteAll');
        if (!result.ok) { return; }
        notifications = [];
        render();
    });
});