<?php
/** Fixed page and section headings. Edit these literals to change heading copy. */
defined( 'ABSPATH' ) || exit;

function rockpack_section_titles() {
    static $titles = array(
        'hero_title' => 'The Rock Pack',
        'about_heading' => 'we believe packaging is far more than a functional necessity, it is a critical enabler of growth, quality, and trust',
        'history_hero_title' => 'History',
        'history_page_heading' => 'Our History',
        'history_home_heading' => 'History',
        'certs_page_title' => 'Certifications',
        'certs_page_heading' => 'Certifications',
        'certs_page_quality_heading' => 'Quality Assurance',
        'certs_page_commitment_heading' => 'At The Rock Pack,',
        'certs_watermark' => 'Certifications',
        'products_heading' => 'Products',
        'products_subtitle' => 'Product Portfolio',
        'process_heading' => 'Process Control & Inspection',
        'planet_title' => 'Planet & Societal Impact',
        'news_heading' => 'News',
        'careers_heading' => 'Careers',
        'cta_heading' => 'Bringing Your Ideas to Market with Precision.',
        'about_page_title' => 'About Us',
        'about_page_heading' => 'we believe packaging is far more than a functional necessity, it is a critical enabler of growth, quality, and trust',
        'about_page_foundation_heading' => 'Technical Foundation & 30-Year Heritage',
        'about_page_partnership_heading' => 'But What Truly Defines The Rock Pack Is Our Approach To Partnership.',
        'about_page_growth_heading' => 'We Are Also Committed To Supporting Regional Growth',
        'about_page_closing_heading' => 'At The Rock Pack, we are building more than just packaging solutions.',
        'portfolio_page_title' => 'Products',
        'portfolio_page_heading' => 'Products',
        'portfolio_page_gallery_heading' => 'Product Portfolio',
        'portfolio_page_quality_heading' => 'Quality Management',
        'portfolio_page_materials_heading' => 'Process and Materials',
        'planet_page_title' => 'Planet & Societal Impact',
        'planet_page_sourcing_heading' => 'Sustainable Sourcing',
        'planet_page_vision_heading' => 'Vision for Africa',
        'planet_page_esg_heading' => 'Environmental, Social and Governance (ESG)',
        'planet_page_statement_heading' => 'At The Rock Pack,',
        'contact_page_title' => 'Contact Us',
        'contact_page_heading' => 'Connect with our team to secure your supply chain',
        'contact_page_form_heading' => 'Bringing Your Ideas to Market with Precision.',
        'footer_newsletter_heading' => 'Subscribe to our newsletter',
    );
    return $titles;
}

function rockpack_is_fixed_title( $key ) {
    return array_key_exists( $key, rockpack_section_titles() );
}
