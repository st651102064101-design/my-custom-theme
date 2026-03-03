<?php
/**
 * Template: Product Category Archive
 * 
 * แสดงรายการสินค้าในแต่ละ category (Inductors, Transformers, Antennas, test, ...)
 * ดึงข้อมูลทั้งหมดจาก database (CPT + ACF)
 */

get_header();

$term = get_queried_object();

// หา parent term (ถ้ามี)
$parent_term = null;
if ($term->parent) {
    $parent_term = get_term($term->parent, 'product_category');
}
?>

<?php
// ── Banner: color + blurred background image ──
$_bg_color = get_option('theme_primary_color', '#0056d6');

// Image priority: 1) term cat_image  2) first product image in this category
$_banner_img = get_term_meta($term->term_id, 'cat_image', true);
if (!$_banner_img) {
    $_first = get_posts([
        'post_type'      => 'product',
        'posts_per_page' => 1,
        'post_status'    => ['publish', 'draft'],
        'tax_query'      => [[
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => $term->term_id,
            'include_children' => true,
        ]],
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);
    if ($_first) {
        $_banner_img = get_post_meta($_first[0]->ID, 'pd_image_1', true);
        if (!$_banner_img && has_post_thumbnail($_first[0]->ID)) {
            $_banner_img = get_the_post_thumbnail_url($_first[0]->ID, 'large');
        }
        // also check gallery
        if (!$_banner_img) {
            $_gallery = json_decode(get_post_meta($_first[0]->ID, 'pd_gallery', true), true);
            if (!empty($_gallery)) $_banner_img = $_gallery[0];
        }
    }
}
?>
<!-- Page Banner -->
<section class="page-banner w-100" style="background-color:<?php echo esc_attr($_bg_color); ?>;padding:60px 0;position:relative;overflow:hidden;">

    <?php if ($_banner_img) : ?>
    <!-- Blurred background image layer -->
    <div style="position:absolute;inset:-30px;background-image:url('<?php echo esc_url($_banner_img); ?>');background-size:cover;background-position:center center;filter:blur(8px);transform:scale(1.15);z-index:0;opacity:0.9;"></div>
    <?php endif; ?>

    <!-- Dark color overlay -->
    <div style="position:absolute;inset:0;background-color:<?php echo esc_attr($_bg_color); ?>;opacity:<?php echo $_banner_img ? '0.35' : '1'; ?>;z-index:1;"></div>

    <div class="container text-center position-relative" style="z-index:2;">
        <h1 style="color:#fff;font-size:clamp(28px,5vw,42px);font-weight:700;margin-bottom:12px;line-height:1.2;">
            <?php echo esc_html($term->name); ?>
        </h1>
        <nav aria-label="breadcrumb" style="display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,0.85);font-size:15px;">
            <a href="<?php echo esc_url(home_url('/')); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;">Home</a>
            <span style="opacity:0.7;">/</span>
            <a href="<?php echo esc_url(home_url('/products/')); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;">Products</a>
            <?php if ($parent_term) : ?>
                <span style="opacity:0.7;">/</span>
                <?php
                $parent_term_link_raw = get_term_link($parent_term);
                $parent_term_link = (!is_wp_error($parent_term_link_raw) && $parent_term_link_raw)
                    ? $parent_term_link_raw
                    : home_url('/products/');
                ?>
                <a href="<?php echo esc_url($parent_term_link); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;"><?php echo esc_html($parent_term->name); ?></a>
            <?php endif; ?>
            <span style="opacity:0.7;">/</span>
            <span style="white-space:nowrap;"><?php echo esc_html($term->name); ?></span>
        </nav>
    </div>
</section>

<!-- Product Cards / Subcategory Cards -->
<section style="padding:60px 0;">
    <div class="container">
        <?php
        // Check if this category has child subcategories
        $child_terms = get_terms([
            'taxonomy'   => 'product_category',
            'parent'     => $term->term_id,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

            // ── Leaf category: show category meta (desc, specs) ──
            $leaf_desc     = get_term_meta($term->term_id, 'cat_description_long', true);
            $leaf_specs_r  = get_term_meta($term->term_id, 'cat_specs', true);
            $leaf_specs    = $leaf_specs_r ? json_decode($leaf_specs_r, true) : [];

            $has_leaf_content = ($leaf_desc || !empty($leaf_specs));
            if ($has_leaf_content) : ?>
            <div style="margin-bottom:56px;">
                <?php if ($leaf_desc) : ?>
                <div style="color:#475569;line-height:1.8;margin-bottom:36px;font-size:1rem;">
                    <?php echo wp_kses_post($leaf_desc); ?>
                </div>
                <?php endif; ?>

                <?php
                $filtered_specs = array_filter($leaf_specs, fn($s) => !empty($s['label']));
                if (!empty($filtered_specs)) : ?>
                <div style="background:#f8fafc;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;margin-bottom:12px;">
                    <div style="background:var(--theme-primary);color:#fff;padding:14px 20px;font-weight:700;font-size:1rem;">
                        Product Specifications
                    </div>
                    <?php $si = 0; foreach ($filtered_specs as $spec) : $si++;
                        $bg = $si % 2 === 0 ? '#fff' : '#f8fafc'; ?>
                    <div style="display:grid;grid-template-columns:220px 1fr;background:<?php echo $bg; ?>;border-bottom:1px solid #e2e8f0;align-items:start;">
                        <div style="padding:14px 20px;font-weight:600;color:#1e293b;display:flex;align-items:center;gap:8px;border-right:1px solid #e2e8f0;">
                            <?php if (!empty($spec['icon'])) : ?>
                            <i class="fa fa-<?php echo esc_attr($spec['icon']); ?>" style="color:var(--theme-primary);width:18px;text-align:center;"></i>
                            <?php else : ?>
                            <span style="color:var(--theme-primary);">▸</span>
                            <?php endif; ?>
                            <?php echo esc_html($spec['label']); ?>
                        </div>
                        <div style="padding:14px 20px;color:#475569;"><?php echo esc_html($spec['value']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; // has_leaf_content ?>

        <?php
        // ═══════════════════════════════════════════════════════
        // ALL PRODUCTS + PARAMETRIC FILTER (always shown)
        // ═══════════════════════════════════════════════════════
        $products = new WP_Query([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'draft'],
            'tax_query'      => [[
                'taxonomy'         => 'product_category',
                'field'            => 'term_id',
                'terms'            => $term->term_id,
                'include_children' => true,
            ]],
            'orderby' => 'title',
            'order'   => 'ASC',
        ]);

        if ($products->have_posts()) :

            // ── Parametric filter: scan all products for spec values ──
            $all_spec_defs = function_exists('pm_get_all_spec_fields')
                ? pm_get_all_spec_fields()
                : [
                    ['key' => 'pd_inductance',    'label' => 'Inductance'],
                    ['key' => 'pd_current_rating','label' => 'Current Rating'],
                    ['key' => 'pd_impedance',     'label' => 'Impedance'],
                    ['key' => 'pd_voltage',       'label' => 'Voltage Rating'],
                    ['key' => 'pd_frequency',     'label' => 'Frequency Range'],
                    ['key' => 'pd_temp_range',    'label' => 'Operating Temperature'],
                    ['key' => 'pd_package_type',  'label' => 'Packaging Options'],
                ];
            $spec_key_label = [];
            foreach ($all_spec_defs as $sf) {
                $spec_key_label[$sf['key']] = $sf['label'];
            }

            // Also add "Subcategory" as a filterable dimension for parent categories
            $is_parent_cat = (!empty($child_terms) && !is_wp_error($child_terms));

            $filter_options = [];
            $product_specs_map = [];
            $product_subcat_map = [];

            while ($products->have_posts()) {
                $products->the_post();
                $pid = get_the_ID();
                $product_specs_map[$pid] = [];

                // Collect spec values
                foreach ($spec_key_label as $key => $label) {
                    $val = trim(get_post_meta($pid, $key, true));
                    if ($val !== '') {
                        $product_specs_map[$pid][$key] = $val;
                        if (!isset($filter_options[$key])) {
                            $filter_options[$key] = [];
                        }
                        if (!in_array($val, $filter_options[$key], true)) {
                            $filter_options[$key][] = $val;
                        }
                    }
                }

                // Collect subcategory for parent-level pages
                if ($is_parent_cat) {
                    $p_terms = wp_get_object_terms($pid, 'product_category');
                    $subcat_name = '';
                    if ($p_terms && !is_wp_error($p_terms)) {
                        foreach ($p_terms as $pt) {
                            if ($pt->parent == $term->term_id) {
                                $subcat_name = $pt->name;
                                break;
                            }
                            // Check grandchild → find direct child of current term
                            if ($pt->parent) {
                                $ancestor = get_term($pt->parent, 'product_category');
                                if ($ancestor && $ancestor->parent == $term->term_id) {
                                    $subcat_name = $ancestor->name;
                                    break;
                                }
                            }
                        }
                    }
                    if ($subcat_name) {
                        $product_specs_map[$pid]['_subcat'] = $subcat_name;
                        if (!isset($filter_options['_subcat'])) {
                            $filter_options['_subcat'] = [];
                        }
                        if (!in_array($subcat_name, $filter_options['_subcat'], true)) {
                            $filter_options['_subcat'][] = $subcat_name;
                        }
                    }
                }
            }
            $products->rewind_posts();

            // Only show filters for fields with 2+ unique values
            $filterable_specs = [];
            foreach ($filter_options as $key => $values) {
                if (count($values) >= 2) {
                    sort($values, SORT_NATURAL | SORT_FLAG_CASE);
                    $filterable_specs[$key] = $values;
                }
            }
            $has_filters = !empty($filterable_specs);
        ?>

        <!-- Products heading -->
        <h2 style="font-size:1.4rem;font-weight:700;color:#1e293b;margin:0 0 28px;">
            All <?php echo esc_html($term->name); ?> Products
        </h2>

        <?php if ($has_filters) : ?>
        <!-- ═══ Parametric Filter + Product Grid Layout ═══ -->
        <div class="pf-layout">
            <!-- Filter Sidebar -->
            <aside class="pf-sidebar" id="pfSidebar">
                <div class="pf-sidebar-header">
                    <span class="pf-sidebar-title"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align:-2px;margin-right:6px;"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/></svg>Filter by Specs</span>
                    <button type="button" class="pf-clear-all" id="pfClearAll" style="display:none;">Clear All</button>
                </div>

                <div class="pf-search-box">
                    <input type="text" id="pfSearchInput" placeholder="Search products..." class="pf-search-input">
                </div>

                <div class="pf-filter-groups" id="pfFilterGroups">
                <?php
                $spec_icons = function_exists('pm_default_spec_icons') ? pm_default_spec_icons() : [];
                // Add subcategory icon
                $spec_icons['_subcat'] = 'fa fa-folder-open';
                $spec_key_label['_subcat'] = 'Subcategory';

                foreach ($filterable_specs as $key => $values) :
                    $label = $spec_key_label[$key] ?? $key;
                    $icon  = $spec_icons[$key] ?? '';
                    $group_id = 'pf-group-' . sanitize_title($key);
                ?>
                    <div class="pf-group" data-spec-key="<?php echo esc_attr($key); ?>">
                        <button type="button" class="pf-group-toggle" aria-expanded="true" data-target="<?php echo esc_attr($group_id); ?>">
                            <?php if ($icon) : ?><i class="<?php echo esc_attr($icon); ?>" style="width:16px;text-align:center;margin-right:4px;font-size:13px;color:var(--theme-primary);"></i><?php endif; ?>
                            <span><?php echo esc_html($label); ?></span>
                            <span class="pf-badge" style="display:none;">0</span>
                            <svg class="pf-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>
                        </button>
                        <div class="pf-group-body" id="<?php echo esc_attr($group_id); ?>">
                            <?php foreach ($values as $val) :
                                $cb_id = 'pf-' . sanitize_title($key) . '-' . sanitize_title($val);
                            ?>
                            <label class="pf-checkbox-label" for="<?php echo esc_attr($cb_id); ?>">
                                <input type="checkbox" id="<?php echo esc_attr($cb_id); ?>" class="pf-checkbox" data-spec-key="<?php echo esc_attr($key); ?>" data-spec-value="<?php echo esc_attr($val); ?>">
                                <span class="pf-checkbox-text"><?php echo esc_html($val); ?></span>
                                <span class="pf-value-count" data-count-key="<?php echo esc_attr($key); ?>" data-count-value="<?php echo esc_attr($val); ?>"></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </aside>

            <!-- Product Grid -->
            <div class="pf-products">
                <div class="pf-toolbar">
                    <button type="button" class="pf-mobile-filter-btn" id="pfMobileToggle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/></svg>
                        Filters
                    </button>
                    <span class="pf-result-count" id="pfResultCount"></span>
                    <select class="pf-sort-select" id="pfSortSelect">
                        <option value="name-asc">Name A → Z</option>
                        <option value="name-desc">Name Z → A</option>
                    </select>
                </div>

                <div class="pf-active-tags" id="pfActiveTags" style="display:none;"></div>

                <div class="row g-4" id="pfProductGrid">
        <?php else : ?>
        <!-- No filterable specs — standard grid -->
        <div class="row g-4" id="pfProductGrid">
        <?php endif; // has_filters ?>

            <?php
            $products->rewind_posts();
            while ($products->have_posts()) : $products->the_post();
                    $pid = get_the_ID();
                    $image = '';
                    $gallery = json_decode(get_post_meta($pid, 'pd_gallery', true), true);
                    if (!empty($gallery) && is_array($gallery)) {
                        $image = $gallery[0];
                    }
                    if (!$image) {
                        $image = get_post_meta($pid, 'pd_image_1', true);
                    }
                    if (!$image && has_post_thumbnail()) {
                        $image = get_the_post_thumbnail_url($pid, 'medium_large');
                    }

                    $card_specs = isset($product_specs_map[$pid]) ? $product_specs_map[$pid] : [];
                    $specs_display = [];
                    foreach ($card_specs as $sk => $sv) {
                        if ($sk === '_subcat') continue;
                        $specs_display[] = ['label' => $spec_key_label[$sk] ?? $sk, 'value' => $sv];
                    }
            ?>
            <div class="col-12 col-md-6 col-lg-4 pf-product-card" data-product-name="<?php echo esc_attr(strtolower(get_the_title())); ?>" data-specs="<?php echo esc_attr(json_encode($card_specs)); ?>">
                <div class="product-item">
                    <?php if ($image) : ?>
                    <img decoding="async" src="<?php echo esc_url($image); ?>" alt="<?php the_title_attribute(); ?>">
                    <?php else : ?>
                    <div style="width:100%;height:200px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#cbd5e1" viewBox="0 0 16 16"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2H2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1H14z"/></svg>
                    </div>
                    <?php endif; ?>
                    <div class="product-info">
                        <h3><?php the_title(); ?></h3>
                        <?php if (!empty($specs_display)) : ?>
                        <ul class="pf-card-specs">
                            <?php foreach (array_slice($specs_display, 0, 3) as $sp) : ?>
                            <li><strong><?php echo esc_html($sp['label']); ?>:</strong> <?php echo esc_html($sp['value']); ?></li>
                            <?php endforeach; ?>
                            <?php if (count($specs_display) > 3) : ?>
                            <li class="pf-more-specs">+<?php echo count($specs_display) - 3; ?> more specs</li>
                            <?php endif; ?>
                        </ul>
                        <?php else : ?>
                        <p><?php echo esc_html(get_the_excerpt()); ?></p>
                        <?php endif; ?>
                        <a href="<?php the_permalink(); ?>" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            </div>
            <?php
            endwhile;
            wp_reset_postdata();
            ?>
                </div>

        <?php if ($has_filters) : ?>
                <!-- No results message -->
                <div class="pf-no-results" id="pfNoResults" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#94a3b8" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    <h4>No products match your filters</h4>
                    <p>Try removing some filters or broadening your search criteria.</p>
                    <button type="button" class="pf-reset-btn" id="pfResetBtn">Reset All Filters</button>
                </div>
            </div>
        </div>
        <div class="pf-overlay" id="pfOverlay"></div>
        <?php endif; ?>

        <?php else : ?>
        <!-- No products in this category -->
        <div style="text-align:center;padding:60px 0 80px;">
            <div style="display:inline-flex;align-items:center;justify-content:center;width:80px;height:80px;background:#f1f5f9;border-radius:50%;margin-bottom:20px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#94a3b8" viewBox="0 0 16 16">
                    <path d="M0 2a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1v7.5a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 1 12.5V5a1 1 0 0 1-1-1V2zm2 3v7.5A1.5 1.5 0 0 0 3.5 14h9a1.5 1.5 0 0 0 1.5-1.5V5H2zm13-3H1v2h14V2zM5 7.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
                </svg>
            </div>
            <h4 style="color:#475569;font-weight:600;margin-bottom:12px;">No products found</h4>
            <p style="color:#94a3b8;margin-bottom:24px;">This category does not have any products yet.</p>
            <a href="<?php echo esc_url(home_url('/products/')); ?>" style="display:inline-block;background:var(--theme-accent);color:#fff;padding:10px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:.95rem;">
                ← Back to Products
            </a>
        </div>
        <?php endif; // products ?>
    </div>
</section>

<?php get_footer(); ?>
