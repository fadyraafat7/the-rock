<?php
/**
 * Missing-page template.
 * @package Rockpack
 */
defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/includes/data.php';
get_header();
?>
<main class="not-found-page company-page" id="main-content">
    <?php get_template_part( 'template-parts/inner-navigation' ); ?>
    <section class="rp-not-found" aria-labelledby="not-found-title">
        <p class="rp-not-found__code" aria-hidden="true">404</p>
        <p class="rp-not-found__eyebrow"><?php esc_html_e( 'Error 404', 'rockpack' ); ?></p>
        <h1 id="not-found-title"><?php esc_html_e( 'Page not found', 'rockpack' ); ?></h1>
        <p class="rp-not-found__text"><?php esc_html_e( 'The page you are looking for may have moved or no longer exists. Let us help you find your way.', 'rockpack' ); ?></p>
        <a class="rp-not-found__home" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'rockpack' ); ?> <span aria-hidden="true">&rarr;</span></a>
        <form class="rp-not-found__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <label for="not-found-search"><?php esc_html_e( 'Or search the website', 'rockpack' ); ?></label>
            <div class="rp-not-found__search-controls">
                <input id="not-found-search" type="search" name="s" placeholder="<?php esc_attr_e( 'What are you looking for?', 'rockpack' ); ?>" required>
                <button type="submit"><?php esc_html_e( 'Search', 'rockpack' ); ?></button>
            </div>
        </form>
    </section>
</main>
<?php get_footer(); ?>
