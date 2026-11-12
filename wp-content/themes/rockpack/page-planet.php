<?php
/**
 * Template Name: Planet & Societal Impact
 * Template Post Type: page
 * @package Rockpack
 */
defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/includes/data.php';
$content = rockpack_planet_page_content();
get_header();
?>
<main class="planet-page company-page" id="main-content">
    <?php get_template_part( 'template-parts/inner-navigation' ); ?>
    <?php get_template_part( 'template-parts/inner-hero', null, array( 'title' => $content['title'], 'image' => $content['hero_image'], 'show_breadcrumb' => false ) ); ?>

    <section class="planet-sourcing" id="sustainable-sourcing" aria-labelledby="planet-sourcing-title">
        <?php if ( $content['sourcing_image'] ) : ?><img class="planet-sourcing__image" src="<?php echo esc_url(rp_webp_image_url(  $content['sourcing_image']  )); ?>" alt="<?php echo esc_attr(rp_option('planet_image_description_68','Printed packaging rolls and responsibly sourced packaging materials')); ?>" width="413" height="510" loading="lazy"><?php endif; ?>
        <div class="planet-page__copy"><h2 id="planet-sourcing-title"><?php echo esc_html( $content['sourcing_heading'] ); ?></h2><p><?php echo esc_html( $content['sourcing_text'] ); ?></p></div>
        <?php get_template_part( 'template-parts/angle-accent' ); ?>
    </section>

    <section class="planet-vision" id="vision-for-africa" aria-labelledby="planet-vision-title">
        <div class="planet-page__copy"><h2 id="planet-vision-title"><?php echo esc_html( $content['vision_heading'] ); ?></h2><p><?php echo esc_html( $content['vision_text'] ); ?></p><p><?php echo esc_html( $content['vision_text_2'] ); ?></p></div>
        <figure class="planet-vision__figure"><?php if ( $content['vision_image'] ) : ?><img class="planet-vision__image" src="<?php echo esc_url(rp_webp_image_url(  $content['vision_image']  )); ?>" alt="<?php echo esc_attr(rp_option('planet_image_description_69','A technician inspecting aseptic production equipment')); ?>" width="555" height="562" loading="lazy"><?php endif; ?><span class="planet-vision__badge" aria-hidden="true"><img src="<?php echo esc_url(rp_webp_image_url(  get_template_directory_uri() . '/assets/images/home/packaging-symbol.svg'  )); ?>" alt="" width="65" height="90" loading="lazy"></span></figure>
    </section>

    <section class="planet-esg" id="environmental-and-social" aria-labelledby="planet-esg-title">
        <header class="planet-esg__header"><h2 id="planet-esg-title"><?php echo esc_html( $content['esg_heading'] ); ?></h2><p><?php echo esc_html( $content['esg_intro'] ); ?></p></header>
        <ol class="planet-esg__pillars">
            <?php foreach ( rockpack_collection('esg_pillars') as $pillar ) : ?>
            <li><article><h3><?php echo esc_html( $pillar['title'] ); ?></h3><ul><?php foreach ( $pillar['points'] as $point ) : ?><li><?php echo esc_html( $point['text'] ); ?></li><?php endforeach; ?></ul></article></li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="planet-statement" aria-labelledby="planet-statement-title"><h2 id="planet-statement-title"><?php echo esc_html( $content['statement_heading'] ); ?></h2><p><?php echo esc_html( $content['statement_text'] ); ?></p></section>
</main>
<?php get_template_part( 'template-parts/footer-section' ); ?>
<?php get_footer(); ?>
