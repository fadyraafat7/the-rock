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
            <article class="contact-details__card contact-details__card--address"><svg viewBox="0 0 48 60" fill="currentColor" aria-hidden="true"><path d="M24 1C11 1 5 9 5 19c0 13 19 33 19 33s19-20 19-33C43 9 37 1 24 1Zm0 28a10 10 0 1 1 0-20 10 10 0 0 1 0 20Z"/><path d="m9 44 10 12h10l10-12c13 13-1 16-15 16S-4 57 9 44Z"/></svg><div><h3><?php echo esc_html( $content['address_title'] ); ?></h3><?php if ( '' !== $map_embed ) : ?>
                <p class="contact-details__address"><a href="<?php echo esc_url( $map_link ); ?>"><?php echo esc_html( trim( $content['address'] ) ?: __( 'Open in Google Maps', 'rockpack' ) ); ?></a></p>
            <?php endif; ?></div></article>
            <article class="contact-details__card"><svg viewBox="0 0 48 48" fill="currentColor" aria-hidden="true"><path d="M9 3 3 8c-7 10 21 39 32 37l9-8c1-2-7-9-10-9l-5 5c-6-2-12-8-14-14l5-5c1-2-7-12-11-11Z"/></svg><div><h3><?php echo esc_html( $content['phone_title'] ); ?></h3><p><?php echo esc_html( $content['phone_hint'] ); ?><br><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $content['phone'] ) ); ?>"><?php echo esc_html( $content['phone'] ); ?></a></p></div></article>
            <article class="contact-details__card"><svg viewBox="0 0 60 46" fill="currentColor" aria-hidden="true"><path d="M0 0h60v46H0z"/><path d="m2 2 28 27L58 2M2 44l18-18m38 18L40 26" fill="none" stroke="white" stroke-width="4"/></svg><div><h3><?php echo esc_html( $content['email_title'] ); ?></h3><p><?php if ( ('mailto:' . $content['email']) && ($content['email']) ) : ?><a href="<?php echo esc_url( 'mailto:' . $content['email'] ); ?>"><?php echo esc_html( $content['email'] ); ?></a><?php endif; ?><br><?php if ( ('mailto:' . $content['email_2']) && ($content['email_2']) ) : ?><a href="<?php echo esc_url( 'mailto:' . $content['email_2'] ); ?>"><?php echo esc_html( $content['email_2'] ); ?></a><?php endif; ?></p></div></article>
        </div>
        <?php get_template_part( 'template-parts/angle-accent', null, array( 'align_right' => true ) ); ?>
    </section>
    <section class="contact-enquiry" id="contact-form" aria-labelledby="contact-form-title">
        <div class="contact-enquiry__copy"><p class="contact-enquiry__eyebrow"><?php echo esc_html( $content['form_eyebrow'] ); ?></p><h2 id="contact-form-title"><?php echo esc_html( $content['form_heading'] ); ?></h2><p><?php echo esc_html( $content['form_text'] ); ?></p><svg viewBox="0 0 420 420" fill="currentColor" aria-hidden="true"><path d="M410 0v390q0 20-20 20H20q-25 0-8-18L392 12Q410-7 410 0Z"/></svg></div>
        <?php rockpack_render_contact_form7(); ?>
    </section>
    <?php if ( '' !== $map_embed ) : ?>
        <div class="contact-map">
            <iframe title="<?php esc_attr_e( 'Office location on Google Maps', 'rockpack' ); ?>" src="<?php echo esc_url( $map_embed ); ?>" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
        </div>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
