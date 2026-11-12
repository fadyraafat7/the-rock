<?php
require_once get_template_directory() . '/includes/home-sections.php';
require_once get_template_directory() . '/includes/page-editor.php';
require_once get_template_directory() . '/includes/news-careers.php';
require_once get_template_directory() . '/includes/listing-heroes.php';
require_once get_template_directory() . '/includes/seo.php';
require_once get_template_directory() . '/includes/newsletter.php';
// ── Theme setup ───────────────────────────────────────────────────────────────
function rockpack_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', ['search-form','comment-form','comment-list','gallery','caption'] );
    register_nav_menus( ['primary' => __( 'Primary Menu', 'rockpack' )] );
}
add_action( 'after_setup_theme', 'rockpack_setup' );

// Use the same artwork as the header for WordPress's favicon and touch icons.
add_filter( 'get_site_icon_url', static function ( $url, $size, $blog_id ) {
    if ( $blog_id && (int) $blog_id !== get_current_blog_id() ) return $url;
    $logo = rp_option( 'nav_brand_image', get_template_directory_uri() . '/assets/images/certifications/logo.png' );
    if ( ! $logo ) return $url;
    return esc_url_raw( is_ssl() ? set_url_scheme( $logo, 'https' ) : $logo );
}, 10, 3 );

/**
 * Site-wide presentation controls.
 *
 * Navigation and footer data is intentionally stored in the existing
 * rockpack_settings option, so all templates and static renders read the same
 * single source of truth.
 */
function rockpack_header_footer_fields() {
    $fields = array();

    foreach ( rockpack_shared_fields() as $key => $field ) {
        if ( ! preg_match( '/^(?:nav_|footer_|shared_link_)/', $key ) ) {
            continue;
        }
        if ( str_starts_with( $key, 'shared_link_' ) && ! in_array( $key, array( 'shared_link_home', 'shared_link_contact_us', 'shared_link_contact_us_contact_form' ), true ) ) {
            continue; // Footer destinations are edited through the link repeaters.
        }

        // Menu and footer-link repeaters below are the authoritative editor
        // for these legacy defaults. Do not expose duplicate controls.
        if ( preg_match( '/^nav_(?:destination_|label_(?:company|about_us|history|certifications|news|careers|products|product_portfolio|quality_management|planet|sustainable_sourcing|vision_for_africa|environmental_and_social)$)/', $key ) ) {
            continue;
        }
        if ( preg_match( '/^footer_label_(?:home|company|about_us|history|certifications|news|careers|products_services|planet_societal_impact|contact_us|facebook|linkedin|instagram|youtube)$/', $key ) || preg_match( '/^footer_social_/', $key ) ) {
            continue;
        }

        $fields[ $key ] = $field;
    }

    foreach ( rockpack_home_sections()['contact']['fields'] as $field ) {
        $fields[ $field['key'] ] = $field;
    }

    return array_diff_key( $fields, rockpack_section_titles() );
}

function rockpack_header_footer_sections() {
    $fields = rockpack_header_footer_fields();
    $sections = array(
        'Header' => array(),
        'Footer' => array(),
    );

    foreach ( $fields as $key => $field ) {
        $sections[ preg_match( '/^(?:nav_|shared_link_)/', $key ) ? 'Header' : 'Footer' ][ $key ] = $field;
    }

    return $sections;
}

add_action( 'admin_menu', function () {
    add_theme_page(
        __( 'Header & Footer', 'rockpack' ),
        __( 'Header & Footer', 'rockpack' ),
        'manage_options',
        'rockpack-header-footer',
        'rockpack_render_header_footer_page'
    );
} );

