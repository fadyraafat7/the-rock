<?php
defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/includes/data.php';
$jobs = 'careers' === ($args['kind'] ?? 'news');
$hero = rockpack_listing_hero( $jobs ? 'careers' : 'news' );
get_header();
?>
<main class="company-page content-page" id="main-content">
    <?php get_template_part('template-parts/inner-navigation'); ?>
    <?php get_template_part('template-parts/inner-hero',null,array(
        'title'=>$hero['title'],
        'image'=>$hero['image'],
        'show_lines'=>true,
        'parent'=>__('Home','rockpack'),
        'parent_url'=>home_url('/'),
    )); ?>
    <div class="rp-content-intro"><p><?php echo esc_html(rp_option($jobs ? 'careers_sub' : 'news_subtitle', $jobs ? 'At The Rock Pack, People Are At The Core Of Everything We Do.' : 'Stay Connected With The Latest Updates From The Rock Pack.')); ?></p></div>
    <section id="content-list" class="rp-content-list" aria-live="polite" aria-label="<?php echo esc_attr($jobs ? __('Open job opportunities','rockpack') : __('News articles','rockpack')); ?>">
        <?php if ( have_posts() ) : ?>
            <div class="rp-content-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article class="rp-content-card">
                        <?php if ( ! $jobs && has_post_thumbnail() ) : ?><a class="rp-content-card__image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail('large',array('loading'=>'lazy','alt'=>'')); ?></a><?php endif; ?>
                        <div class="rp-content-card__copy">
                            <?php if ( $jobs ) : $details = array_filter(array(get_post_meta(get_the_ID(),'_rp_job_location',true),get_post_meta(get_the_ID(),'_rp_job_employment',true))); ?>
                                <?php if ( $details ) : ?><p class="rp-content-meta"><?php echo esc_html(implode(' · ',$details)); ?></p><?php endif; ?>
                            <?php else : ?><p class="rp-content-meta"><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date()); ?></time></p><?php endif; ?>
                            <h2><a href="<?php the_permalink(); ?>"><?php echo esc_html(get_the_title() ?: __('Untitled','rockpack')); ?></a></h2>
                            <?php $excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt()),32); if($excerpt) : ?><p><?php echo esc_html($excerpt); ?></p><?php endif; ?>
                            <a class="rp-content-link" href="<?php the_permalink(); ?>"><?php echo esc_html($jobs ? __('View opportunity','rockpack') : __('Read article','rockpack')); ?> <span aria-hidden="true">&rarr;</span><span class="rp-content-sr-only">: <?php echo esc_html(get_the_title()); ?></span></a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php
            $pagination = array('mid_size'=>1,'prev_text'=>__('Previous','rockpack'),'next_text'=>__('Next','rockpack'));
            if ( ! $jobs ) {
                $news_url = rockpack_news_url();
                $pagination['base'] = $news_url . '%_%';
                $pagination['format'] = ( str_contains($news_url,'?') ? '&' : '?' ) . 'page=%#%';
            }
            echo '<div hx-boost="true" hx-target="#content-list" hx-select="#content-list" hx-swap="outerHTML" hx-push-url="true">';
            the_posts_pagination($pagination);
            echo '</div>';
            ?>
        <?php else : ?>
            <div class="rp-content-empty"><h2><?php echo esc_html($jobs ? __('No open opportunities right now','rockpack') : __('News updates are coming soon','rockpack')); ?></h2><p><?php echo esc_html($jobs ? __('Please check back for future opportunities to join our team.','rockpack') : __('Visit again for our latest company news.','rockpack')); ?></p><a class="rp-content-link" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to home','rockpack'); ?> &rarr;</a></div>
        <?php endif; ?>
    </section>
</main>
<?php get_template_part('template-parts/footer-section'); get_footer(); ?>
