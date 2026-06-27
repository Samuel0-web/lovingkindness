(function () {
    /* ================= TOAST ================= */
    const MAX_TOASTS = 5;
    let container = document.getElementById('toastContainer');

    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
    }

    window.UI = window.UI || {};

    window.UI.toast = function (message, type = 'success', duration = 3000) {
        const colors = {
            success: 'linear-gradient(135deg, #10b981, #059669)',
            error: 'linear-gradient(135deg, #ef4444, #dc2626)',
            info: 'linear-gradient(135deg, #3b82f6, #2563eb)',
            warning: 'linear-gradient(135deg, #f59e0b, #d97706)'
        };

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            info: 'fa-info-circle',
            warning: 'fa-exclamation-triangle'
        };

        const toast = document.createElement('div');
        
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px; width: 100%;">
                <i class="fas ${icons[type] || icons.success}" style="font-size: 1.1rem;"></i>
                <span style="flex: 1; font-size: 0.85rem; font-weight: 500; line-height: 1.4;">${message}</span>
                <button class="toast-close" style="background: none; border: none; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 1.2rem; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">&times;</button>
            </div>
        `;

        toast.className = 'ui-toast';
        toast.dataset.type = type;

        let removed = false;

        function removeToast(el) {
            if (removed) return;
            removed = true;

            el.style.animation = 'toastSlideOut 0.25s ease';
            setTimeout(() => el.remove(), 250);
        }

        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.onclick = () => removeToast(toast);

        if (container.children.length >= MAX_TOASTS) {
            container.firstChild.remove();
        }

        container.appendChild(toast);
        setTimeout(() => removeToast(toast), duration);
    };

    /* ================= MODAL ================= */
    let modal = null;
    let isModalOpen = false;

    function createModal() {
        const modalDiv = document.createElement('div');
        modalDiv.id = 'globalConfirmModal';
        modalDiv.className = 'global-modal-overlay';
        modalDiv.style.display = 'none';

        modalDiv.innerHTML = `
            <div class="global-modal-container">
                <div class="global-modal-header">
                    <div class="global-modal-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 id="gcm-title">Confirm Action</h3>
                </div>
                <div class="global-modal-body">
                    <p class="global-modal-message" id="gcm-message">Are you sure you want to proceed?</p>
                    <div class="global-modal-warning" id="gcm-warning" style="display: none;">
                        <i class="fas fa-info-circle"></i>
                        <span>This action cannot be undone.</span>
                    </div>
                </div>
                <div class="global-modal-footer">
                    <button class="global-modal-btn global-modal-btn-cancel" id="gcm-cancel">Cancel</button>
                    <button class="global-modal-btn global-modal-btn-delete" id="gcm-confirm">Confirm</button>
                </div>
            </div>
        `;

        document.body.appendChild(modalDiv);
        return modalDiv;
    }

    modal = document.getElementById('globalConfirmModal');
    if (!modal) {
        modal = createModal();
    }

    const titleEl = modal.querySelector('#gcm-title');
    const messageEl = modal.querySelector('#gcm-message');
    const warningEl = modal.querySelector('#gcm-warning');
    const iconDiv = modal.querySelector('.global-modal-icon');
    const icon = iconDiv.querySelector('i');
    const confirmBtn = modal.querySelector('#gcm-confirm');
    const cancelBtn = modal.querySelector('#gcm-cancel');

    window.UI.confirm = function (options = {}) {
        if (isModalOpen) return Promise.resolve(false);

        isModalOpen = true;
        
        const {
            title = 'Confirm Action',
            message = 'Are you sure you want to proceed?',
            type = 'danger',
            showWarning = true,
            confirmText = 'Confirm',
            cancelText = 'Cancel'
        } = options;

        return new Promise((resolve) => {
            titleEl.textContent = title;
            messageEl.textContent = message;
            confirmBtn.textContent = confirmText;
            cancelBtn.textContent = cancelText;

            confirmBtn.className = 'global-modal-btn';
            if (type === 'danger') {
                confirmBtn.classList.add('global-modal-btn-delete');
            } else {
                confirmBtn.classList.add('global-modal-btn-confirm');
            }

            iconDiv.className = 'global-modal-icon';
            if (type === 'danger') {
                iconDiv.classList.add('danger');
                icon.className = 'fas fa-exclamation-triangle';
            } else if (type === 'warning') {
                iconDiv.classList.add('warning');
                icon.className = 'fas fa-exclamation-circle';
            } else if (type === 'info') {
                iconDiv.classList.add('info');
                icon.className = 'fas fa-info-circle';
            } else if (type === 'success') {
                iconDiv.classList.add('success');
                icon.className = 'fas fa-check-circle';
            }

            if (showWarning) {
                warningEl.style.display = 'flex';
            } else {
                warningEl.style.display = 'none';
            }

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            let escHandler;
            let clickHandler;

            function cleanup(result) {
                modal.style.display = 'none';
                document.body.style.overflow = '';

                if (escHandler) {
                    document.removeEventListener('keydown', escHandler);
                    escHandler = null;
                }

                if (clickHandler) {
                    modal.removeEventListener('click', clickHandler);
                    clickHandler = null;
                }

                isModalOpen = false;
                resolve(result);
            }

            confirmBtn.onclick = () => cleanup(true);
            cancelBtn.onclick = () => cleanup(false);

            clickHandler = (e) => {
                if (e.target === modal) cleanup(false);
            };

            modal.addEventListener('click', clickHandler);
            
            escHandler = (e) => {
                if (e.key === 'Escape') {
                    cleanup(false);
                }
            };
            document.addEventListener('keydown', escHandler);
        });
    };

    // Shortcut methods
    window.UI.confirmDelete = (message, title = 'Delete Confirmation') => {
        return window.UI.confirm({
            title: title,
            message: message,
            type: 'danger',
            showWarning: true,
            confirmText: 'Delete',
            cancelText: 'Cancel'
        });
    };

    window.UI.confirmAction = (message, title = 'Confirm Action') => {
        return window.UI.confirm({
            title: title,
            message: message,
            type: 'info',
            showWarning: false,
            confirmText: 'Continue',
            cancelText: 'Cancel'
        });
    };

    // Toast shortcuts
    window.UI.toastSuccess = (message) => window.UI.toast(message, 'success');
    window.UI.toastError = (message) => window.UI.toast(message, 'error');
    window.UI.toastInfo = (message) => window.UI.toast(message, 'info');
    window.UI.toastWarning = (message) => window.UI.toast(message, 'warning');

    // =================================
    // GLOBAL DROPDOWN SYSTEM
    // =================================
    
    function closeDropdowns(except = null) {
        document.querySelectorAll('.uiDropdown.open').forEach(dropdown => {
            if (dropdown !== except) { dropdown.classList.remove('open'); }
        });
    }

    document.addEventListener('click', e => {
        const trigger = e.target.closest('.uiDropdownTrigger');

        if (trigger) {
            const dropdown = trigger.closest('.uiDropdown');
            const isOpen = dropdown.classList.contains('open');
            closeDropdowns();

            if (!isOpen) {
                dropdown.classList.add('open');
            }
            return;
        }

        const option = e.target.closest('.uiDropdownMenu button');

        if (option) {
            const dropdown = option.closest('.uiDropdown');
            const trigger = dropdown.querySelector('.uiDropdownTrigger');
            trigger.dataset.value = option.dataset.value || '';
            trigger.firstChild.textContent = option.textContent.trim() + ' ';
            dropdown.classList.remove('open');

            trigger.dispatchEvent(
                new CustomEvent('dropdown:change', {
                    bubbles: true,
                    detail: {
                        value: option.dataset.value || '',
                        text: option.textContent.trim()
                    }
                })
            );
            return;
        }

        closeDropdowns();
    });
})();