function rockpack_render_header_footer_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $options = get_option( 'rockpack_settings', array() );
    ?>
    <div class="wrap rockpack-header-footer">
        <h1><?php esc_html_e( 'Header & Footer', 'rockpack' ); ?></h1>
        <p><?php esc_html_e( 'These settings are used across the entire website. Changes here update every page that uses the shared header or footer.', 'rockpack' ); ?></p>
        <?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Header and footer settings saved.', 'rockpack' ); ?></p></div><?php endif; ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="rockpack_save_header_footer">
            <?php wp_nonce_field( 'rockpack_save_header_footer', 'rockpack_header_footer_nonce' ); ?>

            <?php foreach ( rockpack_header_footer_sections() as $title => $fields ) : ?>
                <hr>
                <h2><?php echo esc_html( $title ); ?></h2>
                <div class="rp-fields">
                    <?php foreach ( $fields as $key => $field ) :
                        $field['default'] = $field['default'] ?? '';
                        rockpack_editor_input( $field, rp_option( $key, $field['default'] ), 'rp_content[' . $key . ']' );
                        if ( 'image' === $field['type'] ) {
                            rockpack_editor_input( array( 'key' => $key . '_id', 'label' => 'Attachment ID', 'type' => 'hidden' ), $options['_media'][ $key ] ?? 0, 'rp_content[' . $key . '_id]' );
                        }
                    endforeach; ?>
                </div>
            <?php endforeach; ?>

            <hr>
            <h2><?php esc_html_e( 'Navigation menus', 'rockpack' ); ?></h2>
            <p><?php esc_html_e( 'Dropdown menus are shared in the header on every page.', 'rockpack' ); ?></p>
            <?php $schema = rockpack_repeaters()['navigation_groups']; rockpack_editor_repeater( $schema, rockpack_collection( 'navigation_groups' ), 'rp_lists[navigation_groups]' ); ?>

            <hr>
            <h2><?php esc_html_e( 'Footer links', 'rockpack' ); ?></h2>
            <?php foreach ( array( 'footer_page_links', 'footer_service_links', 'footer_social' ) as $key ) :
                $schema = rockpack_repeaters()[ $key ]; ?>
                <h3><?php echo esc_html( $schema['label'] ); ?></h3>
                <?php rockpack_editor_repeater( $schema, rockpack_collection( $key ), 'rp_lists[' . $key . ']' ); ?>
            <?php endforeach; ?>

            <?php submit_button( __( 'Save Header & Footer', 'rockpack' ) ); ?>
        </form>
    </div>
    <?php
}

add_action( 'admin_post_rockpack_save_header_footer', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You are not allowed to edit these settings.', 'rockpack' ), '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'rockpack_save_header_footer', 'rockpack_header_footer_nonce' );

    $input = wp_unslash( $_POST );
    $content = is_array( $input['rp_content'] ?? null ) ? $input['rp_content'] : array();
    $lists = is_array( $input['rp_lists'] ?? null ) ? $input['rp_lists'] : array();
    $options = get_option( 'rockpack_settings', array() );
    $options = is_array( $options ) ? $options : array();
    $media = $options['_media'] ?? array();

    foreach ( rockpack_header_footer_fields() as $key => $field ) {
        if ( ! array_key_exists( $key, $content ) ) {
            continue;
        }
        $value = rockpack_sanitize_editor_field( $content[ $key ], $field );
        if ( is_wp_error( $value ) ) {
            wp_die( esc_html( $value->get_error_message() ) );
        }
        $options[ $key ] = $value;
        $options['_explicit'][ $key ] = true;

        if ( 'image' === $field['type'] ) {
            $id = absint( $content[ $key . '_id' ] ?? 0 );
            if ( $id && ! wp_attachment_is_image( $id ) ) {
                wp_die( esc_html__( 'Please select a valid image from the Media Library.', 'rockpack' ) );
            }
            $media[ $key ] = $id;
        }
    }

    foreach ( array( 'navigation_groups', 'footer_page_links', 'footer_service_links', 'footer_social' ) as $key ) {
        $schema = rockpack_repeaters()[ $key ];
        $rows = rockpack_sanitize_editor_rows( $lists[ $key ] ?? array(), $schema['fields'] );
        if ( is_wp_error( $rows ) ) {
            wp_die( esc_html( $rows->get_error_message() ) );
        }
        $options['_repeaters'][ $key ] = $rows;
    }

    $options['_media'] = $media;
    update_option( 'rockpack_settings', $options );
    do_action( 'rockpack_content_saved', 0 );

    wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'themes.php?page=rockpack-header-footer' ) ) );
    exit;
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'appearance_page_rockpack-header-footer' !== $hook ) {
        return;
    }

    wp_enqueue_media();
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();
    wp_enqueue_style( 'rockpack-page-editor', $theme_uri . '/assets/css/page-editor.css', array(), (string) filemtime( $theme_dir . '/assets/css/page-editor.css' ) );
    wp_enqueue_script( 'rockpack-page-editor', $theme_uri . '/assets/js/page-editor.js', array( 'media-editor' ), (string) filemtime( $theme_dir . '/assets/js/page-editor.js' ), true );
} );

