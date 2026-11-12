<?php
$slides = rockpack_collection('history','home');
$learn_text = rp_option( 'history_learn_text', 'Learn More' );
$learn_url = rp_option( 'history_learn_url', home_url( '/history/' ) );
?>
<section class="rp-history" id="history" aria-labelledby="home-history-title" data-history-carousel>
  <?php if ( ($learn_url) && ($learn_text) ) : ?><a href="<?php echo esc_url( $learn_url ); ?>" class="link-arr history-learn"><?php echo esc_html( $learn_text ); ?> →</a><?php endif; ?>
  <h2 id="home-history-title"><?php echo esc_html( rp_option( 'history_home_heading', 'History' ) ); ?></h2>
  <div class="history-carousel">
    <div class="history-slide-viewport"><div class="history-slide" id="home-history-slide" aria-live="polite" aria-atomic="true"><h3 class="history-slide-title" id="hs-title"><?php echo esc_html( $slides[0]['title'] ?? '' ); ?></h3>
    <p class="history-slide-text" id="hs-text"><?php echo esc_html( $slides[0]['text'] ?? '' ); ?></p></div></div>
    <div class="carousel-nav">
      <button class="carousel-btn prev" type="button" aria-label="Previous history slide" aria-controls="home-history-slide">&#8249;</button>
      <button class="carousel-btn next" type="button" aria-label="Next history slide" aria-controls="home-history-slide">&#8250;</button>
    </div>
  </div>
</section>
<script type="application/json" id="home-history-data"><?php echo wp_json_encode( $slides, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
