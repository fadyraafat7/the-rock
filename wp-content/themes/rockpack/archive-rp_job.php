<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main class="company-page content-page" id="main-content">
    <?php get_template_part( 'template-parts/inner-navigation' ); ?>
    <?php get_template_part( 'template-parts/inner-hero', null, rockpack_listing_hero( 'careers' ) ); ?>
    <section class="rp-content-list careers-enquiry" aria-label="<?php esc_attr_e( 'Career enquiries', 'rockpack' ); ?>">
        <?php rockpack_render_contact_form7(); ?>
    </section>
</main>
<?php get_footer(); ?>
