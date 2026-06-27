document.addEventListener('DOMContentLoaded', () => {
    // ================= API =================
    const apiCall = async (action, bodyData) => {
        const endpoints = {
            update: 'api/profile?action=update',
            password: 'api/profile?action=password',
            avatar: 'api/profile?action=avatar',
            remove_avatar: 'api/profile?action=remove_avatar'
        };

        const isFormData = bodyData instanceof FormData;

        const options = {
            method: 'POST',
            ...(isFormData
                ? (bodyData.append('csrf_token', APP.csrf), { body: bodyData })
                : {
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        p: JSON.stringify(bodyData),
                        csrf_token: APP.csrf
                    })
                })
        };

        try {
            const res = await fetch(endpoints[action], options);
            return await res.json();
        } catch {
            return { ok: false, e: 'Network error' };
        }
    };

    // ================= STATE =================
    let currentFullName = APP.name;
    let currentEmail = APP.email;

    // ================= HELPERS =================
    const $ = (sel, parent = document) => parent.querySelector(sel);
    const $$ = (sel, parent = document) => [...parent.querySelectorAll(sel)];

    const updateText = (selectors, value) => {
        selectors.forEach(sel => $$(sel).forEach(el => el.textContent = value));
    };

    // ================= PROFILE UPDATE =================
    async function updateProfileField(field, value) {
        const payload = {
            n: field === 'name' ? value : currentFullName,
            e: field === 'email' ? value : currentEmail
        };

        const res = await apiCall('update', payload);

        if (!res.ok) {
            UI.toast(res.e || 'Update failed', 'error');
            return false;
        }

        if (field === 'name') currentFullName = value;
        if (field === 'email') currentEmail = value;

        updateText([
            '.display-name',
            '#mobileDisplayName',
            '.profileSidebarUser h2',
            '.profileMobileUserInfo h1'
        ], currentFullName);

        updateText([
            '.display-email',
            '#mobileDisplayEmail',
            '.profileSidebarEmail',
            '.profileMobileUserInfo p'
        ], currentEmail);

        UI.toast('Profile updated successfully', 'success');
        return true;
    }

    // ================= PASSWORD =================
    const checkPasswordStrength = pwd => {
        let score = 0;
        if (pwd.length >= 8) score++;
        if (/[a-z]/.test(pwd)) score++;
        if (/[A-Z]/.test(pwd)) score++;
        if (/[0-9]/.test(pwd)) score++;
        if (/[\W_]/.test(pwd)) score++;

        return {
            percentage: (score / 5) * 100,
            color: ['#ef4444','#f59e0b','#eab308','#10b981','#059669'][score-1] || '#e2e8f0'
        };
    };

    const updateStrengthBar = (pwd, id) => {
        const bar = $(`#${id} .profileStrengthFill`);
        if (!bar) return;

        if (!pwd) {
            bar.style.width = '0%';
            bar.style.backgroundColor = '#e2e8f0';
            return;
        }

        const { percentage, color } = checkPasswordStrength(pwd);
        bar.style.width = percentage + '%';
        bar.style.backgroundColor = color;
    };

    // strength listeners
    $('#deskNewPwd')?.addEventListener('input', e => updateStrengthBar(e.target.value, 'deskStrengthBar'));
    $('#mobileNewPwd')?.addEventListener('input', e => updateStrengthBar(e.target.value, 'mobileStrengthBar'));

    // toggle password visibility
    $$('.profilePasswordToggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = $('input', btn.parentElement);
            const icon = $('i', btn);

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    });

    // update password (shared logic)
    const handlePasswordUpdate = async (prefix) => {
        const oldPwd = $(`#${prefix}CurrentPwd`).value;
        const newPwd = $(`#${prefix}NewPwd`).value;
        const confirm = $(`#${prefix}ConfirmPwd`).value;

        if (!oldPwd || !newPwd) return UI.toast('Please fill all fields', 'error');
        if (newPwd !== confirm) return UI.toast('Passwords do not match', 'error');
        if (newPwd.length < 8) return UI.toast('Password must be at least 8 characters', 'error');

        const res = await apiCall('password', { old: oldPwd, new: newPwd });

        if (!res.ok) return UI.toast(res.e, 'error');

        UI.toast('Password changed successfully', 'success');

        ['CurrentPwd','NewPwd','ConfirmPwd'].forEach(id => $(`#${prefix}${id}`).value = '');
        updateStrengthBar('', `${prefix}StrengthBar`);
    };

    $('#deskUpdatePwdBtn')?.addEventListener('click', () => handlePasswordUpdate('desk'));
    $('#mobileUpdatePwdBtn')?.addEventListener('click', () => handlePasswordUpdate('mobile'));

    // ================= INLINE EDIT =================
    $$('.profileSettingRow').forEach(row => {
        const editBtn = $('.profileInlineEditBtn', row);
        const cancelBtn = $('.cancel-edit-btn', row);
        const form = $('.profileInlineEditForm', row);
        const span = $('.profileSettingValue > span', row);

        editBtn?.addEventListener('click', () => {
            editBtn.style.display = 'none';
            span.style.display = 'none';
            form.style.display = 'flex';
            $('input', form).focus();
        });

        cancelBtn?.addEventListener('click', () => {
            editBtn.style.display = 'inline-flex';
            span.style.display = 'inline';
            form.style.display = 'none';
        });
    });

    const resetInlineEdit = (row) => {
        $('.profileInlineEditBtn', row).style.display = 'inline-flex';
        $('.profileSettingValue > span', row).style.display = 'inline';
        $('.profileInlineEditForm', row).style.display = 'none';
    };

    // ===== MOBILE NAME EDIT =====
    const mobileEditNameBtn = document.getElementById('mobileEditNameBtn');
    const mobileNameEditForm = document.getElementById('mobileNameEditForm');
    const mobileDisplayName = document.getElementById('mobileDisplayName');
    const mobileSaveNameBtn = document.getElementById('mobileSaveNameBtn');
    const mobileCancelNameBtn = document.getElementById('mobileCancelNameBtn');
    const mobileNameInput = document.getElementById('mobileNameInput');

    mobileEditNameBtn?.addEventListener('click', () => {
        mobileNameEditForm.style.display = 'block';
        mobileDisplayName.parentElement.style.display = 'none';
        mobileNameInput.focus();
    });

    mobileCancelNameBtn?.addEventListener('click', () => {
        mobileNameEditForm.style.display = 'none';
        mobileDisplayName.parentElement.style.display = 'flex';
    });

    mobileSaveNameBtn?.addEventListener('click', async (e) => {
        e.preventDefault();

        const value = mobileNameInput.value.trim();

        if (value && await updateProfileField('name', value)) {
            mobileDisplayName.textContent = value;
            mobileNameEditForm.style.display = 'none';
            mobileDisplayName.parentElement.style.display = 'flex';
        }
    });

    const mobileEditEmailBtn = document.getElementById('mobileEditEmailBtn');
    const mobileEmailEditForm = document.getElementById('mobileEmailEditForm');
    const mobileDisplayEmail = document.getElementById('mobileDisplayEmail');
    const mobileSaveEmailBtn = document.getElementById('mobileSaveEmailBtn');
    const mobileCancelEmailBtn = document.getElementById('mobileCancelEmailBtn');
    const mobileEmailInput = document.getElementById('mobileEmailInput');

    mobileEditEmailBtn?.addEventListener('click', () => {
        mobileEmailEditForm.style.display = 'block';
        mobileDisplayEmail.parentElement.style.display = 'none';
        mobileEmailInput.focus();
    });

    mobileCancelEmailBtn?.addEventListener('click', () => {
        mobileEmailEditForm.style.display = 'none';
        mobileDisplayEmail.parentElement.style.display = 'flex';
    });

    mobileSaveEmailBtn?.addEventListener('click', async (e) => {
        e.preventDefault();

        const value = mobileEmailInput.value.trim();

        if (value && await updateProfileField('email', value)) {
            mobileDisplayEmail.textContent = value;
            mobileEmailEditForm.style.display = 'none';
            mobileDisplayEmail.parentElement.style.display = 'flex';
        }
    });

    $$('.save-name-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const row = btn.closest('.profileSettingRow');
            const val = $('.name-input', row).value.trim();

            if (val && await updateProfileField('name', val)) {
                resetInlineEdit(row);
            }
        });
    });

    $$('.save-email-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const row = btn.closest('.profileSettingRow');
            const val = $('.email-input', row).value.trim();

            if (val && await updateProfileField('email', val)) {
                resetInlineEdit(row);
            }
        });
    });

    // ================= NAV =================
    $$('.profileNavItem').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();

            const sectionId = link.dataset.section;

            $$('.profileNavItem').forEach(n => n.classList.remove('active'));
            link.classList.add('active');

            $$('.profileSettingsSection').forEach(s => s.classList.remove('active'));
            $(`#section-${sectionId}`).classList.add('active');
        });
    });

    // ================= ACCORDION =================
    $$('.profileAccordionHeader').forEach(header => {
        header.addEventListener('click', () => {
            const item = header.closest('.profileAccordionItem');
            const isOpen = item.classList.contains('open');

            $$('.profileAccordionItem').forEach(i => i.classList.remove('open'));
            if (!isOpen) item.classList.add('open');
        });
    });

    // ================= AVATAR =================
    let selectedAvatarFile = null;
    let avatarMarkedForRemoval = false;
    const isMobile = () => window.innerWidth <= 768;

    const modal = $('#avatarModal');
    const drawer = $('#avatarDrawer');
    const drawerContent = $('.profileDrawerContent', drawer);
    const drawerBackdrop = $('#avatarDrawerBackdrop');

    // ========== DRAWER TOUCH HANDLING ==========
    let drawerTouchStartY = 0;
    let drawerTouchCurrentY = 0;
    let isDraggingDrawer = false;
    let drawerAnimFrame = null;
    const CLOSE_THRESHOLD = 100;
    const RUBBER_BAND_LIMIT = 150;
    const RUBBER_BAND_RESISTANCE = 0.4;

    const handleDrawerTouchStart = (e) => {
        const stickyArea = e.target.closest('.profileDrawerStickyArea');
        if (!stickyArea) return;
        
        drawerTouchStartY = e.touches[0].clientY;
        drawerTouchCurrentY = drawerTouchStartY;
        isDraggingDrawer = true;
        if (drawerContent) drawerContent.style.transition = 'none';
    };

    const handleDrawerTouchMove = (e) => {
        if (!isDraggingDrawer) return;
        
        drawerTouchCurrentY = e.touches[0].clientY;
        const deltaY = drawerTouchCurrentY - drawerTouchStartY;
        
        if (deltaY > 0 && drawer?.classList.contains('open')) {
            let translateY;
            if (deltaY <= RUBBER_BAND_LIMIT) {
                translateY = deltaY;
            } else {
                const excess = deltaY - RUBBER_BAND_LIMIT;
                translateY = RUBBER_BAND_LIMIT + (excess * RUBBER_BAND_RESISTANCE);
            }
            
            if (drawerAnimFrame) cancelAnimationFrame(drawerAnimFrame);
            drawerAnimFrame = requestAnimationFrame(() => {
                if (drawerContent) drawerContent.style.transform = `translateY(${translateY}px)`;
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
        
        if (drawerContent) {
            drawerContent.style.transition = 'transform 0.3s cubic-bezier(0.32, 0.72, 0, 1)';
            
            if (deltaY > CLOSE_THRESHOLD) {
                drawerContent.style.transform = 'translateY(100%)';
                setTimeout(() => {
                    closeAvatarDrawer();
                    if (drawerContent) drawerContent.style.transform = '';
                }, 300);
            } else {
                drawerContent.style.transform = '';
                setTimeout(() => {
                    if (drawerContent) drawerContent.style.transition = '';
                }, 300);
            }
        }
        
        drawerTouchStartY = 0;
        drawerTouchCurrentY = 0;
    };

    if (drawerContent) {
        drawerContent.addEventListener('touchstart', handleDrawerTouchStart, { passive: false });
        drawerContent.addEventListener('touchmove', handleDrawerTouchMove, { passive: false });
        drawerContent.addEventListener('touchend', handleDrawerTouchEnd);
    }

    // ========== CLOSE DRAWER ON BACKDROP CLICK ==========
    if (drawerBackdrop) {
        drawerBackdrop.addEventListener('click', () => {
            if (drawer?.classList.contains('open')) {
                closeAvatarDrawer();
            }
        });
    }

    // ========== DRAWER OPEN/CLOSE ==========
    function openAvatarDrawer() {
        if (!drawer) return;
        drawer.classList.add('open');
        document.body.style.overflow = 'hidden';
        if (drawerContent) {
            drawerContent.style.transform = '';
            drawerContent.style.transition = '';
        }
    }

    function closeAvatarDrawer() {
        if (!drawer) return;
        drawer.classList.remove('open');
        document.body.style.overflow = '';
        if (drawerContent) {
            drawerContent.style.transform = '';
            drawerContent.style.transition = '';
        }
    }

    // ========== UNIFIED OPEN AVATAR ==========
    function openAvatarForm() {
        if (isMobile()) {
            openAvatarDrawer();
        } else {
            if (modal) modal.style.display = 'flex';
        }
    }

    function closeAvatarForm() {
        if (isMobile()) {
            closeAvatarDrawer();
        } else {
            if (modal) modal.style.display = 'none';
        }
        
        // Reset state
        selectedAvatarFile = null;
        avatarMarkedForRemoval = false;
        $('#saveAvatarBtn').disabled = true;
        $('#saveAvatarDrawerBtn').disabled = true;
    }

    // ========== TRIGGER BUTTONS ==========
    ['desktopAvatarBtn', 'mobileAvatarBtn'].forEach(id => {
        $(`#${id}`)?.addEventListener('click', openAvatarForm);
    });

    // ========== CLOSE BUTTONS ==========
    $('#closeAvatarModalBtn')?.addEventListener('click', closeAvatarForm);
    $('#cancelAvatarBtn')?.addEventListener('click', closeAvatarForm);
    $('#closeAvatarDrawerBtn')?.addEventListener('click', closeAvatarForm);
    $('#cancelAvatarDrawerBtn')?.addEventListener('click', closeAvatarForm);

    // ========== FILE INPUTS (sync both) ==========
    const handleFileSelect = (e) => {
        selectedAvatarFile = e.target.files[0];
        if (!selectedAvatarFile) return;

        avatarMarkedForRemoval = false;

        const reader = new FileReader();
        reader.onload = ev => {
            const imgHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
            
            // Update both previews
            const modalPreview = $('.profilePreviewCircle', modal || document);
            const drawerPreview = $('.profilePreviewCircle', drawer || document);
            
            if (modalPreview) modalPreview.innerHTML = imgHTML;
            if (drawerPreview) drawerPreview.innerHTML = imgHTML;
        };
        reader.readAsDataURL(selectedAvatarFile);

        // Enable both save buttons
        const saveModal = $('#saveAvatarBtn');
        const saveDrawer = $('#saveAvatarDrawerBtn');
        if (saveModal) saveModal.disabled = false;
        if (saveDrawer) saveDrawer.disabled = false;
    };

    $('#avatarFileInput')?.addEventListener('change', handleFileSelect);
    $('#avatarDrawerFileInput')?.addEventListener('change', handleFileSelect);

    // ========== SAVE AVATAR (unified) ==========
    const handleSaveAvatar = async () => {
        if (selectedAvatarFile) {
            // Upload new image
            const fd = new FormData();
            fd.append('avatar', selectedAvatarFile);

            const res = await apiCall('avatar', fd);
            if (!res.ok) return UI.toast(res.e, 'error');

            const newUrl = res.url + '?t=' + Date.now();

            // Update all avatar images
            $$('.profileAvatarLarge img, .profileMobileAvatarImg img, .user-avatar img')
                .forEach(img => img.src = newUrl);

            $$('.profileAvatarLarge, .profileMobileAvatarImg')
                .forEach(container => {
                    if (!container.querySelector('img')) {
                        container.innerHTML = `<img src="${newUrl}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
                    }
                });

            // Update drawer preview
            const drawerPreview = $('.profilePreviewCircle', drawer || document);
            if (drawerPreview) {
                drawerPreview.innerHTML = `<img src="${newUrl}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
            }

            UI.toast('Avatar updated successfully', 'success');

            // Ensure remove button exists in both modal and drawer
            ['#removeAvatarBtn', '#removeAvatarDrawerBtn'].forEach(btnId => {
                if (!$(btnId)) {
                    const parent = btnId.includes('Drawer') 
                        ? $('.profileDrawerBody') 
                        : $('.profileModalBody');
                    if (parent) {
                        const btn = document.createElement('button');
                        btn.id = btnId.replace('#', '');
                        btn.className = 'profileBtnDanger';
                        btn.innerHTML = '<i class="fas fa-trash-alt"></i> Remove Image';
                        const hint = $('.profileUploadHint', parent);
                        if (hint) {
                            hint.parentNode.insertBefore(btn, hint);
                        } else {
                            parent.appendChild(btn);
                        }
                        btn.addEventListener('click', handleRemoveClick);
                    }
                }
            });

        } else if (avatarMarkedForRemoval) {
            // Remove avatar
            const res = await apiCall('remove_avatar', {});
            if (!res.ok) return UI.toast(res.e, 'error');

            const initialsText = APP.initials;
            const encodedName = APP.name;

            // Update all displays to initials
            $$('.profileAvatarLarge, .profileMobileAvatarImg')
                .forEach(el => el.innerHTML = `<span>${initialsText}</span>`);

            $$('.user-avatar img').forEach(img => {
                img.src = `https://ui-avatars.com/api/?background=0f5b3e&color=fff&name=${encodedName}&t=${Date.now()}`;
            });

            // Update both previews
            const modalPreview = $('.profilePreviewCircle', modal || document);
            const drawerPreview = $('.profilePreviewCircle', drawer || document);
            if (modalPreview) modalPreview.innerHTML = `<span>${initialsText}</span>`;
            if (drawerPreview) drawerPreview.innerHTML = `<span>${initialsText}</span>`;

            // Remove both remove buttons
            $('#removeAvatarBtn')?.remove();
            $('#removeAvatarDrawerBtn')?.remove();

            UI.toast('Avatar removed successfully', 'success');
        }

        closeAvatarForm();
    };

    $('#saveAvatarBtn')?.addEventListener('click', handleSaveAvatar);
    $('#saveAvatarDrawerBtn')?.addEventListener('click', handleSaveAvatar);

    // ========== REMOVE AVATAR ==========
    const handleRemoveClick = async () => {
        const confirmed = await UI.confirmDelete(
            'Are you sure you want to remove your profile picture?'
        );

        if (!confirmed) return;

        avatarMarkedForRemoval = true;
        selectedAvatarFile = null;

        const initials = APP.initials;
        const fallbackHTML = `<span>${initials}</span>`;

        // Update both previews
        const modalPreview = $('.profilePreviewCircle', modal || document);
        const drawerPreview = $('.profilePreviewCircle', drawer || document);
        if (modalPreview) modalPreview.innerHTML = fallbackHTML;
        if (drawerPreview) drawerPreview.innerHTML = fallbackHTML;

        // Enable both save buttons
        const saveModal = $('#saveAvatarBtn');
        const saveDrawer = $('#saveAvatarDrawerBtn');
        if (saveModal) saveModal.disabled = false;
        if (saveDrawer) saveDrawer.disabled = false;

        UI.toast('Image will be removed when you save', 'info');
    };

    $('#removeAvatarBtn')?.addEventListener('click', handleRemoveClick);
    $('#removeAvatarDrawerBtn')?.addEventListener('click', handleRemoveClick);
});