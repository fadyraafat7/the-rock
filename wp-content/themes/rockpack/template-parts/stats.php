<?php
$items = array_map(function($row){ $row['label']=$row['title']; return $row; },rockpack_collection('stats'));
?>
<section class="rp-stats" aria-label="The Rock Pack at a glance">
  <?php foreach ( $items as $item ) : ?>
  <div class="stat-item">
    <div class="stat-diamond">
      <?php $icon = in_array( $item['icon'], array( 'expertise', 'location', 'markets', 'standards' ), true ) ? $item['icon'] : 'expertise'; ?>
      <?php $icon_url = $item['image'] ?: add_query_arg( 'ver', filemtime( get_template_directory() . '/assets/images/home/stat-' . $icon . '.svg' ), get_template_directory_uri() . '/assets/images/home/stat-' . $icon . '.svg' ); ?>
      <img src="<?php echo esc_url( rp_webp_image_url( $icon_url ) ); ?>" alt="" width="50" height="58">
    </div>
    <div class="stat-lbl"><?php echo esc_html( $item['label'] ); ?></div>
    <div class="stat-sub"><?php echo esc_html( $item['sub'] ); ?></div>
  </div>
  <?php endforeach; ?>
</section>