function rockpack_scripts() {
    $htmx = '/assets/vendor/htmx/htmx.min.js';
    wp_enqueue_script( 'rockpack-htmx', get_template_directory_uri() . $htmx, array(), '2.0.11', true );
    wp_enqueue_style( 'rockpack-fonts', get_template_directory_uri() . '/assets/css/fonts.css', [], filemtime( get_template_directory() . '/assets/css/fonts.css' ) );
    wp_enqueue_style( 'rockpack-style', get_stylesheet_uri(), [], '1.0' );

    $portfolio_page = is_page( 'product-portfolio' ) || is_page_template( 'page-product-portfolio.php' );
    $planet_page = is_page( 'planet' ) || is_page_template( 'page-planet.php' );
    $contact_page = is_page( 'contact-us' ) || is_page_template( 'page-contact-us.php' );
    $company_page = is_page( array( 'about-us', 'history', 'certifications' ) ) || is_page_template( array( 'page-about-us.php', 'page-history.php', 'page-certifications.php' ) ) || $portfolio_page || $planet_page || $contact_page;
    if ( $company_page || is_front_page() || is_404() || is_search() || rockpack_content_page() ) {
        $shared_css = '/assets/css/company-page.css';
        $shared_js = '/assets/js/inner-page.js';
        wp_enqueue_style( 'rockpack-company-page', get_template_directory_uri() . $shared_css, [ 'rockpack-style' ], (string) filemtime( get_template_directory() . $shared_css ) );
        wp_enqueue_script( 'rockpack-inner-page', get_template_directory_uri() . $shared_js, [], (string) filemtime( get_template_directory() . $shared_js ), true );
        $motion_js = '/assets/js/motion.js';
        wp_enqueue_script( 'rockpack-motion', get_template_directory_uri() . $motion_js, [], (string) filemtime( get_template_directory() . $motion_js ), true );
    }

    if ( is_404() ) {
        $css = '/assets/css/not-found.css';
        wp_enqueue_style( 'rockpack-not-found', get_template_directory_uri() . $css, [ 'rockpack-company-page' ], (string) filemtime( get_template_directory() . $css ) );
    }

    if ( is_search() ) {
        $css = '/assets/css/search.css';
        wp_enqueue_style( 'rockpack-search', get_template_directory_uri() . $css, [ 'rockpack-company-page' ], (string) filemtime( get_template_directory() . $css ) );
    }

    if ( rockpack_content_page() ) {
        $css = '/assets/css/content-pages.css';
        wp_enqueue_style( 'rockpack-content-pages', get_template_directory_uri() . $css, array( 'rockpack-company-page' ), (string) filemtime( get_template_directory() . $css ) );
    }

    if ( is_front_page() ) {
        $css = '/assets/css/home.css';
        $js = '/assets/js/home.js';
        wp_enqueue_style( 'rockpack-home', get_template_directory_uri() . $css, [ 'rockpack-company-page' ], (string) filemtime( get_template_directory() . $css ) );
        wp_enqueue_script( 'rockpack-home', get_template_directory_uri() . $js, [], (string) filemtime( get_template_directory() . $js ), true );
        wp_enqueue_script( 'rockpack-home-tabs', get_template_directory_uri() . '/assets/js/about-page.js', [], (string) filemtime( get_template_directory() . '/assets/js/about-page.js' ), true );
    }

    if ( is_front_page() || $portfolio_page ) {
        $css = '/assets/css/product-components.css';
        $js = '/assets/js/product-gallery.js';
        wp_enqueue_style( 'rockpack-product-components', get_template_directory_uri() . $css, [ is_front_page() ? 'rockpack-home' : 'rockpack-company-page' ], (string) filemtime( get_template_directory() . $css ) );
        wp_enqueue_script( 'rockpack-product-gallery', get_template_directory_uri() . $js, [], (string) filemtime( get_template_directory() . $js ), true );
    }

    if ( $portfolio_page ) {
        $css = '/assets/css/portfolio-page.css';
        wp_enqueue_style( 'rockpack-portfolio-page', get_template_directory_uri() . $css, [ 'rockpack-product-components' ], (string) filemtime( get_template_directory() . $css ) );
    }

    if ( $contact_page ) {
        $css = '/assets/css/contact-page.css';
        wp_enqueue_style( 'rockpack-contact-page', get_template_directory_uri() . $css, [ 'rockpack-company-page' ], (string) filemtime( get_template_directory() . $css ) );
    }

    if ( $planet_page ) {
        $css = '/assets/css/planet-page.css';
        wp_enqueue_style( 'rockpack-planet-page', get_template_directory_uri() . $css, [ 'rockpack-company-page' ], (string) filemtime( get_template_directory() . $css ) );
    }

    if ( is_page( 'about-us' ) || is_page_template( 'page-about-us.php' ) ) {
        $css = '/assets/css/about-page.css';
        $js = '/assets/js/about-page.js';
        wp_enqueue_style( 'rockpack-about-page', get_template_directory_uri() . $css, [ 'rockpack-company-page' ], (string) filemtime( get_template_directory() . $css ) );
        wp_enqueue_script( 'rockpack-about-page', get_template_directory_uri() . $js, [], (string) filemtime( get_template_directory() . $js ), true );
    }

    if ( is_page( 'history' ) || is_page_template( 'page-history.php' ) ) {
        $history_css = get_template_directory() . '/assets/css/history.css';
        $history_js = '/assets/js/history.js';
        wp_enqueue_style(
            'rockpack-history',
            get_template_directory_uri() . '/assets/css/history.css',
            [ 'rockpack-company-page' ],
            is_file( $history_css ) ? (string) filemtime( $history_css ) : '1.0'
        );
        wp_enqueue_script( 'rockpack-history', get_template_directory_uri() . $history_js, [], (string) filemtime( get_template_directory() . $history_js ), true );
    }

    if ( is_page( 'certifications' ) || is_page_template( 'page-certifications.php' ) ) {
        $css = '/assets/css/certifications.css';
        wp_enqueue_style( 'rockpack-certifications', get_template_directory_uri() . $css, [ 'rockpack-company-page' ], (string) filemtime( get_template_directory() . $css ) );
    }
}
add_action( 'wp_enqueue_scripts', 'rockpack_scripts' );

