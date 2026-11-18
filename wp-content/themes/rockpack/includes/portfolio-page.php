<?php
/** Editable Product Portfolio page content. @package Rockpack */
defined( 'ABSPATH' ) || exit;

function rockpack_portfolio_page_fields() {
    $assets = get_template_directory_uri() . '/assets/images/products/';
    $definitions = array(
        'title' => array( 'Hero title', 'text', 'Products' ),
        'hero_image' => array( 'Hero image URL', 'url', $assets . 'hero.jpg' ),
        'heading' => array( 'Section heading', 'text', 'Products' ),
        'gallery_heading' => array( 'Gallery heading', 'text', 'Product Portfolio' ),
        'gallery_intro' => array( 'Gallery introduction', 'textarea', 'Our role goes beyond production; we act as a performance ally, helping brands navigate complexity, accelerate launches and maintain consistent product quality across markets.' ),
        'quality_heading' => array( 'Quality heading', 'text', 'Quality Management' ),
        'quality_intro' => array( 'Quality - first paragraph', 'textarea', 'Quality is embedded in every stage of our process.' ),
        'quality_text' => array( 'Quality - second paragraph', 'textarea', 'From raw materials to final production, we apply strict quality control systems and advanced technologies to ensure consistency, safety and reliable performance at scale.' ),
        'materials_heading' => array( 'Process and Materials - heading', 'text', 'Process and Materials' ),
        'materials_intro' => array( 'Process and Materials - introduction', 'textarea', 'The Rock Pack utilizes world class technology to deliver aseptic packaging solutions that ensure global export eligibility and the highest food safety standards.' ),
        'materials_image' => array( 'Process and Materials - image URL', 'url', $assets . 'materials.jpg' ),
        'step_1_title' => array( 'Step 1 - title', 'text', 'Printing' ),
        'step_1_text' => array( 'Step 1 - description', 'textarea', "We partner with globally recognized suppliers for raw materials, including FSC-approved paperboard.\nThis technical foundation enables high-definition printing results that accurately reflect brand designs, while supporting a transition toward eco-laminate materials." ),
        'step_2_title' => array( 'Step 2 - title', 'text', 'Aseptic Packaging' ),
        'step_2_text' => array( 'Step 2 - description', 'textarea', 'Our aseptic packs are primarily paper-based, contributing to a lower environmental footprint and reduced plastic dependency.' ),
        'step_3_title' => array( 'Step 3 - title', 'text', 'Manufacturing' ),
        'step_3_text' => array( 'Step 3 - description', 'textarea', 'Our high-precision manufacturing is designed for maximum efficiency, achieving near-zero waste during changeovers and runs.' ),
        'step_4_title' => array( 'Step 4 - title', 'text', 'Consistency & Sustainability' ),
        'step_4_text' => array( 'Step 4 - description', 'textarea', 'This focus on "Green means Lean" operations ensures that we maintain the highest possible consistency and sustainability across the entire production process.' ),
    );
    $fields = array();
    foreach ( $definitions as $key => $definition ) {
        $fields[] = array( 'key' => 'portfolio_page_' . $key, 'label' => $definition[0], 'type' => $definition[1], 'placeholder' => $definition[2], 'default' => $definition[2] );
    }
    return $fields;
}

function rockpack_portfolio_page_content() {
    $content = array();
    foreach ( rockpack_portfolio_page_fields() as $field ) {
        $content[ substr( $field['key'], strlen( 'portfolio_page_' ) ) ] = rp_option( $field['key'], $field['default'] );
    }
    return $content;
}
