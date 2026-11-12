<?php
/** Contact page presentation. Form fields and delivery are managed by Contact Form 7. */
defined( 'ABSPATH' ) || exit;

function rockpack_contact_page_fields() {
    $assets = get_template_directory_uri() . '/assets/images/contact/';
    $definitions = array(
        'title' => array( 'Hero title', 'text', 'Contact Us' ),
        'hero_image' => array( 'Hero image URL', 'url', $assets . 'hero.jpg' ),
        'heading' => array( 'Introduction heading', 'textarea', 'Connect with our team to secure your supply chain' ),
        'address_title' => array( 'Address card title', 'text', 'Address' ),
        'address' => array( 'Address', 'textarea', "54 Elharby, Lorem Street,\nNew Cairo, Egypt" ),
        'phone_title' => array( 'Phone card title', 'text', 'Phone Number' ),
        'phone_hint' => array( 'Phone card description', 'text', 'Emergency Cases' ),
        'phone' => array( 'Phone number', 'text', '+20 156 523 462' ),
        'email_title' => array( 'Email card title', 'text', 'Email' ),
        'email' => array( 'Public email address', 'email', 'magedmaher58@gmail.com' ),
        'email_2' => array( 'Second public email address', 'email', 'magedmaher58@gmail.com' ),
        'form_eyebrow' => array( 'Form introduction label', 'text', 'Contact Us' ),
        'form_heading' => array( 'Form introduction heading', 'text', 'Bringing Your Ideas to Market with Precision.' ),
        'form_text' => array( 'Form introduction description', 'textarea', 'Connect with The Rock Pack to explore how our aseptic packaging solutions can support your business goals.' ),
        'map_image' => array( 'Map image URL (PDF reference shows Los Angeles, not the Cairo address)', 'url', $assets . 'map.jpg' ),
        'map_alt' => array( 'Map image description', 'text', 'Reference map of Los Angeles from the supplied design' ),
    );
    $fields = array();
    foreach ( $definitions as $key => $definition ) {
        $fields[] = array( 'key' => 'contact_page_' . $key, 'label' => $definition[0], 'type' => $definition[1], 'placeholder' => $definition[2], 'default' => $definition[2] );
    }
    $fields[] = array( 'key'=>'contact_page_form_id', 'label'=>'Contact Form 7 form', 'type'=>'select', 'default'=>'0', 'placeholder'=>'', 'choices'=>rockpack_contact_form7_choices() );
    return $fields;
}

function rockpack_contact_page_content() {
    $content = array();
    foreach ( rockpack_contact_page_fields() as $field ) {
        $content[ substr( $field['key'], strlen( 'contact_page_' ) ) ] = rp_option( $field['key'], $field['default'] );
    }
    return $content;
}

require_once __DIR__ . '/contact-form7.php';
