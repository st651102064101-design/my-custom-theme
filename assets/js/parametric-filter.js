/**
 * Parametric Filter — KV Electronics
 * Client-side filtering of products by spec values / text search / sort
 */
(function () {
    'use strict';

    const grid         = document.getElementById('pfProductGrid');
    if (!grid) return; // not on a filterable page

    const cards        = Array.from(grid.querySelectorAll('.pf-product-card'));
    const checkboxes   = Array.from(document.querySelectorAll('.pf-checkbox'));
    const searchInput  = document.getElementById('pfSearchInput');
    const sortSelect   = document.getElementById('pfSortSelect');
    const resultCount  = document.getElementById('pfResultCount');
    const activeTags   = document.getElementById('pfActiveTags');
    const clearAllBtn  = document.getElementById('pfClearAll');
    const resetBtn     = document.getElementById('pfResetBtn');
    const noResults    = document.getElementById('pfNoResults');
    const toggles      = Array.from(document.querySelectorAll('.pf-group-toggle'));
    const mobileToggle = document.getElementById('pfMobileToggle');
    const sidebar      = document.getElementById('pfSidebar');
    const overlay      = document.getElementById('pfOverlay');

    // ── Initial value counts ──
    function updateValueCounts() {
        const visibleSpecs = {};
        cards.forEach(card => {
            if (card.classList.contains('pf-hidden')) return;
            const specs = JSON.parse(card.dataset.specs || '{}');
            Object.entries(specs).forEach(([k, v]) => {
                const key = k + '::' + v;
                visibleSpecs[key] = (visibleSpecs[key] || 0) + 1;
            });
        });

        document.querySelectorAll('.pf-value-count').forEach(el => {
            const k = el.dataset.countKey + '::' + el.dataset.countValue;
            const count = visibleSpecs[k] || 0;
            el.textContent = '(' + count + ')';
        });
    }

    // ── Apply Filters ──
    function applyFilters() {
        // Gather active filters: { spec_key: [value1, value2, ...] }
        const activeFilters = {};
        checkboxes.forEach(cb => {
            if (cb.checked) {
                const key = cb.dataset.specKey;
                if (!activeFilters[key]) activeFilters[key] = [];
                activeFilters[key].push(cb.dataset.specValue);
            }
        });

        const searchTerm = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const filterKeys = Object.keys(activeFilters);
        let visibleCount = 0;

        cards.forEach(card => {
            const specs = JSON.parse(card.dataset.specs || '{}');
            const name = card.dataset.productName || '';

            // Text search: match name or any spec value
            let matchesSearch = true;
            if (searchTerm) {
                const allText = name + ' ' + Object.values(specs).join(' ').toLowerCase();
                matchesSearch = allText.includes(searchTerm);
            }

            // Spec filters: AND across groups, OR within a group
            let matchesFilters = true;
            for (const key of filterKeys) {
                const productVal = specs[key] || '';
                if (!activeFilters[key].includes(productVal)) {
                    matchesFilters = false;
                    break;
                }
            }

            if (matchesSearch && matchesFilters) {
                card.classList.remove('pf-hidden');
                visibleCount++;
            } else {
                card.classList.add('pf-hidden');
            }
        });

        // Update result count
        if (resultCount) {
            resultCount.textContent = visibleCount + ' of ' + cards.length + ' products';
        }

        // Show/hide no-results
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
        grid.style.display = visibleCount === 0 ? 'none' : '';

        // Show/hide clear-all button
        const hasActive = filterKeys.length > 0 || searchTerm;
        if (clearAllBtn) clearAllBtn.style.display = hasActive ? '' : 'none';

        // Update badges per group
        document.querySelectorAll('.pf-group').forEach(group => {
            const key = group.dataset.specKey;
            const count = activeFilters[key] ? activeFilters[key].length : 0;
            const badge = group.querySelector('.pf-badge');
            if (badge) {
                badge.style.display = count > 0 ? '' : 'none';
                badge.textContent = count;
            }
        });

        // Render active tags
        renderActiveTags(activeFilters, searchTerm);

        // Update value counts
        updateValueCounts();
    }

    // ── Active Tags ──
    function renderActiveTags(activeFilters, searchTerm) {
        if (!activeTags) return;
        activeTags.innerHTML = '';
        let hasTags = false;

        if (searchTerm) {
            hasTags = true;
            activeTags.appendChild(createTag('Search: "' + searchTerm + '"', () => {
                searchInput.value = '';
                applyFilters();
            }));
        }

        Object.entries(activeFilters).forEach(([key, values]) => {
            // Find label for this key
            const group = document.querySelector('.pf-group[data-spec-key="' + key + '"]');
            const labelEl = group ? group.querySelector('.pf-group-toggle span') : null;
            const groupLabel = labelEl ? labelEl.textContent.trim() : key;

            values.forEach(val => {
                hasTags = true;
                activeTags.appendChild(createTag(groupLabel + ': ' + val, () => {
                    // Uncheck this specific checkbox
                    const cb = document.querySelector('.pf-checkbox[data-spec-key="' + key + '"][data-spec-value="' + CSS.escape(val) + '"]');
                    if (cb) cb.checked = false;
                    applyFilters();
                }));
            });
        });

        activeTags.style.display = hasTags ? 'flex' : 'none';
    }

    function createTag(text, onRemove) {
        const tag = document.createElement('span');
        tag.className = 'pf-tag';
        tag.innerHTML = '<span>' + escapeHtml(text) + '</span><button type="button" class="pf-tag-remove" aria-label="Remove">&times;</button>';
        tag.querySelector('.pf-tag-remove').addEventListener('click', onRemove);
        return tag;
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ── Sort ──
    function applySort() {
        const val = sortSelect ? sortSelect.value : 'name-asc';
        const sorted = [...cards].sort((a, b) => {
            const nameA = a.dataset.productName || '';
            const nameB = b.dataset.productName || '';
            return val === 'name-desc' ? nameB.localeCompare(nameA) : nameA.localeCompare(nameB);
        });
        sorted.forEach(card => grid.appendChild(card));
    }

    // ── Group accordion toggles ──
    toggles.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const body = document.getElementById(targetId);
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', !expanded);
            if (body) body.classList.toggle('pf-collapsed', expanded);
        });
    });

    // ── Mobile sidebar ──
    function openSidebar() {
        if (sidebar) sidebar.classList.add('pf-open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('pf-open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    if (mobileToggle) mobileToggle.addEventListener('click', openSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // ── Event listeners ──
    checkboxes.forEach(cb => cb.addEventListener('change', applyFilters));
    if (searchInput) {
        let debounce;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(applyFilters, 200);
        });
    }
    if (sortSelect) sortSelect.addEventListener('change', () => { applySort(); applyFilters(); });

    if (clearAllBtn) clearAllBtn.addEventListener('click', resetAll);
    if (resetBtn) resetBtn.addEventListener('click', resetAll);

    function resetAll() {
        checkboxes.forEach(cb => cb.checked = false);
        if (searchInput) searchInput.value = '';
        applyFilters();
    }

    // ── Init ──
    applyFilters();
    updateValueCounts();

})();
