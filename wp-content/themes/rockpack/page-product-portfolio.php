<?php
/**
 * Template Name: Product Portfolio
 * Template Post Type: page
 * @package Rockpack
 */
defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/includes/data.php';
$content = rockpack_portfolio_page_content();
get_header();
?>
<main class="portfolio-page company-page" id="main-content">
    <?php get_template_part( 'template-parts/inner-navigation' ); ?>
    <?php get_template_part( 'template-parts/inner-hero', null, array( 'title' => $content['title'], 'image' => $content['hero_image'], 'show_breadcrumb' => false ) ); ?>

    <section class="portfolio-intro" id="product-portfolio" aria-labelledby="portfolio-heading">
        <h2 id="portfolio-heading"><?php echo esc_html( $content['heading'] ); ?></h2>
        <header class="portfolio-intro__header"><h3><?php echo esc_html( $content['gallery_heading'] ); ?></h3><p><?php echo esc_html( $content['gallery_intro'] ); ?></p></header>
        <?php get_template_part( 'template-parts/product-gallery', null, array( 'id' => 'portfolio-product-gallery', 'desktop_count' => 9 ) ); ?>
    </section>

    <section class="portfolio-quality" id="quality-management" aria-labelledby="portfolio-quality-title">
        <h2 id="portfolio-quality-title"><?php echo esc_html( $content['quality_heading'] ); ?></h2>
        <div><p><?php echo esc_html( $content['quality_intro'] ); ?></p><p><?php echo esc_html( $content['quality_text'] ); ?></p></div>
        <?php get_template_part( 'template-parts/angle-accent' ); ?>
    </section>

    <section class="portfolio-materials" aria-labelledby="portfolio-materials-title">
        <?php if ( $content['materials_image'] ) : ?><img class="portfolio-materials__image" src="<?php echo esc_url(rp_webp_image_url(  $content['materials_image']  )); ?>" alt="<?php echo esc_attr(rp_option('product_portfolio_image_description_70','Printed packaging rolls and aseptic packaging materials')); ?>" width="685" height="724" loading="lazy"><?php endif; ?>
        <div class="portfolio-materials__copy">
            <h2 id="portfolio-materials-title"><?php echo esc_html( $content['materials_heading'] ); ?></h2>
            <p><?php echo esc_html( $content['materials_intro'] ); ?></p>
            <div class="portfolio-materials__steps" tabindex="0" role="region" aria-label="Process and Materials - scroll to explore all four steps">
                <ol><?php foreach ( rockpack_collection('material_steps') as $index => $step ) : ?><li><span class="portfolio-materials__number" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span><article><h3><?php echo esc_html( $step['title'] ); ?></h3><p><?php echo nl2br( esc_html( $step['text'] ) ); ?></p></article></li><?php endforeach; ?></ol>
            </div>
        </div>
    </section>

    <?php get_template_part( 'template-parts/process' ); ?>
    <?php get_template_part( 'template-parts/local' ); ?>
</main>
<?php get_template_part( 'template-parts/footer-section' ); ?>
<?php get_footer(); ?>
