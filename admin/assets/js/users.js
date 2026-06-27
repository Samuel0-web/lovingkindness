document.addEventListener('DOMContentLoaded', () => {
    let editingUserId = null;
    let allCheckboxes = () => document.querySelectorAll('.user-checkbox');
    const CURRENT_USER_ID = window.APP.currentUserId;
    const searchInput = document.getElementById('searchInput');
    const selectAll = document.getElementById('selectAll');
    const tableBody = document.getElementById('usersTableBody');
    const totalCountSpan = document.getElementById('totalCount');
    const showingStartSpan = document.getElementById('showingStart');
    const showingEndSpan = document.getElementById('showingEnd');
    const totalUsers = window.APP.totalUsers;
    const paginationExists = document.getElementById('totalCount') !== null;
    const emptyState = document.getElementById('umEmptyState');
    const emptyStateTitle = document.getElementById('emptyStateTitle');
    
    // Bulk delete elements
    const bulkActionBar = document.getElementById('bulkActionBar');
    const selectedCountSpan = document.getElementById('selectedCount');
    const cancelBulkBtn = document.getElementById('cancelBulkBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    let pendingDeleteIds = [];

    // ========== DEVICE DETECTION ==========
    const isMobile = () => window.innerWidth <= 768;
    if (totalCountSpan) totalCountSpan.textContent = totalUsers;

    const totalPages = Math.ceil(window.APP.totalUsers / window.APP.perPage);
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    
    if (prevBtn && nextBtn) {
        prevBtn.disabled = window.APP.currentPage <= 1;
        nextBtn.disabled = window.APP.currentPage >= totalPages;

        nextBtn.onclick = () => {
            if (window.APP.currentPage < totalPages) {
                window.location.search = `?page=${window.APP.currentPage + 1}`;
            }
        };

        prevBtn.onclick = () => {
            if (window.APP.currentPage > 1) {
                window.location.search = `?page=${window.APP.currentPage - 1}`;
            }
        };
    }

    function showEmptyState(searchTerm) {
        if (!emptyState) return;
        if (!searchTerm || searchTerm.trim().length === 0) {
            hideEmptyState();
            return;
        }
        
        const tableWrapper = document.querySelector('.um-table-wrapper');
        const cardsContainer = document.getElementById('usersCards');
        if (tableWrapper) tableWrapper.style.display = 'none';
        if (cardsContainer) cardsContainer.style.display = 'none';
        if (emptyStateTitle) emptyStateTitle.textContent = `No results for "${escapeHtml(searchTerm)}"`;
        emptyState.style.display = 'flex';
    }
    
    function hideEmptyState() {
        if (!emptyState) return;
        emptyState.style.display = 'none';
        const tableWrapper = document.querySelector('.um-table-wrapper');
        const cardsContainer = document.getElementById('usersCards');
        
        // Restore display based on mobile/desktop
        if (tableWrapper) tableWrapper.style.display = '';
        if (cardsContainer) {
            cardsContainer.style.display = isMobile() ? 'flex' : '';
        }
    }

    const rows = tableBody.querySelectorAll('tr');
    const cards = document.querySelectorAll('.um-card');

    function filterUsers() {
        const searchTerm = searchInput.value.toLowerCase();
        const hasSearch = searchInput.value.trim().length > 0;
        let visibleCount = 0;
        
        rows.forEach(row => {
            const name = row.querySelector('.um-user-name')?.textContent.toLowerCase() || '';
            const email = row.cells[2]?.textContent.toLowerCase() || '';
            const matches = name.includes(searchTerm) || email.includes(searchTerm);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        cards.forEach(card => {
            const name = card.querySelector('.um-card-name')?.textContent.toLowerCase() || '';
            const email = card.querySelectorAll('.um-card-detail .um-detail-value')[0]?.textContent.toLowerCase() || '';
            const matches = name.includes(searchTerm) || email.includes(searchTerm);
            card.style.display = matches ? '' : 'none';
        });
        
        // Only show empty state when searching AND no results found
        if (hasSearch && visibleCount === 0) {
            showEmptyState(searchInput.value.trim());
        } else {
            hideEmptyState();
        }
        
        if (showingStartSpan && showingEndSpan) {
            const start = (window.APP.currentPage - 1) * window.APP.perPage + 1;
            const end = start + visibleCount - 1;
            showingStartSpan.textContent = visibleCount ? start : 0;
            showingEndSpan.textContent = visibleCount ? end : 0;
        }
        clearBulkSelection();
    }

    function debounce(fn, delay) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    }

    searchInput.addEventListener('input', filterUsers);

    // ========== BULK DELETE FUNCTIONS WITH ANIMATION ==========
    function hideBulkActionBarWithAnimation() {
        if (!bulkActionBar) return;
        bulkActionBar.classList.add('closing');
        setTimeout(() => {
            bulkActionBar.style.display = 'none';
            bulkActionBar.classList.remove('closing');
        }, 250);
    }

    function getSelectedUserIds() {
        const checked = document.querySelectorAll('.user-checkbox:checked');
        const ids = new Set();
        checked.forEach(cb => ids.add(cb.value));
        return Array.from(ids);
    }
    
    function updateBulkUI() {
        const selectedIds = getSelectedUserIds();
        const count = selectedIds.length;
        
        if (count > 0) {
            if (bulkActionBar) {
                if (bulkActionBar.style.display !== 'flex') {
                    bulkActionBar.style.display = 'flex';
                    bulkActionBar.classList.remove('closing');
                }
                if (selectedCountSpan) selectedCountSpan.textContent = count;
            }
        } else {
            if (bulkActionBar && bulkActionBar.style.display === 'flex') {
                hideBulkActionBarWithAnimation();
            }
        }
        
        const allCheckboxes = document.querySelectorAll('#usersTableBody .user-checkbox, .um-card .user-checkbox');
        const allVisibleCheckboxes = Array.from(allCheckboxes).filter(cb => {
            const row = cb.closest('tr');
            const card = cb.closest('.um-card');

            if (row) return row.style.display !== 'none';
            if (card) return card.style.display !== 'none';

            return true;
        });
        const visibleCheckedCount = allVisibleCheckboxes.filter(cb => cb.checked).length;
        
        if (selectAll) {
            selectAll.checked = visibleCheckedCount > 0 && visibleCheckedCount === allVisibleCheckboxes.length;
            selectAll.indeterminate = visibleCheckedCount > 0 && visibleCheckedCount < allVisibleCheckboxes.length;
        }
    }
    
    function clearBulkSelection() {
        const checkboxes = allCheckboxes();
        checkboxes.forEach(cb => cb.checked = false);
        updateBulkUI();
    }
    
    async function executeBulkDelete() {
        if (!pendingDeleteIds.length) return;

        const btn = bulkDeleteBtn;
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Deleting...';

        try {
            const res = await fetch('api/users?_api=bulk_delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                     p: JSON.stringify({ ids: pendingDeleteIds }),
                     csrf_token: window.APP.csrfToken
                })
            });

            let data;
            try {
                data = await res.json();
            } catch {
                throw new Error('Invalid server response');
            }

            if (!data.ok) {
                UI.toast(data.e || 'Bulk delete failed', 'error');
                return;
            }

            const deletedIds = data.m?.ids || [];

            if (deletedIds.length === 0) {
                UI.toast("Nothing was deleted", "warning");
                return;
            }

            // ✅ NOW remove from UI (correct timing)
            deletedIds.forEach(id => {
                document.querySelector(`tr[data-user-id="${id}"]`)?.remove();
                document.querySelector(`.um-card[data-user-id="${id}"]`)?.remove();
            });

            if (document.querySelectorAll('#usersTableBody tr').length === 0 && window.APP.currentPage > 1) {
                window.location.search = `?page=${window.APP.currentPage - 1}`;
            }

            updateUserCount();
            clearBulkSelection();

            UI.toast(`${deletedIds.length} user(s) deleted`, 'success');

        } catch (err) {
            console.error(err);
            UI.toast('Bulk delete failed', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
            pendingDeleteIds = [];
        }
    }
    
    document.addEventListener('change', (e) => {
        if (!e.target.classList.contains('user-checkbox')) return;
        const id = e.target.value;
        const checked = e.target.checked;
        document.querySelectorAll(`.user-checkbox[value="${id}"]`).forEach(cb => cb.checked = checked);
        updateBulkUI();
    });
    
    // Bulk delete event listeners
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', async () => {
            const selectedIds = getSelectedUserIds();
            if (selectedIds.length === 0) {
                UI.toast('No users selected', 'warning');
                return;
            }

            // If only YOU is selected (after backend filtering this becomes empty)
            if (selectedIds.length === 1 && selectedIds[0] == CURRENT_USER_ID) {
                UI.toast("You can't delete yourself", 'error');
                return;
            }
            
            // Use global confirm modal
            const confirmed = await UI.confirm({
                title: 'Delete Users',
                message: `Are you sure you want to delete ${selectedIds.length} user(s)?`,
                type: 'danger',
                showWarning: true,
                confirmText: 'Delete',
                cancelText: 'Cancel'
            });
            
            if (confirmed) {
                pendingDeleteIds = selectedIds;
                await executeBulkDelete();
            }
        });
    }
    
    if (cancelBulkBtn) cancelBulkBtn.addEventListener('click', clearBulkSelection);

    // ========== CHECKBOX EVENT LISTENERS ==========
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const isVisible = (el) => {
                const row = el.closest('tr');
                const card = el.closest('.um-card');
                if (row) return row.style.display !== 'none';
                if (card) return card.style.display !== 'none';
                return true;
            };
            const checkboxes = allCheckboxes();
            checkboxes.forEach(cb => { if (isVisible(cb)) cb.checked = selectAll.checked; });
            updateBulkUI();
        });
    }

    // ========== MODAL / DRAWER ELEMENTS ==========
    const modal = document.getElementById('userModal');
    const drawer = document.getElementById('userDrawer');
    const modalContent = modal?.querySelector('.um-modal-content');
    const drawerBody = document.getElementById('drawerBody');
    const addUserBtn = document.getElementById('addUserBtn');

    // ========== DRAWER TOUCH HANDLING WITH RUBBER BAND ==========
    let drawerTouchStartY = 0;
    let drawerTouchCurrentY = 0;
    let isDraggingDrawer = false;
    let drawerAnimFrame = null;
    const CLOSE_THRESHOLD = 100;
    const RUBBER_BAND_LIMIT = 150;
    const RUBBER_BAND_RESISTANCE = 0.4;

    const drawerContent = drawer?.querySelector('.drawerContent');
    const drawerBackdrop = document.getElementById('drawerBackdrop');

    const handleDrawerTouchStart = (e) => {
        // Only start dragging if touching the sticky area (handle + header)
        const stickyArea = e.target.closest('.drawerStickyArea');
        if (!stickyArea) return;
        
        drawerTouchStartY = e.touches[0].clientY;
        drawerTouchCurrentY = drawerTouchStartY;
        isDraggingDrawer = true;
        drawerContent.style.transition = 'none';
    };

    const handleDrawerTouchMove = (e) => {
        if (!isDraggingDrawer) return;
        
        drawerTouchCurrentY = e.touches[0].clientY;
        const deltaY = drawerTouchCurrentY - drawerTouchStartY;
        
        if (deltaY > 0 && drawer?.classList.contains('open')) {
            // Apply rubber band effect
            let translateY;
            if (deltaY <= RUBBER_BAND_LIMIT) {
                translateY = deltaY;
            } else {
                const excess = deltaY - RUBBER_BAND_LIMIT;
                translateY = RUBBER_BAND_LIMIT + (excess * RUBBER_BAND_RESISTANCE);
            }
            
            if (drawerAnimFrame) cancelAnimationFrame(drawerAnimFrame);
            drawerAnimFrame = requestAnimationFrame(() => {
                drawerContent.style.transform = `translateY(${translateY}px)`;
            });
            e.preventDefault();
        }
    };

    const handleDrawerTouchEnd = () => {
        if (!isDraggingDrawer) return;
        isDraggingDrawer = false;
        
        if (drawerAnimFrame) {
            cancelAnimationFrame(drawerAnimFrame);
            drawerAnimFrame = null;
        }
        
        const deltaY = drawerTouchCurrentY - drawerTouchStartY;
        
        // Add smooth transition back
        drawerContent.style.transition = 'transform 0.3s cubic-bezier(0.32, 0.72, 0, 1)';
        
        if (deltaY > CLOSE_THRESHOLD) {
            // Close drawer
            drawerContent.style.transform = 'translateY(100%)';
            setTimeout(() => {
                closeDrawer();
                drawerContent.style.transform = '';
            }, 300);
        } else {
            // Snap back
            drawerContent.style.transform = '';
            setTimeout(() => {
                drawerContent.style.transition = '';
            }, 300);
        }
        
        drawerTouchStartY = 0;
        drawerTouchCurrentY = 0;
    };

    // Attach touch listeners to drawer content (the whole sheet)
    if (drawerContent) {
        drawerContent.addEventListener('touchstart', handleDrawerTouchStart, { passive: false });
        drawerContent.addEventListener('touchmove', handleDrawerTouchMove, { passive: false });
        drawerContent.addEventListener('touchend', handleDrawerTouchEnd);
    }

    // ========== CLICK OUTSIDE TO CLOSE DRAWER ==========
    if (drawerBackdrop) {
        drawerBackdrop.addEventListener('click', () => {
            if (drawer?.classList.contains('open')) {
                closeDrawer();
            }
        });
    }

    // ========== OPEN / CLOSE FUNCTIONS ==========
    function openModal() {
        if (!modal) return;
        modal.classList.remove('closing');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            document.getElementById('userName')?.focus();
        }, 50);
    }

    function closeModalWithAnimation() {
        if (!modal?.classList.contains('show')) return;
        modal.classList.add('closing');
        setTimeout(() => {
            modal.classList.remove('show');
            modal.classList.remove('closing');
            document.body.style.overflow = '';
        }, 250);
    }

    function openDrawer() {
        if (!drawer) return;
        drawer.classList.add('open');
        document.body.style.overflow = 'hidden';
        if (drawerContent) {
            drawerContent.style.transform = '';
            drawerContent.style.transition = '';
        }
        if (drawerBody) {
            drawerBody.scrollTop = 0;
            drawerBody.style.overflowY = 'auto';
        }
        // Don't auto-focus here - let fillEditForm handle it
    }

    function closeDrawer() {
        if (!drawer) return;
        drawer.classList.remove('open');
        document.body.style.overflow = '';
        if (drawerContent) {
            drawerContent.style.transform = '';
            drawerContent.style.transition = '';
        }
    }

    // ========== FORM HTML GENERATOR (used by BOTH modal and drawer) ==========
    function getFormHTML(isEdit) {
        return `
            <form id="userForm" autocomplete="off">
                <div class="um-modal-body">
                    <div class="um-form-group">
                        <label>Full Name *</label>
                        <input class="formInput" type="text" id="userName" name="new_user_name" autocomplete="off" required>
                    </div>
                    <div class="um-form-group">
                        <label>Email Address *</label>
                        <input class="formInput" type="email" id="userEmail" name="new_user_email" autocomplete="off" required>
                    </div>
                    <div class="um-form-group">
                        <label>Password</label>
                        <div class="password-input-wrapper">
                            <input class="formInput" type="password" id="userPassword" name="new_user_password" autocomplete="new-password" placeholder="Enter password">
                            <i class="fas fa-eye-slash toggle-password" data-target="userPassword"></i>
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="strength-info">
                                <small id="strengthText">Weak</small>
                            </div>
                        </div>
                        <small id="passwordHint">${isEdit ? 'Leave blank to keep current password' : 'Required for new users'}</small>
                    </div>
                </div>
                <div class="um-modal-footer">
                    <button type="button" class="um-btn-cancel" id="cancelFormBtn">Cancel</button>
                    <button type="submit" class="um-btn-save">Save User</button>
                </div>
            </form>
        `;
    }

    // ========== UNIFIED OPEN FUNCTION ==========
    function openUserForm(userId = null) {
        // CRITICAL: Clear any pending edit data fill timeout
        if (window._editFillTimeout) {
            clearTimeout(window._editFillTimeout);
            window._editFillTimeout = null;
        }
        
        editingUserId = null;

        if (userId !== null) {
            editingUserId = userId;
        }

        const isEdit = userId !== null;
        
        if (isMobile()) {
            // Update drawer title
            const drawerTitle = document.querySelector('.drawerTitle');
            if (drawerTitle) {
                drawerTitle.textContent = isEdit ? 'Edit User' : 'Add New User';
            }
            // Inject form into drawer body
            if (drawerBody) {
                drawerBody.innerHTML = getFormHTML(isEdit);
            }
            openDrawer();
        } else {
            // Inject form + header into modal
            if (modalContent) {
                modalContent.innerHTML = `
                    <div class="um-modal-header">
                        <h3>${isEdit ? 'Edit User' : 'Add New User'}</h3>
                        <button class="um-modal-close" id="closeModal">&times;</button>
                    </div>
                    ${getFormHTML(isEdit)}
                `;
            }
            openModal();
        }
        
        // Setup event listeners FIRST
        setupFormListeners();
        setupModalPasswordToggle();

        const form = document.getElementById('userForm');
        if (form) form.reset();
        
        // Fill form ONLY if editing
        if (isEdit) {
            fillEditForm(userId);
        }

        if (!isEdit) {
            document.getElementById('userName')?.setAttribute('value', '');
            document.getElementById('userEmail')?.setAttribute('value', '');
            document.getElementById('userPassword')?.setAttribute('value', '');

            document.getElementById('userName').value = '';
            document.getElementById('userEmail').value = '';
            document.getElementById('userPassword').value = '';
        }
    }

    // ========== SEPARATE FUNCTION TO FILL EDIT DATA ==========
    function fillEditForm(userId) {
        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
        const card = document.querySelector(`.um-card[data-user-id="${userId}"]`);
        let name = '', email = '';
        
        if (row) {
            const nameCell = row.querySelector('.um-user-name');
            if (nameCell) {
                const clone = nameCell.cloneNode(true);
                clone.querySelectorAll('.badge-owner, .badge-you').forEach(b => b.remove());
                name = clone.textContent.trim();
            }
            email = row.cells[2]?.textContent.trim() || '';
        } else if (card) {
            const nameEl = card.querySelector('.um-card-name');
            if (nameEl) {
                const clone = nameEl.cloneNode(true);
                clone.querySelectorAll('.badge-owner, .badge-you').forEach(b => b.remove());
                name = clone.textContent.trim();
            }
            const emailEl = card.querySelectorAll('.um-detail-value')[0];
            email = emailEl?.textContent.trim() || '';
        }
        
        const nameInput = document.getElementById('userName');
        const emailInput = document.getElementById('userEmail');
        const passwordInput = document.getElementById('userPassword');
        const strengthWrap = document.querySelector('.password-strength');
        
        if (nameInput) {
            nameInput.value = name;
        }

        if (emailInput) {
            emailInput.value = email;
        }

        if (passwordInput) passwordInput.value = '';
        if (strengthWrap) strengthWrap.style.display = 'none';
        
        setTimeout(() => nameInput?.focus(), 50);
    }

    // ========== FORM SUBMIT HANDLER ==========
    async function handleFormSubmit(e) {
        e.preventDefault();
        const btn = document.querySelector('.um-btn-save');
        if (!btn) return;
        
        const originalText = btn.textContent;
        const name = document.getElementById('userName')?.value.trim();
        const email = document.getElementById('userEmail')?.value.trim();
        const password = document.getElementById('userPassword')?.value;
        
        if (!name || !email) {
            UI.toast('Name and email are required', 'error');
            return;
        }
        
        btn.disabled = true;
        btn.textContent = 'Saving...';
        
        try {
            const action = editingUserId ? 'update' : 'create';
            if (!editingUserId) { // CREATE
                if (!password) {
                    UI.toast('Password is required for new users', 'error');
                    btn.disabled = false;
                    btn.textContent = originalText;
                    return;
                }

                const score = checkStrength(password);
                if (score < 3) {
                    UI.toast('Password is too weak', 'error');
                    btn.disabled = false;
                    btn.textContent = originalText;
                    return;
                }
            }
            const res = await fetch(`api/users?_api=${action}`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    p: JSON.stringify({ i: editingUserId || 0, n: name, e: email, p: password }),
                    csrf_token: window.APP.csrfToken
                })
            });
            if (!res.ok) throw new Error('Server error');
            let data;
            try {
                data = await res.json();
            } catch {
                throw new Error('Invalid server response');
            }

            if (data.ok) {
                const user = normalizeUser(data.m.u);
                if (editingUserId) { 
                    updateUserInUI(user); 
                    editingUserId = null;
                    UI.toast('User updated successfully', 'success');
                } else { 
                    addUserToTable(user); 
                    addUserToCards(user);
                    UI.toast('User created successfully', 'success');
                }
                updateUserCount();
                filterUsers();
                // Reset form if it exists
                const userForm = document.getElementById('userForm');
                if (userForm) userForm.reset();
                // Close both modal and drawer
                closeModalWithAnimation();
                closeDrawer();
            } else { 
                UI.toast(data.e || 'Failed to save user', 'error');
            }
        } catch (err) { 
            console.error(err);
            UI.toast('Something went wrong', 'error');
        } finally { 
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    // ========== FORM LISTENERS SETUP ==========
    function setupFormListeners() {
        const closeModalBtn = document.getElementById('closeModal');
        const cancelFormBtn = document.getElementById('cancelFormBtn');
        const userForm = document.getElementById('userForm');
        const closeDrawerBtn = document.getElementById('closeDrawerBtn');
        
        // Close buttons for modal
        closeModalBtn?.addEventListener('click', closeModalWithAnimation);
        
        // Cancel button works for both modal and drawer
        cancelFormBtn?.addEventListener('click', () => {
            closeModalWithAnimation();
            closeDrawer();
        });
        
        // Close button in drawer header
        closeDrawerBtn?.addEventListener('click', closeDrawer);
        
        // Form submission
        userForm?.addEventListener('submit', handleFormSubmit);
        
        // Password strength checker
        const passInput = document.getElementById('userPassword');
        const strengthWrap = document.querySelector('.password-strength');
        
        if (passInput && strengthWrap) {
            passInput.addEventListener('input', () => {
                const val = passInput.value.trim();
                if (val.length > 0) {
                    strengthWrap.style.display = 'block';
                    checkStrength(val);
                } else {
                    strengthWrap.style.display = 'none';
                    const strengthBar = document.getElementById('strengthBar');
                    const strengthText = document.getElementById('strengthText');
                    if (strengthBar) strengthBar.className = 'strength-bar';
                    if (strengthText) {
                        strengthText.textContent = 'Weak';
                        strengthText.className = '';
                    }
                }
            });
        }
    }

    // ========== ADD USER BUTTON ==========
    if (addUserBtn) {
        addUserBtn.addEventListener('click', () => openUserForm(null));
    }

    // Modal backdrop & escape key
    window.addEventListener('click', (e) => { 
        if (e.target === modal && modal?.classList.contains('show')) {
            closeModalWithAnimation();
        }
    });

    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape' && modal?.classList.contains('show')) {
            closeModalWithAnimation();
        }
    });

    async function deleteUser(userId) {
        // Use global confirm modal
        const confirmed = await UI.confirm({
            title: 'Delete User',
            message: 'Are you sure you want to delete this user?',
            type: 'danger',
            showWarning: true,
            confirmText: 'Delete',
            cancelText: 'Cancel'
        });

        if (!confirmed) return;
        
        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
        const card = document.querySelector(`.um-card[data-user-id="${userId}"]`);
        clearBulkSelection();

        try {
            const res = await fetch('api/users?_api=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    p: JSON.stringify({ i: userId }),
                    csrf_token: window.APP.csrfToken
                })
            });
            let data;
            try {
                data = await res.json();
            } catch {
                throw new Error('Invalid server response');
            }
            if (!data.ok) throw new Error();
            
            if (data.ok) {
                row?.remove();
                card?.remove();

                if (document.querySelectorAll('#usersTableBody tr').length === 0 && window.APP.currentPage > 1) {
                    window.location.search = `?page=${window.APP.currentPage - 1}`;
                }

                updateUserCount();
            }

            // Use global toast
            UI.toast('User deleted successfully', 'success');
        } catch (err) {
            UI.toast('Delete failed', 'error');
            console.error(err);
        }
    }

    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.um-action-btn.edit');
        const deleteBtn = e.target.closest('.um-action-btn.delete');
        if (editBtn) openUserForm(editBtn.dataset.id);
        if (deleteBtn) deleteUser(deleteBtn.dataset.id);
    });

    // ========== PASSWORD TOGGLE FUNCTIONALITY ==========
    function initPasswordToggle() {
        const toggleIcons = document.querySelectorAll('.toggle-password');
        
        toggleIcons.forEach(icon => {
            icon.removeEventListener('click', handlePasswordToggle);
            icon.addEventListener('click', handlePasswordToggle);
        });
    }

    function handlePasswordToggle(e) {
        const icon = e.currentTarget;
        const targetId = icon.getAttribute('data-target');
        const input = document.getElementById(targetId);
        
        if (input) {
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
    }

    // Call this when modal opens to ensure toggle works on dynamically added elements
    function setupModalPasswordToggle() {
        setTimeout(() => {
            initPasswordToggle();
        }, 100);
    }

    // Call this after adding new users to the table/cards (if needed)
    function attachPasswordToggle() {
        initPasswordToggle();
    }

    function checkStrength(password, bar = null, text = null) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[\W]/.test(password)) score++;

        const levels = ['very-weak', 'weak', 'okay', 'strong', 'very-strong'];
        const names = ['Very Weak', 'Weak', 'Okay', 'Strong', 'Very Strong'];
        const index = Math.min(score, 4);

        const strengthBar = bar || document.getElementById('strengthBar');
        const strengthText = text || document.getElementById('strengthText');

        if (strengthBar) {
            strengthBar.className = 'strength-bar';
            strengthBar.classList.add(levels[index]);
        }

        if (strengthText) {
            strengthText.textContent = names[index];
            strengthText.className = levels[index];
        }

        return score;
    }

    // ========== DYNAMIC FUNCTIONS ==========
    function updateUserCount() {
        if (!paginationExists) return;
        
        const rows = document.querySelectorAll('#usersTableBody tr');
        const visibleRows = document.querySelectorAll('#usersTableBody tr:not([style*="display: none"])').length;

        if (totalCountSpan) totalCountSpan.textContent = rows.length;

        if (showingStartSpan && showingEndSpan) {
            const start = (window.APP.currentPage - 1) * window.APP.perPage + 1;
            const end = start + visibleRows - 1;

            showingStartSpan.textContent = visibleRows ? start : 0;
            showingEndSpan.textContent = visibleRows ? end : 0;
        }
    }

    function renderPagination() {
        const container = document.getElementById('pageNumbers');
        if (!container) return;
        container.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = 'um-page-num' + (i === window.APP.currentPage ? ' active' : '');
            btn.textContent = i;

            btn.onclick = () => {
                window.location.search = `?page=${i}`;
            };

            container.appendChild(btn);
        }
    }
    renderPagination();

    function safeUrl(url) {
        try {
            const u = new URL(url);
            if (url.startsWith('/')) return url;
            return (u.protocol === 'http:' || u.protocol === 'https:') ? url : '';
        } catch {
            return '';
        }
    }
    
    function addUserToTable(user) {
        const row = document.createElement('tr');
        row.setAttribute('data-user-id', user.id);

        const avatar = safeUrl(user.profile_picture) || `https://ui-avatars.com/api/?background=0f5b3e&color=fff&name=${encodeURIComponent(user.name)}`;
        const createdAt = new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        const lastLogin = user.last_login ? new Date(user.last_login).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) : 'Never';
        const canManage = window.APP.currentUserRole === 'owner' && user.id != CURRENT_USER_ID;
        row.innerHTML = `
        <td>
            ${canManage ? `<input type="checkbox" class="user-checkbox" value="${user.id}">` : ''}
        </td>

        <td>
            <div class="um-user-cell">
                <div class="um-avatar">
                    <img src="${avatar}">
                </div>
                <span class="um-user-name">
                    ${escapeHtml(user.name)}
                    ${user.role === 'owner'
                        ? '<span class="badge-owner">Owner</span>'
                        : (user.id == CURRENT_USER_ID ? '<span class="badge-you">You</span>' : '')
                    }
                </span>
            </div>
        </td>

        <td>${escapeHtml(user.email)}</td>
        <td>${createdAt}</td>
        <td>${lastLogin}</td>

        <td>
            <div class="um-action-btns">
                ${
                    canManage
                        ? `
                        <button class="um-action-btn edit" data-id="${user.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="um-action-btn delete" data-id="${user.id}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        `
                        : '<span style="font-size:12px; color:#888;">—</span>'
                }
            </div>
        </td>
        `;
        const firstRow = tableBody.querySelector('tr');

        if (firstRow && firstRow.dataset.userId == CURRENT_USER_ID) {
            // Insert after current user
            firstRow.after(row);
        } else {
            // Fallback (no current user found)
            tableBody.prepend(row);
        }
    }

    function addUserToCards(user) {
        const container = document.getElementById('usersCards');

        const avatar = safeUrl(user.profile_picture) || `https://ui-avatars.com/api/?background=0f5b3e&color=fff&name=${encodeURIComponent(user.name)}`;
        const createdAt = new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        const lastLogin = user.last_login ? new Date(user.last_login).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) : 'Never';
        const card = document.createElement('div');
        card.className = 'um-card';
        card.setAttribute('data-user-id', user.id);
        const canManage = window.APP.currentUserRole === 'owner' && user.id != CURRENT_USER_ID;
        card.innerHTML = `
            <div class="um-card-checkbox">
                ${canManage ? `<input type="checkbox" class="user-checkbox" value="${user.id}">` : ''}
            </div>
            <div class="um-card-user">
                <div class="um-card-avatar"><img src="${avatar}"></div>
                <div class="um-card-name">
                    ${escapeHtml(user.name)}
                    ${user.role === 'owner' 
                        ? '<span class="badge-owner">Owner</span>' 
                        : (user.id == CURRENT_USER_ID ? '<span class="badge-you">You</span>' : '')
                    }
                </div>
            </div>
            <div class="um-card-details">
                <div class="um-card-detail">
                    <span class="um-detail-label">Email:</span>
                    <span class="um-detail-value">${escapeHtml(user.email)}</span>
                </div>
                <div class="um-card-detail">
                    <span class="um-detail-label">Joined:</span>
                    <span class="um-detail-value">${createdAt}</span>
                </div>
                <div class="um-card-detail">
                    <span class="um-detail-label">Last Login:</span>
                    <span class="um-detail-value">${lastLogin}</span>
                </div>
            </div>
            <div class="um-card-actions">
                ${
                    canManage
                        ? `
                        <button class="um-action-btn edit" data-id="${user.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="um-action-btn delete" data-id="${user.id}">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                        `
                        : '<span style="font-size:12px; color:#888;">—</span>'
                }
            </div>
        `;
        const firstCard = container.querySelector('.um-card');

        if (firstCard && firstCard.dataset.userId == CURRENT_USER_ID) {
            firstCard.after(card);
        } else {
            container.prepend(card);
        }
    }

    function updateUserInUI(user) {
        const row = document.querySelector(`tr[data-user-id="${user.id}"]`);
        if (row) {
            const nameSpan = row.querySelector('.um-user-name');
            nameSpan.innerHTML = `
                ${escapeHtml(user.name)}
                ${
                    user.role === 'owner'
                        ? '<span class="badge-owner">Owner</span>'
                        : (user.id == CURRENT_USER_ID ? '<span class="badge-you">You</span>' : '')
                }
            `;

            if (user.role === 'owner') {
                const badge = document.createElement('span');
                badge.className = 'badge-owner';
                badge.textContent = 'Owner';
                nameSpan.appendChild(badge);
            }
            row.cells[2].textContent = user.email;
            const img = row.querySelector('img');
            if (img && !user.profile_picture) img.src = `https://ui-avatars.com/api/?background=0f5b3e&color=fff&name=${encodeURIComponent(user.name)}`;
        }
        const card = document.querySelector(`.um-card[data-user-id="${user.id}"]`);
        if (card) {
            card.querySelector('.um-card-name').innerHTML = `
                ${escapeHtml(user.name)} 
                ${user.role === 'owner' 
                    ? '<span class="badge-owner">Owner</span>' 
                    : (user.id == CURRENT_USER_ID ? '<span class="badge-you">You</span>' : '')
                }
            `;
            card.querySelectorAll('.um-detail-value')[0].textContent = user.email;
            const img = card.querySelector('img');
            if (img && !user.profile_picture) img.src = `https://ui-avatars.com/api/?background=0f5b3e&color=fff&name=${encodeURIComponent(user.name)}`;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function normalizeUser(u) {
        return {
            id: u.i,
            name: u.n,
            email: u.e,
            profile_picture: u.p,
            created_at: u.c,
            last_login: u.la || null,
            role: u.r
        };
    }

    filterUsers();
});