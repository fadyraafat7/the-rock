<?php
/**
 * Template Name: Company History
 * Template Post Type: page
 *
 * @package Rockpack
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/includes/data.php';

$slides = rockpack_collection('history','history');

$hero_eyebrow = rp_option( 'history_hero_eyebrow', 'Company' );
$hero_title = rp_option( 'history_hero_title', 'History' );
$history_assets = get_template_directory_uri() . '/assets/images/history/';
$hero_image = rp_option( 'history_hero_image', $history_assets . 'hero.jpg' );
$heading = rp_option( 'history_page_heading', 'Our History' );
$intro = rp_option( 'history_page_intro', 'The foundation of The Rock Pack is built on decades of technical mastery in printing, materials and packaging innovation.' );
$timeline_images = array(
	$history_assets . 'building-expertise.jpg',
	$history_assets . 'mastering-production.jpg',
	$history_assets . 'aseptic-technology.jpg',
);

$timeline_icons = array( 'expertise-icon.png', 'production-icon.png', 'technology-icon.png' );
$timeline_kinds = array( 'expertise', 'production', 'technology' );

get_header();
?>

<main class="history-page company-page" id="main-content">
	<?php get_template_part( 'template-parts/inner-navigation' ); ?>

	<?php get_template_part( 'template-parts/inner-hero', null, array( 'title' => $hero_title, 'image' => $hero_image, 'subtitle' => rp_option( 'history_hero_subtitle', rockpack_editor_defaults()['history_hero_subtitle'] ), 'parent' => $hero_eyebrow ) ); ?>

	<section class="history-story" aria-labelledby="history-story-title">
		<header class="history-story__header">
			<div class="history-story__intro"><h2 id="history-story-title"><?php echo esc_html( $heading ); ?></h2><p><?php echo esc_html( $intro ); ?></p></div>
			<div class="history-controls" aria-label="History timeline controls">
				<button class="history-controls__prev" type="button" aria-label="Previous history milestone" aria-controls="history-timeline">‹</button>
				<button class="history-controls__next" type="button" aria-label="Next history milestone" aria-controls="history-timeline">›</button>
			</div>
		</header>
		<div class="history-timeline" id="history-timeline" role="region" aria-label="Company history milestones" tabindex="0">
			<div class="history-timeline__track">
			<?php foreach ( $slides as $index => $slide ) :
				$title = $slide['title'];
				$text = $slide['text'];
				$image = $slide['image'];
			?>
				<article class="history-timeline__item history-timeline__item--<?php echo esc_attr( $timeline_kinds[ $index % 3 ] ); ?><?php echo $index % 2 ? ' history-timeline__item--lower' : ''; ?>">
					<div class="history-timeline__content">
						<div class="history-timeline__icon" aria-hidden="true"><?php if ( $slide['icon_image'] ) : ?><img src="<?php echo esc_url(rp_webp_image_url(  $slide['icon_image']  )); ?>" alt="" width="168" height="171"><?php endif; ?></div>
						<h3><?php echo esc_html( $title ); ?></h3>
						<p><?php echo esc_html( $text ); ?></p>
					</div>
					<figure class="history-timeline__media"><?php if ( $image ) : ?><img src="<?php echo esc_url(rp_webp_image_url(  $image  )); ?>" alt=""<?php echo rp_image_dimensions( $image ); ?> loading="lazy"><?php endif; ?></figure>
					<span class="history-timeline__marker" aria-hidden="true"></span>
				</article>
			<?php endforeach; ?>
			</div>
		</div>
		<p class="screen-reader-text history-status" aria-live="polite" aria-atomic="true"></p>
	</section>
</main>

<?php get_footer(); ?>
