<?php
/**
 * Template Name: Company Certifications
 * Template Post Type: page
 * @package Rockpack
 */
defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/includes/data.php';
$assets = get_template_directory_uri() . '/assets/images/certifications/';
$certificates = rockpack_collection('certs','certifications');
get_header();
?>
<main class="certifications-page company-page" id="main-content">
	<?php get_template_part( 'template-parts/inner-navigation' ); ?>
	<?php get_template_part( 'template-parts/inner-hero', null, array( 'title' => rp_option( 'certs_page_title', 'Certifications' ), 'image' => rp_option( 'certs_page_hero_image', $assets . 'hero.jpg' ), 'subtitle' => rp_option( 'certs_page_hero_subtitle', rockpack_editor_defaults()['certs_page_hero_subtitle'] ) ) ); ?>
	<section class="certifications-intro rp-cert-section" aria-labelledby="certifications-heading">
		<h2 id="certifications-heading"><?php echo esc_html( rp_option( 'certs_page_heading', 'Certifications' ) ); ?></h2>
		<p><?php echo esc_html( rp_option( 'certs_page_intro', 'Our operations are aligned with globally recognized standards, including FSC, ISO and BRC certifications, ensuring that every package meets strict international requirements for quality, food safety and responsible sourcing.' ) ); ?></p>
		<ul class="certifications-standards" aria-label="Certification standards">
			<?php foreach ( $certificates as $certificate ) : ?><li><?php if ( $certificate['image'] ) : ?><img src="<?php echo esc_url(rp_webp_image_url(  $certificate['image']  )); ?>" alt="" width="41" height="56"><?php endif; ?><span><?php echo esc_html( $certificate['title'] ); ?></span></li><?php endforeach; ?>
		</ul>
	</section>
	<section class="certifications-production rp-cert-section" aria-label="Integrated production environment">
		<div class="certifications-production__collage"><?php if ( rp_option( 'certs_page_production_image', $assets . 'production-rolls.jpg' ) ) : ?><img class="certifications-production__image certifications-production__image--low" src="<?php echo esc_url(rp_webp_image_url(  rp_option( 'certs_page_production_image', $assets . 'production-rolls.jpg' )  )); ?>" alt="<?php echo esc_attr(rp_option('certifications_image_description_65','Packaging material rolls in the production facility')); ?>" loading="lazy" width="736" height="1104"><?php endif; ?><?php if ( rp_option( 'certs_page_packaging_image', $assets . 'packaging-rolls.jpg' ) ) : ?><img class="certifications-production__image certifications-production__image--high" src="<?php echo esc_url(rp_webp_image_url(  rp_option( 'certs_page_packaging_image', $assets . 'packaging-rolls.jpg' )  )); ?>" alt="<?php echo esc_attr(rp_option('certifications_image_description_66','Printed packaging rolls and aseptic packaging')); ?>" loading="lazy" width="736" height="1104"><?php endif; ?></div>
		<div class="certifications-production__copy"><p><?php echo esc_html( rp_option( 'certs_page_production_intro', 'This commitment is supported by a fully integrated production environment that combines advanced European aseptic technology with high-precision manufacturing processes' ) ); ?></p><p><?php echo esc_html( rp_option( 'certs_page_production_result', 'The result is packaging that delivers reliable performance with close-to-zero material waste, supporting both operational efficiency and sustainability goals.' ) ); ?></p></div>
		<img class="certifications-accent certifications-accent--right" src="<?php echo esc_url(rp_webp_image_url(  $assets . 'accent-right.png'  )); ?>" alt="" width="450" height="1170" loading="lazy">
	</section>
	<section class="certifications-quality rp-cert-section" aria-labelledby="quality-heading">
		<div class="certifications-quality__header"><h2 id="quality-heading"><?php echo esc_html( rp_option( 'certs_page_quality_heading', 'Quality Assurance' ) ); ?></h2><p><?php echo esc_html( rp_option( 'certs_page_quality_text', 'Quality Assurance at The Rock Pack is driven by continuous control and technical expertise. From material selection to final output, every stage is monitored to ensure consistency, hygiene and product integrity.' ) ); ?></p></div>
		<ol class="certifications-quality__steps"><?php $steps=rockpack_collection('quality_steps'); foreach($steps as $i=>$step) : ?><li><?php echo esc_html($step['text']); ?><?php if($i<count($steps)-1) : ?><span aria-hidden="true">→</span><?php endif; ?></li><?php endforeach; ?></ol>
	</section>
	<section class="certifications-partners rp-cert-section" aria-label="A people-driven approach">
		<img class="certifications-accent certifications-accent--left" src="<?php echo esc_url(rp_webp_image_url(  $assets . 'accent-left.png'  )); ?>" alt="" width="450" height="1170" loading="lazy">
		<div class="certifications-partners__inner"><div class="certifications-partners__copy"><p><?php echo esc_html( rp_option( 'certs_page_partners_intro', 'Beyond systems and certifications, our approach remains people-driven.' ) ); ?></p><p><?php echo esc_html( rp_option( 'certs_page_partners_text', 'We work closely with our partners to ensure that global standards are applied with a deep understanding of local market needs, providing a level of responsiveness, precision and trust that supports long-term growth.' ) ); ?></p></div><div class="certifications-partners__media"><?php if ( rp_option( 'certs_page_partners_image', $assets . 'partners.jpg' ) ) : ?><img class="certifications-partners__image" src="<?php echo esc_url(rp_webp_image_url(  rp_option( 'certs_page_partners_image', $assets . 'partners.jpg' )  )); ?>" alt="<?php echo esc_attr(rp_option('certifications_image_description_67','Understanding our partners and their quality requirements')); ?>" width="736" height="491" loading="lazy"><?php endif; ?><img class="certifications-partners__badge" src="<?php echo esc_url(rp_webp_image_url(  $assets . 'packaging-badge.png'  )); ?>" alt="" width="390" height="378" loading="lazy"></div></div>
	</section>
	<section class="certifications-commitment rp-cert-section" aria-labelledby="commitment-heading"><h2 id="commitment-heading"><?php echo esc_html( rp_option( 'certs_page_commitment_heading', 'At The Rock Pack,' ) ); ?></h2><p><?php echo esc_html( rp_option( 'certs_page_commitment_text', 'certifications are not just compliance, they are part of a broader commitment to delivering packaging that performs, protects and scales with our partners.' ) ); ?></p></section>
</main>
<?php get_footer(); ?>
