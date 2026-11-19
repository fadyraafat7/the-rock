<?php
/**
 * Template Name: Contact Us
 * Template Post Type: page
 * @package Rockpack
 */
defined( 'ABSPATH' ) || exit;
// Contact Form 7 forms must remain dynamic, not cached as static snapshots.
if ( ! defined( 'DONOTCACHEPAGE' ) ) define( 'DONOTCACHEPAGE', true );
nocache_headers();
require_once get_template_directory() . '/includes/data.php';
$content = rockpack_contact_page_content();
$map_location = trim( rp_option( 'contact_page_map_location', '' ) );
if ( '' === $map_location ) {
    $map_location = trim( preg_replace( '/\s+/', ' ', $content['address'] ) );
}
$map_link = $content['map_url'];
$map_embed = rockpack_contact_map_embed( $map_link );
if ( '' === $map_embed && '' !== $map_location ) {
    $query = rawurlencode( $map_location );
    $map_link = 'https://www.google.com/maps/search/?api=1&query=' . $query;
    $map_embed = 'https://maps.google.com/maps?output=embed&q=' . $query;
}
get_header();
?>
<main class="contact-page company-page" id="main-content">
    <?php get_template_part( 'template-parts/inner-navigation' ); ?>
    <?php get_template_part( 'template-parts/inner-hero', null, array( 'title' => $content['title'], 'image' => $content['hero_image'], 'show_breadcrumb' => false ) ); ?>
    <section class="contact-details" aria-labelledby="contact-details-title">
        <h2 id="contact-details-title"><?php echo esc_html( $content['heading'] ); ?></h2>
        <div class="contact-details__cards">
            <article class="contact-details__card contact-details__card--address"><svg viewBox="0 0 48 60" fill="currentColor" aria-hidden="true"><path d="M24 2C14.06 2 6 10.06 6 20c0 13.5 18 30 18 30s18-16.5 18-30C42 10.06 33.94 2 24 2Zm0 26a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/><path fill-rule="evenodd" d="M24 42c9.94 0 18 3.58 18 8s-8.06 8-18 8S6 54.42 6 50s8.06-8 18-8Zm0 1.5c-7.18 0-13 2.01-13 4.5s5.82 4.5 13 4.5 13-2.01 13-4.5-5.82-4.5-13-4.5Z"/></svg><div><h3><?php echo esc_html( $content['address_title'] ); ?></h3><?php if ( '' !== $map_embed ) : ?>
                <p class="contact-details__address"><a href="<?php echo esc_url( $map_link ); ?>"><?php echo esc_html( trim( $content['address'] ) ?: __( 'Open in Google Maps', 'rockpack' ) ); ?></a></p>
            <?php endif; ?></div></article>
            <article class="contact-details__card"><svg viewBox="0 0 48 48" fill="currentColor" aria-hidden="true"><path d="M9 3 3 8c-7 10 21 39 32 37l9-8c1-2-7-9-10-9l-5 5c-6-2-12-8-14-14l5-5c1-2-7-12-11-11Z"/></svg><div><h3><?php echo esc_html( $content['phone_title'] ); ?></h3><p><?php echo esc_html( $content['phone_hint'] ); ?><br><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $content['phone'] ) ); ?>"><?php echo esc_html( $content['phone'] ); ?></a></p></div></article>
            <article class="contact-details__card"><svg viewBox="0 0 60 46" fill="currentColor" aria-hidden="true"><path d="M0 0h60v46H0z"/><path d="m2 2 28 27L58 2M2 44l18-18m38 18L40 26" fill="none" stroke="white" stroke-width="4"/></svg><div><h3><?php echo esc_html( $content['email_title'] ); ?></h3><p><?php if ( ('mailto:' . $content['email']) && ($content['email']) ) : ?><a href="<?php echo esc_url( 'mailto:' . $content['email'] ); ?>"><?php echo esc_html( $content['email'] ); ?></a><?php endif; ?><br><?php if ( ('mailto:' . $content['email_2']) && ($content['email_2']) ) : ?><a href="<?php echo esc_url( 'mailto:' . $content['email_2'] ); ?>"><?php echo esc_html( $content['email_2'] ); ?></a><?php endif; ?></p></div></article>
        </div>
        <?php get_template_part( 'template-parts/angle-accent', null, array( 'variant' => 'about', 'align_right' => true ) ); ?>
    </section>
    <section class="contact-enquiry" id="contact-form" aria-labelledby="contact-form-title">
        <div class="contact-enquiry__copy"><p class="contact-enquiry__eyebrow"><?php echo esc_html( $content['form_eyebrow'] ); ?></p><h2 id="contact-form-title"><?php echo esc_html( $content['form_heading'] ); ?></h2><p><?php echo esc_html( $content['form_text'] ); ?></p><svg viewBox="0 0 415 418" fill="currentColor" aria-hidden="true"><path d="M395.973 3.34028L2.92148 399.215C-3.47065 405.997 1.32984 417.029 10.5439 416.732L404.037 417.468C409.769 417.286 414.352 412.474 414.268 406.727L413.827 10.1156C413.691 0.806984 402.399 -3.47183 395.973 3.34028Z"/></svg></div>
        <?php rockpack_render_contact_form7(); ?>
    </section>
    <?php if ( '' !== $map_embed ) : ?>
        <div class="contact-map">
            <iframe title="<?php esc_attr_e( 'Office location on Google Maps', 'rockpack' ); ?>" src="<?php echo esc_url( $map_embed ); ?>" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
        </div>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
