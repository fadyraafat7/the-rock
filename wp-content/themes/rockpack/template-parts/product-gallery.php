<?php
/** Reusable, keyboard- and touch-accessible product gallery. @package Rockpack */
defined( 'ABSPATH' ) || exit;
$items = $args['items'] ?? rockpack_product_gallery_items();
$items = array_values(array_filter($items,function($item){return !empty($item['image']);}));
$original_count = count( $items );
if ( $original_count && ! empty( $args['desktop_count'] ) ) {
    for ( $i = $original_count; $i < (int) $args['desktop_count']; $i++ ) {
        $items[] = $items[ $i % $original_count ];
    }
}
$gallery_id = sanitize_html_class( $args['id'] ?? 'product-gallery' );
?>
<div class="product-gallery" data-product-carousel>
  <div class="prod-viewport" id="<?php echo esc_attr( $gallery_id ); ?>" tabindex="0" role="region" aria-label="Product portfolio - scroll to explore">
    <div class="prod-grid"><?php foreach ( $items as $index => $item ) : ?><figure class="prod-card prod-card--source-<?php echo (int) ( $index % $original_count ); ?><?php echo $index >= $original_count ? ' product-gallery__desktop-repeat' : ''; ?>"><?php if(!empty($item['url'])) : ?><a href="<?php echo esc_url($item['url']); ?>"><?php endif; ?><?php if ( $item['image'] ) : ?><img src="<?php echo esc_url(rp_webp_image_url(  $item['image']  )); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" width="440" height="548" loading="lazy"><?php endif; ?><?php if(!empty($item['url'])) : ?></a><?php endif; ?></figure><?php endforeach; ?></div>
  </div>
  <div class="dots" role="group" aria-label="Product gallery controls"><?php foreach ( $items as $index => $item ) : if ( ! empty( $args['desktop_count'] ) && $index >= max( $original_count, count( $items ) - 2 ) ) continue; ?><button type="button" class="dot<?php echo $index >= $original_count ? ' product-gallery__desktop-repeat' : ''; ?>" aria-controls="<?php echo esc_attr( $gallery_id ); ?>" aria-label="Show image <?php echo (int) $index + 1; ?>: <?php echo esc_attr( $item['title'] ); ?>" aria-pressed="<?php echo $index ? 'false' : 'true'; ?>" data-product-index="<?php echo (int) $index; ?>"></button><?php endforeach; ?></div>
</div>
