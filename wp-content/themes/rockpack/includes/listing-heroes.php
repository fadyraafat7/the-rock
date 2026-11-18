<?php
/** Administrator controls for the News and Careers archive heroes. */
defined( 'ABSPATH' ) || exit;

function rockpack_listing_hero( $kind ) {
    $kind = 'careers' === $kind ? 'careers' : 'news';
    return array(
        'parent' => __( 'Company', 'rockpack' ),
        'parent_url' => home_url( '/' ),
        'show_lines' => true,
        'title' => rp_option( $kind . '_listing_hero_title', rp_option( $kind . '_heading', ucfirst( $kind ) ) ),
        'image' => rp_option( $kind . '_listing_hero_image', get_template_directory_uri() . '/assets/images/history/hero.jpg' ),
    );
}

add_action( 'admin_menu', function () {
    add_pages_page( 'News & Careers Heroes', 'News & Careers Heroes', 'manage_options', 'rockpack-listing-heroes', 'rockpack_listing_heroes_admin' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'pages_page_rockpack-listing-heroes' !== $hook ) return;
    wp_enqueue_media();
    wp_enqueue_script( 'rockpack-listing-heroes', get_template_directory_uri() . '/assets/js/listing-heroes-admin.js', array( 'media-editor' ), (string) filemtime( get_template_directory() . '/assets/js/listing-heroes-admin.js' ), true );
} );

function rockpack_listing_heroes_admin() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1>News &amp; Careers Heroes</h1>
        <p>Edit each page's hero title and image. The preview updates before you save.</p>
        <?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Hero settings saved.</p></div><?php endif; ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="rockpack_save_listing_heroes">
            <?php wp_nonce_field( 'rockpack_save_listing_heroes' ); ?>
            <?php foreach ( array( 'news' => 'News', 'careers' => 'Careers' ) as $kind => $label ) :
                $hero = rockpack_listing_hero( $kind );
                ob_start();
                get_template_part( 'template-parts/inner-hero', null, $hero );
                $markup = ob_get_clean();
                $document = '<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap"><link rel="stylesheet" href="' . esc_url( get_template_directory_uri() . '/assets/css/company-page.css' ) . '"><style>html,body{margin:0}body{font-family:Inter,Arial,sans-serif;--company-page-width:100%}a{pointer-events:none}</style></head><body>' . $markup . '</body></html>';
            ?>
            <section class="rp-listing-hero-editor" style="max-width:900px;margin:24px 0 40px">
                <h2><?php echo esc_html( $label ); ?> Hero</h2>
                <p><label>Title<br><input class="large-text rp-listing-title" name="<?php echo esc_attr( $kind ); ?>_title" value="<?php echo esc_attr( $hero['title'] ); ?>" required></label></p>
                <p><label>Image URL<br><input class="large-text rp-listing-image" name="<?php echo esc_attr( $kind ); ?>_image" value="<?php echo esc_attr( $hero['image'] ); ?>" type="url" required></label></p>
                <p><button type="button" class="button rp-listing-select">Choose image</button></p>
                <p>Live preview (changes are not saved yet)</p>
                <iframe class="rp-listing-preview" title="<?php echo esc_attr( $label . ' hero preview' ); ?>" sandbox="allow-same-origin" style="width:100%;height:320px;border:1px solid #c3c4c7" srcdoc="<?php echo esc_attr( $document ); ?>"></iframe>
            </section>
            <?php endforeach; ?>
            <?php submit_button( 'Save heroes' ); ?>
        </form>
    </div>
    <?php
}

add_action( 'admin_post_rockpack_save_listing_heroes', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'You cannot edit these settings.', '', array( 'response' => 403 ) );
    check_admin_referer( 'rockpack_save_listing_heroes' );
    $opts = get_option( 'rockpack_settings', array() );
    if ( ! is_array( $opts ) ) $opts = array();
    foreach ( array( 'news', 'careers' ) as $kind ) {
        $title = sanitize_text_field( wp_unslash( $_POST[ $kind . '_title' ] ?? '' ) );
        $image = esc_url_raw( wp_unslash( $_POST[ $kind . '_image' ] ?? '' ), array( 'http', 'https' ) );
        if ( ! $title || ! $image ) wp_die( 'A title and image URL are required for each hero.', '', array( 'response' => 400 ) );
        $opts[ $kind . '_listing_hero_title' ] = $title;
        $opts[ $kind . '_listing_hero_image' ] = $image;
    }
    update_option( 'rockpack_settings', $opts );
    do_action( 'rockpack_content_saved', 0 );
    wp_safe_redirect( add_query_arg( array( 'page' => 'rockpack-listing-heroes', 'updated' => '1' ), admin_url( 'edit.php?post_type=page' ) ) );
    exit;
} );
