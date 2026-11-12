<?php
/** Legacy navigation definitions, retained for backward-compatible defaults. */
defined('ABSPATH') || exit;
function rockpack_navigation_defaults() {
return array(
	'company' => array( 'label' => rp_option('nav_label_company','Company'), 'items' => array(
		array( 'label' => rp_option('nav_label_about_us','About Us'), 'slug' => 'about-us', 'fallback' => '/#about' ),
		array( 'label' => rp_option('nav_label_history','History'), 'slug' => 'history', 'fallback' => '/#history' ),
		array( 'label' => rp_option('nav_label_certifications','Certifications'), 'slug' => 'certifications', 'fallback' => '/#certifications' ),
		array( 'label' => rp_option('nav_label_news','News'), 'slug' => 'news', 'fallback' => '/news/' ),
		array( 'label' => rp_option('nav_label_careers','Careers'), 'slug' => 'careers', 'fallback' => '/careers/' ),
	) ),
	'products' => array( 'label' => rp_option('nav_label_products','Products'), 'items' => array(
		array( 'label' => rp_option('nav_label_product_portfolio','Product Portfolio'), 'slug' => 'product-portfolio', 'fallback' => '/#products' ),
		array( 'label' => rp_option('nav_label_quality_management','Quality Management'), 'slug' => 'quality-management', 'fallback' => '/#process', 'anchor_page' => 'product-portfolio', 'anchor' => 'quality-management' ),
	) ),
	'planet' => array( 'label' => rp_option('nav_label_planet','Planet'), 'items' => array(
		array( 'label' => rp_option('nav_label_sustainable_sourcing','Sustainable Sourcing'), 'slug' => 'sustainable-sourcing', 'fallback' => '/#sustainable-sourcing', 'anchor_page' => 'planet', 'anchor' => 'sustainable-sourcing' ),
		array( 'label' => rp_option('nav_label_vision_for_africa','Vision for Africa'), 'slug' => 'vision-for-africa', 'fallback' => '/#vision-for-africa', 'anchor_page' => 'planet', 'anchor' => 'vision-for-africa' ),
		array( 'label' => rp_option('nav_label_environmental_and_social','Environmental And Social'), 'slug' => 'environmental-and-social', 'fallback' => '/#planet', 'anchor_page' => 'planet', 'anchor' => 'environmental-and-social' ),
	) ),
);
}
