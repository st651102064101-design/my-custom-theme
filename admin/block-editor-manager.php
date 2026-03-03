<?php
/**
 * Block Editor Manager — Backend
 *
 * Controls block visibility, patterns, colors, typography, layout,
 * and editor settings from the Theme Settings page.
 * Included from functions.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════
// WORDPRESS FILTERS — Apply Block Editor Settings
// ═══════════════════════════════════════════════════════

/* 1. Block Visibility — hide disabled blocks from editor */
add_filter( 'allowed_block_types_all', function ( $allowed, $editor_context ) {
    if ( get_option( 'be_freedom_mode', '0' ) === '1' ) return true;
    $disabled = json_decode( get_option( 'be_disabled_blocks', '[]' ), true );
    if ( ! is_array( $disabled ) || empty( $disabled ) ) return $allowed;

    $all = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );
    return array_values( array_diff( $all, $disabled ) );
}, 10, 2 );

/* 2. Editor feature toggles */
add_filter( 'block_editor_settings_all', function ( $settings, $context ) {
    if ( get_option( 'be_freedom_mode', '0' ) === '1' ) {
        $settings['codeEditingEnabled'] = true;
        $settings['enableOpenverseMediaCategory'] = true;
        $settings['fontLibraryEnabled'] = true;
        return $settings;
    }
    if ( get_option( 'be_disable_code_editor', '0' ) === '1' )
        $settings['codeEditingEnabled'] = false;
    if ( get_option( 'be_disable_openverse', '0' ) === '1' )
        $settings['enableOpenverseMediaCategory'] = false;
    if ( get_option( 'be_disable_font_library', '0' ) === '1' )
        $settings['fontLibraryEnabled'] = false;
    return $settings;
}, 10, 2 );

/* 3. Remote block patterns */
add_filter( 'should_load_remote_block_patterns', function ( $should ) {
    if ( get_option( 'be_freedom_mode', '0' ) === '1' ) return true;
    return get_option( 'be_disable_remote_patterns', '0' ) === '1' ? false : $should;
} );

/* 4. Block directory */
add_action( 'admin_init', function () {
    if ( get_option( 'be_freedom_mode', '0' ) === '1' ) return;
    if ( get_option( 'be_disable_block_directory', '0' ) === '1' )
        remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
} );

/* 5. Store full pattern list BEFORE removal, then remove disabled */
add_action( 'init', function () {
    global $_kv_be_all_patterns;
    $_kv_be_all_patterns = [];
    if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) return;
    foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $p ) {
        $_kv_be_all_patterns[] = [
            'name'        => $p['name'],
            'title'       => $p['title'] ?? '',
            'description' => $p['description'] ?? '',
            'categories'  => $p['categories'] ?? [],
        ];
    }
}, 998 );

add_action( 'init', function () {
    if ( get_option( 'be_freedom_mode', '0' ) === '1' ) return;
    $disabled = json_decode( get_option( 'be_disabled_patterns', '[]' ), true );
    if ( ! is_array( $disabled ) || empty( $disabled ) ) return;
    if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) return;
    $reg = WP_Block_Patterns_Registry::get_instance();
    foreach ( $disabled as $name ) {
        if ( $reg->is_registered( $name ) ) $reg->unregister( $name );
    }
}, 999 );

/** Get full list of patterns (including disabled) for settings UI */
function kv_be_get_all_patterns() {
    global $_kv_be_all_patterns;
    return is_array( $_kv_be_all_patterns ) ? $_kv_be_all_patterns : [];
}

/** Get full list of registered blocks for settings UI */
function kv_be_get_all_blocks() {
    if ( ! class_exists( 'WP_Block_Type_Registry' ) ) return [];
    $blocks = [];
    foreach ( WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $bt ) {
        $blocks[] = [
            'name'     => $name,
            'title'    => ! empty( $bt->title ) ? $bt->title : $name,
            'category' => $bt->category ?? 'uncategorized',
            'desc'     => $bt->description ?? '',
            'icon'     => is_string( $bt->icon ?? null ) ? $bt->icon : '',
            'keywords' => $bt->keywords ?? [],
        ];
    }
    usort( $blocks, function ( $a, $b ) {
        $c = strcmp( $a['category'], $b['category'] );
        return $c !== 0 ? $c : strcmp( $a['title'], $b['title'] );
    } );
    return $blocks;
}

