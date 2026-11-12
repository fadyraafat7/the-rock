<?php
/** Isolated, read-only previews of the actual public templates. */
defined( 'ABSPATH' ) || exit;

function rockpack_preview_sections() {
    return array(
        'home' => array('Hero'=>'rp-hero','Statistics'=>'rp-stats','About'=>'rp-about','History'=>'rp-history','Certifications'=>'rp-certs','Products and Services'=>'rp-products','Process Control and Inspection'=>'rp-process','Localization'=>'rp-local','Planet and Societal Impact'=>'rp-planet','News'=>'rp-news','Partners'=>'rp-logos','Careers'=>'rp-careers','Closing call to action'=>'rp-cta'),
        'about-us' => array('Hero'=>'rp-inner-hero','Introduction'=>'about-intro','Technical foundation'=>'about-foundation','Partnership'=>'about-partnership','Regional growth'=>'about-growth','Closing call to action'=>'about-closing'),
        'history' => array('Hero'=>'rp-inner-hero','History timeline'=>'history-story'),
        'certifications' => array('Hero'=>'rp-inner-hero','Introduction and certifications'=>'certifications-intro','Production'=>'certifications-production','Quality assurance'=>'certifications-quality','Partners'=>'certifications-partners','Closing statement'=>'certifications-commitment'),
        'product-portfolio' => array('Hero'=>'rp-inner-hero','Product portfolio'=>'portfolio-intro','Quality management'=>'portfolio-quality','Process and materials'=>'portfolio-materials','Process Control and Inspection'=>'rp-process','Localization'=>'rp-local'),
        'planet' => array('Hero'=>'rp-inner-hero','Sustainable sourcing'=>'planet-sourcing','Vision for Africa'=>'planet-vision','Environmental, Social and Governance'=>'planet-esg','Closing statement'=>'planet-statement'),
        'contact-us' => array('Hero'=>'rp-inner-hero','Contact details'=>'contact-details','Contact form'=>'contact-enquiry','Map'=>'contact-map'),
    );
}

function rockpack_editor_live_preview( $post, $title ) {
    $kind = rockpack_page_kind( $post );
    if ( ! isset( rockpack_preview_sections()[$kind][$title] ) ) return;
    $values = array('action'=>'rockpack_section_preview','post_id'=>$post->ID,'section'=>$title,'nonce'=>wp_create_nonce('rockpack_section_preview_'.$post->ID));
    echo '<div class="rp-live-preview" hx-post="' . esc_url(admin_url('admin-ajax.php')) . '" hx-vals="' . esc_attr(wp_json_encode($values)) . '" hx-include="closest .postbox" hx-trigger="revealed once, input changed delay:800ms from:closest .postbox, change delay:800ms from:closest .postbox" hx-sync="this:replace" hx-swap="innerHTML" aria-live="polite">';
    echo '<p class="description">' . esc_html__('Section preview loads when visible. Changes here are not saved until you update the page.', 'rockpack') . '</p></div>';
}

function rockpack_render_section_preview() {
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    if ( ! $post_id || ! current_user_can('edit_post', $post_id) || ! check_ajax_referer('rockpack_section_preview_'.$post_id, 'nonce', false) ) wp_die(esc_html__('You cannot preview this page.', 'rockpack'), '', array('response'=>403));
    $post = get_post($post_id);
    $kind = rockpack_page_kind($post);
    $title = isset($_POST['section']) && is_string($_POST['section']) ? sanitize_text_field(wp_unslash($_POST['section'])) : '';
    $sections = rockpack_page_sections($post);
    $class = rockpack_preview_sections()[$kind][$title] ?? '';
    if ( ! $class || ! isset($sections[$title]) ) wp_die(esc_html__('Section not found.', 'rockpack'), '', array('response'=>400));
    $section = $sections[$title];
    $input = wp_unslash($_POST);
    $fields = is_array($input['rp_content'] ?? null) ? $input['rp_content'] : array();
    $lists = is_array($input['rp_lists'] ?? null) ? $input['rp_lists'] : array();
    $opts = get_option('rockpack_settings', array());
    if (!is_array($opts)) $opts = array();
    foreach ($section['fields'] ?? array() as $key=>$field) {
        if (!array_key_exists($key, $fields)) continue;
        $value = rockpack_sanitize_editor_field($fields[$key], $field);
        if (is_wp_error($value)) $value = '';
        $opts[$key] = $value;
        $opts['_explicit'][$key] = true;
        if ('image' === $field['type']) {
            $id = absint($fields[$key.'_id'] ?? 0);
            $opts['_media'][$key] = $id && wp_attachment_is_image($id) ? $id : 0;
        }
    }
    $GLOBALS['rockpack_preview_collections'] = array();
    foreach ($section['repeaters'] ?? array() as $key) {
        $schema = rockpack_repeaters()[$key];
        $rows = rockpack_sanitize_editor_rows($lists[$key] ?? array(), $schema['fields']);
        if (is_wp_error($rows)) wp_die(esc_html__('Please check the section fields to update its preview.', 'rockpack'), '', array('response'=>400));
        $GLOBALS['rockpack_preview_collections'][$key] = $rows;
    }
    add_filter('option_rockpack_settings', static function() use ($opts) { return $opts; });

    global $wp_query, $wp_the_query;
    $wp_query = new WP_Query(array('page_id'=>$post_id, 'post_status'=>'any'));
    $wp_the_query = $wp_query;
    $GLOBALS['post'] = $post;
    setup_postdata($post);
    $template = 'home' === $kind ? 'front-page.php' : 'page-'.$kind.'.php';
    ob_start();
    include get_template_directory().'/'.$template;
    $html = ob_get_clean();
    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($dom);
    $node = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " '.$class.' ")]')->item(0);
    if (!$node) wp_die(esc_html__('This section could not be rendered.', 'rockpack'), '', array('response'=>500));
    $styles = array('style.css','assets/css/company-page.css');
    $page_styles = array('home'=>array('home.css','product-components.css'),'about-us'=>array('about-page.css'),'history'=>array('history.css'),'certifications'=>array('certifications.css'),'product-portfolio'=>array('product-components.css','portfolio-page.css'),'planet'=>array('planet-page.css'),'contact-us'=>array('contact-page.css'));
    foreach ($page_styles[$kind] ?? array() as $css) $styles[] = 'assets/css/'.$css;
    $document = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    foreach ($styles as $style) $document .= '<link rel="stylesheet" href="'.esc_url(get_template_directory_uri().'/'.$style).'">';
    $document .= '<style>html,body{margin:0} body{overflow:auto} a,button,input,select,textarea{pointer-events:none}</style></head><body class="'.esc_attr('home' === $kind ? 'rockpack-home-page' : 'rockpack-company-page').'">';
    $document .= '<main inert class="'.esc_attr(('home' === $kind ? 'home' : $kind).'-page company-page').'">'.$dom->saveHTML($node).'</main></body></html>';
    echo '<p class="description">'.esc_html__('Live preview — changes are not saved yet.', 'rockpack').'</p>';
    echo '<iframe class="rp-live-preview__frame" title="'.esc_attr(sprintf(__('Preview of %s', 'rockpack'),$title)).'" sandbox="allow-same-origin" srcdoc="'.esc_attr($document).'"></iframe>';
    wp_die('', '', array('response'=>200));
}
add_action('wp_ajax_rockpack_section_preview', 'rockpack_render_section_preview');
