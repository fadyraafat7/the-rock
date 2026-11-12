<?php
/** Editable Planet page content. @package Rockpack */
defined( 'ABSPATH' ) || exit;

function rockpack_planet_page_fields() {
    $assets = get_template_directory_uri() . '/assets/images/planet/';
    $definitions = array(
        'title' => array( 'Hero title', 'text', 'Planet & Societal Impact' ),
        'hero_image' => array( 'Hero image URL', 'url', $assets . 'hero.jpg' ),
        'sourcing_heading' => array( 'Sourcing heading', 'text', 'Sustainable Sourcing' ),
        'sourcing_text' => array( 'Sourcing description', 'textarea', 'We prioritize responsible material sourcing, including FSC-certified paperboard and environmentally conscious practices that reduce environmental impact, while maintaining product integrity.' ),
        'sourcing_image' => array( 'Sourcing image URL', 'url', $assets . 'sourcing.jpg' ),
        'vision_heading' => array( 'Vision heading', 'text', 'Vision for Africa' ),
        'vision_text' => array( 'Vision - first paragraph', 'textarea', 'Our vision goes beyond the factory floor; The Rock Pack is dedicated to leading Africa into a new era of safe, clean, and sustainable aseptic packaging. By empowering producers with world-class technology, we aim to inspire consumers to choose healthier options, while elevating the appeal, safety and convenience of everyday products.' ),
        'vision_text_2' => array( 'Vision - second paragraph', 'textarea', 'Our commitment is to provide the foundation for a competitive African market, ensuring that manufacturers have the tools to deliver quality and excellence for a life lived with confidence and conscience.' ),
        'vision_image' => array( 'Vision image URL', 'url', $assets . 'vision.jpg' ),
        'esg_heading' => array( 'ESG heading', 'text', 'Environmental, Social and Governance (ESG)' ),
        'esg_intro' => array( 'ESG introduction', 'textarea', 'Our ESG approach focuses on creating long-term value through responsible operations' ),
        'environmental_title' => array( 'Environmental title', 'text', 'Environmental' ),
        'environmental_1' => array( 'Environmental - first point', 'textarea', 'Manufacturing process with close-to-zero waste of material, ensuring highest possible efficiency and maintaining sustainability' ),
        'environmental_2' => array( 'Environmental - second point', 'textarea', 'Committed to sustainable sourcing of materials, including FSC-approved paperboard' ),
        'environmental_3' => array( 'Environmental - third point', 'textarea', 'Flexibility and precision' ),
        'social_title' => array( 'Social title', 'text', 'Social' ),
        'social_1' => array( 'Social - first point', 'textarea', 'Investing in people, skills and safe working environments' ),
        'social_2' => array( 'Social - second point', 'textarea', 'Supporting regional development and job creation' ),
        'governance_title' => array( 'Governance title', 'text', 'Governance' ),
        'governance_1' => array( 'Governance - first point', 'textarea', 'Maintaining transparency and compliance' ),
        'governance_2' => array( 'Governance - second point', 'textarea', 'Adhering to international standards' ),
        'governance_3' => array( 'Governance - third point', 'textarea', 'Building trust through accountability' ),
        'statement_heading' => array( 'Closing statement heading', 'text', 'At The Rock Pack,' ),
        'statement_text' => array( 'Closing statement', 'textarea', 'certifications are not just compliance, they are part of a broader commitment to delivering packaging that performs, protects and scales with our partners.' ),
    );
    $fields = array();
    foreach ( $definitions as $key => $definition ) {
        $fields[] = array( 'key' => 'planet_page_' . $key, 'label' => $definition[0], 'type' => $definition[1], 'placeholder' => $definition[2], 'default' => $definition[2] );
    }
    return $fields;
}

function rockpack_planet_page_content() {
    $content = array();
    foreach ( rockpack_planet_page_fields() as $field ) {
        $content[ substr( $field['key'], strlen( 'planet_page_' ) ) ] = rp_option( $field['key'], $field['default'] );
    }
    return $content;
}