/* 6. Theme JSON overrides — colors, fonts, layout, spacing, dropCap */
if ( class_exists( 'WP_Theme_JSON_Data' ) ) {
    add_filter( 'wp_theme_json_data_theme', function ( $theme_json ) {
        $data     = $theme_json->get_data();
        $modified = false;

        // Custom Colors
        $colors = json_decode( get_option( 'be_custom_colors', '' ), true );
        if ( is_array( $colors ) && ! empty( $colors ) ) {
            $data['settings']['color']['palette'] = $colors;
            $modified = true;
        }
        // Custom Font Sizes
        $sizes = json_decode( get_option( 'be_custom_font_sizes', '' ), true );
        if ( is_array( $sizes ) && ! empty( $sizes ) ) {
            $data['settings']['typography']['fontSizes'] = $sizes;
            $modified = true;
        }
        // Layout
        $cw = get_option( 'be_content_width', '' );
        $ww = get_option( 'be_wide_width', '' );
        if ( $cw !== '' ) { $data['settings']['layout']['contentSize'] = $cw; $modified = true; }
        if ( $ww !== '' ) { $data['settings']['layout']['wideSize']    = $ww; $modified = true; }
        // Spacing units
        $units = json_decode( get_option( 'be_spacing_units', '' ), true );
        if ( is_array( $units ) && ! empty( $units ) ) {
            $data['settings']['spacing']['units'] = $units;
            $modified = true;
        }
        // Drop cap
        if ( get_option( 'be_freedom_mode', '0' ) !== '1' && get_option( 'be_disable_drop_cap', '0' ) === '1' ) {
            $data['settings']['typography']['dropCap'] = false;
            $modified = true;
        }

        return $modified ? new WP_Theme_JSON_Data( $data, 'theme' ) : $theme_json;
    }, 20 );
}

/* 7. Custom editor CSS */
add_action( 'enqueue_block_editor_assets', function () {
    $css = get_option( 'be_editor_css', '' );
    if ( empty( $css ) ) return;
    wp_register_style( 'kv-be-custom-css', false );
    wp_enqueue_style( 'kv-be-custom-css' );
    wp_add_inline_style( 'kv-be-custom-css', $css );
} );

/* 8. Default fullscreen off */
add_action( 'enqueue_block_editor_assets', function () {
    if ( get_option( 'be_default_fullscreen', '1' ) !== '0' ) return;
    wp_add_inline_script( 'wp-edit-post',
        "window.addEventListener('load',function(){setTimeout(function(){try{" .
        "if(wp.data.select('core/edit-post').isFeatureActive('fullscreenMode'))" .
        "wp.data.dispatch('core/edit-post').toggleFeature('fullscreenMode');" .
        "}catch(e){}},100);});"
    );
} );

