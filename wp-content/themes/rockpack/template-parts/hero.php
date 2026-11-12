<?php
$slides = array_values(array_filter(rockpack_collection('hero_images'), static function ($slide) {
  return !empty($slide['image']);
}));
$has_primary_heading = false;
?>
<section class="rp-hero" id="hero" aria-label="Home hero" data-hero-gallery>
  <?php foreach ( $slides as $index => $slide ) : ?>
    <article class="rp-hero-slide"<?php echo $index ? ' hidden' : ''; ?>>
      <img class="rp-hero-bg" src="<?php echo esc_url(rp_webp_image_url(  rp_optimized_hero_url( $slide['image'] )  )); ?>" alt=""<?php echo rp_image_dimensions( rp_optimized_hero_url( $slide['image'] ) ); ?> <?php echo $index ? 'loading="lazy"' : 'fetchpriority="high"'; ?> decoding="async">
      <div class="rp-hero-overlay" aria-hidden="true"></div>
      <div class="rp-hero-content">
        <?php if ( ! empty( $slide['eyebrow'] ) ) : ?><p class="rp-eyebrow-hero"><?php echo esc_html( $slide['eyebrow'] ); ?></p><?php endif; ?>
        <?php if ( ! empty( $slide['heading'] ) ) : ?>
          <?php if ( ! $has_primary_heading ) : $has_primary_heading = true; ?><h1 class="rp-hero-title"><?php echo esc_html( $slide['heading'] ); ?></h1>
          <?php else : ?><h2 class="rp-hero-title"><?php echo esc_html( $slide['heading'] ); ?></h2><?php endif; ?>
        <?php endif; ?>
        <?php if ( ! empty( $slide['description'] ) ) : ?><p class="rp-hero-desc"><?php echo esc_html( $slide['description'] ); ?></p><?php endif; ?>
        <?php if ( ! empty( $slide['button_url'] ) && ! empty( $slide['button_text'] ) ) : ?><a href="<?php echo esc_url( $slide['button_url'] ); ?>" class="btn-prim"><?php echo esc_html( $slide['button_text'] ); ?><span aria-hidden="true">→</span></a><?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
  <svg class="rp-hero-lines" viewBox="0 0 600 840" preserveAspectRatio="none" fill="none" aria-hidden="true"><path d="M570 -30 90 390Q62 410 80 440L300 870" stroke="#3eb978" stroke-width="3"/><path d="M660 -15 77 480Q54 505 72 535L215 870" stroke="white" stroke-width="1.5"/></svg>
  <div class="rp-hero-dots" role="group" aria-label="Production gallery">
    <?php foreach ( $slides as $index => $slide ) : ?><button type="button" aria-label="Show hero slide <?php echo (int) $index + 1; ?>" aria-pressed="<?php echo $index ? 'false' : 'true'; ?>"><svg class="rp-hero-progress" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11"></circle></svg></button><?php endforeach; ?>
  </div>
</section>
