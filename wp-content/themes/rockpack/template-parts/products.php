<?php
defined( 'ABSPATH' ) || exit;
?>
<section class="rp-products" id="products" aria-labelledby="home-products-title">
  <h2 id="home-products-title"><?php echo esc_html( rp_option( 'products_heading', 'Products & Services' ) ); ?></h2>
  <div class="prod-header"><h3><?php echo esc_html( rp_option( 'products_subtitle', 'Product Portfolio' ) ); ?></h3><?php if ( (rp_option( 'products_learn_url', home_url( '/product-portfolio/' ) )) && (rp_option( 'products_learn_text', 'Learn More' )) ) : ?><a class="link-arr" href="<?php echo esc_url( rp_option( 'products_learn_url', home_url( '/product-portfolio/' ) ) ); ?>"><?php echo esc_html( rp_option( 'products_learn_text', 'Learn More' ) ); ?> <span aria-hidden="true">→</span></a><?php endif; ?></div>
  <?php get_template_part( 'template-parts/product-gallery', null, array( 'id' => 'home-product-gallery', 'desktop_count' => 9 ) ); ?>
</section>
