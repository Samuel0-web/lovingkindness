// activity.js — Refactored interaction layer
(function () {
    'use strict';

    const auditPanel = document.querySelector('.audit__panel');
    const searchInput = document.getElementById('js-audit-search');
    const filterAdmin = document.getElementById('js-filter-admin');
    const filterAction = document.getElementById('js-filter-action');
    const clearFiltersBtn = document.getElementById('js-clear-filters');
    const searchClearBtn = document.getElementById('js-search-clear');
    const exportBtn = document.getElementById('js-audit-export');

    if (!auditPanel) return;

    // Attach expand/collapse to all items
    function attachExpandListeners(container = document) {
        container.querySelectorAll('.audit__item').forEach(item => {
            if (item.dataset.bound) return;
            item.dataset.bound = '1';
            
            item.addEventListener('click', function (e) {
                // Don't toggle when clicking inside details or on links
                if (e.target.closest('.audit__details-box')) return;

                const wasExpanded = this.classList.contains('audit__item--expanded');

                // Collapse all
                document.querySelectorAll('.audit__item--expanded').forEach(el => {
                    el.classList.remove('audit__item--expanded');
                });

                // Toggle current
                if (!wasExpanded) {
                    this.classList.add('audit__item--expanded');
                }
            });

            // Keyboard support
            item.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
    }

    // Filter logic
    function filterItems() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const adminFilter = filterAdmin?.dataset.value || '';
        const actionFilter = filterAction?.dataset.value || '';
        const existingEmpty = document.querySelector('.audit__empty');
        if (existingEmpty) existingEmpty.remove();
        document.querySelectorAll('.audit__group').forEach(g => { g.style.display = ''; });
        let visibleCount = 0;

        // Show/hide clear filters button
        if (clearFiltersBtn) {
            if (adminFilter || actionFilter) {
                clearFiltersBtn.hidden = false;
            } else {
                clearFiltersBtn.hidden = true;
            }
        }

        // Show/hide search clear icon
        if (searchClearBtn) {
            if (searchTerm) {
                searchClearBtn.hidden = false;
            } else {
                searchClearBtn.hidden = true;
            }
        }

        const totalCount = document.querySelectorAll('.audit__item').length;
        document.querySelectorAll('.audit__item').forEach(item => {
            const text = item.textContent.toLowerCase();
            const adminName = (item.querySelector('.audit__actor')?.textContent || '').toLowerCase();
            const action = item.dataset.action || '';
            let show = true;

            if (searchTerm && !text.includes(searchTerm)) show = false;
            if (adminFilter && adminName !== adminFilter.toLowerCase()) show = false;
            if (actionFilter && action !== actionFilter) show = false;

            item.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Hide empty groups
        document.querySelectorAll('.audit__group').forEach(group => {
            const items = group.querySelectorAll('.audit__item');
            const visibleItems = [...items].filter(item => item.style.display !== 'none');
            group.style.display = visibleItems.length ? '' : 'none';
        });

        if (visibleCount === 0) {
            let type = 'empty';
            if (totalCount > 0) {
                if (searchTerm && (adminFilter || actionFilter)) type = 'combined';
                else if (searchTerm) type = 'search';
                else if (adminFilter || actionFilter) type = 'filters';
            }

            const states = {
                empty: {
                    icon: 'fa-journal-whills', title: 'No activity recorded yet.',
                    text: 'Administrative actions, changes, and system events will appear here once activity is detected.',
                },
                search: {
                    icon: 'fa-search', title: 'No activities match your search.',
                    text: 'Try a different keyword, actor name, entity, or IP address.'
                },
                filters: {
                    icon: 'fa-filter',
                    title: 'No activities match the selected filters.',
                    text: 'Try adjusting or clearing your filters.'
                },
                combined: {
                    icon: 'fa-search',
                    title: 'No activities match the current search and filters.',
                    text: 'Try broadening your search or removing some filters.'
                }
            };

            const s = states[type];
            const emptyEl = document.createElement('div');
            emptyEl.className = 'audit__empty';
            emptyEl.innerHTML = `
                <i class="fas ${s.icon} audit__empty-icon"></i>
                <p class="audit__empty-title">${s.title}</p>
                <p class="audit__empty-text">${s.text}</p>
            `;
        
            document.querySelectorAll('.audit__group').forEach(g => { g.style.display = 'none'; });
            auditPanel.appendChild(emptyEl);
        }
    }

    // Search input
    if (searchInput) {
        searchInput.addEventListener('input', filterItems);
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function () {
            searchInput.value = '';
            searchClearBtn.hidden = true;
            filterItems();
            searchInput.focus();
        });
    }

    // Filter selects
    if (filterAdmin) {
        filterAdmin.addEventListener('dropdown:change', filterItems);
    }

    if (filterAction) {
        filterAction.addEventListener('dropdown:change', filterItems);
    }

    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function () {
            // Reset Admin dropdown to "All"
            if (filterAdmin) {
                filterAdmin.dataset.value = '';
                filterAdmin.innerHTML = 'All <i class="fas fa-chevron-down"></i>';
            }

            // Reset Action dropdown to "All"
            if (filterAction) {
                filterAction.dataset.value = '';
                filterAction.innerHTML = 'All <i class="fas fa-chevron-down"></i>';
            }

            clearFiltersBtn.hidden = true;
            filterItems();
        });
    }

    // Export CSV
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const rows = [];
            document.querySelectorAll('.audit__item').forEach(item => {
                if (item.style.display === 'none') return;
                const id = item.dataset.id || '';
                const actor = item.querySelector('.audit__actor')?.textContent?.trim() || '';
                const action = item.dataset.action || '';
                const entity = item.dataset.entityType || '';
                const entityId = item.dataset.entityId || '';
                const desc = item.querySelector('.audit__item-description')?.textContent?.trim() || '';
                const ip = item.querySelector('.audit__meta-chip--ip')?.textContent?.trim() || '';
                rows.push(
                    [id, actor, action, entity, entityId, desc, ip]
                        .map(v => `"${String(v).replace(/"/g, '""')}"`)
                        .join(',')
                );
            });

            const csv = 'ID,Actor,Action,Entity,Entity ID,Description,IP\n' + rows.join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'audit-export.csv';
            a.click();
            URL.revokeObjectURL(url);
        });
    }

    const loadMoreBtn = document.getElementById('js-load-more');

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', async () => {
            const offset = Number(loadMoreBtn.dataset.offset);
            
            try {
                if (loadMoreBtn.dataset.loading === '1') return;
                loadMoreBtn.dataset.loading = '1';
                loadMoreBtn.disabled = true;
                loadMoreBtn.textContent = 'Loading...';
                const res = await fetch(`api/activity_logs?offset=${offset}`);

                if (!res.ok) {
                    UI.toastError?.("Failed to load more, please try again ");
                    throw new Error('Failed to load logs');
                }

                const data = await res.json();

                const temp = document.createElement('div');
                temp.innerHTML = data.html;

                temp.querySelectorAll('.audit__group').forEach(newGroup => {
                    const label = newGroup.dataset.group;

                    const existingGroup = auditPanel.querySelector(
                        `.audit__group[data-group="${CSS.escape(label)}"]`
                    );

                    if (existingGroup) {
                        const existingList = existingGroup.querySelector('.audit__list');
                        const newItems = newGroup.querySelector('.audit__list');

                        existingList.insertAdjacentHTML(
                            'beforeend',
                            newItems.innerHTML
                        );
                    } else {
                        auditPanel.appendChild(newGroup);
                    }
                });

                loadMoreBtn.dataset.offset = offset + 100;

                if (!data.hasMore) {
                    loadMoreBtn.remove();
                    return;
                }

                attachExpandListeners();
                filterItems();
            } catch (err) {
                console.error(err);
                loadMoreBtn.textContent = 'Try Again';
            } finally {
                delete loadMoreBtn.dataset.loading;

                if (document.body.contains(loadMoreBtn)) {
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Load More';
                }
            }
        });
    }

    // Initialize
    attachExpandListeners();
    filterItems();
})();