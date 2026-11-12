<?php
$title    = rp_option( 'planet_title',       'Planet &amp; Societal Impact' );
$subtitle = rp_option( 'planet_subtitle',    'Sustainability is not a standalone initiative, it is integrated into how we operate and grow.' );
$i1_title = rp_option( 'planet_item1_title', 'Sustainable Sourcing' );
$i1_text  = rp_option( 'planet_item1_text',  'We prioritize responsible material sourcing, including FSC-certified paperboard and environmentally conscious practices that reduce environmental impact, while maintaining product integrity.' );
$i2_title = rp_option( 'planet_item2_title', 'Vision for Africa' );
$i2_text  = rp_option( 'planet_item2_text',  'Our vision goes beyond the factory floor; The Rock Pack is dedicated to leading Africa into a new era of safe, clean, and sustainable aseptic packaging.' );
$btn_text = rp_option( 'planet_btn_text', 'Sustainable Sourcing' );
$btn_url = rp_option( 'planet_btn_url', home_url( '/planet/#sustainable-sourcing' ) );
?>
<section class="rp-planet" id="planet" aria-labelledby="home-planet-title">
  <div class="planet-deco" aria-hidden="true"></div>
  <h2 class="planet-title" id="home-planet-title"><?php echo esc_html( html_entity_decode( $title ) ); ?></h2>
  <p class="planet-subtitle"><?php echo esc_html( $subtitle ); ?></p>
  <div class="planet-items"><?php foreach(rockpack_collection('home_planet_cards') as $i=>$item) : ?>
    <div class="planet-item" id="<?php echo esc_attr(array('sustainable-sourcing','vision-for-africa')[$i]??('planet-feature-'.$i)); ?>">
      <h4><?php if(!empty($item['url'])) : ?><a href="<?php echo esc_url($item['url']); ?>"><?php endif; ?><?php echo esc_html($item['title']); ?><?php if(!empty($item['url'])) : ?></a><?php endif; ?></h4>
      <?php if(!empty($item['image'])) : ?><img src="<?php echo esc_url(rp_webp_image_url( $item['image'] )); ?>" alt="<?php echo esc_attr($item['alt']); ?>"<?php echo rp_image_dimensions( $item['image'] ); ?> loading="lazy"><?php endif; ?>
      <p><?php echo esc_html($item['text']); ?></p>
    </div><?php endforeach; ?>
  </div>
  <?php if ( ($btn_url) && ($btn_text) ) : ?><a href="<?php echo esc_url( $btn_url ); ?>" class="btn-outline-g"><?php echo esc_html( $btn_text ); ?> &nbsp;→</a><?php endif; ?>
</section>
