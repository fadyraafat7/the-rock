<?php
/** Shared image hero. @package Rockpack */
defined( 'ABSPATH' ) || exit;
$title = $args['title'] ?? get_the_title();
$image = $args['image'] ?? '';
// Optional short line under the title; a blank value omits it.
$subtitle = trim( (string) ( $args['subtitle'] ?? '' ) );
// Earlier local hero exports include the decorative lines in their pixels.
// Use the corresponding unmarked theme image while keeping saved choices intact.
$legacy_heroes = array(
	'WhatsApp-Image-2026-10-21-at-6.14.27-PM.jpeg' => 'about/hero.jpg',
	'WhatsApp-Image-2026-10-21-at-6.21.30-PM.jpeg' => 'history/hero.jpg',
	'WhatsApp-Image-2026-10-21-at-6.22.43-PM.jpeg' => 'certifications/hero.jpg',
	'WhatsApp-Image-2026-10-21-at-6.30.05-PM.jpeg' => 'products/hero.jpg',
	'WhatsApp-Image-2026-10-21-at-6.42.23-PM.jpeg' => 'contact/hero.jpg',
);
$uploads = wp_get_upload_dir();
foreach ( $legacy_heroes as $filename => $replacement ) {
	if ( untrailingslashit( $uploads['baseurl'] ) . '/2026/10/' . $filename === $image ) {
		$image = get_template_directory_uri() . '/assets/images/' . $replacement;
		break;
	}
}
$parent = $args['parent'] ?? rp_option('breadcrumb_parent', 'Company');
$parent_url = $args['parent_url'] ?? ( 0 === strcasecmp( trim( $parent ), 'Home' ) ? home_url( '/' ) : home_url( '/about-us/' ) );
// A breadcrumb must lead to a parent, not back to the current page.
if ( ! isset( $args['parent_url'] ) && is_page( 'about-us' ) && untrailingslashit( $parent_url ) === untrailingslashit( get_permalink() ) ) {
	$parent_url = home_url( '/' );
}
?>
<section class="rp-inner-hero" aria-labelledby="rp-page-title">
	<?php if ( $image ) : ?><img class="rp-inner-hero__image" src="<?php echo esc_url(rp_webp_image_url(  $image  )); ?>" alt=""<?php echo rp_image_dimensions( $image ); ?> fetchpriority="high" decoding="async"><?php endif; ?>
	<div class="rp-inner-hero__copy"><h1 id="rp-page-title"><?php echo esc_html( $title ); ?></h1><?php if ( '' !== $subtitle ) : ?><p class="rp-inner-hero__subtitle"><?php echo esc_html( $subtitle ); ?></p><?php endif; ?><?php if ( $args['show_breadcrumb'] ?? true ) : ?><nav class="rp-inner-breadcrumb" aria-label="Breadcrumb"><?php if ( $parent_url && $parent ) : ?><a href="<?php echo esc_url( $parent_url ); ?>"><?php echo esc_html( $parent ); ?></a><?php endif; ?><span aria-hidden="true">›</span><span aria-current="page"><?php echo esc_html( $title ); ?></span></nav><?php endif; ?></div>
	<svg class="rp-inner-hero__lines" viewBox="0 0 480 420" preserveAspectRatio="none" fill="none" aria-hidden="true"><path d="M380 0 64 250Q44 267 58 284L159 420" stroke="#3eb978" stroke-width="3"/><path d="M480 0 57 305Q35 322 49 340L100 420" stroke="white" stroke-width="1.5"/></svg>

	<?php if ( $args['show_breadcrumb'] ?? true ) rp_seo_breadcrumb_schema( $parent, $parent_url, $title ); ?>
</section>
