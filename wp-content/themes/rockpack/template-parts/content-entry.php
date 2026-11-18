<?php
defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/includes/data.php';
$jobs = 'careers' === ($args['kind'] ?? 'news');
get_header();
?>
<main class="company-page content-page" id="main-content">
    <?php get_template_part('template-parts/inner-navigation'); ?>
    <?php while ( have_posts() ) : the_post(); ?>
        <article class="rp-entry">
            <header class="rp-entry__header">
                <?php $parent_url = $jobs ? rockpack_careers_url() : rockpack_news_url(); $parent_label = $jobs ? __('All opportunities','rockpack') : __('All news','rockpack'); $entry_title = get_the_title() ?: __('Untitled','rockpack'); ?>
                <nav aria-label="<?php esc_attr_e('Breadcrumb','rockpack'); ?>"><a class="rp-content-link" href="<?php echo esc_url($parent_url); ?>"><?php echo esc_html($parent_label); ?></a><span aria-hidden="true"> &rsaquo; </span><span aria-current="page"><?php echo esc_html($entry_title); ?></span></nav>
                <?php rp_seo_breadcrumb_schema( $parent_label, $parent_url, $entry_title ); ?>
                <h1><?php echo esc_html(get_the_title() ?: __('Untitled','rockpack')); ?></h1>
                <?php if ( ! $jobs ) : ?><p class="rp-content-meta"><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date()); ?></time> · <?php echo esc_html(get_the_author()); ?></p><?php endif; ?>
            </header>
            <?php if ( post_password_required() ) : ?>
                <?php echo get_the_password_form(); ?>
            <?php else : ?>
                <?php if ( $jobs ) : $details = array_filter(array(get_post_meta(get_the_ID(),'_rp_job_location',true),get_post_meta(get_the_ID(),'_rp_job_employment',true))); if($details) : ?><p class="rp-entry__job-meta"><?php echo esc_html(implode(' · ',$details)); ?></p><?php endif; endif; ?>
                <?php if ( has_post_thumbnail() ) : ?><figure class="rp-entry__image"><?php the_post_thumbnail('large'); ?></figure><?php endif; ?>
                <div class="rp-entry__body"><?php the_content(); wp_link_pages(); ?></div>
                <?php if ( $jobs ) : $apply = rockpack_job_application_url(get_the_ID()); ?>
                    <div class="rp-entry__apply">
                        <?php if ( $apply ) : ?><a class="rp-content-button" href="<?php echo esc_url($apply); ?>"><?php esc_html_e('Apply now','rockpack'); ?> <span aria-hidden="true">&rarr;</span></a>
                        <?php else : ?><p role="status"><?php esc_html_e('Applications for this opportunity are closed.','rockpack'); ?></p><?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </article>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
