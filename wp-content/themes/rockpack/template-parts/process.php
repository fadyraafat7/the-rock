<?php
$heading = rp_option( 'process_heading', 'Process Control & Inspection' );
$intro = rp_option( 'process_intro', 'TO ENSURE CONSISTENT QUALITY ACROSS EVERY PRODUCTION RUN, THE ROCK PACK IMPLEMENTS CONTROLLED PROCESSES, SUPPORTED BY ADVANCED SYSTEMS AND INTEGRATED OPERATIONS' );
$card1_label = rp_option( 'process_card1_label', 'Advanced Technical Infrastructure' );
$card1_text = rp_option( 'process_card1_text', 'We utilize world-class European equipment to ensure every pack provides superior product protection and hygiene.' );
$card1_image = rp_option( 'process_card1_image', get_template_directory_uri() . '/assets/images/home/technical-team.png' );
$card2_label = rp_option( 'process_card2_label', 'Operational Mastery' );
$year_number = rp_option( 'process_year_number', '30' );
$year_label = rp_option( 'process_year_label', 'Year' );
$card2_text = rp_option( 'process_card2_text', 'of industry expertise, our production processes are engineered to maximize uptime and remove technical friction.' );
?>
<section class="rp-process" id="process" aria-labelledby="home-process-title">
  <div class="process-hdr">
    <h2 id="home-process-title"><?php echo esc_html( $heading ); ?></h2>
    <p><?php echo esc_html( $intro ); ?></p>
  </div>
  <div class="process-grid"><?php foreach(rockpack_collection('process_cards') as $card) : ?>
    <?php if('green'===($card['style']??'')) : ?>
    <div class="proc-green"><div class="sub"><?php echo esc_html($card['title']); ?></div><div class="big-num"><?php echo esc_html($card['number']??''); ?><span><?php echo esc_html($card['unit']??''); ?></span></div><p><?php echo esc_html($card['text']); ?></p><?php if(!empty($card['image'])) : ?><img src="<?php echo esc_url(rp_webp_image_url( $card['image'] )); ?>" alt=""<?php echo rp_image_dimensions( $card['image'] ); ?> loading="lazy"><?php endif; ?></div>
    <?php else : ?>
    <div class="proc-white"><div class="proc-white__copy"><div class="proc-label"><?php echo esc_html($card['title']); ?></div><p class="proc-text"><?php echo esc_html($card['text']); ?></p><?php if(!empty($card['number'])) : ?><div class="big-num"><?php echo esc_html($card['number']); ?><span><?php echo esc_html($card['unit']??''); ?></span></div><?php endif; ?></div><?php if(!empty($card['image'])) : ?><img src="<?php echo esc_url(rp_webp_image_url( $card['image'] )); ?>" alt="<?php echo esc_attr($card['alt']); ?>" width="701" height="379" loading="lazy"><?php endif; ?></div>
    <?php endif; ?>
  <?php endforeach; ?></div>
</section>
