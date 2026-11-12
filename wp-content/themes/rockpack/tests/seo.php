<?php
/** Local sitemap and SEO regression checks. Fixture database writes are rolled back. */
if ( PHP_SAPI !== 'cli' ) exit(2);
$_SERVER['REQUEST_SCHEME'] = 'http'; $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'figma-htmx-site.test';
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! class_exists( 'Rockpack_Static_SSG' ) || ! str_ends_with( (string) wp_parse_url( home_url(), PHP_URL_HOST ), '.test' ) ) exit("Local .test site and active SSG required.\n");
global $wpdb;
foreach ( array( $wpdb->posts, $wpdb->postmeta, $wpdb->options ) as $table ) {
    $status = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $wpdb->esc_like( $table ) ) );
    if ( ! $status || 'InnoDB' !== $status->Engine ) exit("InnoDB required.\n");
}
remove_action( 'transition_post_status', array( 'Rockpack_Static_SSG', 'on_transition_post_status' ), 10 );
remove_action( 'wp_after_insert_post', array( 'Rockpack_Static_SSG', 'on_page_structure_change' ), 10 );
$checks = 0;
$check = static function ( $condition, $message ) use ( &$checks ) { if ( ! $condition ) throw new RuntimeException( $message ); ++$checks; echo "PASS $message\n"; };
$wpdb->query('START TRANSACTION');
try {
    $public = wp_insert_post( array( 'post_type'=>'rp_job', 'post_status'=>'publish', 'post_title'=>'SEO fixture public job' ), true );
    $draft = wp_insert_post( array( 'post_type'=>'rp_job', 'post_status'=>'draft', 'post_title'=>'SEO fixture draft job' ), true );
    $password = wp_insert_post( array( 'post_type'=>'page', 'post_status'=>'publish', 'post_title'=>'SEO fixture protected page', 'post_password'=>'secret' ), true );
    foreach ( array( $public, $draft, $password ) as $id ) if ( is_wp_error( $id ) ) throw new RuntimeException( $id->get_error_message() );
    $excluded = wp_insert_post( array( 'post_type'=>'page', 'post_status'=>'publish', 'post_title'=>'SEO fixture noindex' ), true );
    update_post_meta( $excluded, '_rp_seo_noindex', '1' );
    $xml = simplexml_load_string( Rockpack_Static_SSG::generate_seo_files( true ) );
    $check( false !== $xml, 'sitemap is valid XML' );
    $urls = array(); foreach ( $xml->url as $entry ) $urls[] = (string) $entry->loc;
    $check( in_array( get_permalink( $public ), $urls, true ), 'public dynamic job is included without a snapshot' );
    $check( ! in_array( get_permalink( $draft ), $urls, true ), 'draft job excluded' );
    $check( ! in_array( get_permalink( $password ), $urls, true ), 'password protected page excluded' );
    $check( ! in_array( get_permalink( $excluded ), $urls, true ), 'noindex page excluded from custom sitemap' );
    $query_args = apply_filters( 'wp_sitemaps_posts_query_args', array( 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1 ), 'page' );
    $index_query = new WP_Query( $query_args );
    $check( ! in_array( $excluded, wp_list_pluck( $index_query->posts, 'ID' ), true ), 'noindex page excluded from WordPress sitemap query' );
    $check( in_array( rockpack_news_url(), $urls, true ) && in_array( rockpack_careers_url(), $urls, true ), 'dynamic listings are included' );
    $check( count( $urls ) === count( array_unique( $urls ) ), 'sitemap has no duplicate URLs' );
    global $wp_query, $wp_the_query;
    $previous_query = $wp_query;
    $wp_query = new WP_Query( array( 'p' => $excluded, 'post_type' => 'page' ) );
    $robots = apply_filters( 'wp_robots', array() );
    $check( ! empty( $robots['noindex'] ), 'noindex page emits noindex directive' );
    $second_job = wp_insert_post( array( 'post_type'=>'rp_job', 'post_status'=>'publish', 'post_title'=>'SEO fixture second job' ), true );
    $wp_query = new WP_Query( array( 'post_type'=>'rp_job', 'posts_per_page'=>1, 'paged'=>1 ) );
    ob_start(); do_action( 'wp_head' ); $head = ob_get_clean();
    $check( $wp_query->max_num_pages > 1 && str_contains( $head, 'rel="next" href="' . esc_url( trailingslashit( rockpack_careers_url() ) . user_trailingslashit( 'page/2', 'paged' ) ) . '"' ), 'career archive outputs next page link' );
    $wp_query = $previous_query;
    $image = get_template_directory_uri() . '/assets/images/home/hero.jpg';
    $size = wp_getimagesize( get_template_directory() . '/assets/images/home/hero.jpg' );
    $check( rp_image_dimensions( $image ) === ' width="' . $size[0] . '" height="' . $size[1] . '"', 'local image uses actual dimensions' );
    $check( '' === rp_image_dimensions( 'https://example.invalid/image.jpg' ), 'remote images are never fetched' );
    $optimized = rp_webp_image_url( $image );
    $check( $image . '.webp' === $optimized, 'bundled image uses a smaller generated WebP' );
    $check( $optimized . '?v=1' === rp_webp_image_url( $image . '?v=1' ), 'image query string is preserved' );
    $external = 'https://example.invalid/hero.jpg';
    $check( $external === rp_webp_image_url( $external ), 'external image URL is left unchanged' );
    $attributes = apply_filters( 'wp_get_attachment_image_attributes', array( 'src' => $image ), null, 'full' );
    $check( $optimized === $attributes['src'], 'attachment display uses WebP without changing stored URL' );
    $sources = apply_filters( 'wp_calculate_image_srcset', array( 100 => array( 'url' => $image, 'descriptor' => 'w', 'value' => 100 ) ) );
    $check( $optimized === $sources[100]['url'], 'responsive image candidate uses WebP' );
    echo "$checks SEO checks passed.\n";
} catch ( Throwable $e ) { fwrite( STDERR, "FAIL " . $e->getMessage() . "\n" ); $failure = true; }
finally { $wpdb->query('ROLLBACK'); }
exit( empty( $failure ) ? 0 : 1 );
