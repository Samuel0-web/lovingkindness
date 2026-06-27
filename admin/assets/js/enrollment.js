// ========== PREMIUM ENROLLMENT MANAGER ==========
// Complete rewrite with AJAX frontend filtering, status updates, and swipe-to-close drawer

// ========== DEVICE PARSING UTILITY ==========
const parseUserAgent = (uaString) => {
    const ua = uaString || '';
    
    // Browser detection
    let browser = 'Unknown';
    let browserVersion = '';
    if (ua.includes('Edg/')) {
        browser = 'Edge';
        const match = ua.match(/Edg\/(\d+)/);
        if (match) browserVersion = match[1];
    }  else if (ua.includes('Chrome/')) {
        browser = 'Chrome';
        const match = ua.match(/Chrome\/(\d+)/);
        if (match) browserVersion = match[1];
    } else if (ua.includes('Firefox/')) {
        browser = 'Firefox';
        const match = ua.match(/Firefox\/(\d+)/);
        if (match) browserVersion = match[1];
    } else if (ua.includes('Safari/') && !ua.includes('Chrome')) {
        browser = 'Safari';
        const match = ua.match(/Version\/(\d+)/);
        if (match) browserVersion = match[1];
    }
    
    // OS detection
    let os = 'Unknown';
    if (ua.includes('Windows NT 10.0')) os = 'Windows 11/10';
    else if (ua.includes('Windows NT 6.1')) os = 'Windows 7';
    else if (ua.includes('Mac OS X')) os = 'macOS';
    else if (ua.includes('iPhone') || ua.includes('iPad')) os = 'iOS';
    else if (ua.includes('Android')) os = 'Android';
    else if (ua.includes('Linux')) os = 'Linux';
    
    // Device type
    let deviceType = 'Desktop';
    if (ua.includes('iPad')) {
        deviceType = 'Tablet';
    } else if (ua.includes('Mobile')) {
        deviceType = 'Mobile';
    }
    
    return { browser, browserVersion, os, deviceType };
};

// ========== DOM ELEMENTS ==========
const searchInput = document.getElementById('searchInput');
const resetBtn = document.getElementById('resetFiltersBtn');
const modal = document.getElementById('detailModal');
const drawer = document.getElementById('bottomDrawer');
const modalBody = document.getElementById('modalBody');
const drawerBody = document.getElementById('drawerBody');
const enrollmentGrid = document.querySelector('.enrollmentGrid');
const closeModalBtns = document.querySelectorAll('#closeModalBtn, #closeModalFooterBtn');
const drawerStickyArea = document.querySelector('.drawerStickyArea');
const isMobile = () => window.innerWidth <= 768;
let selectedIds = new Set();
const bulkActionBar = document.getElementById('bulkActionBar');
const bulkCountEl = document.getElementById('bulkCount');
const bulkClearBtn = document.getElementById('bulkClearBtn');
const bulkStatusDropdown = document.getElementById('bulkStatusDropdown');
const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
let programValue = '';
let statusValue = '';
let enrollmentCards = document.querySelectorAll('.enrollmentCard');