// ═══════════════════════════════════════════════════════
// REST API — /kv/v1/block-editor-settings
// ═══════════════════════════════════════════════════════
add_action( 'rest_api_init', function () {
    register_rest_route( 'kv/v1', '/block-editor-settings', [
        /* ── GET ── */
        [
            'methods'             => 'GET',
            'callback'            => function () {
                return rest_ensure_response( [
                    'disabled_blocks'         => json_decode( get_option( 'be_disabled_blocks', '[]' ), true ) ?: [],
                    'disabled_patterns'       => json_decode( get_option( 'be_disabled_patterns', '[]' ), true ) ?: [],
                    'custom_colors'           => json_decode( get_option( 'be_custom_colors', '' ), true ) ?: [],
                    'custom_font_sizes'       => json_decode( get_option( 'be_custom_font_sizes', '' ), true ) ?: [],
                    'content_width'           => get_option( 'be_content_width', '' ),
                    'wide_width'              => get_option( 'be_wide_width', '' ),
                    'editor_css'              => get_option( 'be_editor_css', '' ),
                    'disable_code_editor'     => get_option( 'be_disable_code_editor', '0' ),
                    'disable_block_directory' => get_option( 'be_disable_block_directory', '0' ),
                    'disable_openverse'       => get_option( 'be_disable_openverse', '0' ),
                    'disable_remote_patterns' => get_option( 'be_disable_remote_patterns', '0' ),
                    'disable_font_library'    => get_option( 'be_disable_font_library', '0' ),
                    'default_fullscreen'      => get_option( 'be_default_fullscreen', '1' ),
                    'lock_blocks'             => get_option( 'be_lock_blocks', '0' ),
                    'disable_drop_cap'        => get_option( 'be_disable_drop_cap', '0' ),
                    'freedom_mode'            => get_option( 'be_freedom_mode', '0' ),
                    'spacing_units'           => json_decode( get_option( 'be_spacing_units', '' ), true ) ?: [],
                ] );
            },
            'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        ],
        /* ── POST ── */
        [
            'methods'             => 'POST',
            'callback'            => function ( WP_REST_Request $req ) {
                $body = $req->get_json_params();

                // JSON string-arrays
                foreach ( [ 'disabled_blocks', 'disabled_patterns' ] as $k ) {
                    if ( isset( $body[ $k ] ) && is_array( $body[ $k ] ) ) {
                        $clean = array_values( array_map( 'sanitize_text_field', array_filter( $body[ $k ], 'is_string' ) ) );
                        update_option( 'be_' . $k, wp_json_encode( $clean ) );
                    }
                }
                // Custom colors
                if ( isset( $body['custom_colors'] ) && is_array( $body['custom_colors'] ) ) {
                    $c = [];
                    foreach ( $body['custom_colors'] as $i ) {
                        if ( ! empty( $i['slug'] ) && ! empty( $i['color'] ) && ! empty( $i['name'] ) )
                            $c[] = [
                                'slug'  => sanitize_title( $i['slug'] ),
                                'color' => sanitize_hex_color( $i['color'] ) ?: '#000000',
                                'name'  => sanitize_text_field( $i['name'] ),
                            ];
                    }
                    update_option( 'be_custom_colors', wp_json_encode( $c ) );
                }
                // Custom font sizes
                if ( isset( $body['custom_font_sizes'] ) && is_array( $body['custom_font_sizes'] ) ) {
                    $s = [];
                    foreach ( $body['custom_font_sizes'] as $i ) {
                        if ( ! empty( $i['slug'] ) && ! empty( $i['size'] ) && ! empty( $i['name'] ) )
                            $s[] = [
                                'slug' => sanitize_title( $i['slug'] ),
                                'size' => sanitize_text_field( $i['size'] ),
                                'name' => sanitize_text_field( $i['name'] ),
                            ];
                    }
                    update_option( 'be_custom_font_sizes', wp_json_encode( $s ) );
                }
                // Spacing units
                if ( isset( $body['spacing_units'] ) && is_array( $body['spacing_units'] ) ) {
                    $valid = [ 'px','em','rem','%','vh','vw','svh','svw','dvh','dvw' ];
                    $clean = array_values( array_intersect( array_map( 'sanitize_text_field', $body['spacing_units'] ), $valid ) );
                    update_option( 'be_spacing_units', wp_json_encode( $clean ) );
                }
                // Strings
                if ( isset( $body['content_width'] ) ) update_option( 'be_content_width', sanitize_text_field( $body['content_width'] ) );
                if ( isset( $body['wide_width'] ) )    update_option( 'be_wide_width', sanitize_text_field( $body['wide_width'] ) );
                if ( isset( $body['editor_css'] ) )    update_option( 'be_editor_css', wp_strip_all_tags( $body['editor_css'] ) );

                // Toggles (0 / 1)
                $toggles = [ 'disable_code_editor','disable_block_directory','disable_openverse',
                    'disable_remote_patterns','disable_font_library','default_fullscreen',
                    'lock_blocks','disable_drop_cap','freedom_mode' ];
                foreach ( $toggles as $t ) {
                    if ( isset( $body[ $t ] ) ) {
                        $val = ( $body[ $t ] === '1' || $body[ $t ] === 1 || $body[ $t ] === true ) ? '1' : '0';
                        update_option( 'be_' . $t, $val );
                    }
                }

                if ( isset( $body['freedom_mode'] ) && ( $body['freedom_mode'] === '1' || $body['freedom_mode'] === 1 || $body['freedom_mode'] === true ) ) {
                    update_option( 'be_disabled_blocks', wp_json_encode( [] ) );
                    update_option( 'be_disabled_patterns', wp_json_encode( [] ) );
                    update_option( 'be_disable_code_editor', '0' );
                    update_option( 'be_disable_block_directory', '0' );
                    update_option( 'be_disable_openverse', '0' );
                    update_option( 'be_disable_remote_patterns', '0' );
                    update_option( 'be_disable_font_library', '0' );
                    update_option( 'be_lock_blocks', '0' );
                    update_option( 'be_disable_drop_cap', '0' );
                }

                return rest_ensure_response( [ 'success' => true ] );
            },
            'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        ],
    ] );
} );
