<?php
/** About Us page content and its editable settings. @package Rockpack */
defined( 'ABSPATH' ) || exit;

function rockpack_about_page_fields() {
	$assets = get_template_directory_uri() . '/assets/images/about/';
	$definitions = array(
		'title' => array( 'Hero title', 'text', 'About Us' ),
		'hero_image' => array( 'Hero image URL', 'url', $assets . 'hero.jpg' ),
		'hero_subtitle' => array( 'Hero subtitle', 'text', 'High-performance aseptic packaging for food and beverage brands across MENA and Africa' ),
		'eyebrow' => array( 'Introduction - eyebrow', 'text', 'At The Rock Pack' ),
		'heading' => array( 'Introduction - heading', 'textarea', 'we believe packaging is far more than a functional necessity, it is a critical enabler of growth, quality, and trust' ),
		'bullet_1' => array( 'Introduction - first bullet', 'textarea', 'We are a high-performance aseptic packaging producer, built to support brands that are shaping the future of food and beverage across the MENA and African markets.' ),
		'bullet_2' => array( 'Introduction - second bullet', 'textarea', 'By combining advanced technology with deep technical expertise, we deliver packaging solutions that help our partners scale with confidence and consistency.' ),
		'image' => array( 'Introduction - packaging image URL', 'url', $assets . 'packaging.jpg' ),
		'tab_1_label' => array( 'First tab - label', 'text', 'Our Operations' ),
		'tab_1_text' => array( 'First tab - content', 'textarea', 'Our operations are designed around one core principle: reliability at scale.' ),
		'tab_2_label' => array( 'Second tab - label', 'text', 'Our Production' ),
		'tab_2_text' => array( 'Second tab - content', 'textarea', 'Our production facilities employ world-class European equipment to ensure every pack provides superior product protection and hygiene.' ),
		'button_text' => array( 'Introduction - button text', 'text', 'Learn More' ),
		'button_url' => array( 'Introduction - button URL', 'url', home_url( '/contact-us/#contact-form' ) ),
		'foundation_heading' => array( 'Technical foundation - heading', 'text', 'Technical Foundation & 30-Year Heritage' ),
		'foundation_text' => array( 'Technical foundation - description', 'textarea', 'Behind this scale is a strong technical foundation. With 30 years of experience in printing, packaging and material science, The Rock Pack brings together precision, process control and advanced aseptic technology to meet international standards for safety, hygiene and performance.' ),
		'foundation_image' => array( 'Technical foundation - background image URL', 'url', $assets . 'foundation.jpg' ),
		'partnership_heading' => array( 'Partnership - heading', 'textarea', 'But What Truly Defines The Rock Pack Is Our Approach To Partnership.' ),
		'partnership_text' => array( 'Partnership - description', 'textarea', 'Our role goes beyond production; we act as a performance ally, helping brands navigate complexity, accelerate launches and maintain consistent product quality across markets.' ),
		'partnership_step_1' => array( 'Partnership - first point', 'textarea', 'We work closely with our partners to understand their goals' ),
		'partnership_step_2' => array( 'Partnership - second point', 'textarea', 'We support their product strategies' ),
		'partnership_step_3' => array( 'Partnership - third point', 'textarea', 'We provide the operational clarity needed to progress from concept to shelf efficiently' ),
		'partnership_image' => array( 'Partnership - upper image URL', 'url', $assets . 'partnership.jpg' ),
		'collaboration_image' => array( 'Partnership - lower image URL', 'url', $assets . 'collaboration.jpg' ),
		'growth_heading' => array( 'Regional growth - heading', 'textarea', 'We Are Also Committed To Supporting Regional Growth' ),
		'growth_card_1' => array( 'Regional growth - first card', 'textarea', 'By providing localized production capabilities' ),
		'growth_card_2' => array( 'Regional growth - second card', 'textarea', 'By enabling companies to reduce supply chain complexity' ),
		'growth_card_3' => array( 'Regional growth - third card', 'textarea', 'By improving responsiveness' ),
		'growth_card_4' => array( 'Regional growth - fourth card', 'textarea', 'By expanding their presence with greater agility' ),
		'closing_heading' => array( 'Closing - heading', 'textarea', 'At The Rock Pack, we are building more than just packaging solutions.' ),
		'closing_text' => array( 'Closing - description', 'textarea', 'We are building the foundation that allows brands to grow, innovate and lead with confidence.' ),
		'closing_button_text' => array( 'Closing - button text', 'text', 'Connect with our team' ),
		'closing_button_url' => array( 'Closing - button URL', 'url', home_url( '/contact-us/#contact-form' ) ),
	);
	foreach ( array( 'Experience', 'Precision', 'Control', 'Safety', 'Hygiene', 'Performance' ) as $i => $label ) {
		$definitions[ 'foundation_label_' . ( $i + 1 ) ] = array( 'Technical foundation - ' . $label . ' label', 'text', $label );
	}
	$fields = array();
	foreach ( $definitions as $key => $definition ) {
		$fields[] = array( 'key' => 'about_page_' . $key, 'label' => $definition[0], 'type' => $definition[1], 'placeholder' => $definition[2], 'default' => $definition[2] );
	}
	return $fields;
}

function rockpack_about_page_content() {
	$content = array();
	foreach ( rockpack_about_page_fields() as $field ) {
		$content[ substr( $field['key'], strlen( 'about_page_' ) ) ] = rp_option( $field['key'], $field['default'] );
	}
	return $content;
}
