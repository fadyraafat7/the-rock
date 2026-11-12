<?php
/**
 * Search results template.
 * @package Rockpack
 */
defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/includes/data.php';
$search_term = trim( get_search_query( false ) );
get_header();
?>
<main class="search-page company-page" id="main-content">
    <?php get_template_part( 'template-parts/inner-navigation' ); ?>
    <?php get_template_part( 'template-parts/inner-hero', null, array(
        'title' => __( 'Search', 'rockpack' ),
        'image' => get_template_directory_uri() . '/assets/images/history/hero.jpg',
        'parent' => __( 'Home', 'rockpack' ),
        'parent_url' => home_url( '/' ),
    ) ); ?>
    <section class="rp-search-header" aria-label="<?php esc_attr_e( 'Search the website', 'rockpack' ); ?>">
        <form class="rp-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" hx-get="<?php echo esc_url( home_url( '/' ) ); ?>" hx-target="#search-results" hx-select="#search-results" hx-swap="outerHTML" hx-push-url="true">
            <label for="search-page-input"><?php esc_html_e( 'What are you looking for?', 'rockpack' ); ?></label>
            <div class="rp-search-form__controls">
                <input id="search-page-input" type="search" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by keyword', 'rockpack' ); ?>" required>
                <button type="submit"><?php esc_html_e( 'Search', 'rockpack' ); ?> <span aria-hidden="true">&rarr;</span></button>
            </div>
        </form>
    </section>
    <section id="search-results" class="rp-search-results" aria-labelledby="search-results-title" aria-live="polite">
        <?php if ( '' === $search_term ) : ?>
            <h2 id="search-results-title"><?php esc_html_e( 'Start your search', 'rockpack' ); ?></h2>
            <p class="rp-search-results__message"><?php esc_html_e( 'Enter a keyword above to find pages and news from The Rock Pack.', 'rockpack' ); ?></p>
        <?php else : ?>
            <h2 id="search-results-title"><?php
                /* translators: %s: visitor's search term. */
                printf( esc_html__( 'Results for "%s"', 'rockpack' ), esc_html( $search_term ) );
            ?></h2>
            <?php if ( have_posts() ) : ?>
                <p class="rp-search-results__count"><?php
                    global $wp_query;
                    $result_count = (int) $wp_query->found_posts;
                    /* translators: %s: number of search results. */
                    printf( esc_html( _n( '%s result found', '%s results found', $result_count, 'rockpack' ) ), esc_html( number_format_i18n( $result_count ) ) );
                ?></p>
                <div class="rp-search-results__list">
                    <?php while ( have_posts() ) : the_post();
                        $result_title = get_the_title() ?: __( 'Untitled', 'rockpack' );
                        $result_type = get_post_type_object( get_post_type() );
                        $result_excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 36 );
                    ?>
                        <article class="rp-search-result" aria-labelledby="search-result-<?php the_ID(); ?>">
                            <?php if ( $result_type ) : ?><p class="rp-search-result__type"><?php echo esc_html( $result_type->labels->singular_name ); ?></p><?php endif; ?>
                            <h3 id="search-result-<?php the_ID(); ?>"><a href="<?php the_permalink(); ?>"><?php echo esc_html( $result_title ); ?> <span aria-hidden="true">&rarr;</span></a></h3>
                            <?php if ( '' !== $result_excerpt ) : ?><p class="rp-search-result__excerpt"><?php echo esc_html( $result_excerpt ); ?></p><?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                </div>
                <div hx-boost="true" hx-target="#search-results" hx-select="#search-results" hx-swap="outerHTML" hx-push-url="true"><?php the_posts_pagination( array(
                    'mid_size' => 1,
                    'prev_text' => __( 'Previous', 'rockpack' ),
                    'next_text' => __( 'Next', 'rockpack' ),
                    'screen_reader_text' => __( 'Search results pages', 'rockpack' ),
                    'aria_label' => __( 'Search results pages', 'rockpack' ),
                ) ); ?></div>
            <?php else : ?>
                <div class="rp-search-results__empty">
                    <h3><?php esc_html_e( 'No results found', 'rockpack' ); ?></h3>
                    <p><?php esc_html_e( 'Try a different keyword, check your spelling, or use a more general term.', 'rockpack' ); ?></p>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'rockpack' ); ?> <span aria-hidden="true">&rarr;</span></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>
<?php get_template_part( 'template-parts/footer-section' ); ?>
<?php get_footer(); ?>