// ========== HELPER FUNCTIONS ==========
function escapeHtml(str) {
    if (str === null || str === undefined) return '';

    str = String(str);
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatStatus(status) {
    const statusMap = {
        'pending': 'Pending',
        'contacted': 'Contacted',
        'consultation_booked': 'Consultation Booked',
        'enrolled': 'Enrolled',
        'rejected': 'Rejected'
    };
    return statusMap[status] || status;
}

let searchDebounce;

const fetchEnrollments = async () => {
    const search = searchInput?.value || '';
    const program = programValue;
    const status = statusValue;

    try {
        enrollmentGrid.innerHTML = `
            <div class="emptyState">
            <svg class="loadingSpinner" viewBox="25 25 50 50">
                <circle class="path" cx="50" cy="50" r="20"></circle>
            </svg>
        </div>
        `;

        const params = new URLSearchParams({
            action: 'search',
            search,
            program,
            status
        });

        const res = await fetch(`api/enrollment?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await res.json();
        if (!data.ok) { throw new Error(data.e || 'Failed'); }
        // Clear bulk selection on new data
        selectedIds.clear();
        updateBulkUI();
        renderEnrollmentCards(data.enrollments);
    } catch (err) {
        enrollmentGrid.innerHTML = `
            <div class="emptyState">
                <h3>Failed to load enrollments</h3>
            </div>
        `;
    }
};

const parseDate = (dateString) => {
    if (!dateString) { return null; }
    return new Date( dateString.replace(' ', 'T'));
};

const renderEnrollmentCards = (enrollments) => {
    if (!enrollments.length) {
        enrollmentGrid.innerHTML = getEmptyState();

        document.getElementById('clearFiltersBtn')?.addEventListener('click', () => {
            resetBtn?.click();
        });

        return;
    }

    enrollmentGrid.innerHTML = enrollments.map(enrollment => {
        const initials = (enrollment.full_name || '')
            .split(' ')
            .map(n => n[0])
            .slice(0, 2)
            .join('')
            .toUpperCase();

        return `
            <div class="enrollmentCard" data-id="${enrollment.id}">
                <label class="bulkCheckbox" data-id="${enrollment.id}">
                    <input type="checkbox" class="bulkCheckbox__input" data-id="${enrollment.id}">
                    <span class="bulkCheckbox__visual">
                        <i class="fas fa-check"></i>
                    </span>
                </label>
                <div class="cardHeader">
                    <div class="enrolleeInfo">
                        <div class="enrolleeAvatar">${escapeHtml(initials)}</div>

                        <div class="enrolleeDetails">
                            <h4>${escapeHtml(enrollment.full_name)}</h4>
                            <span class="enrolleeId">#${enrollment.id}</span>
                        </div>
                    </div>

                    <div class="cardActions">
                        <button class="actionIcon viewBtn" data-id="${enrollment.id}">
                            <i class="fas fa-eye"></i>
                        </button>

                        <button class="actionIcon deleteBtn" data-id="${enrollment.id}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>

                <div class="cardBody">
                    <div class="infoRow">
                        <span class="infoLabel">Program</span>

                        <span class="programBadge ${enrollment.program === 'tutoring' ? 'tutoring' : 'training'}">
                            ${enrollment.program === 'tutoring' ? 'Tutoring' : 'Teacher Training'}
                        </span>
                    </div>

                    <div class="infoRow">
                        <span class="infoLabel">Contact</span>

                        <div class="contactInfo">
                            <span class="contactEmail">${escapeHtml(enrollment.email)}</span>
                            <span class="contactPhone">${escapeHtml(enrollment.phone)}</span>
                        </div>
                    </div>

                    ${enrollment.program === 'tutoring' ? `
                    <div class="infoRow">
                        <span class="infoLabel">Student</span>
                        <span class="infoValue">
                            ${escapeHtml(enrollment.student_name || '')}
                            ${enrollment.grade ? `(Grade ${escapeHtml(enrollment.grade)})` : ''}
                        </span>
                    </div>

                    <div class="infoRow">
                        <span class="infoLabel">Subject</span>
                        <span class="infoValue">
                            ${escapeHtml(enrollment.subject || '')}
                        </span>
                    </div>
                ` : `
                    <div class="infoRow">
                        <span class="infoLabel">Note</span>
                        <span class="infoValue">
                            ${escapeHtml(
                                (enrollment.additional_info || 'No additional info')
                                .substring(0, 60)
                            )}
                        </span>
                    </div>
                `}

                <div class="infoRow">
                    <span class="infoLabel">Schedule</span>
                    <span class="scheduleValue">
                        ${escapeHtml(enrollment.preferred_time || '')}
                    </span>
                </div>
                </div>

                <div class="cardFooter">
                    <div class="statusWrapper">
                        <div class="uiDropdown statusDropdown">
                            <button
                                class="uiDropdownTrigger statusTrigger"
                                data-id="${enrollment.id}"
                                data-current="${enrollment.status}"
                                data-value="${enrollment.status}"
                            >
                                ${formatStatus(enrollment.status)}
                                <i class="fas fa-chevron-down"></i>
                            </button>

                            <div class="uiDropdownMenu">
                                <button data-value="pending">Pending</button>
                                <button data-value="contacted">Contacted</button>
                                <button data-value="consultation_booked">Consultation</button>
                                <button data-value="enrolled">Enrolled</button>
                                <button data-value="rejected">Rejected</button>
                            </div>
                        </div>
                    </div>
                    <div class="cardMeta">
                            <span class="dateChip">
                                <i class="far fa-calendar-alt"></i>
                                ${timeAgo(parseDate(enrollment.created_at))}
                            </span>
                        </div>
                </div>
            </div>
        `;

    }).join('');

    attachViewHandlers();
    attachDeleteHandlers();
    attachBulkCheckboxes();
};

const updateStatusDropdown = async (trigger, newStatus) => {
    const id = trigger.dataset.id;
    const oldStatus = trigger.dataset.value;
    if (oldStatus === newStatus) { return; }

    try {
        trigger.disabled = true;

        const res = await fetch('api/enrollment?action=updateStatus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                id,
                status: newStatus
            })
            
        });

        const data = await res.json();
        if (!data.ok) { throw new Error(data.e || 'Update failed'); }

        trigger.dataset.value = newStatus;
        trigger.dataset.current = newStatus;

        trigger.innerHTML = `
            ${formatStatus(newStatus)}
            <i class="fas fa-chevron-down"></i>
        `;

        UI.toastSuccess('Status updated');

    } catch (err) {
        UI.toastError(err.message || 'Failed to update status');
    } finally {
        trigger.disabled = false;
    }
};

document.addEventListener('click', async (e) => {
    const option = e.target.closest('.statusDropdown .uiDropdownMenu button');

    if (option) {
        const dropdown = option.closest('.statusDropdown');
        const trigger = dropdown.querySelector('.statusTrigger');

        await updateStatusDropdown(
            trigger,
            option.dataset.value
        );

        dropdown.classList.remove('open');
        return;
    }
});

document.querySelector('#programDropdown .uiDropdownTrigger')
    ?.addEventListener('dropdown:change', e => {

        programValue = e.detail.value;
        fetchEnrollments();

});

document.querySelector('#statusDropdown .uiDropdownTrigger')
    ?.addEventListener('dropdown:change', e => {

        statusValue = e.detail.value;
        fetchEnrollments();
});

const handleSearchInput = () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => { fetchEnrollments(); }, 300);
    selectedIds.clear();
    updateBulkUI();
};

searchInput?.addEventListener('input', handleSearchInput);

resetBtn?.addEventListener('click', () => {
    searchInput.value = '';
    programValue = '';
    statusValue = '';
    const programTrigger = document.querySelector('#programDropdown .uiDropdownTrigger');
    const statusTrigger = document.querySelector('#statusDropdown .uiDropdownTrigger');
    if (programTrigger) { programTrigger.childNodes[0].textContent = 'All Programs '; }
    if (statusTrigger) { statusTrigger.childNodes[0].textContent = 'All Statuses '; }
    selectedIds.clear();
    updateBulkUI();
    fetchEnrollments();
});

function getEmptyState() {
    const hasSearch = searchInput?.value.trim();
    const hasProgram = !!programValue;
    const hasStatus = !!statusValue;

    if (hasSearch) {
        return `
            <div class="emptyState">
                <div class="emptyIcon">
                    <i class="fas fa-search"></i>
                </div>

                <h3>No results for "${escapeHtml(hasSearch)}"</h3>

                <p>Try a different search term.</p>
            </div>
        `;
    }

    if (hasProgram || hasStatus) {
        return `
            <div class="emptyState">
                <div class="emptyIcon">
                    <i class="fas fa-filter"></i>
                </div>

                <h3>No matching enrollments</h3>

                <p>Your current filters returned no results.</p>
            </div>
        `;
    }

    return `
        <div class="emptyState">
            <div class="emptyIcon">
                <i class="fas fa-user-graduate"></i>
            </div>

            <h3>No enrollments yet</h3>

            <p>New enrollment submissions will appear here automatically.</p>
        </div>
    `;
}

// Command + K shortcut
document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        searchInput?.focus();
    }
});

// ========== VIEW DETAILS (MODAL / DRAWER) ==========
const timeAgo = (date) => {
    const seconds = Math.floor((Date.now() - date.getTime()) / 1000);
    if (seconds < 60) { return 'Just now'; }

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) { return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`; }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) { return `${hours} hour${hours !== 1 ? 's' : ''} ago`; }

    const days = Math.floor(hours / 24);
    if (days <= 7) { return `${days} day${days !== 1 ? 's' : ''} ago`; }

    return date.toLocaleDateString();
};

const showDetailContent = (enrollment, container) => {
    const deviceInfo = parseUserAgent(enrollment.user_agent);
    const createdDate = parseDate(enrollment.created_at);
    const updatedDate = parseDate(enrollment.updated_at);
    
    container.innerHTML = `
        <div class="detailSection">
            <div class="detailSectionTitle">Personal Information</div>
            <div class="detailGrid">
                <div class="detailItem">
                    <span class="detailItemLabel">Full Name</span>
                    <span class="detailItemValue">${escapeHtml(enrollment.full_name)}</span>
                </div>
                <div class="detailItem">
                    <span class="detailItemLabel">Email</span>
                    <span class="detailItemValue">${escapeHtml(enrollment.email)}</span>
                </div>
                <div class="detailItem">
                    <span class="detailItemLabel">Phone</span>
                    <span class="detailItemValue">${escapeHtml(enrollment.phone)}</span>
                </div>
                <div class="detailItem">
                    <span class="detailItemLabel">Country</span>
                    <span class="detailItemValue">${escapeHtml(enrollment.country)}</span>
                </div>
            </div>
        </div>
        ${enrollment.program === 'tutoring' ? `
            <div class="detailSection">
                <div class="detailSectionTitle">Academic Information</div>
                <div class="detailGrid">
                    <div class="detailItem">
                        <span class="detailItemLabel">Student Name</span>
                        <span class="detailItemValue">${escapeHtml(enrollment.student_name || 'N/A')}</span>
                    </div>
                    <div class="detailItem">
                        <span class="detailItemLabel">Grade Level</span>
                        <span class="detailItemValue">${escapeHtml(enrollment.grade || 'N/A')}</span>
                    </div>
                    <div class="detailItem">
                        <span class="detailItemLabel">Subject</span>
                        <span class="detailItemValue">${escapeHtml(enrollment.subject || 'N/A')}</span>
                    </div>
                </div>
            </div>
        ` : ''}
        <div class="detailSection">
            <div class="detailSectionTitle">Program Details</div>
            <div class="detailGrid">
                <div class="detailItem">
                    <span class="detailItemLabel">Program Type</span>
                    <span class="detailItemValue">${enrollment.program === 'tutoring' ? 'Tutoring' : 'Teacher Training'}</span>
                </div>
                <div class="detailItem">
                    <span class="detailItemLabel">Preferred Schedule</span>
                    <span class="detailItemValue">${escapeHtml(enrollment.preferred_time)}</span>
                </div>
                <div class="detailItem">
                    <span class="detailItemLabel">Current Status</span>
                    <span class="detailItemValue">${formatStatus(enrollment.status)}</span>
                </div>
                <div class="detailItem">
                    <span class="detailItemLabel">Enrollment ID</span>
                    <span class="detailItemValue">#${enrollment.id}</span>
                </div>
            </div>
        </div>
        ${enrollment.additional_info ? `
        <div class="detailSection">
            <div class="detailSectionTitle">Additional Notes</div>
            <div class="detailItem">
                <span class="detailItemValue"><p>${escapeHtml(enrollment.additional_info)}</p></span>
            </div>
        </div>
        ` : ''}
        <div class="detailSection">
            <div class="detailSectionTitle">Technical Metadata</div>
            <div class="detailItem" style="margin-bottom: 16px;">
                <span class="detailItemLabel">IP Address</span>
                <span class="detailItemValue">${escapeHtml(enrollment.ip_address || 'Not recorded')}</span>
            </div>
            <div class="deviceInfo">
                <div class="deviceIcon">
                    <i class="fas ${deviceInfo.deviceType === 'Mobile' ? 'fa-mobile-alt' : deviceInfo.deviceType === 'Tablet' ? 'fa-tablet-alt' : 'fa-desktop'}"></i>
                </div>
                <div class="deviceDetails">
                    <strong>${deviceInfo.browser} ${deviceInfo.browserVersion}</strong>
                    <span>${deviceInfo.os} • ${deviceInfo.deviceType}</span>
                </div>
            </div>
        </div>
        <div class="detailSection">
            <div class="detailSectionTitle">Timeline</div>
            <div class="detailGrid">
                <div class="detailItem">
                    <span class="detailItemLabel">Created</span>
                    <span class="detailItemValue">${createdDate.toLocaleString()} <span style="color: var(--gray-600);">(${timeAgo(createdDate)})</span></span>
                </div>
                <div class="detailItem">
                    <span class="detailItemLabel">Last Updated</span>
                    <span class="detailItemValue">${updatedDate.toLocaleString()}</span>
                </div>
            </div>
        </div>
    `;
};

// ========== AJAX DELETE (NO PAGE RELOAD) ==========
const attachDeleteHandlers = () => {
    const deleteBtns = document.querySelectorAll('.deleteBtn');
    deleteBtns.forEach(btn => {
        btn.removeEventListener('click', handleDeleteClick);
        btn.addEventListener('click', handleDeleteClick);
    });
};

// ========== BULK SELECTION ==========
const updateBulkUI = () => {
    const count = selectedIds.size;
    
    if (bulkCountEl) {
        bulkCountEl.textContent = `✓ ${count} selected`;
    }
    
    // Show/hide bulk bar
    if (count > 0) {
        bulkActionBar?.classList.add('bulkActionBar--visible');
    } else {
        bulkActionBar?.classList.remove('bulkActionBar--visible');
    }
    
    // Toggle bulk mode on cards
    document.querySelectorAll('.enrollmentCard').forEach(card => {
        const id = card.dataset.id;
        if (selectedIds.size > 0) {
            card.classList.add('enrollmentCard--bulk-mode');
        } else {
            card.classList.remove('enrollmentCard--bulk-mode');
        }
        
        if (selectedIds.has(id)) {
            card.classList.add('enrollmentCard--selected');
        } else {
            card.classList.remove('enrollmentCard--selected');
        }
        
        const checkbox = card.querySelector('.bulkCheckbox__input');
        if (checkbox) {
            checkbox.checked = selectedIds.has(id);
        }
    });
};

const handleCheckboxChange = (e) => {
    e.stopPropagation();
    const checkbox = e.target;
    const id = checkbox.dataset.id;
    
    if (checkbox.checked) {
        selectedIds.add(id);
    } else {
        selectedIds.delete(id);
    }
    
    updateBulkUI();
};

const attachBulkCheckboxes = () => {
    document.querySelectorAll('.bulkCheckbox__input').forEach(cb => {
        cb.removeEventListener('change', handleCheckboxChange);
        cb.addEventListener('change', handleCheckboxChange);
    });
};

// Clear selection
bulkClearBtn?.addEventListener('click', () => {
    selectedIds.clear();
    updateBulkUI();
});

// Bulk status change via uiDropdown
document.querySelector('#bulkStatusDropdown .uiDropdownTrigger')?.addEventListener('dropdown:change', (e) => {
    const newStatus = e.detail.value;
    if (!newStatus || selectedIds.size === 0) return;
    
    // Backend logic placeholder
    console.log('Bulk status change:', [...selectedIds], 'to', newStatus);
    UI.toastSuccess(`Status updated for ${selectedIds.size} enrollments`);
    
    selectedIds.clear();
    updateBulkUI();
    
    // Reset dropdown display
    const trigger = document.querySelector('#bulkStatusDropdown .uiDropdownTrigger');
    if (trigger) {
        trigger.childNodes[0].textContent = 'Change Status ';
    }
});

// Bulk delete
bulkDeleteBtn?.addEventListener('click', async () => {
    if (selectedIds.size === 0) return;
    
    const confirmed = await UI.confirmDelete(
        `This will permanently delete ${selectedIds.size} enrollment record(s). This action cannot be undone.`,
        'Bulk Delete?'
    );
    
    if (!confirmed) return;
    
    // Backend logic placeholder
    console.log('Bulk delete:', [...selectedIds]);
    UI.toastSuccess(`${selectedIds.size} enrollments deleted`);
    
    selectedIds.clear();
    updateBulkUI();
});

const handleDeleteClick = async (e) => {
    const btn = e.currentTarget;
    const id = btn.dataset.id;
    const card = btn.closest('.enrollmentCard');
    
    const confirmed = await UI.confirmDelete(
        'This will permanently delete this enrollment record. This action cannot be undone.',
        'Delete Enrollment?'
    );
    
    if (!confirmed) return;
    
    try {
        const res = await fetch(`api/enrollment?action=delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        
        if (data.ok) {
            UI.toastSuccess('Enrollment deleted successfully');
            card.remove();
            if (!document.querySelector('.enrollmentCard')) {
                enrollmentGrid.innerHTML = getEmptyState();
            }
            attachDeleteHandlers();
            attachBulkCheckboxes();
            enrollmentCards = document.querySelectorAll('.enrollmentCard');
        } else {
            UI.toastError(data.e || 'Delete failed');
        }
    } catch (err) {
        UI.toastError('Network error');
    }
};

// ========== MODAL CLOSE ==========
const closeModal = () => {
    modal.style.display = 'none';
    document.body.style.overflow = '';
};

closeModalBtns.forEach(btn => {
    btn.addEventListener('click', closeModal);
});

const modalBackdrop = document.querySelector('.modalBackdrop');
modalBackdrop?.addEventListener('click', closeModal);

// ========== DRAWER FUNCTIONS ==========
let drawerTouchStartY = 0;
let drawerTouchCurrentY = 0;
let isDraggingDrawer = false;

const CLOSE_THRESHOLD = 120;

const closeDrawer = () => {
    drawer.classList.remove('open');
    document.body.style.overflow = '';
    drawer.style.transform = '';
    if (drawerBody) {
        drawerBody.style.transform = '';
        drawerBody.style.overflowY = 'auto';
    }
};

// Handle swipe on drawer handle
const handleDrawerTouchStart = (e) => {
    drawerTouchStartY = e.touches[0].clientY;
    isDraggingDrawer = true;
    e.stopPropagation();
};

const handleDrawerTouchMove = (e) => {
    if (!isDraggingDrawer) return;
    drawerTouchCurrentY = e.touches[0].clientY;
    const deltaY = drawerTouchCurrentY - drawerTouchStartY;
    
    if (deltaY > 0 && drawer.classList.contains('open')) {
        const translateY = Math.min(deltaY, 300);
        drawer.style.transform = `translateY(${translateY}px)`;
        drawer.style.transition = 'none';
        e.preventDefault();
    }
};

const handleDrawerTouchEnd = () => {
    if (!isDraggingDrawer) return;
    isDraggingDrawer = false;
    
    const deltaY = drawerTouchCurrentY - drawerTouchStartY;
    drawer.style.transition = '';
    
    if (deltaY > CLOSE_THRESHOLD) {
        closeDrawer();
    } else {
        drawer.style.transform = '';
    }
    
    drawerTouchStartY = 0;
    drawerTouchCurrentY = 0;
};

// Add event listeners
if (drawerStickyArea) {
    drawerStickyArea.addEventListener('touchstart', handleDrawerTouchStart, { passive: false });
    drawerStickyArea.addEventListener('touchmove', handleDrawerTouchMove, { passive: false });
    drawerStickyArea.addEventListener('touchend', handleDrawerTouchEnd);
}

// Close drawer via close buttons
const drawerCloseBtns = document.querySelectorAll('#closeDrawerBtn, #closeDrawerFooterBtn');
drawerCloseBtns.forEach(btn => {
    btn.addEventListener('click', closeDrawer);
});

// Prevent closing when clicking inside drawer content
drawer?.addEventListener('click', (e) => {
    e.stopPropagation();
});

async function openEnrollmentDetail(id) {
    try {
        const res = await fetch(`api/enrollment?action=get&id=${id}`);
        const data = await res.json();

        if (data.ok && data.enrollment) {

            if (isMobile()) {
                showDetailContent(data.enrollment, drawerBody);

                drawer.classList.add('open');
                document.body.style.overflow = 'hidden';

                if (drawerBody) {
                    drawerBody.scrollTop = 0;
                    drawerBody.style.transform = '';
                    drawerBody.style.overflowY = 'auto';
                }

                drawer.style.transform = '';
            } else {
                showDetailContent(data.enrollment, modalBody);

                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

        } else {
            UI.toastError('Failed to load details');
        }

    } catch (err) {
        UI.toastError('Network error');
    }
}

// ========== VIEW HANDLER ==========
const handleViewClick = async (e) => {
    const btn = e.currentTarget;
    const id = btn.dataset.id;
    
    try {
        const res = await fetch(`api/enrollment?action=get&id=${id}`);
        const data = await res.json();
        
        if (data.ok && data.enrollment) {
            if (isMobile()) {
                showDetailContent(data.enrollment, drawerBody);
                drawer.classList.add('open');
                document.body.style.overflow = 'hidden';
                // Reset drawer state
                if (drawerBody) {
                    drawerBody.scrollTop = 0;
                    drawerBody.style.transform = '';
                    drawerBody.style.overflowY = 'auto';
                }
                drawer.style.transform = '';
            } else {
                showDetailContent(data.enrollment, modalBody);
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        } else {
            UI.toastError('Failed to load details');
        }
    } catch (err) {
        UI.toastError('Network error');
    }
};

const attachViewHandlers = () => {
    const viewBtns = document.querySelectorAll('.viewBtn');
    viewBtns.forEach(btn => {
        btn.removeEventListener('click', handleViewClick);
        btn.addEventListener('click', handleViewClick);
    });
};

// ========== INITIALIZE ==========
const init = () => {
    attachViewHandlers();
    attachDeleteHandlers();
    attachBulkCheckboxes();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}