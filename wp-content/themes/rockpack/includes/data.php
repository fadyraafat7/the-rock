<?php
require_once __DIR__ . '/section-titles.php';
// ── Option helper ─────────────────────────────────────────────────────────────
function rp_option( $key, $default = '' ) {
    if ( rockpack_is_fixed_title( $key ) ) return rockpack_section_titles()[ $key ];
    $opts = get_option( 'rockpack_settings', [] );
    if ( ! is_array( $opts ) ) $opts = array();
    if ( ! empty( $opts['_media'][ $key ] ) ) {
        $image = wp_get_attachment_image_url( absint( $opts['_media'][ $key ] ), 'full' );
        if ( $image ) return $image;
    }
    if ( ! empty( $opts['_explicit'][ $key ] ) && isset( $opts[ $key ] ) && is_scalar( $opts[ $key ] ) ) return $opts[ $key ];
    return ( isset( $opts[ $key ] ) && is_scalar( $opts[ $key ] ) && '' !== $opts[ $key ] ) ? $opts[ $key ] : $default;
}

// ── CPT queries ───────────────────────────────────────────────────────────────
function rp_get_stats() {
    return get_posts( [
        'post_type'      => 'rp_stat',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ] );
}

function rp_get_history_slides() {
    return get_posts( [
        'post_type'      => 'rp_history',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ] );
}

function rp_get_certs() {
    return get_posts( [
        'post_type'      => 'rp_cert',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ] );
}

function rp_get_products() {
    return get_posts( [
        'post_type'      => 'rp_product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ] );
}

/** Shared gallery data for Home and the Product Portfolio page. */
function rockpack_product_gallery_items() {
    return rockpack_collection('products');
}

function rp_get_logos() {
    return get_posts( [
        'post_type'      => 'rp_logo',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ] );
}

// ── SVG icons for stats ───────────────────────────────────────────────────────
function rp_stat_svg( $icon ) {
    $map = [
        'expertise' => '<svg width="30" height="30" viewBox="0 0 32 32" fill="none"><circle cx="13" cy="10" r="5" stroke="#3ec97a" stroke-width="2"/><path d="M5 26c0-5 4-8 8-8h2" stroke="#3ec97a" stroke-width="2" stroke-linecap="round"/><circle cx="24" cy="20" r="5" stroke="#3ec97a" stroke-width="1.5"/><path d="M22.5 20l1 1 2-2" stroke="#3ec97a" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'location'  => '<svg width="30" height="30" viewBox="0 0 32 32" fill="none"><path d="M16 4C11.58 4 8 7.58 8 12c0 6.63 8 16 8 16s8-9.37 8-16c0-4.42-3.58-8-8-8z" stroke="#3ec97a" stroke-width="2"/><circle cx="16" cy="12" r="3" fill="#3ec97a"/></svg>',
        'markets'   => '<svg width="30" height="30" viewBox="0 0 32 32" fill="none"><rect x="6" y="10" width="20" height="14" rx="3" stroke="#3ec97a" stroke-width="2"/><path d="M10 7l6-3 6 3" stroke="#3ec97a" stroke-width="2" stroke-linecap="round"/><path d="M11 17h10M11 21h7" stroke="#3ec97a" stroke-width="2" stroke-linecap="round"/></svg>',
        'standards' => '<svg width="30" height="30" viewBox="0 0 32 32" fill="none"><rect x="9" y="6" width="14" height="20" rx="2" stroke="#3ec97a" stroke-width="2"/><path d="M12 11h8M12 15h8M12 19h5" stroke="#3ec97a" stroke-width="2" stroke-linecap="round"/><circle cx="23" cy="9" r="4" fill="#3ec97a"/><path d="M21.5 9l1 1 2-2" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>',
    ];
    return $map[ $icon ] ?? $map['expertise'];
}

// ── Default fallback data (used when CPTs are empty) ──────────────────────────
function rp_default_stats() {
    return [
        [ 'label' => '30 Years',     'sub' => 'Technical Expertise',    'icon' => 'expertise' ],
        [ 'label' => 'Egypt',        'sub' => 'Regional Production Hub', 'icon' => 'location'  ],
        [ 'label' => 'Global Reach','sub' => 'Markets We Serve',        'icon' => 'markets'   ],
        [ 'label' => 'FSC / ISO / BRC','sub' => 'International Standards','icon' => 'standards'],
    ];
}

function rp_default_slides() {
    return [
        [ 'title' => 'Building Expertise',    'text' => 'The Rock Pack was built on decades of technical expertise in printing, materials, and packaging innovation.' ],
        [ 'title' => 'Mastering Production', 'text' => 'Its expertise evolved alongside the food and beverage industry’s growing needs for safety, shelf life, and scalability.' ],
        [ 'title' => 'Advancing Aseptic Technology', 'text' => 'The company integrated advanced aseptic technology to deliver high-performance, sterile packaging solutions that meet international food safety standards.' ],
        [ 'title' => 'Expanding in Egypt', 'text' => 'Its large-scale production facility in Egypt supports regional markets with greater proximity, agility, and operational control.' ],
        [ 'title' => 'Leading Regionally', 'text' => 'Today, The Rock Pack is a regional production powerhouse combining experience, advanced systems, and scalable operations.' ],
    ];
}

function rp_default_certs() {
    return [ 'ISO', 'FSC', 'BRC' ];
}
