<?php
$address = rp_option( 'contact_address', '54 Elharby, Cairo, Arab Republic of Egypt, North Africa' );
$phone   = rp_option( 'contact_phone',   '+201154232620' );
$phone_link = preg_replace( '/[^0-9+]/', '', $phone );
$copyright = str_replace( '{year}', wp_date( 'Y' ), rp_option( 'footer_copyright', '© Copyright {year} – Pledge Communications. All Rights Reserved.' ) );
$copyright_parts = explode( 'Pledge Communications', $copyright );
$socials = array_filter( rockpack_collection( 'footer_social' ), static function ( $social ) {
  return ! empty( $social['title'] ) && ! empty( $social['url'] )
    && ( '#' === $social['url'] || (
      in_array( wp_parse_url( $social['url'], PHP_URL_SCHEME ), array( 'http', 'https' ), true )
      && wp_parse_url( $social['url'], PHP_URL_HOST )
    ) );
} );
?>
<footer class="rp-footer" id="contact">
  <div class="footer-grid">
    <div class="footer-nl">
      <h4><?php echo esc_html( rp_option( 'footer_newsletter_heading', 'Subscribe to our newsletter' ) ); ?></h4>
      <p>Get exclusive insights delivered directly to your inbox.</p>
      <?php rockpack_render_newsletter(); ?>
      <?php if ( $socials ) : ?>
      <div class="footer-social">
        <p><?php echo esc_html(rp_option('footer_label_follow_us_on','Follow us on')); ?></p>
        <div class="social-row">
          <?php foreach($socials as $social) : if(empty($social['url']) || empty($social['title'])) continue; ?><a href="<?php echo esc_url($social['url']); ?>"><?php if('facebook'===$social['icon']) : ?><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg><?php endif; ?><?php if('linkedin'===$social['icon']) : ?><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg><?php endif; ?><?php if('instagram'===$social['icon']) : ?><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg><?php endif; ?><?php if('youtube'===$social['icon']) : ?><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg><?php endif; ?> <?php echo esc_html($social['title']); ?></a><?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <div class="footer-col">
      <h5><?php echo esc_html(rp_option('footer_label_pages','Pages')); ?></h5>
      <ul data-rp-footer-list="footer_page_links"><?php foreach(rockpack_collection('footer_page_links') as $link) : if(empty($link['url']) || empty($link['title'])) continue; ?><li><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['title']); ?></a></li><?php endforeach; ?></ul>
    </div>
    <div class="footer-col">
      <h5>&nbsp;</h5>
      <ul data-rp-footer-list="footer_service_links"><?php foreach(rockpack_collection('footer_service_links') as $link) : if(empty($link['url']) || empty($link['title'])) continue; ?><li><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['title']); ?></a></li><?php endforeach; ?></ul>
    </div>
    <div class="footer-info">
      <h5><?php echo esc_html(rp_option('footer_label_address','Address')); ?></h5>
      <p class="footer-info__address"><?php if ( trim( $address ) !== '' ) : ?><a href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $address ); ?></a><?php endif; ?></p>
      <h5><?php echo esc_html(rp_option('footer_label_phone_number','Phone Number')); ?></h5>
      <p><?php if ( preg_match( '/\d/', $phone_link ) ) : ?><a href="<?php echo esc_url( 'tel:' . $phone_link ); ?>"><?php echo esc_html( $phone ); ?></a><?php else : ?><?php echo esc_html( $phone ); ?><?php endif; ?></p>
    </div>
  </div>
  <div class="footer-btm">
    <p><?php foreach ( $copyright_parts as $index => $part ) : ?><?php if ( $index ) : ?><a href="https://www.pledgecommunications.com/" target="_blank" rel="noopener noreferrer">Pledge Communications</a><?php endif; ?><?php echo esc_html( $part ); ?><?php endforeach; ?></p>
  </div>
</footer>
