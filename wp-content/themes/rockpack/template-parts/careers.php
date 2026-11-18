<?php
$heading      = rp_option( 'careers_heading',      'Careers' );
$sub          = rp_option( 'careers_sub',          'At The Rock Pack, People Are At The Core Of Everything We Do.' );
$card_heading = rp_option( 'careers_card_heading', 'We foster a culture of collaboration, precision and continuous improvement.' );
$card_text    = rp_option( 'careers_card_text',    'Join a team that is shaping the future of packaging in the region.' );
$btn_text     = rp_option( 'careers_card_btn',     'Apply Now' );
$btn_url      = rockpack_careers_url();
?>
<section class="rp-careers" id="careers" aria-labelledby="home-careers-title">
  <?php if ( rp_option( 'careers_image', get_template_directory_uri() . '/assets/images/home/careers.jpg' ) ) : ?><img class="rp-careers-bg" src="<?php echo esc_url(rp_webp_image_url(  rp_option( 'careers_image', get_template_directory_uri() . '/assets/images/home/careers.jpg' )  )); ?>" alt="" width="4096" height="2731" loading="lazy"><?php endif; ?>
  <div class="careers-hdr">
    <h2 id="home-careers-title"><?php echo esc_html( $heading ); ?></h2>
    <p><?php echo esc_html( rtrim( $sub, '. ' ) ); ?></p>
  </div>
  <div class="careers-card">
    <h3><?php echo esc_html( $card_heading ); ?></h3>
    <p><?php echo esc_html( $card_text ); ?></p>
    <?php if ( ($btn_url) && ($btn_text) ) : ?><a href="<?php echo esc_url( $btn_url ); ?>" class="btn-prim"><?php echo esc_html( $btn_text ); ?> &nbsp;→</a><?php endif; ?>
  </div>
</section>
