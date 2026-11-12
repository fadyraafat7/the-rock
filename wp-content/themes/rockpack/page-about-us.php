<?php
/**
 * Template Name: Company About Us
 * Template Post Type: page
 * @package Rockpack
 */
defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/includes/data.php';
$content = rockpack_about_page_content();
$assets = get_template_directory_uri() . '/assets/images/about/';
$icons = array( 'experience', 'precision', 'control', 'safety', 'hygiene', 'performance' );
get_header();
?>
<main class="about-page company-page" id="main-content">
	<?php get_template_part( 'template-parts/inner-navigation' ); ?>
	<?php get_template_part( 'template-parts/inner-hero', null, array( 'title' => $content['title'], 'image' => $content['hero_image'] ) ); ?>

	<section class="about-intro about-section" aria-labelledby="about-intro-title">
		<div class="about-intro__media">
			<?php if ( $content['image'] ) : ?><img class="about-intro__image" src="<?php echo esc_url(rp_webp_image_url(  $content['image']  )); ?>" alt="<?php echo esc_attr(rp_option('about_us_image_description_62','The Rock Pack silver packaging pouches on a production conveyor')); ?>" width="660" height="782" loading="lazy"><?php endif; ?>
			<img class="about-intro__badge" src="<?php echo esc_url(rp_webp_image_url(  $assets . 'packaging-badge.png'  )); ?>" alt="" width="132" height="140" loading="lazy">
		</div>
		<div class="about-intro__copy">
			<p class="about-intro__eyebrow"><img src="<?php echo esc_url(rp_webp_image_url(  get_template_directory_uri() . '/assets/images/certifications/logo.png'  )); ?>" alt="" width="20" height="23"><?php echo esc_html( $content['eyebrow'] ); ?></p>
			<h2 id="about-intro-title"><?php echo esc_html( $content['heading'] ); ?></h2>
			<ul class="about-intro__bullets"><?php foreach(rockpack_collection('about_bullets') as $bullet) : ?><li><?php echo esc_html($bullet['text']); ?></li><?php endforeach; ?></ul>
			<div class="about-tabs" data-about-tabs>
				<div class="about-tabs__list" role="tablist" aria-label="Our capabilities">
        <?php $tabs=rockpack_collection('about_tabs'); foreach($tabs as $i=>$tab) : ?>
        <button class="rp-tab" type="button" role="tab" id="about-tab-<?php echo (int)$i; ?>" aria-controls="about-panel-<?php echo (int)$i; ?>" aria-selected="<?php echo $i?'false':'true'; ?>" tabindex="<?php echo $i?-1:0; ?>"><?php echo esc_html($tab['title']); ?></button>
        <?php endforeach; ?></div>
        <?php foreach($tabs as $i=>$tab) : ?><div class="about-tabs__panel" id="about-panel-<?php echo (int)$i; ?>" role="tabpanel" aria-labelledby="about-tab-<?php echo (int)$i; ?>" tabindex="0" <?php if($i) echo 'hidden'; ?>><?php echo esc_html($tab['text']); ?></div><?php endforeach; ?>
			</div>
			<?php if ( ($content['button_url']) && ($content['button_text']) ) : ?><a class="about-button" href="<?php echo esc_url( $content['button_url'] ); ?>"><?php echo esc_html( $content['button_text'] ); ?><span aria-hidden="true">→</span></a><?php endif; ?>
		</div>
		<img class="about-intro__accent" src="<?php echo esc_url(rp_webp_image_url(  get_template_directory_uri() . '/assets/images/certifications/accent-right.png'  )); ?>" alt=""<?php echo rp_image_dimensions( get_template_directory_uri() . '/assets/images/certifications/accent-right.png' ); ?> loading="lazy">
	</section>

	<section class="about-foundation about-section" aria-labelledby="about-foundation-title">
		<?php if ( $content['foundation_image'] ) : ?><img class="about-foundation__background" src="<?php echo esc_url(rp_webp_image_url(  $content['foundation_image']  )); ?>" alt="" width="2999" height="2123" loading="lazy"><?php endif; ?>
		<header><h2 id="about-foundation-title"><?php echo esc_html( $content['foundation_heading'] ); ?></h2><p><?php echo esc_html( $content['foundation_text'] ); ?></p></header>
		<ul class="about-foundation__features">
		<?php foreach ( rockpack_collection('foundation_features') as $item ) : ?>
			<li><?php if ( $item['image'] ) : ?><img src="<?php echo esc_url(rp_webp_image_url(  $item['image']  )); ?>" alt="" width="55" height="60" loading="lazy"><?php endif; ?><span><?php echo esc_html( $item['text'] ); ?></span></li>
		<?php endforeach; ?>
		</ul>
	</section>

	<section class="about-partnership about-section" aria-labelledby="about-partnership-title">
		<div class="about-partnership__copy">
			<h2 id="about-partnership-title"><?php echo esc_html( $content['partnership_heading'] ); ?></h2>
			<p><?php echo esc_html( $content['partnership_text'] ); ?></p>
			<ul><?php foreach ( rockpack_collection('partnership_points') as $item ) : ?><li><?php if ( $item['image'] ) : ?><img src="<?php echo esc_url(rp_webp_image_url(  $item['image']  )); ?>" alt="" width="54" height="61" loading="lazy"><?php endif; ?><span><?php echo esc_html( $item['text'] ); ?></span></li><?php endforeach; ?></ul>
		</div>
		<div class="about-partnership__collage">
			<?php if ( $content['partnership_image'] ) : ?><img class="about-partnership__image about-partnership__image--high" src="<?php echo esc_url(rp_webp_image_url(  $content['partnership_image']  )); ?>" alt="<?php echo esc_attr(rp_option('about_us_image_description_63','Business partners shaking hands')); ?>" width="331" height="412" loading="lazy"><?php endif; ?>
			<?php if ( $content['collaboration_image'] ) : ?><img class="about-partnership__image about-partnership__image--low" src="<?php echo esc_url(rp_webp_image_url(  $content['collaboration_image']  )); ?>" alt="<?php echo esc_attr(rp_option('about_us_image_description_64','Team members bringing puzzle pieces together')); ?>" width="331" height="412" loading="lazy"><?php endif; ?>
		</div>
	</section>

	<section class="about-growth about-section" aria-labelledby="about-growth-title">
		<h2 id="about-growth-title"><?php echo esc_html( $content['growth_heading'] ); ?></h2>
		<div class="about-growth__carousel"><div class="about-growth__cards" role="region" aria-label="Regional growth capabilities — scroll to explore cards" tabindex="0">
			<ul><?php foreach ( rockpack_collection('growth_cards') as $item ) : ?><li><span class="about-growth__diamond"><?php if ( $item['image'] ) : ?><img src="<?php echo esc_url(rp_webp_image_url(  $item['image']  )); ?>" alt="" width="48" height="48" loading="lazy"><?php endif; ?></span><p><?php echo esc_html( $item['text'] ); ?></p></li><?php endforeach; ?></ul>
		</div><div class="about-growth__dots" aria-label="Regional growth carousel controls"></div></div>
	</section>

	<section class="about-closing about-section" aria-labelledby="about-closing-title">
		<h2 id="about-closing-title"><?php echo esc_html( $content['closing_heading'] ); ?></h2>
		<p><?php echo esc_html( $content['closing_text'] ); ?></p>
		<?php if ( ($content['closing_button_url']) && ($content['closing_button_text']) ) : ?><a class="about-button" href="<?php echo esc_url( $content['closing_button_url'] ); ?>"><?php echo esc_html( $content['closing_button_text'] ); ?><span aria-hidden="true">→</span></a><?php endif; ?>
	</section>
</main>
<?php get_template_part( 'template-parts/footer-section' ); ?>
<?php get_footer(); ?>
