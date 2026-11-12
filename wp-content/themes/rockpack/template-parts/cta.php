<?php
$heading  = rp_option( 'cta_heading',  'Bringing Your Ideas to Market with Precision.' );
$desc     = rp_option( 'cta_desc',     'Connect with The Rock Pack to explore how our aseptic packaging solutions can support your business goals.' );
$btn_text = rp_option( 'cta_btn_text', 'Connect with our team' );
$btn_url  = rp_option( 'cta_btn_url',  home_url( '/contact-us/#contact-form' ) );
?>
<section class="rp-cta" aria-labelledby="home-cta-title">
  <h2 id="home-cta-title"><?php echo esc_html( $heading ); ?></h2>
  <p><?php echo esc_html( $desc ); ?></p>
  <?php if ( ($btn_url) && ($btn_text) ) : ?><a href="<?php echo esc_url( $btn_url ); ?>" class="btn-prim" style="position:relative;z-index:2"><?php echo esc_html( $btn_text ); ?> &nbsp;→</a><?php endif; ?>
</section>
