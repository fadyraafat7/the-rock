<?php
/** Newsletter requests use the site's existing Contact Form 7 delivery. */
defined( 'ABSPATH' ) || exit;

function rockpack_render_newsletter() {
    $id = absint( get_option( 'rockpack_newsletter_form_id', 0 ) );
    if ( class_exists( 'WPCF7_ContactForm' ) && $id && 'publish' === get_post_status( $id ) ) {
        echo do_shortcode( '[contact-form-7 id="' . $id . '" html_class="newsletter-form"]' );
    } else {
        echo '<a class="footer-contact-link" href="' . esc_url( home_url( '/contact-us/' ) ) . '">Request newsletter updates →</a>';
    }
}

add_filter( 'wpcf7_autop_or_not', static function ( $autop, $options ) {
    $form = class_exists( 'WPCF7_ContactForm' ) ? WPCF7_ContactForm::get_current() : null;
    return $form && $form->id() === absint( get_option( 'rockpack_newsletter_form_id', 0 ) ) ? false : $autop;
}, 10, 2 );
