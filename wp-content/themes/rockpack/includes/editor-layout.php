<?php
/** Page-editor components in the order used by the public templates. */
defined( 'ABSPATH' ) || exit;

function rockpack_editor_layout( $kind ) {
    $group = static function ( $fields = array(), $repeaters = array() ) {
        return array( 'fields' => $fields, 'repeaters' => $repeaters );
    };
    $layouts = array(
        'home' => array(
            'Hero' => $group( array(), array( 'hero_images' ) ),
            'Statistics' => $group( array(), array( 'stats' ) ),
            'About' => $group( array( 'about_image', 'home_image_description_71', 'about_eyebrow', 'about_growth_url', 'about_quality_url', 'about_trust_url', 'about_btn_text', 'about_btn_url' ), array( 'home_bullets', 'home_tabs' ) ),
            'History' => $group( array( 'history_learn_text', 'history_learn_url' ), array( 'history' ) ),
            'Certifications' => $group( array( 'certs_btn_text', 'certs_btn_url' ), array( 'certs' ) ),
            'Products and Services' => $group( array( 'products_learn_text', 'products_learn_url' ), array( 'products' ) ),
            'Process Control and Inspection' => $group( array( 'process_intro' ), array( 'process_cards' ) ),
            'Localization' => $group( array(), array( 'local_cards' ) ),
            'Planet and Societal Impact' => $group( array( 'planet_subtitle', 'planet_btn_text', 'planet_btn_url' ), array( 'home_planet_cards' ) ),
            'News' => $group( array( 'news_subtitle', 'news_image', 'home_image_description_72', 'news_intro', 'news_author_prefix' ) ),
            'Partners' => $group( array(), array( 'logos' ) ),
            'Careers' => $group( array( 'careers_image', 'careers_sub', 'careers_card_heading', 'careers_card_text', 'careers_card_btn' ) ),
            'Closing call to action' => $group( array( 'cta_desc', 'cta_btn_text', 'cta_btn_url' ) ),
        ),
        'about-us' => array(
            'Hero' => $group( array( 'about_page_hero_image', 'about_page_hero_subtitle', 'breadcrumb_parent' ) ),
            'Introduction' => $group( array( 'about_page_image', 'about_us_image_description_62', 'about_page_eyebrow', 'about_page_button_text', 'about_page_button_url' ), array( 'about_bullets', 'about_tabs' ) ),
            'Technical foundation' => $group( array( 'about_page_foundation_image', 'about_page_foundation_text' ), array( 'foundation_features' ) ),
            'Partnership' => $group( array( 'about_page_partnership_text', 'about_page_partnership_image', 'about_us_image_description_63', 'about_page_collaboration_image', 'about_us_image_description_64' ), array( 'partnership_points' ) ),
            'Regional growth' => $group( array(), array( 'growth_cards' ) ),
            'Closing call to action' => $group( array( 'about_page_closing_text', 'about_page_closing_button_text', 'about_page_closing_button_url' ) ),
        ),
        'history' => array(
            'Hero' => $group( array( 'history_hero_image', 'history_hero_subtitle', 'history_hero_eyebrow' ) ),
            'History timeline' => $group( array( 'history_page_intro' ), array( 'history' ) ),
        ),
        'certifications' => array(
            'Hero' => $group( array( 'certs_page_hero_image', 'certs_page_hero_subtitle', 'breadcrumb_parent' ) ),
            'Introduction and certifications' => $group( array( 'certs_page_intro' ), array( 'certs' ) ),
            'Production' => $group( array( 'certs_page_production_image', 'certifications_image_description_65', 'certs_page_packaging_image', 'certifications_image_description_66', 'certs_page_production_intro', 'certs_page_production_result' ) ),
            'Quality Assurance' => $group( array( 'certs_page_quality_text' ), array( 'quality_steps' ) ),
            'Partners' => $group( array( 'certs_page_partners_intro', 'certs_page_partners_text', 'certs_page_partners_image', 'certifications_image_description_67' ) ),
            'Closing statement' => $group( array( 'certs_page_commitment_text' ) ),
        ),
        'product-portfolio' => array(
            'Hero' => $group( array( 'portfolio_page_hero_image' ) ),
            'Product portfolio' => $group( array( 'portfolio_page_gallery_intro' ), array( 'products' ) ),
            'Quality management' => $group( array( 'portfolio_page_quality_intro', 'portfolio_page_quality_text' ) ),
            'Process and materials' => $group( array( 'portfolio_page_materials_image', 'product_portfolio_image_description_70', 'portfolio_page_materials_intro' ), array( 'material_steps' ) ),
            'Process Control and Inspection' => $group( array( 'process_intro' ), array( 'process_cards' ) ),
            'Localization' => $group( array(), array( 'local_cards' ) ),
        ),
        'planet' => array(
            'Hero' => $group( array( 'planet_page_hero_image' ) ),
            'Sustainable sourcing' => $group( array( 'planet_page_sourcing_image', 'planet_image_description_68', 'planet_page_sourcing_text' ) ),
            'Vision for Africa' => $group( array( 'planet_page_vision_text', 'planet_page_vision_text_2', 'planet_page_vision_image', 'planet_image_description_69' ) ),
            'Environmental, Social and Governance' => $group( array( 'planet_page_esg_intro' ), array( 'esg_pillars' ) ),
            'Closing statement' => $group( array( 'planet_page_statement_text' ) ),
        ),
        'contact-us' => array(
            'Hero' => $group( array( 'contact_page_hero_image' ) ),
            'Contact details' => $group( array( 'contact_page_address_title', 'contact_page_address', 'contact_page_phone_title', 'contact_page_phone_hint', 'contact_page_phone', 'contact_page_email_title', 'contact_page_email', 'contact_page_email_2' ) ),
            'Contact form' => $group( array( 'contact_page_form_eyebrow', 'contact_page_form_text', 'contact_page_form_id' ) ),
            'Map' => $group( array( 'contact_page_map_url' ) ),
        ),
    );
    return $layouts[ $kind ] ?? array();
}

