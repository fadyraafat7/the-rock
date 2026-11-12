<?php
$posts = rp_get_certs();
$watermark = rp_option( 'certs_watermark', 'Certifications' );
$btn_text = rp_option( 'certs_btn_text', 'Learn More' );
$btn_url = rp_option( 'certs_btn_url', home_url( '/certifications/' ) );
$certs = array_map(function($row){ $row['name']=$row['title']; return $row; }, rockpack_collection('certs'));
?>
<section class="rp-certs" id="certifications" aria-labelledby="home-certs-title">
  <h2 id="home-certs-title"><?php echo esc_html( $watermark ); ?></h2>
  <div class="certs-grid">
    <?php foreach ( $certs as $cert ) : ?>
    <div class="cert-card">
      <?php if ( $cert['image'] ) : ?><img class="cert-ico" src="<?php echo esc_url(rp_webp_image_url(  $cert['image']  )); ?>" alt="" width="41" height="58" loading="lazy"><?php endif; ?>
      <span class="cert-name"><?php echo esc_html( $cert['name'] ); ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="certs-cta"><?php if ( ($btn_url) && ($btn_text) ) : ?><a href="<?php echo esc_url( $btn_url ); ?>" class="btn-outline-w"><?php echo esc_html( $btn_text ); ?> &nbsp;→</a><?php endif; ?></div>
</section>
