<?php
$card1_label = rp_option( 'local_card1_label', 'Strategic Localization' );
$card1_text = rp_option( 'local_card1_text', 'Local manufacturing enables "Campaign Agility," minimizing supply chain timelines and providing a reliable packaging infrastructure in the region.' );
$card2_label = rp_option( 'local_card2_label', 'Consistency & Reliability' );
$card2_text = rp_option( 'local_card2_text', 'Our streamlined coordination helps shorten lead times, allowing brands to move from product concept to retail shelves with speed and reliability.' );
$image = rp_option( 'local_image', get_template_directory_uri() . '/assets/images/home/pack.png' );
?>
<section class="rp-local" aria-label="Localization and reliability">
  <div class="local-grid"><?php foreach(rockpack_collection('local_cards') as $card) : ?>
    <?php $dark='dark'===($card['style']??''); ?><div class="<?php echo $dark?'local-dark':'local-light'; ?>">
      <?php if($dark) : ?><div class="deco"></div><?php endif; ?><div class="lbl"><?php echo esc_html($card['title']); ?></div>
      <?php if($dark && !empty($card['image'])) : ?><?php if ( $card['image'] ) : ?><img class="local-growth-icon" src="<?php echo esc_url(rp_webp_image_url( $card['image'] )); ?>" alt="" width="76" height="70" loading="lazy"><?php endif; ?><?php endif; ?>
      <p><?php echo esc_html($card['text']); ?></p>
      <?php if(!$dark && !empty($card['image'])) : ?><?php if ( $card['image'] ) : ?><img class="local-pack" src="<?php echo esc_url(rp_webp_image_url( $card['image'] )); ?>" alt="<?php echo esc_attr($card['alt']); ?>" width="416" height="416" loading="lazy"><?php endif; ?><?php endif; ?>
    </div>
  <?php endforeach; ?></div>
</section>
