/**
 * Product Manager — Admin SPA JavaScript
 * World-class CRUD interface for managing products & categories
 */
(function($) {
    'use strict';

    // ============================================
    // STATE
    // ============================================
    const State = {
        categories: [],
        products: [],
        currentCat: 0,
        currentPage: 1,
        totalPages: 1,
        totalProducts: 0,
        globalProductCount: 0,
        search: '',
        statusFilter: '',
        viewMode: 'table',
        selectedIds: [],
        isLoading: false,
        catFlatMap: {},
    };

    // ============================================
    // INIT
    // ============================================
    $(document).ready(function() {
        renderSpecsTab();
        loadCategories();
        loadProducts();
        bindEvents();
        bindKeyboard();
    });

    // ============================================
    // API HELPERS
    // ============================================
    function api(action, data = {}) {
        return $.ajax({
            url: PM.ajax,
            type: 'POST',
            data: { action, nonce: PM.nonce, ...data },
        });
    }

    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    function toast(message, type = 'success') {
        const icons = {
            success: '✅',
            error: '❌',
            info: 'ℹ️',
            warning: '⚠️',
        };
        const $t = $(`
            <div class="pm-toast pm-toast-${type}">
                <span class="pm-toast-icon">${icons[type] || ''}</span>
                <span class="pm-toast-msg">${message}</span>
                <button class="pm-toast-close">&times;</button>
            </div>
        `);
        $('#pm-toast-container').append($t);
        setTimeout(() => $t.addClass('show'), 10);
        setTimeout(() => {
            $t.removeClass('show');
            setTimeout(() => $t.remove(), 300);
        }, 4000);
        $t.find('.pm-toast-close').on('click', () => {
            $t.removeClass('show');
            setTimeout(() => $t.remove(), 300);
        });
    }

    // ============================================
    // CATEGORIES
    // ============================================
    function loadCategories() {
        api('pm_get_categories').done(function(res) {
            if (res.success) {
                const data = res.data;
                // Support both old array format and new {tree, total} format
                const cats  = Array.isArray(data) ? data : (data.tree || []);
                State.categories         = cats;
                State.globalProductCount  = Array.isArray(data) ? State.globalProductCount : (data.total || 0);
                State.totalProducts       = State.globalProductCount; // reset display count
                buildCatFlatMap(cats);
                renderCatTree();
                populateCatDropdowns();
            }
        });
    }

    function buildCatFlatMap(cats, parent = null) {
        if (!parent) State.catFlatMap = {};   // clear before full rebuild
        cats.forEach(c => {
            State.catFlatMap[c.id] = c;
            if (c.children && c.children.length) {
                buildCatFlatMap(c.children, c);
            }
        });
    }

    function renderCatTree() {
        const $tree = $('#pm-cat-tree');
        const search = $('#pm-cat-search').val().toLowerCase();

        if (!State.categories.length) {
            $tree.html(`
                <div class="pm-cat-empty">
                    <p>ยังไม่มี Category</p>
                    <button class="pm-btn pm-btn-sm pm-btn-primary" id="pm-seed-cats">
                        🌱 สร้าง Categories เริ่มต้น
                    </button>
                </div>
            `);
            return;
        }

        let html = renderCatNodes(State.categories, search);

        // "All Products" item at top
        const allActive = State.currentCat === 0 ? 'active' : '';        const totalCount = State.globalProductCount;
        html = `<div class="pm-cat-node pm-cat-all ${allActive}" data-id="0">
            <div class="pm-cat-row" data-id="0">
                <span class="pm-cat-icon">📦</span>
                <span class="pm-cat-name">All Products</span>
                <span class="pm-cat-count">${totalCount}</span>
            </div>
        </div>` + html;

        $tree.html(html);
    }

    function renderCatNodes(cats, search = '', depth = 0) {
        let html = '';
        cats.forEach(c => {
            const matches = !search || c.name.toLowerCase().includes(search);
            const childrenHtml = c.children && c.children.length
                ? renderCatNodes(c.children, search, depth + 1)
                : '';
            const hasMatchingChildren = childrenHtml.includes('pm-cat-node');

            if (!matches && !hasMatchingChildren) return;

            const isActive  = State.currentCat === c.id ? 'active' : '';
            const hasKids   = c.children && c.children.length > 0;
            const expanded  = search || isActive ? 'expanded' : '';
            const arrow     = hasKids
                ? `<span class="pm-cat-arrow ${expanded}">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                   </span>`
                : `<span class="pm-cat-arrow-space"></span>`;

            html += `<div class="pm-cat-node ${expanded}" data-id="${c.id}" style="--depth:${depth}">
                <div class="pm-cat-row ${isActive}" data-id="${c.id}">
                    ${arrow}
                    <span class="pm-cat-icon">${depth === 0 ? '📁' : '📄'}</span>
                    <span class="pm-cat-name">${escHtml(c.name)}</span>
                    <span class="pm-cat-count">${c.count}</span>
                    <div class="pm-cat-actions">
                        <button class="pm-cat-act-btn pm-edit-cat" data-id="${c.id}" title="Edit">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button class="pm-cat-act-btn pm-delete-cat" data-id="${c.id}" data-name="${escAttr(c.name)}" title="Delete">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </div>
                </div>
                ${hasKids ? `<div class="pm-cat-children">${childrenHtml}</div>` : ''}
            </div>`;
        });
        return html;
    }

    function populateCatDropdowns() {
        const flat = flattenCats(State.categories);
        let options = '<option value="">— Select Category —</option>';
        let parentOptions = '<option value="0">— None (Top Level) —</option>';
        let moveOptions = '<option value="">— Select Category —</option>';

        flat.forEach(c => {
            const indent = '—'.repeat(c.depth) + (c.depth ? ' ' : '');
            options += `<option value="${c.id}">${indent}${escHtml(c.name)}</option>`;
            // Only show top-level as parent options
            if (c.depth === 0) {
                parentOptions += `<option value="${c.id}">${escHtml(c.name)}</option>`;
            }
            moveOptions += `<option value="${c.id}">${indent}${escHtml(c.name)}</option>`;
        });

        $('#pm-field-category').html(options);
        $('#pm-cat-parent').html(parentOptions);
        $('#pm-move-cat-select').html(moveOptions);
    }

    function flattenCats(cats, depth = 0) {
        let result = [];
        cats.forEach(c => {
            result.push({ ...c, depth });
            if (c.children && c.children.length) {
                result = result.concat(flattenCats(c.children, depth + 1));
            }
        });
        return result;
    }

    // ============================================
    // PRODUCTS
    // ============================================
    function loadProducts() {
        State.isLoading = true;
        renderLoadingState();

        api('pm_get_products', {
            page: State.currentPage,
            search: State.search,
            category: State.currentCat,
            status: State.statusFilter,
        }).done(function(res) {
            if (res.success) {
                State.products = res.data.products;
                State.totalPages = res.data.total_pages;
                State.totalProducts = res.data.total;
                State.selectedIds = [];
                renderProducts();
                renderPagination();
                updateBulkBar();
            }
        }).always(function() {
            State.isLoading = false;
        });
    }

    function renderLoadingState() {
        const $c = $('#pm-content');
        $c.html(`
            <div class="pm-loading-skeleton">
                ${Array(5).fill('<div class="pm-skeleton-row"><div class="pm-skeleton-thumb"></div><div class="pm-skeleton-text"><div class="pm-skeleton-line w70"></div><div class="pm-skeleton-line w40"></div></div></div>').join('')}
            </div>
        `);
    }

    function renderProducts() {
        const $c = $('#pm-content');

        if (!State.products.length) {
            $c.html(`
                <div class="pm-empty">
                    <div class="pm-empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    </div>
                    <h3>ไม่พบสินค้า</h3>
                    <p>ยังไม่มีสินค้าในหมวดนี้ เพิ่มสินค้าใหม่ได้เลย!</p>
                    <button class="pm-btn pm-btn-primary" onclick="document.getElementById('pm-add-product-btn').click()">
                        + เพิ่มสินค้าใหม่
                    </button>
                </div>
            `);
            return;
        }

        if (State.viewMode === 'grid') {
            renderGrid();
        } else {
            renderTable();
        }
    }

    function renderTable() {
        const allChecked = State.selectedIds.length === State.products.length && State.products.length > 0;

        let html = `
        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th class="pm-th-check">
                            <label class="pm-checkbox">
                                <input type="checkbox" id="pm-check-all" ${allChecked ? 'checked' : ''}>
                                <span class="pm-checkmark"></span>
                            </label>
                        </th>
                        <th class="pm-th-img"></th>
                        <th>ชื่อสินค้า</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>แก้ไขล่าสุด</th>
                        <th class="pm-th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>`;

        State.products.forEach(p => {
            const checked = State.selectedIds.includes(p.id) ? 'checked' : '';
            const statusBadge = p.status === 'publish'
                ? '<span class="pm-badge pm-badge-green">Published</span>'
                : '<span class="pm-badge pm-badge-yellow">Draft</span>';

            const img = p.image
                ? `<img src="${escAttr(p.image)}" alt="" class="pm-table-thumb" data-id="${p.id}">`
                : `<div class="pm-table-thumb-empty"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>`;

            const modified = timeAgo(p.modified);

            html += `
                <tr class="pm-row" data-id="${p.id}">
                    <td class="pm-td-check">
                        <label class="pm-checkbox">
                            <input type="checkbox" class="pm-row-check" value="${p.id}" ${checked}>
                            <span class="pm-checkmark"></span>
                        </label>
                    </td>
                    <td class="pm-td-img">${img}</td>
                    <td class="pm-td-title">
                        <div class="pm-product-name">${escHtml(p.title)}</div>
                    </td>
                    <td class="pm-td-cat">${escHtml(p.cat_name || '—')}</td>
                    <td class="pm-td-status">${statusBadge}</td>
                    <td class="pm-td-date">${modified}</td>
                    <td class="pm-td-actions">
                        <div class="pm-action-group">
                            <button class="pm-action-btn pm-edit-product" data-id="${p.id}" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <a href="${escAttr(p.permalink)}" target="_blank" class="pm-action-btn" title="View">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            </a>
                            <button class="pm-action-btn pm-action-danger pm-delete-product" data-id="${p.id}" data-title="${escAttr(p.title)}" title="Delete">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });

        html += `</tbody></table></div>`;
        html += `<div class="pm-table-footer">
            <span class="pm-result-count">แสดง ${State.products.length} จาก ${State.totalProducts} รายการ</span>
        </div>`;

        $('#pm-content').html(html);
    }

    function renderGrid() {
        let html = '<div class="pm-grid">';
        State.products.forEach(p => {
            const img = p.image
                ? `<img src="${escAttr(p.image)}" alt="" class="pm-grid-thumb">`
                : `<div class="pm-grid-thumb-empty"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>`;

            const statusBadge = p.status === 'publish'
                ? '<span class="pm-badge pm-badge-green">Published</span>'
                : '<span class="pm-badge pm-badge-yellow">Draft</span>';

            html += `
            <div class="pm-grid-card" data-id="${p.id}">
                <div class="pm-grid-img-wrap">
                    ${img}
                    <div class="pm-grid-overlay">
                        <button class="pm-btn pm-btn-sm pm-btn-white pm-edit-product" data-id="${p.id}">Edit</button>
                    </div>
                </div>
                <div class="pm-grid-body">
                    <h4 class="pm-grid-title">${escHtml(p.title)}</h4>
                    <div class="pm-grid-meta">
                        ${statusBadge}
                        <span class="pm-grid-cat">${escHtml(p.cat_name || '')}</span>
                    </div>
                </div>
                <div class="pm-grid-footer">
                    <button class="pm-action-btn pm-edit-product" data-id="${p.id}" title="Edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <a href="${escAttr(p.permalink)}" target="_blank" class="pm-action-btn" title="View">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>
                    <button class="pm-action-btn pm-action-danger pm-delete-product" data-id="${p.id}" data-title="${escAttr(p.title)}" title="Delete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </div>
            </div>`;
        });
        html += '</div>';
        html += `<div class="pm-table-footer"><span class="pm-result-count">แสดง ${State.products.length} จาก ${State.totalProducts} รายการ</span></div>`;
        $('#pm-content').html(html);
    }

    function renderPagination() {
        if (State.totalPages <= 1) {
            $('#pm-pagination').html('');
            return;
        }

        let html = '<div class="pm-pag-inner">';
        
        // Prev
        html += `<button class="pm-pag-btn" ${State.currentPage <= 1 ? 'disabled' : ''} data-page="${State.currentPage - 1}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </button>`;

        // Page numbers
        const range = getPageRange(State.currentPage, State.totalPages, 5);
        if (range[0] > 1) {
            html += `<button class="pm-pag-btn" data-page="1">1</button>`;
            if (range[0] > 2) html += `<span class="pm-pag-dots">...</span>`;
        }
        range.forEach(p => {
            html += `<button class="pm-pag-btn ${p === State.currentPage ? 'active' : ''}" data-page="${p}">${p}</button>`;
        });
        if (range[range.length - 1] < State.totalPages) {
            if (range[range.length - 1] < State.totalPages - 1) html += `<span class="pm-pag-dots">...</span>`;
            html += `<button class="pm-pag-btn" data-page="${State.totalPages}">${State.totalPages}</button>`;
        }

        // Next
        html += `<button class="pm-pag-btn" ${State.currentPage >= State.totalPages ? 'disabled' : ''} data-page="${State.currentPage + 1}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>`;

        html += '</div>';
        $('#pm-pagination').html(html);
    }

    function getPageRange(current, total, size) {
        let start = Math.max(1, current - Math.floor(size / 2));
        let end = Math.min(total, start + size - 1);
        start = Math.max(1, end - size + 1);
        const range = [];
        for (let i = start; i <= end; i++) range.push(i);
        return range;
    }

    // ============================================
    // BREADCRUMB
    // ============================================
    function updateBreadcrumb() {
        let html = `<span class="pm-breadcrumb-item ${State.currentCat === 0 ? 'active' : 'clickable'}" data-id="0">All Products</span>`;
        
        if (State.currentCat) {
            // Walk up the hierarchy
            const trail = [];
            let node = State.catFlatMap[State.currentCat];
            while (node) {
                trail.unshift(node);
                node = node.parent ? State.catFlatMap[node.parent] : null;
            }
            trail.forEach((n, i) => {
                html += ' <span class="pm-breadcrumb-sep">/</span> ';
                if (i === trail.length - 1) {
                    html += `<span class="pm-breadcrumb-item active">${escHtml(n.name)}</span>`;
                } else {
                    html += `<span class="pm-breadcrumb-item clickable" data-id="${n.id}">${escHtml(n.name)}</span>`;
                }
            });
        }

        if (State.search) {
            html += ` <span class="pm-breadcrumb-sep">/</span> <span class="pm-breadcrumb-item active">Search: "${escHtml(State.search)}"</span>`;
        }

        $('#pm-breadcrumb').html(html);
    }

    // ============================================
    // PRODUCT FORM (SLIDE-OVER PANEL)
    // ============================================

    /**
     * Dynamically render input fields in the Specs tab from PM.specFields.
     * Called once at init and again after spec fields are updated.
     */
    function renderSpecsTab(preservedValues = null) {
        const $wrap = $('#pm-specs-fields-wrap');

        const existingValues = {};
        $wrap.find('input[id^="pm-field-"]').each(function() {
            const id = String($(this).attr('id') || '');
            const key = id.replace(/^pm-field-/, '');
            if (key) existingValues[key] = $(this).val();
        });

        const valueMap = Object.assign({}, existingValues, preservedValues || {});
        $wrap.empty();

        const allFields = Array.isArray(PM.specFields) ? PM.specFields : [];
        const orderedFields = allFields;

        orderedFields.forEach(function(f, idx) {
            const v = valueMap[f.key] == null ? '' : String(valueMap[f.key]);
            $wrap.append(
                '<div class="pm-form-group">' +
                '<label class="pm-label">' + escHtml(f.label) + '</label>' +
                '<input type="text" id="pm-field-' + escAttr(f.key) + '" class="pm-input" value="' + escAttr(v) + '" placeholder="กรอก ' + escAttr(f.label) + '">' +
                '</div>'
            );
        });
    }

    // ============================================
    // PER-PRODUCT CUSTOM ATTRIBUTES
    // ============================================
    function renderProductAttrs(attrs) {
        const $wrap = $('#pm-product-attrs-wrap');
        $wrap.empty();
        if (!Array.isArray(attrs)) attrs = [];
        attrs.forEach(function(attr, idx) {
            const type = attr.type || 'text';
            const options = attr.options || '';
            let valueHtml;
            if (type === 'select') {
                const optArr = options.split(',').map(function(o){ return o.trim(); }).filter(Boolean);
                let selectOpts = '<option value="">-- เลือก --</option>';
                optArr.forEach(function(o) {
                    selectOpts += '<option value="' + escAttr(o) + '"' + (attr.value === o ? ' selected' : '') + '>' + escAttr(o) + '</option>';
                });
                valueHtml =
                    '<div class="pm-attr-value-wrap" style="display:flex;flex-direction:column;gap:4px;">' +
                        '<input type="text" class="pm-input pm-attr-options" value="' + escAttr(options) + '" placeholder="ตัวเลือก คั่นด้วย , เช่น A, B, C" style="font-size:.8rem;">' +
                        '<select class="pm-input pm-attr-value" style="font-size:.85rem;">' + selectOpts + '</select>' +
                    '</div>';
            } else {
                valueHtml = '<input type="text" class="pm-input pm-attr-value" value="' + escAttr(attr.value || '') + '" placeholder="Value เช่น 50g" style="font-size:.85rem;">';
            }
            $wrap.append(
                '<div class="pm-attr-row" data-idx="' + idx + '" style="display:grid;grid-template-columns:1fr 70px 1fr 32px;gap:6px;align-items:start;">' +
                    '<input type="text" class="pm-input pm-attr-label" value="' + escAttr(attr.label || '') + '" placeholder="Label เช่น Weight" style="font-size:.85rem;">' +
                    '<select class="pm-input pm-attr-type" style="font-size:.78rem;padding:6px 4px;">' +
                        '<option value="text"' + (type === 'text' ? ' selected' : '') + '>Text</option>' +
                        '<option value="select"' + (type === 'select' ? ' selected' : '') + '>Select</option>' +
                    '</select>' +
                    valueHtml +
                    '<button type="button" class="pm-remove-attr-btn" style="padding:4px 8px;border-radius:6px;border:1px solid #fecaca;background:#fff5f5;color:#ef4444;cursor:pointer;font-size:.8rem;line-height:1;margin-top:4px;" title="ลบ">✕</button>' +
                '</div>'
            );
        });
        updateCustomAttrsField();
    }

    /* Collect all attr rows (including empty labels for internal re-render) */
    function collectProductAttrsAll() {
        const attrs = [];
        $('#pm-product-attrs-wrap .pm-attr-row').each(function() {
            const type = $(this).find('.pm-attr-type').val() || 'text';
            attrs.push({
                label:   $(this).find('.pm-attr-label').val() || '',
                type:    type,
                value:   $(this).find('.pm-attr-value').val() || '',
                options: $(this).find('.pm-attr-options').val() || ''
            });
        });
        return attrs;
    }

    function collectProductAttrs() {
        return collectProductAttrsAll().filter(function(a) { return a.label.trim() !== ''; });
    }

    function updateCustomAttrsField() {
        $('#pm-field-custom-attrs').val(JSON.stringify(collectProductAttrs()));
    }

    let _isCreatingProduct = false;
    let _autoTitleEnabled  = false;

    function suggestTitleFromCategory(catId) {
        if (!_isCreatingProduct) return;
        const categoryId = parseInt(catId || 0, 10);
        if (!categoryId) return;

        api('pm_suggest_product_title', { category: categoryId }).done(function(res) {
            if (!res || !res.success || !res.data || !res.data.title) return;
            const current = ($('#pm-field-title').val() || '').trim();
            if (_autoTitleEnabled || current === '') {
                $('#pm-field-title').val(res.data.title).data('pmAutoTitle', res.data.title);
                _autoTitleEnabled = true;
            }
        });
    }

    function setActiveFormTab(tab) {
        const tabName = tab || 'general';
        $('.pm-form-tab').removeClass('active');
        $(`.pm-form-tab[data-tab="${tabName}"]`).addClass('active');
        $('.pm-tab-content').removeClass('active');
        $(`#pm-tab-${tabName}`).addClass('active');
    }

    function openProductEditorById(id, tab = 'general') {
        api('pm_get_product', { id }).done(function(res) {
            if (res.success) {
                openPanel(res.data, tab);
            } else {
                toast('ไม่พบข้อมูลสินค้า', 'error');
            }
        });
    }

    function openPanel(product = null, initialTab = 'general') {
        const $panel = $('#pm-panel');
        const $overlay = $('#pm-overlay');

        // Reset form
        resetProductForm();

        if (product) {
            _isCreatingProduct = false;
            _autoTitleEnabled = false;
            $('#pm-panel-title').text('แก้ไขสินค้า');
            $('#pm-edit-id').val(product.id);
            $('#pm-field-title').val(product.title || '');
            $('#pm-field-status').val(product.status || 'publish');
            $('#pm-field-category').val(product.category || '');
            // Set TinyMCE content
            if (typeof tinymce !== 'undefined' && tinymce.get('pm-field-content')) {
                tinymce.get('pm-field-content').setContent(product.content || '');
            } else {
                $('#pm-field-content').val(product.content || '');
            }

            // Gallery images - render dynamically from gallery array
            const gallery = product.gallery || [];
            renderGalleryGrid(gallery);

            // Spec fields — populate dynamically from PM.specFields
            (PM.specFields || []).forEach(function(f) {
                $('#pm-field-' + f.key).val(product[f.key] || '');
            });

            // Details tab fields
            $('#pm-field-subtitle').val(product.pd_subtitle || '');
            $('#pm-field-datasheet').val(product.pd_datasheet || '');
            renderDatasheetPreview(product.pd_datasheet || '');

            // Per-product custom attributes
            renderProductAttrs(product.custom_attrs || []);
        } else {
            _isCreatingProduct = true;
            _autoTitleEnabled = false;
            $('#pm-panel-title').text('เพิ่มสินค้าใหม่');
            // Pre-select current category
            if (State.currentCat) {
                $('#pm-field-category').val(State.currentCat);
                suggestTitleFromCategory(State.currentCat);
            }
        }

        $overlay.addClass('active');
        $panel.addClass('active');
        $('body').addClass('pm-panel-open');
        setActiveFormTab(initialTab);

        // Focus title field
        setTimeout(() => $('#pm-field-title').focus(), 300);
    }

    function closePanel() {
        $('#pm-panel').removeClass('active');
        $('#pm-overlay').removeClass('active');
        $('body').removeClass('pm-panel-open');
    }

    function resetProductForm() {
        $('#pm-product-form')[0].reset();
        $('#pm-edit-id').val('');
        renderGalleryGrid([]);
        $('#pm-field-gallery').val('');

        // Clear TinyMCE editor
        if (typeof tinymce !== 'undefined' && tinymce.get('pm-field-content')) {
            tinymce.get('pm-field-content').setContent('');
        }

        // Clear details fields
        $('#pm-field-subtitle').val('');
        $('#pm-field-datasheet').val('');
        renderDatasheetPreview('');

        // Clear per-product custom attrs
        renderProductAttrs([]);

        // Reset tabs
        $('.pm-form-tab').removeClass('active').first().addClass('active');
        $('.pm-tab-content').removeClass('active').first().addClass('active');
    }

    function renderGalleryGrid(gallery) {
        const $grid = $('#pm-gallery-grid');
        $grid.empty();
        
        if (!Array.isArray(gallery)) gallery = [];
        
        gallery.forEach((url, index) => {
            const $box = $(`
                <div class="pm-gallery-item" data-index="${index}">
                    <div class="pm-gallery-preview">
                        ${url ? `<img src="${escAttr(url)}" alt="">` : '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>'}
                    </div>
                    <div class="pm-gallery-buttons">
                        <button type="button" class="pm-btn pm-btn-sm pm-upload-gallery-btn" data-index="${index}">📁 อัปโหลด</button>
                        <button type="button" class="pm-btn pm-btn-sm pm-btn-danger pm-remove-gallery-btn" data-index="${index}">✕ ลบ</button>
                    </div>
                    <input type="hidden" class="pm-gallery-url-input" value="${escAttr(url)}">
                </div>
            `);
            $grid.append($box);
        });
        
        updateGalleryField();
    }

    function updateGalleryField() {
        const gallery = [];
        $('#pm-gallery-grid .pm-gallery-url-input').each(function() {
            const url = $(this).val().trim();
            if (url) gallery.push(url);
        });
        $('#pm-field-gallery').val(JSON.stringify(gallery));
    }

    function renderDatasheetPreview(url) {
        const cleanUrl = (url || '').trim();
        if (!cleanUrl) {
            $('#pm-datasheet-preview').text('No datasheet uploaded');
            return;
        }
        $('#pm-datasheet-preview').html(`<a href="${escAttr(cleanUrl)}" target="_blank" rel="noopener">📄 View current datasheet</a>`);
    }

    function saveProduct() {
        const id = $('#pm-edit-id').val();
        const title = $('#pm-field-title').val().trim();

        if (!title) {
            toast('กรุณาใส่ชื่อสินค้า', 'error');
            $('#pm-field-title').focus();
            return;
        }

        // Ensure gallery field is up-to-date
        updateGalleryField();

        const data = {
            id,
            title,
            content: (typeof tinymce !== 'undefined' && tinymce.get('pm-field-content')) ? tinymce.get('pm-field-content').getContent() : $('#pm-field-content').val(),
            status: $('#pm-field-status').val(),
            category: $('#pm-field-category').val(),
            pd_gallery: $('#pm-field-gallery').val(),
            // Details tab
            pd_subtitle: $('#pm-field-subtitle').val(),
            pd_datasheet: $('#pm-field-datasheet').val(),
        };

        // Add all spec fields dynamically
        (PM.specFields || []).forEach(function(f) {
            data[f.key] = $('#pm-field-' + f.key).val();
        });

        // Per-product custom attributes
        data.custom_attrs = JSON.stringify(collectProductAttrs());

        const $btn = $('#pm-save-product');
        $btn.prop('disabled', true).html('⏳ กำลังบันทึก...');

        api('pm_save_product', data).done(function(res) {
            if (res.success) {
                toast(res.data.message, 'success');
                closePanel();
                loadProducts();
                loadCategories();
            } else {
                toast(res.data.message || 'เกิดข้อผิดพลาด', 'error');
            }
        }).fail(function() {
            toast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        }).always(function() {
            $btn.prop('disabled', false).html(`
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                บันทึก
            `);
        });
    }

    // ============================================
    // CATEGORY MODAL
    // ============================================
    function openCatModal(category = null) {
        $('#pm-cat-form')[0].reset();
        $('#pm-cat-edit-id').val('');
        $('#pm-cat-image').val('');
        $('#pm-cat-image-preview').html('');
        $('#pm-cat-image-remove').hide();
        $('#pm-cat-desc-long').val('');
        renderSpecRows([]);

        if (category) {
            $('#pm-cat-modal-title').text('แก้ไข Category');
            $('#pm-cat-edit-id').val(category.id);
            $('#pm-cat-name').val(category.name);
            $('#pm-cat-parent').val(category.parent);
            $('#pm-cat-desc').val(category.desc || '');
            $('#pm-cat-desc-long').val(category.desc_long || '');
            // Parse specs
            let specs = [];
            try { specs = JSON.parse(category.specs || '[]'); } catch(e) {}
            renderSpecRows(specs);
            if (category.image) {
                $('#pm-cat-image').val(category.image);
                $('#pm-cat-image-preview').html(`<img src="${escAttr(category.image)}" alt="">`);
                $('#pm-cat-image-remove').show();
            }
        } else {
            $('#pm-cat-modal-title').text('เพิ่ม Category ใหม่');
        }

        $('#pm-cat-modal-overlay').addClass('active');
        $('#pm-cat-modal').addClass('active');
        setTimeout(() => $('#pm-cat-name').focus(), 200);
    }

    function renderSpecRows(specs) {
        const $wrap = $('#pm-cat-specs-wrap');
        $wrap.empty();
        (specs || []).forEach((s, i) => addSpecRow(s.label, s.value, s.icon));
    }

    function addSpecRow(label = '', value = '', icon = '') {
        const $wrap = $('#pm-cat-specs-wrap');
        const idx   = $wrap.children().length;
        $wrap.append(`
            <div class="pm-spec-row" style="display:grid;grid-template-columns:1fr 1fr 80px 32px;gap:6px;align-items:center;">
                <input type="text" class="pm-input pm-spec-label" placeholder="Label (เช่น Size Range)" value="${escAttr(label)}" style="font-size:.85rem;">
                <input type="text" class="pm-input pm-spec-value" placeholder="Value" value="${escAttr(value)}" style="font-size:.85rem;">
                <input type="text" class="pm-input pm-spec-icon" placeholder="icon" value="${escAttr(icon)}" style="font-size:.85rem;">
                <button type="button" class="pm-btn pm-btn-sm" style="padding:4px 8px;color:#ef4444;background:none;border:1px solid #fecaca;" onclick="$(this).closest('.pm-spec-row').remove()">✕</button>
            </div>
        `);
    }

    function collectSpecs() {
        const specs = [];
        $('#pm-cat-specs-wrap .pm-spec-row').each(function() {
            const label = $(this).find('.pm-spec-label').val().trim();
            if (label) {
                specs.push({
                    label,
                    value: $(this).find('.pm-spec-value').val().trim(),
                    icon:  $(this).find('.pm-spec-icon').val().trim(),
                });
            }
        });
        return JSON.stringify(specs);
    }

    function closeCatModal() {
        $('#pm-cat-modal-overlay').removeClass('active');
        $('#pm-cat-modal').removeClass('active');
    }

    function saveCategory() {
        const name = $('#pm-cat-name').val().trim();
        if (!name) {
            toast('กรุณาใส่ชื่อ Category', 'error');
            return;
        }

        const data = {
            id: $('#pm-cat-edit-id').val(),
            name,
            parent: $('#pm-cat-parent').val(),
            desc: $('#pm-cat-desc').val(),
            desc_long: $('#pm-cat-desc-long').val(),
            specs: collectSpecs(),
            image: $('#pm-cat-image').val(),
        };

        const $btn = $('#pm-cat-save');
        $btn.prop('disabled', true).text('⏳ กำลังบันทึก...');

        api('pm_save_category', data).done(function(res) {
            if (res.success) {
                toast(res.data.message, 'success');
                closeCatModal();
                loadCategories();
                loadProducts();
            } else {
                toast(res.data.message || 'เกิดข้อผิดพลาด', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).text('💾 บันทึก');
        });
    }

    // ============================================
    // SPEC FIELDS MANAGER MODAL
    // ============================================
    let _specFieldsData = []; // cached data loaded from server
    let _disabledBuiltinKeys = []; // builtin keys marked deleted

    function openSpecFieldsManager() {
        api('pm_get_spec_fields').done(function(res) {
            if (res.success) {
                const allFields = res.data.fields || [];
                _disabledBuiltinKeys = allFields
                    .filter(f => !!f.builtin && !!f.disabled)
                    .map(f => String(f.key));
                _specFieldsData = allFields.filter(f => !(!!f.builtin && !!f.disabled));
                renderSpecFieldsManager(_specFieldsData);
                $('#pm-icons-modal-overlay').addClass('active');
                $('#pm-icons-modal').addClass('active');
                setTimeout(() => $('#pm-new-field-label').focus(), 200);
            } else {
                toast('ไม่สามารถโหลด Spec Fields ได้', 'error');
            }
        });
    }

    function renderSpecFieldsManager(fields) {
        const $container = $('#pm-icons-fields');
        $container.empty();
        (fields || []).forEach(function(f) {
            const isSvg  = f.icon_type === 'svg';
            const rowHtml =
                '<div class="pm-sf-row" data-key="' + escAttr(f.key) + '" data-builtin="' + (f.builtin ? '1' : '0') + '" ' +
                    'style="border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;background:#fff;">' +
                    '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">' +
                        '<div style="flex:1;display:flex;align-items:center;gap:6px;">' +
                            '<span style="font-size:.7rem;font-family:monospace;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:1px 6px;color:#64748b;white-space:nowrap;">' + escHtml(f.key) + '</span>' +
                            '<input type="text" class="pm-input pm-sf-label-input" value="' + escAttr(f.label) + '" placeholder="ชื่อ attr เช่น ' + escAttr(f.label || 'Wire Size') + '" style="font-size:.8rem;padding:4px 8px;height:auto;' + (f.builtin ? 'font-weight:600;' : '') + '">' +
                        '</div>' +
                        '<div style="display:flex;gap:4px;align-items:center;">' +
                            '<button type="button" class="pm-sf-move-up" title="ย้ายขึ้น" style="font-size:.75rem;padding:3px 8px;border-radius:4px;border:1px solid #dbeafe;background:#eff6ff;color:#1d4ed8;cursor:pointer;">↑</button>' +
                            '<button type="button" class="pm-sf-move-down" title="ย้ายลง" style="font-size:.75rem;padding:3px 8px;border-radius:4px;border:1px solid #dbeafe;background:#eff6ff;color:#1d4ed8;cursor:pointer;">↓</button>' +
                            '<button type="button" class="pm-icon-type-toggle ' + (!isSvg ? 'active' : '') + '" data-type="fa" ' +
                                'style="font-size:.7rem;padding:3px 8px;border-radius:4px;border:1px solid #e2e8f0;background:' + (!isSvg ? 'var(--pm-primary,#3b82f6);color:#fff' : '#f8fafc;color:#64748b') + ';cursor:pointer;">FA</button>' +
                            '<button type="button" class="pm-icon-type-toggle ' + (isSvg ? 'active' : '') + '" data-type="svg" ' +
                                'style="font-size:.7rem;padding:3px 8px;border-radius:4px;border:1px solid #e2e8f0;background:' + (isSvg ? 'var(--pm-primary,#3b82f6);color:#fff' : '#f8fafc;color:#64748b') + ';cursor:pointer;">SVG</button>' +
                            (f.builtin
                                ? '<button type="button" class="pm-toggle-builtin-field" data-key="' + escAttr(f.key) + '" ' +
                                    'style="font-size:.75rem;padding:3px 8px;border-radius:4px;border:1px solid #fecaca;background:#fff5f5;color:#ef4444;cursor:pointer;" title="ลบ field นี้" onclick="return confirm(\'ยืนยันการลบ field นี้?\\n\\nข้อมูล attr ที่เคยกรอกในสินค้าจะยังคงอยู่ในฐานข้อมูล แต่ field นี้จะถูกซ่อนจากฟอร์มและหน้าแสดงผลจนกว่าจะกู้คืน\')">✕</button>'
                                : '') +
                            (!f.builtin ? '<button type="button" class="pm-delete-custom-field" data-key="' + escAttr(f.key) + '" ' +
                                'style="font-size:.75rem;padding:3px 8px;border-radius:4px;border:1px solid #fecaca;background:#fff5f5;color:#ef4444;cursor:pointer;" title="ลบ field นี้">✕</button>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="pm-sf-icon-wrap">' +
                        (!isSvg
                                                        ? '<input type="text" class="pm-input pm-sf-icon-val" placeholder="ไอคอน FA เช่น fa fa-bolt" value="' + escAttr(f.icon_type === 'fa' ? f.icon_value : '') + '" ' +
                              'style="font-size:.8rem;padding:5px 10px;height:auto;">'
                                                        : '<textarea class="pm-input pm-sf-icon-val" rows="3" placeholder="วาง SVG เช่น &lt;svg ...&gt;&lt;/svg&gt;" ' +
                              'style="font-size:.75rem;font-family:monospace;padding:5px 10px;resize:vertical;">' + escHtml(f.icon_value) + '</textarea>'
                        ) +
                    '</div>' +
                '</div>';
            $container.append(rowHtml);
        });
    }

    function addCustomFieldRow(label) {
        // Generate slug key from label
        const slug = label.toLowerCase()
            .replace(/[^a-z0-9\u0E00-\u0E7F]+/g, '_')
            .replace(/^_+|_+$/g, '')
            .substring(0, 40);
        const key = 'pd_custom_' + slug + '_' + Date.now().toString(36);

        const rowData = {
            key: key,
            label: label,
            builtin: false,
            icon_type: 'fa',
            icon_value: '',
        };
        // Append to current fields data
        _specFieldsData.push(rowData);
        renderSpecFieldsManager(_specFieldsData);
        // Clear input
        $('#pm-new-field-label').val('').focus();
    }

    function saveSpecFields() {
        const customFields = [];
        const builtinLabels = {};
        const icons = {};
        const fieldOrder = [];

        $('#pm-icons-fields .pm-sf-row').each(function() {
            const $row     = $(this);
            const key      = $row.data('key');
            const isBuiltin = $row.data('builtin') === '1' || $row.data('builtin') === 1;
            const labelInput = $row.find('.pm-sf-label-input').val().trim();
            const iconVal  = $row.find('.pm-sf-icon-val').val().trim();

            fieldOrder.push(String(key));
            icons[key] = iconVal;

            if (isBuiltin && labelInput) {
                builtinLabels[key] = labelInput;
            }

            if (!isBuiltin) {
                if (labelInput) {
                    customFields.push({ key: String(key), label: labelInput });
                }
            }
        });

        const $btn = $('#pm-icons-save');
        $btn.prop('disabled', true).text('⏳ กำลังบันทึก...');

        api('pm_save_spec_fields', {
            custom_fields: JSON.stringify(customFields),
            builtin_labels: JSON.stringify(builtinLabels),
            disabled_builtin_keys: JSON.stringify(Array.from(new Set(_disabledBuiltinKeys))),
            field_order: JSON.stringify(fieldOrder),
            icons: JSON.stringify(icons),
        }).done(function(res) {
            if (res.success) {
                // Update PM globals with fresh data
                PM.specFields = res.data.spec_fields;
                PM.specIcons  = res.data.icons;
                // Re-render the Specs tab inputs
                renderSpecsTab();
                toast(res.data.message, 'success');
                closeIconsModal();
            } else {
                toast(res.data.message || 'เกิดข้อผิดพลาด', 'error');
            }
        }).fail(function() {
            toast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        }).always(function() {
            $btn.prop('disabled', false).html('💾 บันทึก Spec Fields');
        });
    }

    function closeIconsModal() {
        $('#pm-icons-modal-overlay').removeClass('active');
        $('#pm-icons-modal').removeClass('active');
    }

    // ============================================
    // DELETE CONFIRMATION
    // ============================================
    let pendingDelete = null;

    function showDeleteConfirm(message, onConfirm) {
        pendingDelete = onConfirm;
        $('#pm-delete-message').text(message);
        $('#pm-delete-overlay').addClass('active');
        $('#pm-delete-modal').addClass('active');
    }

    function hideDeleteConfirm() {
        $('#pm-delete-overlay').removeClass('active');
        $('#pm-delete-modal').removeClass('active');
        pendingDelete = null;
    }

    // ============================================
    // BULK ACTIONS
    // ============================================
    function updateBulkBar() {
        const count = State.selectedIds.length;
        if (count > 0) {
            $('#pm-selected-count').text(count);
            $('#pm-bulk-bar').slideDown(200);
        } else {
            $('#pm-bulk-bar').slideUp(200);
        }
    }

    // ============================================
    // EVENT BINDINGS
    // ============================================
    function bindEvents() {
        // --- CATEGORY TREE ---
        $(document).on('click', '.pm-cat-row', function(e) {
            if ($(e.target).closest('.pm-cat-actions').length) return;
            const id = parseInt($(this).data('id'));
            State.currentCat = id;
            State.currentPage = 1;
            State.search = '';
            $('#pm-search').val('');
            renderCatTree();
            updateBreadcrumb();
            loadProducts();
        });

        $(document).on('click', '.pm-cat-arrow', function(e) {
            e.stopPropagation();
            const $node = $(this).closest('.pm-cat-node');
            $node.toggleClass('expanded');
            $(this).toggleClass('expanded');
        });

        // Category search
        let catSearchTimer;
        $('#pm-cat-search').on('input', function() {
            clearTimeout(catSearchTimer);
            catSearchTimer = setTimeout(() => renderCatTree(), 200);
        });

        // Expand/Collapse All
        $('#pm-expand-all').on('click', function() {
            $('.pm-cat-node').addClass('expanded');
            $('.pm-cat-arrow').addClass('expanded');
        });
        $('#pm-collapse-all').on('click', function() {
            $('.pm-cat-node').removeClass('expanded');
            $('.pm-cat-arrow').removeClass('expanded');
        });

        // Add category
        $('#pm-add-cat-btn').on('click', () => openCatModal());
        $(document).on('click', '.pm-edit-cat', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            const cat = State.catFlatMap[id];
            if (cat) openCatModal(cat);
        });
        $(document).on('click', '.pm-delete-cat', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            const name = $(this).data('name');
            showDeleteConfirm(`ต้องการลบ Category "${name}" หรือไม่?`, function() {
                api('pm_delete_category', { id }).done(function(res) {
                    if (res.success) {
                        toast(res.data.message, 'success');
                        if (State.currentCat === id) State.currentCat = 0;
                        loadCategories();
                        loadProducts();
                    } else {
                        toast(res.data.message, 'error');
                    }
                });
            });
        });

        // Save category
        $('#pm-cat-save').on('click', saveCategory);
        $('#pm-cat-cancel, #pm-cat-modal-close, #pm-cat-modal-overlay').on('click', closeCatModal);

        // Category image
        $('#pm-cat-image-btn').on('click', function() {
            const frame = wp.media({ title: 'เลือกรูป Category', multiple: false });
            frame.on('select', function() {
                const att = frame.state().get('selection').first().toJSON();
                $('#pm-cat-image').val(att.url);
                $('#pm-cat-image-preview').html(`<img src="${att.url}" alt="">`);
                $('#pm-cat-image-remove').show();
            });
            frame.open();
        });
        $('#pm-cat-image-remove').on('click', function() {
            $('#pm-cat-image').val('');
            $('#pm-cat-image-preview').html('');
            $(this).hide();
        });

        // Add spec row
        $(document).on('click', '#pm-cat-spec-add', function() {
            addSpecRow();
        });

        // Seed categories
        $(document).on('click', '#pm-seed-cats', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).text('⏳ กำลังสร้าง...');
            api('pm_seed_categories').done(function(res) {
                if (res.success) {
                    toast(res.data.message, 'success');
                    loadCategories();
                }
            }).always(() => $btn.prop('disabled', false));
        });

        // --- PRODUCTS ---
        // Search
        let searchTimer;
        $('#pm-search').on('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                State.search = $(this).val().trim();
                State.currentPage = 1;
                updateBreadcrumb();
                loadProducts();
            }, 300);
        });

        // Status filter
        $('#pm-status-filter').on('change', function() {
            State.statusFilter = $(this).val();
            State.currentPage = 1;
            loadProducts();
        });

        // View toggle
        $('.pm-view-btn').on('click', function() {
            $('.pm-view-btn').removeClass('active');
            $(this).addClass('active');
            State.viewMode = $(this).data('view');
            renderProducts();
        });

        // Add product
        $('#pm-add-product-btn').on('click', () => openPanel());

        // Edit product
        $(document).on('click', '.pm-edit-product', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = $(this).data('id');
            openProductEditorById(id);
        });

        $(document).on('click', '.pm-table-thumb', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = parseInt($(this).data('id') || $(this).closest('.pm-row').data('id'), 10);
            if (!id) return;
            openProductEditorById(id, 'gallery');
        });

        // Double-click row to edit
        $(document).on('dblclick', '.pm-row', function() {
            const id = $(this).data('id');
            openProductEditorById(id);
        });

        // Delete product
        $(document).on('click', '.pm-delete-product', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            const title = $(this).data('title');
            showDeleteConfirm(`ต้องการลบสินค้า "${title}" หรือไม่?`, function() {
                api('pm_delete_product', { id }).done(function(res) {
                    if (res.success) {
                        toast(res.data.message, 'success');
                        loadProducts();
                        loadCategories();
                    } else {
                        toast(res.data.message, 'error');
                    }
                });
            });
        });

        // Panel
        $('#pm-save-product').on('click', function(e) {
            e.preventDefault();
            saveProduct();
        });
        $('#pm-panel-close, #pm-panel-cancel, #pm-overlay').on('click', closePanel);

        // Form tabs
        $(document).on('click', '.pm-form-tab', function() {
            const tab = $(this).data('tab');
            $('.pm-form-tab').removeClass('active');
            $(this).addClass('active');
            $('.pm-tab-content').removeClass('active');
            $(`#pm-tab-${tab}`).addClass('active');
        });

        $('#pm-field-category').on('change', function() {
            if (!_isCreatingProduct) return;
            const current = ($('#pm-field-title').val() || '').trim();
            if (_autoTitleEnabled || current === '') {
                suggestTitleFromCategory($(this).val());
            }
        });

        $('#pm-field-title').on('input', function() {
            if (!_isCreatingProduct) return;
            const autoVal = ($(this).data('pmAutoTitle') || '').trim();
            const current = ($(this).val() || '').trim();
            if (current !== autoVal) {
                _autoTitleEnabled = false;
            }
        });

        // Gallery management
        $('#pm-add-gallery-btn').on('click', function() {
            renderGalleryGrid([...$('#pm-gallery-grid .pm-gallery-url-input').map((i, el) => $(el).val()).get(), '']);
        });

        // Per-product custom attributes
        $('#pm-add-attr-btn').on('click', function() {
            const current = collectProductAttrsAll();
            current.push({ label: '', type: 'text', value: '', options: '' });
            renderProductAttrs(current);
            // Focus the new label input
            $('#pm-product-attrs-wrap .pm-attr-row:last .pm-attr-label').focus();
        });

        $(document).on('click', '.pm-remove-attr-btn', function() {
            $(this).closest('.pm-attr-row').remove();
            updateCustomAttrsField();
        });

        $(document).on('input', '.pm-attr-label, .pm-attr-value', function() {
            updateCustomAttrsField();
        });

        // Type toggle: re-render all rows preserving data
        $(document).on('change', '.pm-attr-type', function() {
            renderProductAttrs(collectProductAttrsAll());
        });

        // Options input: rebuild select dropdown live
        $(document).on('input', '.pm-attr-options', function() {
            const $row = $(this).closest('.pm-attr-row');
            const options = $(this).val();
            const currentVal = $row.find('.pm-attr-value').val() || '';
            const optArr = options.split(',').map(function(o){ return o.trim(); }).filter(Boolean);
            let html = '<option value="">-- เลือก --</option>';
            optArr.forEach(function(o) {
                html += '<option value="' + escAttr(o) + '"' + (currentVal === o ? ' selected' : '') + '>' + escAttr(o) + '</option>';
            });
            $row.find('.pm-attr-value').html(html);
            updateCustomAttrsField();
        });

        $(document).on('click', '.pm-upload-gallery-btn', function() {
            const idx = $(this).data('index');
            const frame = wp.media({ title: 'เลือกรูปสินค้า', multiple: false });
            frame.on('select', function() {
                const att = frame.state().get('selection').first().toJSON();
                $(`.pm-gallery-item[data-index="${idx}"] .pm-gallery-url-input`).val(att.url);
                $(`.pm-gallery-item[data-index="${idx}"] .pm-gallery-preview`).html(`<img src="${att.url}" alt="">`);
                updateGalleryField();
            });
            frame.open();
        });

        $(document).on('click', '.pm-remove-gallery-btn', function() {
            const idx = $(this).data('index');
            const gallery = [];
            $('#pm-gallery-grid .pm-gallery-url-input').each((i, el) => {
                if (i !== idx) {
                    const url = $(el).val().trim();
                    if (url) gallery.push(url);
                }
            });
            renderGalleryGrid(gallery);
        });

        $('#pm-datasheet-upload-btn').on('click', function() {
            const frame = wp.media({
                title: 'เลือกไฟล์ Datasheet',
                multiple: false,
                library: { type: 'application/pdf' }
            });
            frame.on('select', function() {
                const att = frame.state().get('selection').first().toJSON();
                $('#pm-field-datasheet').val(att.url || '');
                renderDatasheetPreview(att.url || '');
            });
            frame.open();
        });

        $('#pm-datasheet-clear-btn').on('click', function() {
            $('#pm-field-datasheet').val('');
            renderDatasheetPreview('');
        });

        $('#pm-field-datasheet').on('input', function() {
            renderDatasheetPreview($(this).val());
        });



        // Checkboxes
        $(document).on('change', '#pm-check-all', function() {
            const checked = $(this).prop('checked');
            if (checked) {
                State.selectedIds = State.products.map(p => p.id);
            } else {
                State.selectedIds = [];
            }
            $('.pm-row-check').prop('checked', checked);
            updateBulkBar();
        });

        $(document).on('change', '.pm-row-check', function() {
            const id = parseInt($(this).val());
            if ($(this).prop('checked')) {
                if (!State.selectedIds.includes(id)) State.selectedIds.push(id);
            } else {
                State.selectedIds = State.selectedIds.filter(i => i !== id);
            }
            $('#pm-check-all').prop('checked', State.selectedIds.length === State.products.length && State.products.length > 0);
            updateBulkBar();
        });

        // Bulk actions
        $('#pm-bulk-delete').on('click', function() {
            showDeleteConfirm(`ต้องการลบสินค้า ${State.selectedIds.length} รายการ หรือไม่?`, function() {
                api('pm_bulk_delete', { ids: State.selectedIds }).done(function(res) {
                    if (res.success) {
                        toast(res.data.message, 'success');
                        State.selectedIds = [];
                        loadProducts();
                        loadCategories();
                    }
                });
            });
        });

        $('#pm-bulk-move').on('click', function() {
            $('#pm-move-modal-overlay').addClass('active');
            $('#pm-move-modal').addClass('active');
        });
        $('#pm-move-cancel, #pm-move-modal-close, #pm-move-modal-overlay').on('click', function() {
            $('#pm-move-modal-overlay').removeClass('active');
            $('#pm-move-modal').removeClass('active');
        });
        $('#pm-move-confirm').on('click', function() {
            const catId = $('#pm-move-cat-select').val();
            if (!catId) { toast('กรุณาเลือก Category', 'warning'); return; }
            api('pm_bulk_move', { ids: State.selectedIds, category: catId }).done(function(res) {
                if (res.success) {
                    toast(res.data.message, 'success');
                    $('#pm-move-modal-overlay').removeClass('active');
                    $('#pm-move-modal').removeClass('active');
                    State.selectedIds = [];
                    loadProducts();
                    loadCategories();
                }
            });
        });
        $('#pm-bulk-cancel').on('click', function() {
            State.selectedIds = [];
            $('.pm-row-check, #pm-check-all').prop('checked', false);
            updateBulkBar();
        });

        // Delete confirm
        $('#pm-delete-confirm').on('click', function() {
            if (pendingDelete) pendingDelete();
            hideDeleteConfirm();
        });
        $('#pm-delete-cancel, #pm-delete-overlay').on('click', hideDeleteConfirm);

        // Pagination
        $(document).on('click', '.pm-pag-btn:not([disabled])', function() {
            State.currentPage = parseInt($(this).data('page'));
            loadProducts();
            // Scroll to top
            $('.pm-main').animate({ scrollTop: 0 }, 200);
        });

        // Breadcrumb clicks
        $(document).on('click', '.pm-breadcrumb-item.clickable', function() {
            State.currentCat = parseInt($(this).data('id'));
            State.currentPage = 1;
            renderCatTree();
            updateBreadcrumb();
            loadProducts();
        });

        // --- SPEC FIELDS MANAGER ---
        $('#pm-icons-settings-btn').on('click', openSpecFieldsManager);

        $('#pm-icons-cancel, #pm-icons-modal-close, #pm-icons-modal-overlay').on('click', closeIconsModal);

        $('#pm-icons-save').on('click', saveSpecFields);

        $('#pm-add-custom-field-btn').on('click', function() {
            const label = $('#pm-new-field-label').val().trim();
            if (!label) {
                toast('กรุณาใส่ชื่อ field', 'warning');
                $('#pm-new-field-label').focus();
                return;
            }
            addCustomFieldRow(label);
        });

        $('#pm-new-field-label').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#pm-add-custom-field-btn').trigger('click');
            }
        });

        $(document).on('click', '.pm-delete-custom-field', function(e) {
            e.stopPropagation();
            const key = $(this).data('key');
            _specFieldsData = _specFieldsData.filter(function(f) { return f.key !== key; });
            renderSpecFieldsManager(_specFieldsData);
        });

        $(document).on('click', '.pm-icon-type-toggle', function() {
            const $btn  = $(this);
            const $row  = $btn.closest('.pm-sf-row');
            const type  = $btn.data('type');

            // Update button styles
            $row.find('.pm-icon-type-toggle').each(function() {
                const $t = $(this);
                if ($t.data('type') === type) {
                    $t.addClass('active').css({ background: 'var(--pm-primary, #3b82f6)', color: '#fff' });
                } else {
                    $t.removeClass('active').css({ background: '#f8fafc', color: '#64748b' });
                }
            });

            // Swap input <→ textarea
            const $wrap = $row.find('.pm-sf-icon-wrap');
            const currentVal = $wrap.find('.pm-sf-icon-val').val();
            if (type === 'svg') {
                $wrap.html(
                    '<textarea class="pm-input pm-sf-icon-val" rows="3" placeholder="วาง SVG เช่น &lt;svg ...&gt;&lt;/svg&gt;" ' +
                    'style="font-size:.75rem;font-family:monospace;padding:5px 10px;resize:vertical;">' + escHtml(currentVal) + '</textarea>'
                );
            } else {
                $wrap.html(
                    '<input type="text" class="pm-input pm-sf-icon-val" placeholder="ไอคอน FA เช่น fa fa-bolt" value="' + escAttr(currentVal) + '" ' +
                    'style="font-size:.8rem;padding:5px 10px;height:auto;">'
                );
            }
        });

        $(document).on('click', '.pm-sf-move-up', function(e) {
            e.preventDefault();
            const key = String($(this).closest('.pm-sf-row').data('key') || '');
            const idx = _specFieldsData.findIndex(f => String(f.key) === key);
            if (idx > 0) {
                const tmp = _specFieldsData[idx - 1];
                _specFieldsData[idx - 1] = _specFieldsData[idx];
                _specFieldsData[idx] = tmp;
                renderSpecFieldsManager(_specFieldsData);
            }
        });

        $(document).on('click', '.pm-sf-move-down', function(e) {
            e.preventDefault();
            const key = String($(this).closest('.pm-sf-row').data('key') || '');
            const idx = _specFieldsData.findIndex(f => String(f.key) === key);
            if (idx >= 0 && idx < _specFieldsData.length - 1) {
                const tmp = _specFieldsData[idx + 1];
                _specFieldsData[idx + 1] = _specFieldsData[idx];
                _specFieldsData[idx] = tmp;
                renderSpecFieldsManager(_specFieldsData);
            }
        });

        $(document).on('click', '.pm-toggle-builtin-field', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $row = $btn.closest('.pm-sf-row');
            const key = String($row.data('key') || '');
            if (key) {
                if (!_disabledBuiltinKeys.includes(key)) {
                    _disabledBuiltinKeys.push(key);
                }
            }
            $row.remove();
            toast('ลบแล้ว (กดบันทึกเพื่อยืนยัน)', 'warning');
        });
    }

    // ============================================
    // KEYBOARD SHORTCUTS
    // ============================================
    function bindKeyboard() {
        $(document).on('keydown', function(e) {
            // Escape to close panels/modals
            if (e.key === 'Escape') {
                if ($('#pm-panel').hasClass('active')) {
                    closePanel();
                } else if ($('#pm-icons-modal').hasClass('active')) {
                    closeIconsModal();
                } else if ($('#pm-cat-modal').hasClass('active')) {
                    closeCatModal();
                } else if ($('#pm-delete-modal').hasClass('active')) {
                    hideDeleteConfirm();
                }
            }

            // Cmd+K to focus search
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                $('#pm-search').focus().select();
            }

            // Cmd+Enter to save (when panel is open)
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                if ($('#pm-panel').hasClass('active')) {
                    e.preventDefault();
                    saveProduct();
                } else if ($('#pm-cat-modal').hasClass('active')) {
                    e.preventDefault();
                    saveCategory();
                }
            }
        });
    }

    // ============================================
    // UTILITIES
    // ============================================
    function escHtml(s) {
        if (!s) return '';
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(s));
        return div.innerHTML;
    }

    function escAttr(s) {
        if (!s) return '';
        return s.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function timeAgo(dateStr) {
        const d = new Date(dateStr);
        const now = new Date();
        const diff = Math.floor((now - d) / 1000);
        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
        return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' });
    }

})(jQuery);
