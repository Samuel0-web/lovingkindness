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

// ========== Reply Templates ==========
const REPLY_TEMPLATES = {
    admissions: {
        label: 'Admissions',
        icon: 'fas fa-door-open',
        body: `Thank you for your interest in joining us!\n\nWe'd love to help you through the admissions process. Please visit our admissions page for full requirements and upcoming intake dates. If you have any specific questions about eligibility or documentation, feel free to reply and we'll guide you every step of the way.\n\nWarm regards,\nAdmissions Team`
    },
    technical: {
        label: 'Technical Support',
        icon: 'fas fa-terminal',
        body: `Thank you for reaching out — we're sorry to hear you're having trouble.\n\nTo help us resolve this as quickly as possible, could you share:\n- The device and browser you're using\n- Steps to reproduce the issue\n- Any error messages you've seen\n\nWe'll look into it and get back to you shortly.\n\nBest,\nTechnical Support Team`
    },
    tutoring: {
        label: 'Tutoring',
        icon: 'fas fa-graduation-cap',
        body: `Thank you for your interest in our tutoring services!\n\nWe offer personalised one-on-one sessions tailored to your goals and schedule. To match you with the right tutor, could you let us know:\n- Subject area(s) you need help with\n- Your current level\n- Preferred days and times\n\nLooking forward to supporting your learning journey!\n\nKind regards,\nTutoring Team`
    },
    'teacher-training': {
        label: 'Teacher Training',
        icon: 'fas fa-chalkboard-teacher',
        body: `Thank you for your interest in our teacher training programmes!\n\nWe offer professional development courses for educators at every stage of their career — from foundational certifications to advanced specialisations.\n\nPlease share your current teaching context and the areas you'd like to develop, and we'll recommend the best fit for you.\n\nKind regards,\nTeacher Training Team`
    },
    feedback: {
        label: 'Feedback',
        icon: 'fas fa-star',
        body: `Thank you so much for taking the time to share your feedback — it really means a lot to us.\n\nWe take all comments seriously and will use your input to continue improving. If there's anything specific you'd like us to follow up on, please don't hesitate to let us know.\n\nGratefully,\nThe Support Team`
    },
    general: {
        label: 'General Inquiry',
        icon: 'fas fa-comment',
        body: `Thank you for getting in touch!\n\nWe've received your message and will be in touch within 1–2 business days. If your matter is urgent, please call us directly and we'll be happy to assist.\n\nBest regards,\nSupport Team`
    }
};

const TemplateManager = (() => {
    const SESSION_KEY = 'inbox_last_template';

    function getLastUsed() {
        try { return sessionStorage.getItem(SESSION_KEY); } catch (e) { return null; }
    }

    function setLastUsed(key) {
        try { sessionStorage.setItem(SESSION_KEY, key); } catch (e) {}
    }

    function apply(key) {
        const template = REPLY_TEMPLATES[key];
        if (!template) return;
        const textarea = document.getElementById('replyText');
        if (!textarea) return;
        textarea.value = template.body;
        textarea.dispatchEvent(new Event('input')); // triggers auto-resize + draft save
        textarea.focus();
        setLastUsed(key);
    }

    function open() {
        const dropdown = document.getElementById('templateDropdown');
        const btn = document.querySelector('[data-action="toggleTemplates"]');
        if (!dropdown || !btn) return;

        // Mark last used option before opening
        const lastUsed = getLastUsed();
        dropdown.querySelectorAll('.templateOption').forEach(opt => {
            const pill = opt.querySelector('.lastUsedPill');
            if (lastUsed && opt.dataset.template === lastUsed) {
                if (!pill) {
                    const p = document.createElement('span');
                    p.className = 'lastUsedPill';
                    p.textContent = 'Last used';
                    opt.appendChild(p);
                }
            } else if (pill) {
                pill.remove();
            }
        });

        dropdown.classList.add('open');
        btn.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');

        // Defer document listener so the current click doesn't immediately close it
        setTimeout(() => {
            document.addEventListener('click', _onOutsideClick, { once: true });
        }, 0);
    }

    function close() {
        const dropdown = document.getElementById('templateDropdown');
        const btn = document.querySelector('[data-action="toggleTemplates"]');
        if (dropdown) dropdown.classList.remove('open');
        if (btn) { btn.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); }
    }

    function toggle() {
        const dropdown = document.getElementById('templateDropdown');
        if (dropdown?.classList.contains('open')) { close(); } else { open(); }
    }

    function _onOutsideClick(e) {
        const bar = document.getElementById('templateBar');
        if (bar && !bar.contains(e.target)) close();
    }

    return { apply, open, close, toggle };
})();

function renderTemplateDropdownHtml() {
    return Object.entries(REPLY_TEMPLATES).map(([key, tmpl]) => `
        <button class="templateOption" data-action="selectTemplate" data-template="${key}" role="menuitem">
            <i class="${tmpl.icon}" aria-hidden="true"></i>
            ${escapeHtml(tmpl.label)}
        </button>
    `).join('');
}

