// ========== DOM Elements ==========
const conversationStream = document.getElementById('conversationStream');
const conversationDetail = document.getElementById('conversationDetail');
const conversationList = document.getElementById('conversationList');
const searchInput = document.getElementById('searchInput');
let latestMessageId = 0;
let currentMessageId = null;
let isMobile = window.innerWidth <= 768;

const statusLabels = {
    unread: 'Unread',
    read: 'Read',
    replied: 'Replied',
    archived: 'Archived',
    spam: 'Spam'
};

function csrfHeaders() {
    return {
        'X-CSRF-TOKEN': window.CSRF_TOKEN,
        'X-Requested-With': 'XMLHttpRequest'
    };
}

// ========== Mobile View Management ==========
function showConversationList() {
    if (window.innerWidth <= 768) {
        conversationDetail.classList.remove('open');
    }
}

function showConversationDetail() {
    if (window.innerWidth <= 768) {
        conversationDetail.classList.add('open');
    }
}

function addBackButton() {
    const existingBackBtn = document.querySelector('.detailBackButton');
    if (existingBackBtn) existingBackBtn.remove();
    
    const backBtn = document.createElement('div');
    backBtn.className = 'detailBackButton';
    backBtn.innerHTML = '<i class="fas fa-arrow-left"></i> Back to inbox';
    backBtn.onclick = showConversationList;
    
    const customerHeader = document.querySelector('.customerHeader');
    if (customerHeader) {
        customerHeader.insertBefore(backBtn, customerHeader.firstChild);
    }
}

// Handle resize
window.addEventListener('resize', () => {
    const wasMobile = isMobile;
    isMobile = window.innerWidth <= 768;
    
    if (isMobile && !wasMobile && conversationDetail.classList.contains('open')) {
        conversationDetail.classList.remove('open');
    }
});

function getConversationEmptyState(totalMessages = 0) {
    const status = document.getElementById('statusFilterDropdown')?.dataset.value || 'all';
    const inquiry = document.getElementById('inquiryFilterDropdown')?.dataset.value || 'all';
    const search = searchInput?.value.trim() || '';
    const hasSearch = search.length > 0;
    const hasFilters = status !== 'all' || inquiry !== 'all';

    if (hasSearch) {
        return `
            <div class="emptyState">
                <div class="emptyIcon">
                    <i class="fas fa-search"></i>
                </div>

                <h3>No matching conversations</h3>
                <p>No messages match "${escapeHtml(search)}".</p>
            </div>
        `;
    }

    if (hasFilters) {
        return `
            <div class="emptyState">
                <div class="emptyIcon">
                    <i class="fas fa-filter"></i>
                </div>

                <h3>No conversations match these filters</h3>
                <p>Try changing or clearing your filters.</p>
            </div>
        `;
    }

    if (totalMessages === 0) {
        return `
            <div class="emptyState">
                <div class="emptyIcon">
                    <i class="fas fa-comments"></i>
                </div>

                <h3>No messages yet</h3>
                <p>Contact form submissions and inquiries will appear here.</p>
            </div>
        `;
    }

    return `
        <div class="emptyState">
            <div class="emptyIcon">
                <i class="fas fa-inbox"></i>
            </div>

            <h3>No conversations found</h3>
        </div>
    `;
}

// ========== AJAX Functions ==========
async function fetchMessages() {
    const params = new URLSearchParams();
    const status = document.getElementById('statusFilterDropdown')?.dataset.value || 'all';
    const inquiry = document.getElementById('inquiryFilterDropdown')?.dataset.value || 'all';
    const search = searchInput?.value || '';
    if (status !== 'all') params.set('status', status);
    if (inquiry !== 'all') params.set('inquiry', inquiry);
    if (search) params.set('search', search);
    showConversationSkeletons();
    try {
        const response = await fetch(`api/message?list=1&${params.toString()}`);
        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error('Invalid server response');
        }

        if (data.success) {
            if (!data.messages?.length) {
                conversationStream.innerHTML = getConversationEmptyState(data.totalMessages);

                document.getElementById('clearFiltersBtn')?.addEventListener('click', () => {
                    resetFilters();
                });

                return;
            }

            if (data.messages.length) {
                latestMessageId = Math.max(...data.messages.map(m => Number(m.id)));
            }

            renderConversationList(data.messages);

            conversationStream.querySelectorAll('.conversationItem').forEach(item => {
                item.addEventListener('click', () => {
                    loadMessageDetail(item.dataset.id);
                });
            });
        }
    } catch (err) {
        console.error(err);
        conversationStream.innerHTML = `
            <div class="emptyState">
                <div class="emptyIcon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <p>Failed to load messages</p>
            </div>
        `;
    }
}