/** Regroup the existing allowed controls without changing their keys or storage. */
function rockpack_order_page_sections( $kind, $sections ) {
    $fields = array();
    $repeaters = array();
    foreach ( $sections as $section ) {
        $fields += $section['fields'] ?? array();
        foreach ( $section['repeaters'] ?? ( isset( $section['repeater'] ) ? array( $section['repeater'] ) : array() ) as $key ) $repeaters[ $key ] = true;
    }
    $ordered = array();
    foreach ( rockpack_editor_layout( $kind ) as $title => $layout ) {
        $section = array();
        foreach ( $layout['fields'] as $key ) {
            if ( ! isset( $fields[ $key ] ) ) continue;
            $section['fields'][ $key ] = $fields[ $key ];
            unset( $fields[ $key ] );
        }
        foreach ( $layout['repeaters'] as $key ) {
            if ( ! isset( $repeaters[ $key ] ) ) continue;
            $section['repeaters'][] = $key;
            unset( $repeaters[ $key ] );
        }
        if ( $section ) $ordered[ $title ] = $section;
    }
    // Keep future controls available until their placement is added to the layout.
    if ( $fields || $repeaters ) $ordered['Other settings'] = array( 'fields' => $fields, 'repeaters' => array_keys( $repeaters ) );
    return $ordered;
}

/** Keep each page's component sequence when WordPress has a saved editor order. */
function rockpack_editor_meta_box_order( $order, $post ) {
    $sections = rockpack_page_sections( $post );
    if ( ! $sections ) return $order;
    $order = is_array( $order ) ? $order : array();
    foreach ( $order as $context => $ids ) {
        $ids = array_filter( explode( ',', (string) $ids ), static function ( $id ) { return '' !== $id && ! str_starts_with( $id, 'rockpack-' ); } );
        $order[ $context ] = implode( ',', $ids );
    }
    $ids = array( 'rockpack-editor-help' );
    foreach ( array_keys( $sections ) as $title ) $ids[] = 'rockpack-' . sanitize_title( $title );
    if ( ! empty( $order['normal'] ) ) $ids[] = $order['normal'];
    $order['normal'] = implode( ',', $ids );
    return $order;
}

add_filter( 'get_user_option_meta-box-order_page', static function ( $order ) {
    return rockpack_editor_meta_box_order( $order, get_post() );
} );
