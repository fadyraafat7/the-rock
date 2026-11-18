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
        'map_url' => array( 'Google Maps link (paste a place or share link)', 'url', '' ),
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

/** Use the pin in a place link before its @ coordinates, which describe the camera. */
function rockpack_contact_map_embed( $url ) {
    $parts = wp_parse_url( $url );
    if ( ( $parts['scheme'] ?? '' ) !== 'https' || isset( $parts['user'] ) || isset( $parts['port'] ) ) return '';
    $host = strtolower( $parts['host'] ?? '' );
    if ( ! preg_match( '/^(?:(?:www|maps)\.)?google\.(?:com|com\.eg|co\.uk|de|fr|ae|ca|co\.in)$/D', $host ) ) return '';
    $path = rawurldecode( $parts['path'] ?? '/' );
    if ( ! preg_match( '~^/maps(?:/|$)~', $path ) && ! ( str_starts_with( $host, 'maps.' ) && '/' === $path ) ) return '';
    wp_parse_str( $parts['query'] ?? '', $params );
    if ( rtrim( $path, '/' ) === '/maps/embed' && ! empty( $params['pb'] ) ) return $url;
    $base = 'https://maps.google.com/maps?output=embed';
    if ( ! empty( $params['cid'] ) && is_string( $params['cid'] ) && ctype_digit( $params['cid'] ) ) return $base . '&cid=' . $params['cid'];
    $point = '(-?\d+(?:\.\d+)?)';
    if ( preg_match( '~!3d' . $point . '!4d' . $point . '~', $path, $match ) ) {
        $query = $match[1] . ',' . $match[2];
    } elseif ( ! empty( $params['query_place_id'] ) && is_string( $params['query_place_id'] ) ) {
        $query = 'place_id:' . $params['query_place_id'];
    } elseif ( ! empty( $params['q'] ) || ! empty( $params['query'] ) ) {
        $query = $params['q'] ?? $params['query'];
    } elseif ( preg_match( '~^/maps/(?:place|search)/([^/@]+)~', $path, $match ) ) {
        $query = str_replace( '+', ' ', $match[1] );
    } elseif ( preg_match( '~@' . $point . ',' . $point . '~', $path, $match ) ) {
        $query = $match[1] . ',' . $match[2];
    } else {
        return '';
    }
    return is_string( $query ) && trim( $query ) !== '' ? $base . '&q=' . rawurlencode( $query ) : '';
}

/** Expand Google share links on save so displaying Contact Us needs no server-side request. */
function rockpack_sanitize_contact_map_url( $value ) {
    $url = trim( $value );
    if ( '' === $url ) return '';
    for ( $i = 0; $i < 3 && preg_match( '~^https://(?:maps\.app\.goo\.gl/|goo\.gl/maps/)[a-zA-Z0-9_-]+/?(?:\?[^#]*)?$~D', $url ); $i++ ) {
        $response = wp_safe_remote_get( $url, array( 'redirection' => 0, 'timeout' => 5, 'limit_response_size' => 1024 ) );
        if ( is_wp_error( $response ) ) break;
        $next = wp_remote_retrieve_header( $response, 'location' );
        if ( ! is_string( $next ) || '' === $next ) break;
        $url = $next;
    }
    if ( ! rockpack_contact_map_embed( $url ) ) return new WP_Error( 'map_url', 'Paste a Google Maps place link. If a short link cannot be opened, open it in your browser and copy the full URL.' );
    return esc_url_raw( $url );
}