function prependConversation(message) {
    const inquiryTypes = {
        tutoring: { icon: 'fas fa-graduation-cap', label: 'Tutoring', color: 'blue' },
        'teacher-training': { icon: 'fas fa-chalkboard-teacher', label: 'Training', color: 'indigo' },
        admissions: { icon: 'fas fa-door-open', label: 'Admissions', color: 'green' },
        technical: { icon: 'fas fa-terminal', label: 'Technical', color: 'purple' },
        feedback: { icon: 'fas fa-star', label: 'Feedback', color: 'orange' },
        general: { icon: 'fas fa-comment', label: 'General', color: 'gray' }
    };

    const inquiry = inquiryTypes[message.inquiry_type] || inquiryTypes.general;

    if (document.querySelector(`.conversationItem[data-id="${message.id}"]`)) {
        return;
    }

    const initials = message.full_name
        .split(' ')
        .map(w => w[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();

    const html = `
        <div
            class="conversationItem unread"
            data-id="${message.id}"
        >
            <div class="conversationAvatar">
                <div class="avatarInitials">
                    ${initials}
                </div>
            </div>

            <div class="conversationContent">
                <div class="conversationHeader">
                    <span class="senderName">
                        ${escapeHtml(message.full_name)}
                    </span>

                    <span class="conversationTime" data-time="${message.created_at}">
                        ${formatRelativeTime(message.created_at)}
                    </span>
                </div>

                <div class="conversationBadgeRow">
                    <span class="inquiryBadge badge-${inquiry.color}">
                        <i class="${inquiry.icon}"></i>
                        <span>${inquiry.label}</span>
                    </span>
                </div>

                <div class="conversationPreview">
                    ${escapeHtml(
                        message.message.substring(0, 70)
                    )}
                </div>
            </div>
        </div>
    `;

    conversationStream.insertAdjacentHTML('afterbegin', html);

    conversationStream.querySelector(`.conversationItem[data-id="${message.id}"]`)
    ?.addEventListener('click', () => {
        loadMessageDetail(message.id);
    });
}

function renderConversationList(messages, totalMessages = 0) {
    if (!messages.length) {
        conversationStream.innerHTML =
            getConversationEmptyState(totalMessages);
        return;
    }

    const inquiryTypes = {
        tutoring: { icon: 'fas fa-graduation-cap', label: 'Tutoring', color: 'blue' },
        'teacher-training': { icon: 'fas fa-chalkboard-teacher', label: 'Training', color: 'indigo' },
        admissions: { icon: 'fas fa-door-open', label: 'Admissions', color: 'green' },
        technical: { icon: 'fas fa-terminal', label: 'Technical', color: 'purple' },
        feedback: { icon: 'fas fa-star', label: 'Feedback', color: 'orange' },
        general: { icon: 'fas fa-comment', label: 'General', color: 'gray' }
    };

    conversationStream.innerHTML = messages.map(message => {
        const inquiry =
            inquiryTypes[message.inquiry_type] ||
            inquiryTypes.general;

        const preview = escapeHtml(
            (message.message || '')
                .replace(/\s+/g, ' ')
                .trim()
                .substring(0, 70)
        );

        const initials = message.full_name
            .split(' ')
            .map(word => word[0])
            .join('')
            .substring(0, 2)
            .toUpperCase();

        return `
            <div class="conversationItem ${message.status === 'unread' ? 'unread' : ''} ${currentMessageId == message.id ? 'selected' : ''}" data-id="${message.id}">
                <div class="conversationAvatar">
                    <div class="avatarInitials">${initials}</div>
                </div>

                <div class="conversationContent">
                    <div class="conversationHeader">
                        <span class="senderName">
                            ${escapeHtml(message.full_name)}
                        </span>

                        <span class="conversationTime" data-time="${message.created_at}">
                            ${formatRelativeTime(message.created_at)}
                        </span>
                    </div>

                    <div class="conversationBadgeRow">
                        <span class="inquiryBadge badge-${inquiry.color}">
                            <i class="${inquiry.icon}"></i>
                            <span>${inquiry.label}</span>
                        </span>
                    </div>

                    <div class="conversationPreview">
                        ${preview}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function showConversationSkeletons() {
    conversationStream.innerHTML = `
        <div class="messageSkeleton">
            <div class="skeletonAvatar"></div>
            <div>
                <div class="skeletonLine long"></div>
                <div class="skeletonLine short"></div>
            </div>
        </div>
    `.repeat(6);
}

async function updateMessageStatus(id, status) {
    try {
        const response = await fetch('api/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                ...csrfHeaders()
            },
            body: new URLSearchParams({
                action: 'update_status',
                id: id,
                status: status
            })
        });
        
        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error('Invalid server response');
        }
        if (data.success) {
            updateConversationItemStatus(id, status);
            if (currentMessageId == id) {
                updateDetailPanelStatus(status);
            }
            return true;
        }
        return false;
    } catch (error) {
        UI.toastError(error);
        return false;
    }
}

function updateConversationItemStatus(id, status) {
    const item = document.querySelector(`.conversationItem[data-id="${id}"]`);

    if (!item) return;

    item.classList.remove('unread', 'read', 'replied', 'archived', 'spam');
    item.classList.add(status);

    const avatar = item.querySelector('.conversationAvatar');
}

function updateDetailPanelStatus(status) {
    const chip = document.querySelector('.statusChip');

    if (!chip) return;

    chip.dataset.status = status;

    chip.innerHTML = `
        <i class="fas fa-circle"></i>
        ${statusLabels[status] || status}
    `;
}

async function sendReply(id, replyMessage) {
    try {
        const response = await fetch('api/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                ...csrfHeaders()
            },
            body: new URLSearchParams({
                action: 'send_reply',
                id: id,
                reply_message: replyMessage
            })
        });
        
        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error('Invalid server response');
        }
        if (data.success) {
            await fetchMessages();
            if (currentMessageId == id) {
                await loadMessageDetail(id);
            }
            return true;
        }
        return false;
    } catch (error) {
        UI.toastError(error);
        return false;
    }
}

async function updateNotes(id, notes) {
    try {
        const response = await fetch('api/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                ...csrfHeaders()
            },
            body: new URLSearchParams({
                action: 'update_notes',
                id: id,
                admin_notes: notes
            })
        });
        
        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error('Invalid server response');
        }
        if (data.success) {
            await loadMessageDetail(id);
        }
        return data.success;
    } catch (error) {
        UI.toastError(error);
        return false;
    }
}

async function deleteMessage(id) {
    const confirmed = await UI.confirmDelete(
        'This will permanently delete this message. This action cannot be undone.',
        'Delete Message?'
    );
    
    if (!confirmed) return;
    
    try {
        const response = await fetch('api/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                ...csrfHeaders()
            },
            body: new URLSearchParams({
                action: 'delete_message',
                id: id
            })
        });
        
        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error('Invalid server response');
        }
        if (data.success) {
            showConversationList();
            UI.toastSuccess("Message deleted successfully")
            await fetchMessages();
            conversationDetail.innerHTML = '<div class="detailPlaceholder"><i class="fas fa-comment-dots"></i><p>Select a conversation</p></div>';
            currentMessageId = null;
        }
    } catch (error) {
        UI.toastError(error);
    }
}

async function loadMessageDetail(id) {
    currentMessageId = id;
    
    // Update selected state
    document.querySelectorAll('.conversationItem').forEach(item => {
        item.classList.remove('selected');
        if (item.dataset.id == id) {
            item.classList.add('selected');
        }
    });
    
    try {
        const response = await fetch(`api/message?id=${id}`);

        if (!response.ok) {
            throw new Error('Request failed');
        }

        const message = await response.json();
        
        if (message && !message.error) {
            if (message.status === 'unread') {
                await updateMessageStatus(id, 'read');
                message.status = 'read';
            }

            renderConversationDetail(message);
            initAutoResizeTextarea();
            showConversationDetail();

            if (window.innerWidth <= 768) {
                addBackButton();
            }
        } else {
            conversationDetail.innerHTML = '<div class="detailPlaceholder"><div class="emptyIcon"><i class="fas fa-exclamation-triangle"></i></div><p>Error loading conversation</p><span class="emptyHint">Please try again later</span></div>';
        }
    } catch (error) {
        UI.toastError(error);
    }
}

function renderConversationDetail(message) {
    const deviceInfo = message.parsed_ua || { browser: 'Unknown', os: 'Unknown', device: 'Unknown', isLocal: false };
    const hasReply = message.reply_message && message.reply_message.trim();
    
    const inquiryTypes = {
        'tutoring': { icon: 'fas fa-graduation-cap', label: 'Tutoring', color: 'blue' },
        'teacher-training': { icon: 'fas fa-chalkboard-teacher', label: 'Training', color: 'indigo' },
        'admissions': { icon: 'fas fa-door-open', label: 'Admissions', color: 'green' },
        'technical': { icon: 'fas fa-terminal', label: 'Technical', color: 'purple' },
        'feedback': { icon: 'fas fa-star', label: 'Feedback', color: 'orange' },
        'general': { icon: 'fas fa-comment', label: 'General', color: 'gray' }
    };
    const inquiry = inquiryTypes[message.inquiry_type] || inquiryTypes.general;
    
    const ipDisplay = (message.ip_address === '::1' || message.ip_address === '127.0.0.1') 
        ? 'Local Development' 
        : message.ip_address || 'Unknown';
    
    const isLocalEnv = (message.ip_address === '::1' || message.ip_address === '127.0.0.1');
    
    // Normalize message body - trim leading whitespace but preserve intentional formatting
    let messageBody = message.message || '';
    messageBody = messageBody.trimStart(); // Remove only leading whitespace
    
    const html = `
        <div class="conversationThread">
            <div class="customerHeader">
                <div class="customerInfo">
                    <div class="customerDetails">
                        <h2>${escapeHtml(message.full_name)}</h2>
                        <div class="customerEmail">${escapeHtml(message.email)}</div>
                        ${message.phone ? `<div class="customerPhone">${escapeHtml(message.phone)}</div>` : ''}
                    </div>
                    <div class="actionBar">
                        <button class="actionBtn" data-action="markUnread" data-id="${message.id}" title="Mark as unread">
                            <i class="fas fa-envelope"></i>
                            <span class="actionLabel">Unread</span>
                        </button>
                        <button class="actionBtn archive" data-action="archiveMessage" data-id="${message.id}" title="Archive">
                            <i class="fas fa-archive"></i>
                            <span class="actionLabel">Archive</span>
                        </button>
                        <button class="actionBtn danger" data-action="deleteMessage" data-id="${message.id}" title="Delete">
                            <i class="fas fa-trash"></i>
                            <span class="actionLabel">Delete</span>
                        </button>
                    </div>
                </div>
                <div class="conversationMeta">
                    <span class="inquiryBadge badge-${inquiry.color}">
                        <i class="${inquiry.icon}"></i>
                        <span>${inquiry.label}</span>
                    </span>
                    <span class="metaChip statusChip">
                        <i class="fas fa-circle"></i>
                        ${statusLabels[message.status] || message.status}
                    </span>
                    <span class="metaChip">
                        <i class="far fa-calendar"></i>
                        ${formatRelativeTime(message.created_at)}
                    </span>
                </div>
            </div>
            
            <div class="messageContent">
                <div class="messageHeader">
                    <div class="messageSubject">${escapeHtml(message.subject)}</div>
                    <div class="messageDate">${formatFullDate(message.created_at)}</div>
                </div>
                <div class="messageBody">
                    ${escapeHtml(messageBody).replace(/\n/g, '<br>')}
                </div>
            </div>
            
            ${hasReply ? `
            <div class="replySection">
                <div class="replyHeader">
                    <span class="replyLabel"><i class="fas fa-reply"></i> Your Reply</span>
                    <span class="replyDate">${formatFullDate(message.replied_at)}</span>
                </div>
                <div class="replyBody">
                    ${escapeHtml(message.reply_message).replace(/\n/g, '<br>')}
                </div>
            </div>
            ` : ''}
            
            <div class="composerSection">
                <div class="composerLabel">Reply to ${escapeHtml(message.full_name)}</div>
                <textarea id="replyText" class="composerTextarea" rows="1" placeholder="Type your reply..."></textarea>
                <div class="composerActions">
                    <button class="btnPrimary" data-action="submitReply" data-id="${message.id}">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                </div>
            </div>
            
            <div class="notesSection">
                <div class="notesHeader">
                    <span class="notesTitle"><i class="fas fa-sticky-note"></i> Internal Notes</span>
                </div>
                <textarea id="notesText" class="notesTextarea" rows="2" placeholder="Private notes for admin reference...">${escapeHtml(message.admin_notes || '')}</textarea>
                <button class="btnSecondary" data-action="saveNotes" data-id="${message.id}">
                    <i class="fas fa-save"></i> Save Notes
                </button>
            </div>
            
            <div class="techSection">
                <button class="accordionTrigger" data-action="toggleAccordion">
                    <span><i class="fas fa-code"></i> Technical Details</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="technicalContent">
                    <div class="techGrid">
                        <div class="techItem">
                            <div class="techIcon"><i class="fas fa-network-wired"></i></div>
                            <div class="techInfo">
                                <span class="techLabel">IP Address</span>
                                <span class="techValue">${isLocalEnv ? 'Local Development Environment' : escapeHtml(ipDisplay)}</span>
                            </div>
                        </div>
                        <div class="techItem">
                            <div class="techIcon"><i class="fas fa-globe"></i></div>
                            <div class="techInfo">
                                <span class="techLabel">Browser</span>
                                <span class="techValue">${escapeHtml(deviceInfo.browser)} ${escapeHtml(deviceInfo.version || '')}</span>
                            </div>
                        </div>
                        <div class="techItem">
                            <div class="techIcon"><i class="fas fa-desktop"></i></div>
                            <div class="techInfo">
                                <span class="techLabel">Operating System</span>
                                <span class="techValue">${escapeHtml(deviceInfo.os)}</span>
                            </div>
                        </div>
                        <div class="techItem">
                            <div class="techIcon"><i class="fas fa-mobile-alt"></i></div>
                            <div class="techInfo">
                                <span class="techLabel">Device Type</span>
                                <span class="techValue">${escapeHtml(deviceInfo.device)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    conversationDetail.innerHTML = html;
}

// Event delegation for dynamically rendered buttons
conversationDetail.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const action = btn.getAttribute('data-action');
    const id = btn.getAttribute('data-id');
    
    if (action === 'markUnread' && id) {
        markUnread(parseInt(id));
    } else if (action === 'archiveMessage' && id) {
        archiveMessage(parseInt(id));
    } else if (action === 'deleteMessage' && id) {
        deleteMessage(parseInt(id));
    } else if (action === 'submitReply' && id) {
        submitReply(parseInt(id), btn);
    } else if (action === 'saveNotes' && id) {
        saveNotes(parseInt(id), btn);
    } else if (action === 'toggleAccordion') {
        const trigger = btn;
        const content = trigger.nextElementSibling;
        trigger.classList.toggle('open');
        content.classList.toggle('show');
    }
});

async function markUnread(id) {
    const success = await updateMessageStatus(id, 'unread');

    if (success && currentMessageId == id) {
        updateDetailPanelStatus('unread');
    }
}

async function archiveMessage(id) {
    const success = await updateMessageStatus(id, 'archived');
    if (success && currentMessageId == id) {
        updateDetailPanelStatus('archived');
    }
    await fetchMessages();
    if (currentMessageId == id) {
        conversationDetail.innerHTML = '<div class="detailPlaceholder"><i class="fas fa-comment-dots"></i><p>Select a conversation</p></div>';
        currentMessageId = null;
    }
}

async function submitReply(id, btn) {
    if (btn.disabled) return;
    const replyText = document.getElementById('replyText')?.value;
    if (!replyText || !replyText.trim()) {
        UI.toastError('Please enter a reply');
        return;
    }
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    btn.disabled = true;
    const success = await sendReply(id, replyText);
    
    if (success) {
        btn.innerHTML = '<i class="fas fa-check"></i> Sent!';
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 1500);
    } else {
        btn.innerHTML = originalText;
        btn.disabled = false;
        UI.toastError('Error sending reply');
    }
}

async function saveNotes(id, btn) {
    const notes = document.getElementById('notesText')?.value || '';
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    const success = await updateNotes(id, notes);
    
    if (success) {
        btn.innerHTML = '<i class="fas fa-check"></i> Saved!';
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 1500);
    } else {
        btn.innerHTML = originalText;
        btn.disabled = false;
        UI.toastError('Error saving notes');
    }
}

function refreshRelativeTimes() {
    document.querySelectorAll('[data-time]').forEach(el => {
        const date = el.dataset.time;
        const icon = el.querySelector('i');
        const text = formatRelativeTime(date);

        if (icon) {
            el.innerHTML = `${icon.outerHTML} ${text}`;
        } else {
            el.textContent = text;
        }
    });
}

setInterval(refreshRelativeTimes, 60000);

function formatRelativeTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) return 'Just now';
    if (diff < 3600000) {
        const mins = Math.floor(diff / 60000);
        return `${mins} minute${mins !== 1 ? 's' : ''} ago`;
    } else if (diff < 86400000) {
        const hours = Math.floor(diff / 3600000);
        return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
    } else if (diff < 604800000) {
        const days = Math.floor(diff / 86400000);
        return `${days} day${days !== 1 ? 's' : ''} ago`;
    } else {
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
}

function formatFullDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function startSSE() {
    const source = new EventSource(`api/message-stream?last_id=${latestMessageId}`);

    source.addEventListener('new_message', event => {
        const message = JSON.parse(event.data);
        latestMessageId = Math.max(latestMessageId, Number(message.id));
        prependConversation(message);
        UI.toastSuccess(`New message from ${message.full_name}`);
    });

    source.onerror = () => {
        source.close();

        setTimeout(() => {
            startSSE();
        }, 5000);
    };
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function initAutoResizeTextarea() {
    const textarea = document.getElementById('replyText');
    if (!textarea) return;

    const resize = () => {
        textarea.style.height = '44px';
        const maxHeight = 170;
        textarea.style.height = Math.min(textarea.scrollHeight, maxHeight) + 'px';
        textarea.style.overflowY = textarea.scrollHeight > maxHeight ? 'auto' : 'hidden';
    };

    textarea.addEventListener('input', resize);
    resize();

    textarea.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const sendBtn = document.querySelector('[data-action="submitReply"]');
            sendBtn?.click();
        }
    });
}

// ========== Event Listeners ==========
searchInput?.addEventListener('input', debounce(() => {
    fetchMessages();
}, 300));

document.querySelector('#statusFilterDropdown .uiDropdownTrigger')
    ?.addEventListener('dropdown:change', e => {

        document.getElementById('statusFilterDropdown').dataset.value = e.detail.value;
        fetchMessages();
});

document.querySelector('#inquiryFilterDropdown .uiDropdownTrigger')
    ?.addEventListener('dropdown:change', e => {

        document.getElementById('inquiryFilterDropdown').dataset.value = e.detail.value;
        fetchMessages();
});

document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const messageId = params.get('message');

    if (messageId) { loadMessageDetail(messageId); }
});

// Initial load
(async () => {
    await fetchMessages();
    startSSE();
})();