// ========== Draft Manager ==========
const DraftManager = (() => {
    const PREFIX = 'inbox_draft_';
    let _autoSaveTimer = null;

    function _key(id) {
        return `${PREFIX}${id}`;
    }

    function save(id, text) {
        if (!id) return;
        try {
            if (text.trim() === '') {
                localStorage.removeItem(_key(id));
            } else {
                localStorage.setItem(_key(id), text);
            }
        } catch (e) {
            console.warn('Draft save failed:', e);
        }
    }

    function get(id) {
        if (!id) return null;
        try {
            return localStorage.getItem(_key(id));
        } catch (e) {
            return null;
        }
    }

    function remove(id) {
        if (!id) return;
        try {
            localStorage.removeItem(_key(id));
        } catch (e) {}
    }

    function startAutoSave(id, getTextFn) {
        stopAutoSave();
        _autoSaveTimer = setInterval(() => {
            save(id, getTextFn());
        }, 5000);
    }

    function stopAutoSave() {
        if (_autoSaveTimer) {
            clearInterval(_autoSaveTimer);
            _autoSaveTimer = null;
        }
    }

    return { save, get, remove, startAutoSave, stopAutoSave };
})();

// ========== Status Badge Helper ==========
const statusBadgeConfig = {
    unread:   { icon: 'fas fa-circle',  label: 'Unread'   },
    read:     { icon: 'fas fa-check',   label: 'Read'     },
    replied:  { icon: 'fas fa-reply',   label: 'Replied'  },
    archived: { icon: 'fas fa-archive', label: 'Archived' },
    spam:     { icon: 'fas fa-ban',     label: 'Spam'     },
};

function getStatusBadgeHtml(status) {
    const cfg = statusBadgeConfig[status] || statusBadgeConfig.read;
    return `<span class="statusBadge status-${status}">
        <i class="${cfg.icon}" aria-hidden="true"></i>
        <span>${cfg.label}</span>
    </span>`;
}

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
                    ${getStatusBadgeHtml('unread')}
                </div>

                <div class="conversationSubject">
                    ${escapeHtml(message.subject || '')}
                </div>

                <div class="conversationPreview">
                    ${escapeHtml(cleanPreviewText(message.message))}
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
        const inquiry = inquiryTypes[message.inquiry_type] || inquiryTypes.general;
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
                        ${getStatusBadgeHtml(message.status)}
                    </div>

                    <div class="conversationSubject">
                        ${escapeHtml(message.subject || '')}
                    </div>

                    <div class="conversationPreview">
                        ${escapeHtml(cleanPreviewText(message.message))}
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

// FIND & REPLACE the whole function:
function updateConversationItemStatus(id, status) {
    const item = document.querySelector(`.conversationItem[data-id="${id}"]`);
    if (!item) return;
    item.classList.remove('unread', 'read', 'replied', 'archived', 'spam');
    item.classList.add(status);

    const existingBadge = item.querySelector('.statusBadge');
    if (existingBadge) {
        existingBadge.outerHTML = getStatusBadgeHtml(status);
    }
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
    DraftManager.stopAutoSave();
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
            initDraftSaving(id);
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
                <div class="templateBar" id="templateBar">
                    <button class="templateBtn" data-action="toggleTemplates" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-bolt" aria-hidden="true"></i>
                        Templates
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="templateDropdown" id="templateDropdown" role="menu">
                        ${renderTemplateDropdownHtml()}
                    </div>
                </div>
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
    } else if (action === 'toggleTemplates') {
        TemplateManager.toggle();
    } else if (action === 'selectTemplate') {
        const key = btn.dataset.template;
        TemplateManager.apply(key);
        TemplateManager.close();
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
        DraftManager.remove(id);       // wipe draft on success
        DraftManager.stopAutoSave();   // stop the interval too
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

// ========== Preview Text Helper ==========
function cleanPreviewText(text, maxLength = 160) {
    if (!text) return '';
    return text
        .replace(/\r\n|\r|\n/g, ' ')   // flatten line breaks to spaces
        .replace(/\s+/g, ' ')           // collapse multiple spaces/tabs
        .replace(/<[^>]*>/g, '')        // strip any accidental HTML
        .trim()
        .substring(0, maxLength);
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

function initDraftSaving(id) {
    const textarea = document.getElementById('replyText');
    if (!textarea) return;

    // Restore draft if one exists
    const draft = DraftManager.get(id);
    if (draft) {
        textarea.value = draft;
        textarea.dispatchEvent(new Event('input')); // triggers auto-resize
        showDraftIndicator();
    }

    // Save on every keystroke (debounced 500ms)
    const debouncedSave = debounce(() => {
        DraftManager.save(id, textarea.value);
    }, 500);

    textarea.addEventListener('input', debouncedSave);

    // Belt-and-suspenders: also auto-save every 5 seconds
    DraftManager.startAutoSave(id, () => textarea.value);
}

function showDraftIndicator() {
    const actions = document.querySelector('.composerActions');
    if (!actions || document.getElementById('draftIndicator')) return;
    const indicator = document.createElement('span');
    indicator.id = 'draftIndicator';
    indicator.className = 'draftIndicator';
    indicator.innerHTML = '<i class="fas fa-pencil-alt"></i> Draft restored';
    actions.appendChild(indicator);

    // Fade out after 3 seconds
    setTimeout(() => {
        indicator.classList.add('fading');
        setTimeout(() => indicator.remove(), 300);
    }, 3000);
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