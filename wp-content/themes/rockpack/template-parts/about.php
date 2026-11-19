<?php
$heading = rp_option( 'about_heading', 'we believe packaging is far more than a functional necessity, it is a critical enabler of growth, quality, and trust' );
$bullet1 = rp_option( 'about_bullet1', 'We are a high-performance aseptic packaging producer, built to support brands that are shaping the future of food and beverage across the MENA and African markets.' );
$bullet2 = rp_option( 'about_bullet2', 'By combining advanced technology with deep technical expertise, we deliver packaging solutions that help our partners scale with confidence and consistency.' );
$tab1    = rp_option( 'about_tab1',    'Our operations are designed around one core principle: reliability at scale.' );
$tab2    = rp_option( 'about_tab2',    'Our production facilities employ world-class European equipment to ensure every pack provides superior product protection and hygiene.' );
$eyebrow = rp_option( 'about_eyebrow', 'At THE ROCK PACK' );
$tab1_label = rp_option( 'about_tab1_label', 'Our Operations' );
$tab2_label = rp_option( 'about_tab2_label', 'Our Production' );
$btn_text = rp_option( 'about_btn_text', 'Learn More' );
$btn_url = rp_option( 'about_btn_url', home_url( '/about-us/' ) );
$image   = rp_option( 'about_image', get_template_directory_uri() . '/assets/images/home/packaging.jpg' );
?>
<section class="rp-about" id="about" aria-labelledby="home-about-title">
  <div class="rp-about-grid">
    <div class="rp-about-img">
      <?php if ( $image ) : ?><img src="<?php echo esc_url(rp_webp_image_url(  $image  )); ?>" alt="<?php echo esc_attr(rp_option('home_image_description_71','Rock Pack Packaging Products')); ?>" width="653" height="728" loading="lazy" /><?php endif; ?>
      <div class="rp-about-badge">
        <img src="<?php echo esc_url(rp_webp_image_url(  get_template_directory_uri() . '/assets/images/home/packaging-symbol.svg'  )); ?>" alt="" width="45" height="65" loading="lazy">
      </div>
    </div>
    <div>
      <div class="eyebrow">
        <img src="<?php echo esc_url(rp_webp_image_url(  get_template_directory_uri() . '/assets/images/certifications/logo.png'  )); ?>" alt="" width="24" height="27" loading="lazy">
        <?php echo esc_html( $eyebrow ); ?>
      </div>
      <?php
      $heading_html = preg_replace_callback( '/\b(growth|quality|trust)\b/i', static function ( $m ) {
          $word = esc_html( $m[1] );
          $url  = rp_option( 'about_' . strtolower( $m[1] ) . '_url', '' );
          return $url ? '<a href="' . esc_url( $url ) . '"><strong>' . $word . '</strong></a>' : '<strong>' . $word . '</strong>';
      }, esc_html( $heading ) );
      ?>
      <h2 class="sec-title" id="home-about-title"><?php echo wp_kses( $heading_html, [ 'em' => [], 'strong' => [], 'a' => [ 'href' => [] ] ] ); ?></h2>
      <div class="about-paragraphs">
        <?php foreach ( rockpack_collection( 'home_bullets' ) as $bullet ) : ?>
          <p><?php echo esc_html( $bullet['text'] ); ?></p>
        <?php endforeach; ?>
      </div>
      <div data-about-tabs>
        <div class="rp-tabs" role="tablist" aria-label="Our capabilities">
        <?php $tabs=rockpack_collection('home_tabs'); foreach($tabs as $i=>$tab) : ?>
        <button class="rp-tab" type="button" role="tab" id="home-tab-<?php echo (int)$i; ?>" aria-controls="home-panel-<?php echo (int)$i; ?>" aria-selected="<?php echo $i?'false':'true'; ?>" tabindex="<?php echo $i?-1:0; ?>"><?php echo esc_html($tab['title']); ?></button>
        <?php endforeach; ?></div>
        <?php foreach($tabs as $i=>$tab) : $operations_copy = 0 === strcasecmp( trim( $tab['text'] ), 'Our operations are designed around one core principle: reliability at scale.' ); ?>
        <div class="rp-tab-body<?php echo $operations_copy ? ' rp-tab-body--operations' : ''; ?>" id="home-panel-<?php echo (int)$i; ?>" role="tabpanel" aria-labelledby="home-tab-<?php echo (int)$i; ?>" tabindex="0" <?php if($i) echo 'hidden'; ?>><?php if ( $operations_copy ) : ?><span>Our operations are designed around</span> <span>one core principle: reliability at scale.</span><?php else : ?><?php echo esc_html($tab['text']); ?><?php endif; ?></div>
        <?php endforeach; ?>
      </div>
      <?php if ( ($btn_url) && ($btn_text) ) : ?><a href="<?php echo esc_url( $btn_url ); ?>" class="btn-prim"><?php echo esc_html( $btn_text ); ?> &nbsp;→</a><?php endif; ?>
    </div>
  </div>
  <?php get_template_part( 'template-parts/angle-accent', null, array( 'variant' => 'about' ) ); ?>
</section>
