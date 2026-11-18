<?php
/**
 * Shared home-section registry.
 *
 * One source of truth for the home page order and the editable fields that
 * belong to each section. Other page templates can call
 * rockpack_render_home_sections() with a subset of IDs when needed.
 *
 * @package Rockpack
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/about-page.php';
require_once __DIR__ . '/portfolio-page.php';
require_once __DIR__ . '/planet-page.php';
require_once __DIR__ . '/contact-page.php';

function rockpack_home_sections() {
	$sections = array(
		'hero'       => array( 'template' => 'hero', 'fields' => array(
			array( 'key' => 'hero_eyebrow', 'label' => 'Eyebrow text', 'type' => 'text', 'placeholder' => 'Packaging Built for Growth' ),
			array( 'key' => 'hero_title', 'label' => 'Main heading', 'type' => 'text', 'placeholder' => 'The Rock Pack' ),
			array( 'key' => 'hero_desc', 'label' => 'Description', 'type' => 'textarea', 'placeholder' => 'A high-performance aseptic packaging producer…' ),
			array( 'key' => 'hero_btn_text', 'label' => 'Button text', 'type' => 'text', 'placeholder' => 'Connect With Our Team' ),
			array( 'key' => 'hero_btn_url', 'label' => 'Button URL', 'type' => 'url', 'placeholder' => '/contact-us/#contact-form' ),
			array( 'key' => 'hero_bg_image', 'label' => 'Background image URL', 'type' => 'url', 'placeholder' => 'https://…' ),
		) ),
		'stats'      => array( 'template' => 'stats', 'description' => 'Manage the individual items in Dashboard → Stats.' ),
		'about'      => array( 'template' => 'about', 'fields' => array(
			array( 'key' => 'about_eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'placeholder' => 'At THE ROCK PACK' ),
			array( 'key' => 'about_heading', 'label' => 'Heading', 'type' => 'textarea', 'placeholder' => 'we believe packaging is far more than a functional necessity…' ),
			array( 'key' => 'about_bullet1', 'label' => 'Bullet 1', 'type' => 'textarea', 'placeholder' => 'We are a high-performance aseptic packaging producer…' ),
			array( 'key' => 'about_bullet2', 'label' => 'Bullet 2', 'type' => 'textarea', 'placeholder' => 'By combining advanced technology…' ),
			array( 'key' => 'about_tab1_label', 'label' => 'First tab label', 'type' => 'text', 'placeholder' => 'Our Operations' ),
			array( 'key' => 'about_tab1', 'label' => 'First tab content', 'type' => 'textarea', 'placeholder' => 'Our operations are designed around…' ),
			array( 'key' => 'about_tab2_label', 'label' => 'Second tab label', 'type' => 'text', 'placeholder' => 'Our Production' ),
			array( 'key' => 'about_tab2', 'label' => 'Second tab content', 'type' => 'textarea', 'placeholder' => 'Our production facilities employ…' ),
			array( 'key' => 'about_btn_text', 'label' => 'Button text', 'type' => 'text', 'placeholder' => 'Learn More' ),
			array( 'key' => 'about_btn_url', 'label' => 'Button URL', 'type' => 'url', 'placeholder' => '/about-us/' ),
			array( 'key' => 'about_image', 'label' => 'Photo URL', 'type' => 'url', 'placeholder' => 'https://…' ),
		) ),
		'about_page' => array( 'fields' => rockpack_about_page_fields(), 'description' => 'Full About Us page. These fields are separate from the Home About section.' ),
		'history'    => array( 'template' => 'history', 'fields' => array(
			array( 'key' => 'history_hero_eyebrow', 'label' => 'Page hero eyebrow', 'type' => 'text', 'placeholder' => 'Company' ),
			array( 'key' => 'history_hero_title', 'label' => 'Page hero title', 'type' => 'text', 'placeholder' => 'History' ),
			array( 'key' => 'history_hero_image', 'label' => 'Page hero image URL', 'type' => 'url', 'placeholder' => 'https://…' ),
			array( 'key' => 'history_hero_subtitle', 'label' => 'Page hero subtitle', 'type' => 'text', 'placeholder' => 'Short line shown under the hero title' ),
			array( 'key' => 'history_milestone_image_1', 'label' => 'Building Expertise image URL', 'type' => 'url', 'placeholder' => 'https://…' ),
			array( 'key' => 'history_milestone_image_2', 'label' => 'Mastering Production image URL', 'type' => 'url', 'placeholder' => 'https://…' ),
			array( 'key' => 'history_milestone_image_3', 'label' => 'Advancing Aseptic Technology image URL', 'type' => 'url', 'placeholder' => 'https://…' ),
			array( 'key' => 'history_page_heading', 'label' => 'Page content heading', 'type' => 'text', 'placeholder' => 'Our History' ),
			array( 'key' => 'history_page_intro', 'label' => 'Page content introduction', 'type' => 'textarea', 'placeholder' => 'The Rock Pack was built on decades of technical expertise…' ),
			array( 'key' => 'history_learn_text', 'label' => 'Learn-more link text', 'type' => 'text', 'placeholder' => 'Learn More' ),
			array( 'key' => 'history_learn_url', 'label' => 'Learn-more link URL', 'type' => 'url', 'placeholder' => '#about' ),
		), 'description' => 'Manage carousel slides in Dashboard → History Slides.' ),
		'certifications' => array( 'template' => 'certs', 'fields' => array(
			array( 'key' => 'certs_page_title', 'label' => 'Certifications page - hero title', 'type' => 'text', 'placeholder' => 'Certifications' ),
			array( 'key' => 'certs_page_hero_image', 'label' => 'Certifications page - hero image URL', 'type' => 'url', 'placeholder' => 'Original PDF image is used by default' ),
			array( 'key' => 'certs_page_hero_subtitle', 'label' => 'Certifications page - hero subtitle', 'type' => 'text', 'placeholder' => 'Short line shown under the hero title' ),
			array( 'key' => 'certs_page_heading', 'label' => 'Page introduction heading', 'type' => 'text', 'placeholder' => 'Certifications' ),
			array( 'key' => 'certs_page_intro', 'label' => 'Page introduction text', 'type' => 'textarea', 'placeholder' => 'Our operations are aligned with globally recognized standards…' ),
			array( 'key' => 'certs_page_production_image', 'label' => 'Production - lower image URL', 'type' => 'url', 'placeholder' => 'Original PDF image is used by default' ),
			array( 'key' => 'certs_page_packaging_image', 'label' => 'Production - upper image URL', 'type' => 'url', 'placeholder' => 'Original PDF image is used by default' ),
			array( 'key' => 'certs_page_production_intro', 'label' => 'Production - first paragraph', 'type' => 'textarea', 'placeholder' => 'This commitment is supported by a fully integrated production environment…' ),
			array( 'key' => 'certs_page_production_result', 'label' => 'Production - second paragraph', 'type' => 'textarea', 'placeholder' => 'The result is packaging that delivers reliable performance…' ),
			array( 'key' => 'certs_page_quality_heading', 'label' => 'Quality heading', 'type' => 'text', 'placeholder' => 'Quality Assurance' ),
			array( 'key' => 'certs_page_quality_text', 'label' => 'Quality paragraph', 'type' => 'textarea', 'placeholder' => 'Quality Assurance at The Rock Pack is driven by continuous control…' ),
			array( 'key' => 'certs_page_step_1', 'label' => 'Quality - first step', 'type' => 'text', 'placeholder' => 'Material selection' ),
			array( 'key' => 'certs_page_step_2', 'label' => 'Quality - second step', 'type' => 'text', 'placeholder' => 'Continuous control' ),
			array( 'key' => 'certs_page_step_3', 'label' => 'Quality - third step', 'type' => 'text', 'placeholder' => 'Final output' ),
			array( 'key' => 'certs_page_partners_intro', 'label' => 'Partners - first paragraph', 'type' => 'textarea', 'placeholder' => 'Beyond systems and certifications, our approach remains people-driven.' ),
			array( 'key' => 'certs_page_partners_text', 'label' => 'Partners - second paragraph', 'type' => 'textarea', 'placeholder' => 'We work closely with our partners…' ),
			array( 'key' => 'certs_page_partners_image', 'label' => 'Partners image URL', 'type' => 'url', 'placeholder' => 'Original PDF image is used by default' ),
			array( 'key' => 'certs_page_commitment_heading', 'label' => 'Closing heading', 'type' => 'text', 'placeholder' => 'At The Rock Pack' ),
			array( 'key' => 'certs_page_commitment_text', 'label' => 'Closing paragraph', 'type' => 'textarea', 'placeholder' => 'certifications are not just compliance…' ),
			array( 'key' => 'certs_watermark', 'label' => 'Watermark', 'type' => 'text', 'placeholder' => 'Certifications' ),
			array( 'key' => 'certs_btn_text', 'label' => 'Button text', 'type' => 'text', 'placeholder' => 'Learn More' ),
			array( 'key' => 'certs_btn_url', 'label' => 'Button URL', 'type' => 'url', 'placeholder' => '#contact' ),
		), 'description' => 'Manage cards in Dashboard → Certifications.' ),
		'products'   => array( 'template' => 'products', 'fields' => array(
			array( 'key' => 'products_heading', 'label' => 'Heading', 'type' => 'text', 'placeholder' => 'Products' ),
			array( 'key' => 'products_subtitle', 'label' => 'Subtitle', 'type' => 'text', 'placeholder' => 'Product Portfolio' ),
		), 'description' => 'Manage products and their images in Dashboard → Products.' ),
		'portfolio_page' => array( 'fields' => rockpack_portfolio_page_fields(), 'description' => 'Full Product Portfolio page. Gallery images are shared with Products; operating cards are shared with Process and Localization.' ),
		'process'    => array( 'template' => 'process', 'fields' => array(
			array( 'key' => 'process_heading', 'label' => 'Heading', 'type' => 'text', 'placeholder' => 'Process Control & Inspection' ),
			array( 'key' => 'process_intro', 'label' => 'Introduction', 'type' => 'textarea', 'placeholder' => 'To Ensure Consistent Quality Across Every Production Run…' ),
			array( 'key' => 'process_card1_label', 'label' => 'First card label', 'type' => 'text', 'placeholder' => 'Advanced Technical Infrastructure' ),
			array( 'key' => 'process_card1_text', 'label' => 'First card text', 'type' => 'textarea', 'placeholder' => 'We utilize world-class European equipment…' ),
			array( 'key' => 'process_card1_image', 'label' => 'First card image URL', 'type' => 'url', 'placeholder' => 'https://…' ),
			array( 'key' => 'process_card2_label', 'label' => 'Second card label', 'type' => 'text', 'placeholder' => 'Operational Mastery' ),
			array( 'key' => 'process_year_number', 'label' => 'Years number', 'type' => 'number', 'placeholder' => '30' ),
			array( 'key' => 'process_year_label', 'label' => 'Years label', 'type' => 'text', 'placeholder' => 'Year' ),
			array( 'key' => 'process_card2_text', 'label' => 'Second card text', 'type' => 'textarea', 'placeholder' => 'of industry expertise…' ),
		) ),
		'localization' => array( 'template' => 'local', 'fields' => array(
			array( 'key' => 'local_card1_label', 'label' => 'First card label', 'type' => 'text', 'placeholder' => 'Strategic Localization' ),
			array( 'key' => 'local_card1_text', 'label' => 'First card text', 'type' => 'textarea', 'placeholder' => 'Local manufacturing enables…' ),
			array( 'key' => 'local_card2_label', 'label' => 'Second card label', 'type' => 'text', 'placeholder' => 'Consistency & Reliability' ),
			array( 'key' => 'local_card2_text', 'label' => 'Second card text', 'type' => 'textarea', 'placeholder' => 'Our streamlined coordination…' ),
			array( 'key' => 'local_image', 'label' => 'Image URL', 'type' => 'url', 'placeholder' => 'https://…' ),
		) ),
		'planet_page' => array( 'fields' => rockpack_planet_page_fields(), 'description' => 'Full Planet & Societal Impact page: sourcing, vision, ESG and closing statement. Separate from the Home Planet section.' ),
		'planet'     => array( 'template' => 'planet', 'fields' => array(
			array( 'key' => 'planet_title', 'label' => 'Heading', 'type' => 'text', 'placeholder' => 'Planet & Societal Impact' ),
			array( 'key' => 'planet_subtitle', 'label' => 'Subtitle', 'type' => 'text', 'placeholder' => 'Sustainability Is Not A Standalone Initiative…' ),
			array( 'key' => 'planet_item1_title', 'label' => 'First item title', 'type' => 'text', 'placeholder' => 'Sustainable Sourcing' ),
			array( 'key' => 'planet_item1_text', 'label' => 'First item text', 'type' => 'textarea', 'placeholder' => 'We prioritize responsible material sourcing…' ),
			array( 'key' => 'planet_item2_title', 'label' => 'Second item title', 'type' => 'text', 'placeholder' => 'Vision for Africa' ),
			array( 'key' => 'planet_item2_text', 'label' => 'Second item text', 'type' => 'textarea', 'placeholder' => 'Our vision goes beyond the factory floor…' ),
			array( 'key' => 'planet_btn_text', 'label' => 'Button text', 'type' => 'text', 'placeholder' => 'Sustainable Sourcing' ),
			array( 'key' => 'planet_btn_url', 'label' => 'Button URL', 'type' => 'url', 'placeholder' => '/planet/#sustainable-sourcing' ),
		) ),
		'news'       => array( 'template' => 'news', 'fields' => array(
			array( 'key' => 'news_heading', 'label' => 'Heading', 'type' => 'text', 'placeholder' => 'News' ),
			array( 'key' => 'news_subtitle', 'label' => 'Subtitle', 'type' => 'text', 'placeholder' => 'Stay Connected With The Latest Updates From The Rock Pack.' ),
			array( 'key' => 'news_intro', 'label' => 'Introduction', 'type' => 'textarea', 'placeholder' => 'From production milestones and technological advancements…' ),
			array( 'key' => 'news_image', 'label' => 'Main image URL', 'type' => 'url', 'placeholder' => 'https://…' ),
		) ),
		'partners'   => array( 'template' => 'logos', 'description' => 'Manage partner logos in Dashboard → Partner Logos.' ),
		'careers'    => array( 'template' => 'careers', 'fields' => array(
			array( 'key' => 'careers_heading', 'label' => 'Section heading', 'type' => 'text', 'placeholder' => 'Careers' ),
			array( 'key' => 'careers_sub', 'label' => 'Section subtitle', 'type' => 'text', 'placeholder' => 'At The Rock Pack, People Are At The Core…' ),
			array( 'key' => 'careers_card_heading', 'label' => 'Card heading', 'type' => 'textarea', 'placeholder' => 'We foster a culture of collaboration…' ),
			array( 'key' => 'careers_card_text', 'label' => 'Card body', 'type' => 'textarea', 'placeholder' => 'Join a team that is shaping the future…' ),
			array( 'key' => 'careers_card_btn', 'label' => 'Button text', 'type' => 'text', 'placeholder' => 'Apply Now' ),
		) ),
		'cta'        => array( 'template' => 'cta', 'fields' => array(
			array( 'key' => 'cta_heading', 'label' => 'Heading', 'type' => 'text', 'placeholder' => 'Bringing Your Ideas to Market with Precision.' ),
			array( 'key' => 'cta_desc', 'label' => 'Description', 'type' => 'textarea', 'placeholder' => 'Connect with The Rock Pack to explore…' ),
			array( 'key' => 'cta_btn_text', 'label' => 'Button text', 'type' => 'text', 'placeholder' => 'Connect with our team' ),
			array( 'key' => 'cta_btn_url', 'label' => 'Button URL', 'type' => 'url', 'placeholder' => '/contact-us/#contact-form' ),
		) ),
		'contact_page' => array( 'fields' => rockpack_contact_page_fields(), 'description' => 'Contact Us page, public contact details and Contact Form 7 selection. Manage form fields, messages and mail under Contact > Contact Forms. Set the Google Maps location under Map.' ),
		'seo'        => array( 'fields' => array(
			array( 'key' => 'seo_default_description', 'label' => 'Default site meta description', 'type' => 'textarea', 'placeholder' => 'Used when a page has no description or section default.' ),
			array( 'key' => 'seo_home_description', 'label' => 'Home page meta description', 'type' => 'textarea', 'placeholder' => '150–160 chars shown in Google results for the home page.' ),
			array( 'key' => 'seo_home_title_suffix', 'label' => 'Home page title suffix', 'type' => 'text', 'placeholder' => 'Aseptic Packaging Manufacturer – Egypt, MENA & Africa' ),
			array( 'key' => 'seo_og_image', 'label' => 'Default OG / social share image URL', 'type' => 'url', 'placeholder' => 'Used on pages that have no specific OG image set (1200×630 px).' ),
		), 'description' => 'SEO defaults used when a page has no specific meta description or share image. Set per-page overrides in the page editor (SEO meta box).' ),
		'contact'    => array( 'template' => 'footer-section', 'fields' => array(
			array( 'key' => 'footer_contact_heading', 'label' => 'Contact heading', 'type' => 'text', 'placeholder' => 'Talk to our team' ),
			array( 'key' => 'footer_contact_text', 'label' => 'Contact text', 'type' => 'textarea', 'placeholder' => 'Contact The Rock Pack to discuss your packaging needs.' ),
			array( 'key' => 'footer_contact_link_text', 'label' => 'Contact link label', 'type' => 'text', 'placeholder' => 'Contact our team' ),
			array( 'key' => 'contact_address', 'label' => 'Address', 'type' => 'textarea', 'placeholder' => '54 Elharby, Cairo, Arab Republic of Egypt, North Africa' ),
			array( 'key' => 'contact_phone', 'label' => 'Phone', 'type' => 'text', 'placeholder' => '+201154232620' ),
		) ),
	);
	$extra_fields = array(
		'hero' => array(
			array( 'key' => 'hero_bg_image_2', 'label' => 'Gallery - second image URL', 'type' => 'url', 'placeholder' => 'Original PDF quality-inspection photo' ),
			array( 'key' => 'hero_bg_image_3', 'label' => 'Gallery - third image URL', 'type' => 'url', 'placeholder' => 'Original PDF production-facility photo' ),
		),
		'history' => array( array( 'key' => 'history_home_heading', 'label' => 'Home section heading', 'type' => 'text', 'placeholder' => 'History' ) ),
		'products' => array(
			array( 'key' => 'products_learn_text', 'label' => 'Learn-more link text', 'type' => 'text', 'placeholder' => 'Learn More' ),
			array( 'key' => 'products_learn_url', 'label' => 'Learn-more link URL', 'type' => 'url', 'placeholder' => '/product-portfolio/' ),
		),
		'careers' => array( array( 'key' => 'careers_image', 'label' => 'Careers background image URL', 'type' => 'url', 'placeholder' => 'Original PDF photo is used by default' ) ),
	);
	for ( $i = 1; $i <= 5; $i++ ) {
		$extra_fields['products'][] = array( 'key' => 'products_image_' . $i, 'label' => 'Default portfolio image ' . $i . ' URL', 'type' => 'url', 'placeholder' => 'Used when no Products posts are configured' );
	}
	for ( $i = 1; $i <= 6; $i++ ) {
		$extra_fields['partners'][] = array( 'key' => 'partner_image_' . $i, 'label' => 'Default partner logo ' . $i . ' URL', 'type' => 'url', 'placeholder' => 'Used when no Partner Logos posts are configured' );
	}
	foreach ( $extra_fields as $section => $fields ) {
		$sections[ $section ]['fields'] = array_merge( $sections[ $section ]['fields'] ?? array(), $fields );
	}
	return $sections;
}

function rockpack_render_home_sections( $section_ids = array() ) {
	$sections = rockpack_home_sections();
	$section_ids = $section_ids ? $section_ids : array_keys( $sections );

	foreach ( $section_ids as $section_id ) {
		if ( isset( $sections[ $section_id ]['template'] ) ) {
			get_template_part( 'template-parts/' . $sections[ $section_id ]['template'] );
		}
	}
}