/** Public, read-only search suggestions; the regular search form remains usable. */
function rockpack_search_preview() {
    $term = isset( $_GET['s'] ) && is_string( $_GET['s'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '';
    if ( strlen( $term ) < 2 ) {
        wp_die( '', '', array( 'response' => 200 ) );
    }
    $query = new WP_Query( array(
        's' => $term,
        'post_status' => 'publish',
        'has_password' => false,
        'posts_per_page' => 5,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ) );
    if ( $query->have_posts() ) {
        echo '<ul class="rp-search-preview__list">';
        while ( $query->have_posts() ) {
            $query->the_post();
            echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ?: __( 'Untitled', 'rockpack' ) ) . '</a></li>';
        }
        echo '</ul>';
        wp_reset_postdata();
    } else {
        echo '<p>' . esc_html__( 'No results found. Press Enter to search the site.', 'rockpack' ) . '</p>';
    }
    wp_die( '', '', array( 'response' => 200 ) );
}
add_action( 'wp_ajax_rockpack_search_preview', 'rockpack_search_preview' );
add_action( 'wp_ajax_nopriv_rockpack_search_preview', 'rockpack_search_preview' );

add_filter( 'body_class', function ( $classes ) {
    if ( is_404() || is_search() || rockpack_content_page() ) {
        $classes[] = 'rockpack-company-page';
    }
    if ( is_front_page() ) {
        $classes[] = 'rockpack-home-page';
        $classes[] = 'rockpack-company-page';
    }
    if ( is_page( array( 'about-us', 'history', 'certifications', 'product-portfolio', 'planet', 'contact-us' ) ) || is_page_template( array( 'page-about-us.php', 'page-history.php', 'page-certifications.php', 'page-product-portfolio.php', 'page-planet.php', 'page-contact-us.php' ) ) ) {
        $classes[] = 'rockpack-company-page';
    }
    if ( is_page( 'contact-us' ) || is_page_template( 'page-contact-us.php' ) ) {
        $classes[] = 'rockpack-contact-page';
    }
    if ( is_page( 'planet' ) || is_page_template( 'page-planet.php' ) ) {
        $classes[] = 'rockpack-planet-page';
    }
    if ( is_page( 'product-portfolio' ) || is_page_template( 'page-product-portfolio.php' ) ) {
        $classes[] = 'rockpack-portfolio-page';
    }
    if ( is_page( 'about-us' ) || is_page_template( 'page-about-us.php' ) ) {
        $classes[] = 'rockpack-about-page';
    }
    if ( is_page( 'history' ) || is_page_template( 'page-history.php' ) ) {
        $classes[] = 'rockpack-history-page';
    }
    if ( is_page( 'certifications' ) || is_page_template( 'page-certifications.php' ) ) {
        $classes[] = 'rockpack-certifications-page';
    }
    return $classes;
} );

// ── Custom Post Types ─────────────────────────────────────────────────────────
add_action( 'init', function () {
    $cpts = [
        'rp_stat' => [
            'name'      => 'Stats',
            'singular'  => 'Stat',
            'icon'      => 'dashicons-chart-bar',
            'supports'  => ['title', 'page-attributes'],
        ],
        'rp_history' => [
            'name'      => 'History Slides',
            'singular'  => 'History Slide',
            'icon'      => 'dashicons-book-alt',
            'supports'  => ['title', 'thumbnail', 'page-attributes'],
        ],
        'rp_cert' => [
            'name'      => 'Certifications',
            'singular'  => 'Certification',
            'icon'      => 'dashicons-awards',
            'supports'  => ['title', 'page-attributes'],
        ],
        'rp_product' => [
            'name'      => 'Products',
            'singular'  => 'Product',
            'icon'      => 'dashicons-archive',
            'supports'  => ['title', 'thumbnail', 'page-attributes'],
        ],
        'rp_logo' => [
            'name'      => 'Partner Logos',
            'singular'  => 'Partner Logo',
            'icon'      => 'dashicons-businessman',
            'supports'  => ['title', 'thumbnail', 'page-attributes'],
        ],
    ];

    foreach ( $cpts as $slug => $cfg ) {
        register_post_type( $slug, [
            'labels' => [
                'name'          => $cfg['name'],
                'singular_name' => $cfg['singular'],
                'add_new_item'  => 'Add New ' . $cfg['singular'],
                'edit_item'     => 'Edit ' . $cfg['singular'],
                'all_items'     => 'All ' . $cfg['name'],
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_icon'    => $cfg['icon'],
            'supports'     => $cfg['supports'],
            'has_archive'  => false,
            'rewrite'      => false,
        ] );
    }
} );

// ── Meta Boxes ────────────────────────────────────────────────────────────────
add_action( 'add_meta_boxes', function () {
    add_meta_box( 'rp_stat_details',    'Stat Details',           'rp_stat_meta_cb',    'rp_stat',    'normal', 'high' );
    add_meta_box( 'rp_history_details', 'Slide Content',          'rp_history_meta_cb', 'rp_history', 'normal', 'high' );
} );

function rp_stat_meta_cb( $post ) {
    $sub  = get_post_meta( $post->ID, 'rp_stat_sub',  true );
    $icon = get_post_meta( $post->ID, 'rp_stat_icon', true ) ?: 'expertise';
    wp_nonce_field( 'rp_stat_save', 'rp_stat_nonce' );
    $icons = [
        'expertise' => 'Expertise (person + checkmark)',
        'location'  => 'Location (map pin)',
        'markets'   => 'Markets (building)',
        'standards' => 'Standards (document + check)',
    ];
    ?>
    <p style="margin-bottom:16px">
        <label style="font-weight:600;display:block;margin-bottom:4px">Main label (post title)</label>
        <span style="color:#888;font-size:12px">Set this in the Title field above — e.g. "30 Years" or "Egypt".</span>
    </p>
    <p>
        <label for="rp_stat_sub" style="font-weight:600;display:block;margin-bottom:4px">Sub-label</label>
        <input type="text" id="rp_stat_sub" name="rp_stat_sub" value="<?php echo esc_attr( $sub ); ?>" style="width:100%" placeholder="e.g. Technical Expertise">
    </p>
    <p>
        <label for="rp_stat_icon" style="font-weight:600;display:block;margin-bottom:4px">Icon</label>
        <select id="rp_stat_icon" name="rp_stat_icon" style="width:100%">
            <?php foreach ( $icons as $val => $lbl ) : ?>
                <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $icon, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p style="margin-top:8px">
        <span style="color:#888;font-size:12px">Use the <strong>Order</strong> field (in the right sidebar) to control display order. Lower numbers appear first.</span>
    </p>
    <?php
}

function rp_history_meta_cb( $post ) {
    $text = get_post_meta( $post->ID, 'rp_history_text', true );
    wp_nonce_field( 'rp_history_save', 'rp_history_nonce' );
    ?>
    <p>
        <label for="rp_history_text" style="font-weight:600;display:block;margin-bottom:4px">Slide body text</label>
        <textarea id="rp_history_text" name="rp_history_text" rows="4" style="width:100%"><?php echo esc_textarea( $text ); ?></textarea>
    </p>
    <p><span style="color:#888;font-size:12px">The slide title is the post title above. Use Order to control slide order.</span></p>
    <?php
}

// ── Save meta boxes ───────────────────────────────────────────────────────────
add_action( 'save_post', function ( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    if ( isset( $_POST['rp_stat_nonce'] ) && wp_verify_nonce( $_POST['rp_stat_nonce'], 'rp_stat_save' ) && current_user_can( 'edit_post', $post_id ) ) {
        update_post_meta( $post_id, 'rp_stat_sub',  sanitize_text_field( wp_unslash( $_POST['rp_stat_sub']  ?? '' ) ) );
        update_post_meta( $post_id, 'rp_stat_icon', sanitize_key( wp_unslash( $_POST['rp_stat_icon'] ?? 'expertise' ) ) );
    }

    if ( isset( $_POST['rp_history_nonce'] ) && wp_verify_nonce( $_POST['rp_history_nonce'], 'rp_history_save' ) && current_user_can( 'edit_post', $post_id ) ) {
        update_post_meta( $post_id, 'rp_history_text', sanitize_textarea_field( wp_unslash( $_POST['rp_history_text'] ?? '' ) ) );
    }

} );

// ── Theme Options Page (Settings → Rockpack) ──────────────────────────────────
add_action( 'admin_menu', function () {
    add_options_page(
        'Rockpack Settings',
        'Rockpack',
        'manage_options',
        'rockpack-settings',
        'rp_render_settings_page'
    );
} );

add_action( 'admin_init', function () {
    register_setting( 'rockpack_settings_group', 'rockpack_settings', [
        'sanitize_callback' => 'rp_sanitize_settings',
    ] );
} );

function rp_sanitize_settings( $input ) {
    $out = get_option( 'rockpack_settings', array() );
    if ( ! is_array( $out ) ) $out = array();
    if ( ! is_array( $input ) ) return $out;
    $settings_fields = rp_settings_fields();
    // This sanitizer also runs for the dedicated Header & Footer form.
    $settings_fields['shared_header_footer'] = array( 'fields' => array_values( rockpack_header_footer_fields() ) );
    foreach ( $settings_fields as $data ) {
        foreach ( $data['fields'] ?? [] as $f ) {
            if ( ! array_key_exists( $f['key'], $input ) || ! is_scalar( $input[$f['key']] ) ) continue;
            $val = $input[ $f['key'] ] ?? '';
            if ( !isset($input['_media']) && isset($out['_media'][$f['key']]) && $val !== ($out[$f['key']]??'') ) unset($out['_media'][$f['key']]);
            if ( 'textarea' === $f['type'] ) {
                $out[ $f['key'] ] = sanitize_textarea_field( $val );
            } elseif ( 'email' === $f['type'] ) {
                $out[ $f['key'] ] = sanitize_email( $val );
            } elseif ( 'url' === $f['type'] ) {
                $out[ $f['key'] ] = esc_url_raw( $val );
            } elseif ( 'number' === $f['type'] ) {
                $out[ $f['key'] ] = '' === $val ? '' : absint( $val );
            } elseif ( 'select' === $f['type'] ) {
                if ( array_key_exists( $val, $f['choices'] ) ) $out[ $f['key'] ] = sanitize_text_field( $val );
            } else {
                $out[ $f['key'] ] = sanitize_text_field( $val );
            }
        }
    }
    foreach ( rockpack_shared_fields() as $key => $field ) if ( isset($input[$key]) && is_scalar($input[$key]) ) {
        $value=rockpack_sanitize_editor_field($input[$key],$field);
        if(!is_wp_error($value)) $out[$key]=$value;
    }
    foreach ( array('_explicit','_collections','_media') as $key ) if(isset($input[$key]) && is_array($input[$key])) {
        $out[$key]=array_map('absint',$input[$key]);
    }
    if(isset($input['_repeaters']) && is_array($input['_repeaters'])) foreach(rockpack_repeaters() as $key=>$schema) {
        if(!empty($schema['cpt']) || !isset($input['_repeaters'][$key])) continue;
        $rows=rockpack_sanitize_editor_rows($input['_repeaters'][$key],$schema['fields']);
        if(!is_wp_error($rows)) $out['_repeaters'][$key]=$rows;
    }
    return $out;
}

function rp_settings_fields() {
    $sections = rockpack_home_sections();
    unset( $sections['contact'] ); // Shared footer fields have their own editor.
    if ( class_exists( 'Portable_SEO' ) ) unset( $sections['seo'] ); // Managed under Settings > Portable SEO.
    foreach ( $sections as &$section ) {
        if ( isset( $section['fields'] ) ) {
            $section['fields'] = array_values( array_filter( $section['fields'], static function ( $field ) {
                return ! rockpack_is_fixed_title( $field['key'] ) && ! rockpack_is_replaced_field( $field['key'] );
            } ) );
        }
    }
    unset( $section );
    return array_filter( $sections, static function ( $section ) { return ! empty( $section['fields'] ); } );
}

function rp_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $opts = get_option( 'rockpack_settings', [] );
    ?>
    <div class="wrap">
        <h1>Rockpack Settings</h1>
        <p style="color:#666">Edit active scalar settings here. Repeated content is managed under Pages > Edit, and shared navigation/footer content under Appearance > Header &amp; Footer. Page and section headings are fixed in the theme. Leave any field blank to keep the default content.</p>
        <form method="post" action="options.php">
            <?php settings_fields( 'rockpack_settings_group' ); ?>
            <?php foreach ( rp_settings_fields() as $section => $data ) : ?>
                <h2 style="border-bottom:1px solid #ddd;padding-bottom:8px;margin-top:32px"><?php echo esc_html( $section ); ?></h2>
                <?php if ( ! empty( $data['description'] ) ) : ?><p class="description"><?php echo esc_html( $data['description'] ); ?></p><?php endif; ?>
                <?php if ( empty( $data['fields'] ) ) : continue; endif; ?>
                <table class="form-table" role="presentation">
                    <?php foreach ( $data['fields'] as $f ) : ?>
                        <tr>
                            <th scope="row">
                                <label for="rp_<?php echo esc_attr( $f['key'] ); ?>"><?php echo esc_html( $f['label'] ); ?></label>
                            </th>
                            <td>
                                <?php $val = $opts[ $f['key'] ] ?? ''; ?>
                                <?php if ( 'textarea' === $f['type'] ) : ?>
                                    <textarea id="rp_<?php echo esc_attr( $f['key'] ); ?>"
                                              name="rockpack_settings[<?php echo esc_attr( $f['key'] ); ?>]"
                                              rows="3" class="large-text"><?php echo esc_textarea( $val ); ?></textarea>
                                <?php elseif ( 'select' === $f['type'] ) : ?>
                                    <select id="rp_<?php echo esc_attr( $f['key'] ); ?>" name="rockpack_settings[<?php echo esc_attr( $f['key'] ); ?>]">
                                        <?php foreach ( $f['choices'] as $choice => $label ) : ?><option value="<?php echo esc_attr($choice); ?>" <?php selected($val,$choice); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
                                    </select>
                                <?php else : ?>
                                    <input type="<?php echo esc_attr( in_array( $f['type'], array( 'url', 'number', 'email' ), true ) ? $f['type'] : 'text' ); ?>"
                                           id="rp_<?php echo esc_attr( $f['key'] ); ?>"
                                           name="rockpack_settings[<?php echo esc_attr( $f['key'] ); ?>]"
                                           value="<?php echo esc_attr( $val ); ?>"
                                           class="large-text"
                                           placeholder="<?php echo esc_attr( $f['placeholder'] ); ?>">
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endforeach; ?>
            <?php submit_button( 'Save Settings' ); ?>
        </form>
    </div>
    <?php
}
