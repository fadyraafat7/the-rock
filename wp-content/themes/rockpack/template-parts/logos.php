<?php
$items = rockpack_collection('logos');
?>
<section class="rp-logos" aria-label="Partners">
  <div class="logos-row">
    <?php foreach ( $items as $index => $item ) :
        $name = $item['title'];
        $image = $item['image'];
    ?>
    <?php if ( ! empty( $item['url'] ) ) : ?><a href="<?php echo esc_url( $item['url'] ); ?>"><?php endif; ?>
    <?php if ( $image ) : ?><img src="<?php echo esc_url( rp_webp_image_url( $image ) ); ?>" alt="<?php echo esc_attr( $name ); ?>"<?php echo rp_image_dimensions( $image ); ?> loading="lazy"><?php else : ?><span><?php echo esc_html( $name ); ?></span><?php endif; ?>
    <?php if ( ! empty( $item['url'] ) ) : ?></a><?php endif; ?><?php endforeach; ?>
  </div>
  <?php get_template_part( 'template-parts/angle-accent' ); ?>
</section>
