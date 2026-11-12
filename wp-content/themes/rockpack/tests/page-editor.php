<?php
/** CLI integration checks. Database changes are enclosed in an InnoDB rollback. */
if ( PHP_SAPI !== 'cli' ) { http_response_code(403); exit; }
$_SERVER['REQUEST_SCHEME']='http'; $_SERVER['HTTP_HOST']='figma-htmx-site.test';
$_SERVER['SERVER_NAME']='figma-htmx-site.test';
require dirname(__DIR__,4).'/wp-load.php';
require_once ABSPATH.'wp-admin/includes/admin.php';
set_current_screen('page');
if(!str_ends_with((string)parse_url(home_url(),PHP_URL_HOST),'.test')) exit("Local .test installation required.\n");
global $wpdb;
foreach(array($wpdb->posts,$wpdb->postmeta,$wpdb->options) as $table) {
    $status=$wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s',$wpdb->esc_like($table)));
    if(!$status || 'InnoDB'!==$status->Engine) exit("InnoDB rollback support required.\n");
}
$checks=array(); $failure=null;
$check=function($condition,$label)use(&$checks){$checks[$label]=(bool)$condition; if(!$condition) throw new RuntimeException($label);};
$admin=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
if(!$admin) exit("An existing local administrator is required.\n");
wp_set_current_user($admin[0]);
// Match the sanitizer registration that WordPress runs on an actual admin save.
register_setting('rockpack_settings_group','rockpack_settings',array('type'=>'array','sanitize_callback'=>'rp_sanitize_settings'));
$before=get_option('rockpack_settings',array());
$wpdb->query('START TRANSACTION');
$save=function($target_page,$extra=array()) {
    $_POST=wp_slash(array_merge(array('rockpack_page_nonce'=>wp_create_nonce('rockpack_page_'.$target_page->ID),'rockpack_complete'=>'1'),$extra));
    delete_transient('rockpack_editor_error_'.get_current_user_id());
    rockpack_save_page_content($target_page->ID);
    $error=get_transient('rockpack_editor_error_'.get_current_user_id());
    if($error) throw new RuntimeException($error);
};
$render=function($target_page) {
    global $wp_query,$post;
    $wp_query=new WP_Query(array('page_id'=>$target_page->ID)); $post=$target_page; setup_postdata($post);
    ob_start(); include get_template_directory().('/home'===('/'.rockpack_page_kind($target_page))?'/front-page.php':'/page-'.rockpack_page_kind($target_page).'.php');
    return ob_get_clean();
};
$rollback=function()use($wpdb){$wpdb->query('ROLLBACK TO SAVEPOINT rp_editor_case'); wp_cache_flush(); unset($GLOBALS['rockpack_refresh_content']); $_POST=array();};
$main_content=function($html){
    $doc=new DOMDocument(); $previous=libxml_use_internal_errors(true); $doc->loadHTML('<?xml encoding="UTF-8">'.$html); libxml_clear_errors(); libxml_use_internal_errors($previous);
    $xpath=new DOMXPath($doc);
    foreach($xpath->query('//script|//style') as $node) $node->parentNode->removeChild($node);
    $main=$xpath->query('//main')->item(0);
    $text=preg_replace('/\s+/u',' ',trim($main->textContent));
    $images=array(); foreach($xpath->query('//main//img') as $image) $images[]=$image->getAttribute('src');
    return array($text,$images);
};
try {
    $target_pages=array();
    foreach(get_pages() as $target_page) if(rockpack_page_kind($target_page)) $target_pages[rockpack_page_kind($target_page)]=$target_page;
    $check(count($target_pages)===7,'all seven existing pages resolve to their editor schema');
    $expected_components = array(
        'home' => array( 'Hero', 'Statistics', 'About', 'History', 'Certifications', 'Products and Services', 'Process Control and Inspection', 'Localization', 'Planet and Societal Impact', 'News', 'Partners', 'Careers', 'Closing call to action' ),
        'about-us' => array( 'Hero', 'Introduction', 'Technical foundation', 'Partnership', 'Regional growth', 'Closing call to action' ),
        'history' => array( 'Hero', 'History timeline' ),
        'certifications' => array( 'Hero', 'Introduction and certifications', 'Production', 'Quality assurance', 'Partners', 'Closing statement' ),
        'product-portfolio' => array( 'Hero', 'Product portfolio', 'Quality management', 'Process and materials', 'Process Control and Inspection', 'Localization' ),
        'planet' => array( 'Hero', 'Sustainable sourcing', 'Vision for Africa', 'Environmental, Social and Governance', 'Closing statement' ),
        'contact-us' => array( 'Hero', 'Contact details', 'Contact form', 'Map' ),
    );
    $expected_repeaters = array(
        'home' => array( 'hero_images', 'stats', 'home_bullets', 'home_tabs', 'history', 'certs', 'products', 'process_cards', 'local_cards', 'home_planet_cards', 'logos' ),
        'about-us' => array( 'about_bullets', 'about_tabs', 'foundation_features', 'partnership_points', 'growth_cards' ),
        'history' => array( 'history' ),
        'certifications' => array( 'certs', 'quality_steps' ),
        'product-portfolio' => array( 'products', 'material_steps', 'process_cards', 'local_cards' ),
        'planet' => array( 'esg_pillars' ),
        'contact-us' => array(),
    );
    $keys=array('home'=>'about_eyebrow','about-us'=>'about_page_eyebrow','history'=>'history_page_intro','certifications'=>'certs_page_intro','product-portfolio'=>'portfolio_page_gallery_intro','planet'=>'planet_page_sourcing_text','contact-us'=>'contact_page_phone_hint');
    foreach($keys as $kind=>$key) {
        $wpdb->query('SAVEPOINT rp_editor_case');
        $target_page=$target_pages[$kind]; $sections=rockpack_page_sections($target_page);
        $check( array_keys( $sections ) === $expected_components[$kind], $kind . ': editor components follow public page order' );
        $repeater_order = array();
        foreach ( $sections as $section ) $repeater_order = array_merge( $repeater_order, $section['repeaters'] ?? array() );
        $check( $repeater_order === $expected_repeaters[$kind], $kind . ': repeaters stay beside their matching page sections' );
        $saved_order = array( 'normal' => 'seo-box,rockpack-map,rockpack-obsolete', 'side' => 'submitdiv,rockpack-old-section' );
        $adjusted_order = rockpack_editor_meta_box_order( $saved_order, $target_page );
        $box_ids = array_merge( array( 'rockpack-editor-help' ), array_map( static function ( $title ) { return 'rockpack-' . sanitize_title( $title ); }, $expected_components[$kind] ) );
        $check( explode( ',', $adjusted_order['normal'] ) === array_merge( $box_ids, array( 'seo-box' ) ) && 'submitdiv' === $adjusted_order['side'], $kind . ': saved WordPress layouts retain component order and other editor boxes' );
        $fields=array(); foreach($sections as $section) $fields=array_merge($fields,$section['fields']??array());
        $check(isset($fields[$key]),$kind.': normal field is in correct page schema');
        $check(str_contains(get_edit_post_link($target_page->ID,'raw'),'post='.$target_page->ID),$kind.': correct native Edit Page URL');
        $marker='Editor integration '.wp_generate_uuid4();
        $save($target_page,array('rp_content'=>array($key=>$marker)));
        $check(rp_option($key)===$marker,$kind.': normal field saves and reloads');
        $check(str_contains($render($target_page),$marker),$kind.': saved field renders on frontend');
        $opts=get_option('rockpack_settings');
        $check(!isset($before['contact_page_recipient']) || $opts['contact_page_recipient']===$before['contact_page_recipient'],$kind.': unrelated recipient preserved');
        // Verify that every section generates actual controls rather than a JSON textarea.
        $GLOBALS['wp_meta_boxes']=array(); do_action('add_meta_boxes_page',$target_page);
        ob_start(); do_meta_boxes('page','normal',$target_page); $boxes=ob_get_clean();
        $last_box_position = -1;
        foreach ( $box_ids as $id ) {
            $box_position = strpos( $boxes, 'id="' . $id . '"' );
            $check( false !== $box_position && $box_position > $last_box_position, $kind . ': actual editor box order includes ' . $id );
            $last_box_position = $box_position;
        }
        $check(str_contains($boxes,'name="rp_content['.$key.']"'),$kind.': native meta box renders populated controls');
        preg_match_all('/<(?:input|textarea|select)\b/',$boxes,$controls);
        $check(count($controls[0])<1000,$kind.': default editor stays below max_input_vars 1000 ('.count($controls[0]).' controls)');
        $rollback();
    }
    foreach($target_pages as $kind=>$target_page) {
        $wpdb->query('SAVEPOINT rp_editor_case');
        $before_render=$main_content($render($target_page));
        $payload=array('rp_content'=>array(),'rp_lists'=>array(),'rp_present'=>array());
        $options=get_option('rockpack_settings',array());
        foreach(rockpack_page_sections($target_page) as $section) {
            foreach($section['fields']??array() as $key=>$field) {
                $payload['rp_content'][$key]=rp_option($key,$field['default']);
                if('image'===$field['type']) $payload['rp_content'][$key.'_id']=$options['_media'][$key]??0;
            }
            foreach($section['repeaters']??(isset($section['repeater'])?array($section['repeater']):array()) as $key) {
                $payload['rp_present'][$key]=1;
                $payload['rp_lists'][$key]=rockpack_collection($key,'certs'===$key && 'certifications'===$kind?'certifications':'');
            }
        }
        $save($target_page,$payload);
        $after_render=$main_content($render($target_page));
        $check($before_render===$after_render,$kind.': complete editor submission preserves current text and images');
        $check(!empty($GLOBALS['rockpack_refresh_content']),$kind.': complete save queues final SSG/cache dependency refresh');
        $rollback();
    }
    foreach(rockpack_repeaters() as $key=>$schema) {
        // Header/footer collections are intentionally maintained on the
        // dedicated Appearance → Header & Footer options page.
        if(in_array($key,array('navigation_groups','footer_page_links','footer_service_links','footer_social'),true)) continue;
        $wpdb->query('SAVEPOINT rp_editor_case');
        $target_page=$target_pages[$schema['pages'][0]];
        $original=rockpack_collection($key);
        $existing=array_values(array_filter($original,function($row){return !empty($row['id']);}));
        $make=function($title)use($schema){
            $row=array(); foreach($schema['fields'] as $k=>$field) {
                $row[$k]='select'===$field['type']?array_key_first($field['choices']):('repeater'===$field['type']?array(array('text'=>'Nested test point')):('hidden'===$field['type']?'':('url'===$field['type']?'https://example.invalid/test':('image'===$field['type']?'https://example.invalid/'.sanitize_title($title).'.jpg':$title))));
            } return $row;
        };
        $first=$make('First fixture'); $second=$make('Second fixture');
        $save($target_page,array('rp_present'=>array($key=>1),'rp_lists'=>array($key=>array_merge(array($first,$second),$existing))));
        $rows=rockpack_collection($key);
        $field=isset($schema['fields']['title'])?'title':(isset($schema['fields']['text'])?'text':'image');
        $edited='image'===$field?'https://example.invalid/edited-second.jpg':'Edited second fixture';
        $check(count($rows)===count($existing)+2 && $first[$field]===$rows[0][$field],$key.': add and reload');
        $rows[1][$field]=$edited;
        $rows=array_merge(array($rows[1],$rows[0]),array_slice($rows,2));
        $save($target_page,array('rp_present'=>array($key=>1),'rp_lists'=>array($key=>$rows)));
        $ordered=rockpack_collection($key);
        $check($edited===$ordered[0][$field] && $first[$field]===$ordered[1][$field],$key.': edit and reorder');
        $rendered=$render($target_page);
        $check(str_contains($rendered,$edited),$key.': reordered item renders');
        $check(strpos($rendered,$edited)<strpos($rendered,$first[$field]),$key.': frontend preserves saved order');
        if('esg_pillars'===$key) $check('Nested test point'===$ordered[0]['points'][0]['text'],$key.': nested points saved');
        $removed=$ordered[0]['id']??0;
        $save($target_page,array('rp_present'=>array($key=>1),'rp_lists'=>array($key=>$existing)));
        $check(count(rockpack_collection($key))===count($existing),$key.': remove without resurrecting defaults');
        if($removed) $check('trash'===get_post_status($removed),$key.': removed CPT record is recoverable in Trash');
        $empty=$render($target_page);
        $check(!str_contains($empty,'src=""'),$key.': empty optional images produce no broken sources');
        $rollback();
    }
    $fixed_titles = rockpack_section_titles();
    foreach ( $target_pages as $kind => $target_page ) {
        $fields = array();
        foreach ( rockpack_page_sections( $target_page ) as $section ) $fields = array_merge( $fields, $section['fields'] ?? array() );
        $check( ! array_intersect_key( $fields, $fixed_titles ), $kind . ': fixed title fields are absent from page editor' );
    }
    $legacy_fields = array();
    foreach ( rp_settings_fields() as $section ) foreach ( $section['fields'] ?? array() as $field ) $legacy_fields[$field['key']] = $field;
    $check( ! array_intersect_key( $legacy_fields, $fixed_titles ), 'fixed title fields are absent from legacy settings' );
    $check( ! array_filter( array_keys( $legacy_fields ), 'rockpack_is_replaced_field' ), 'repeater replacements have no duplicate legacy scalar controls' );
    $check( ! isset( $legacy_fields['certs_subtitle'] ) && ! isset( $legacy_fields['planet_eyebrow'] ), 'unused subtitle and eyebrow controls are removed' );
    $check( ! isset( rockpack_repeaters()['certs']['fields']['bg'] ), 'unused certificate background control is removed' );
    $header_fields = rockpack_header_footer_fields();
    $check( ! isset( $header_fields['nav_label_search_the_rock_pack'] ), 'removed search dialog has no leftover title control' );
    $footer_fallbacks = array( 'shared_link_about_us', 'shared_link_history', 'shared_link_certifications', 'shared_link_news', 'shared_link_careers', 'shared_link_product_portfolio', 'shared_link_planet' );
    $check( ! array_intersect_key( $header_fields, array_flip( $footer_fallbacks ) ), 'footer link repeaters have no duplicate destination controls' );
    foreach ( $target_pages as $kind => $target_page ) {
        $fields = array();
        foreach ( rockpack_page_sections( $target_page ) as $section ) $fields += $section['fields'] ?? array();
        $check( isset( $fields['breadcrumb_parent'] ) === in_array( $kind, array( 'about-us', 'certifications' ), true ), $kind . ': breadcrumb control only appears where rendered' );
    }
    $check( ! array_intersect_key( rockpack_header_footer_fields(), $fixed_titles ), 'fixed newsletter heading is absent from Header & Footer editor' );
    $check( ! isset( rockpack_repeaters()['hero_images']['fields']['heading'] ), 'hero slide heading field is absent from repeater' );
    $override_titles = static function () use ( $fixed_titles ) {
        return array_merge( array_fill_keys( array_keys( $fixed_titles ), 'Stored title override' ), array(
            '_repeaters' => array( 'hero_images' => array( array( 'heading' => 'Stored slide override', 'image' => 'https://example.invalid/hero.jpg' ) ) ),
        ) );
    };
    add_filter( 'pre_option_rockpack_settings', $override_titles );
    foreach ( $fixed_titles as $key => $title ) $check( rp_option( $key ) === $title, $key . ': hardcoded title overrides stored settings' );
    $check( rockpack_collection( 'hero_images' )[0]['heading'] === $fixed_titles['hero_title'], 'saved hero slide cannot override fixed heading' );
    remove_filter( 'pre_option_rockpack_settings', $override_titles );

    $wpdb->query( 'SAVEPOINT rp_editor_case' );
    $shared_options = get_option( 'rockpack_settings', array() );
    $shared_options['contact_address'] = 'Shared footer address fixture';
    $shared_options['footer_contact_text'] = 'Shared contact fixture';
    update_option( 'rockpack_settings', $shared_options );
    $check( rp_option( 'contact_address' ) === 'Shared footer address fixture' && rp_option( 'footer_contact_text' ) === 'Shared contact fixture', 'dedicated footer controls still save through registered sanitizer' );
    $check( str_contains( $render( $target_pages['home'] ), 'Shared footer address fixture' ), 'saved footer content still renders after legacy controls are removed' );
    $rollback();

    $wpdb->query('SAVEPOINT rp_editor_case'); $home=$target_pages['home'];
    $opts=get_option('rockpack_settings',array());
    $save( $home, array( 'rp_content' => array( 'about_heading' => 'Submitted title override' ) ) );
    $check( ( get_option( 'rockpack_settings' )['about_heading'] ?? null ) === ( $opts['about_heading'] ?? null ), 'page save ignores submitted fixed heading' );
    $opts = get_option( 'rockpack_settings', array() );
    $legacy = rp_sanitize_settings( array( 'about_heading' => 'Legacy title override' ) );
    $check( ( $legacy['about_heading'] ?? null ) === ( $opts['about_heading'] ?? null ), 'legacy save ignores submitted fixed heading' );
    $_POST=array('rockpack_page_nonce'=>'wrong','rp_content'=>array('hero_title'=>'Unauthorized'),'rockpack_complete'=>1);
    rockpack_save_page_content($home->ID);
    $check(get_option('rockpack_settings')===$opts,'invalid nonce rejected');
    wp_set_current_user(0);
    $_POST=array('rockpack_page_nonce'=>wp_create_nonce('rockpack_page_'.$home->ID),'rp_content'=>array('hero_title'=>'Unauthorized'),'rockpack_complete'=>1);
    rockpack_save_page_content($home->ID);
    $check(get_option('rockpack_settings')===$opts,'anonymous writes rejected');
    wp_set_current_user($admin[0]);
    $_POST=wp_slash(array('rockpack_page_nonce'=>wp_create_nonce('rockpack_page_'.$home->ID),'rp_content'=>array('hero_title'=>'Incomplete')));
    rockpack_save_page_content($home->ID);
    $check(get_option('rockpack_settings')===$opts,'truncated form rejected without changing content');
    $_POST=wp_slash(array('rockpack_page_nonce'=>wp_create_nonce('rockpack_page_'.$target_pages['contact-us']->ID),'rockpack_complete'=>1,'rp_content'=>array('contact_page_heading'=>'Should not save','contact_page_email'=>'bad-email')));
    rockpack_save_page_content($target_pages['contact-us']->ID);
    $check(get_option('rockpack_settings')===$opts,'invalid email rejects entire content patch');
    $_POST=wp_slash(array('rockpack_page_nonce'=>wp_create_nonce('rockpack_page_'.$home->ID),'rockpack_complete'=>1,'rp_present'=>array('home_tabs'=>1),'rp_versions'=>array('home_tabs'=>'stale-version'),'rp_lists'=>array('home_tabs'=>array())));
    rockpack_save_page_content($home->ID);
    $check(get_option('rockpack_settings')===$opts,'stale shared repeater form rejected');
    $hero_slides=rockpack_collection('hero_images');
    foreach($hero_slides as &$slide) { $slide['button_text']=''; $slide['button_url']=''; $slide['image']=''; $slide['image_id']=0; }
    unset($slide);
    $save($home,array('rp_present'=>array('hero_images'=>1),'rp_lists'=>array('hero_images'=>$hero_slides)));
    $check(''===(get_option('rockpack_settings',array())['_repeaters']['hero_images'][0]['button_text']??null),'explicit blank Hero slide field is saved');
    $html=$render($home);
    $check(!str_contains($html,'class="btn-prim">Connect With Our Team'),'optional blank CTA hidden');
    $check(!str_contains($html,'src=""'),'optional blank images hidden');
    $check(is_wp_error(rockpack_sanitize_editor_rows(array('bad row'),rockpack_repeaters()['home_tabs']['fields'])),'malformed repeater rejected');
    $save($home,array('rp_content'=>array('about_eyebrow'=>'<script>alert(1)</script>Safe')));
    $check(!str_contains(rp_option('about_eyebrow'),'<script>'),'script markup sanitized');
    $legacy=rp_sanitize_settings(array('hero_title'=>'Legacy update'));
    $check(($legacy['contact_page_heading']??null)===($opts['contact_page_heading']??null),'legacy settings partial save preserves unrelated fields');
    $attachment=get_posts(array('post_type'=>'attachment','post_mime_type'=>'image','posts_per_page'=>1,'post_status'=>'inherit'));
    if($attachment) {
        $id=$attachment[0]->ID;
        $save($home,array('rp_content'=>array('about_image'=>'https://example.invalid/legacy.jpg','about_image_id'=>$id)));
        $check(rp_option('about_image')===wp_get_attachment_image_url($id,'full'),'Media Library attachment ID resolves current image URL');
    }
    $cache=WP_CONTENT_DIR.'/cache/rockpack-full-page/rockpack-editor-test-'.wp_generate_uuid4().'.html';
    file_put_contents($cache,'fixture');
    $save($home,array('rp_content'=>array('about_eyebrow'=>'Invalidate cache')));
    $check(!is_file($cache),'page content save invalidates local HTML cache');
    $check(!Rockpack_Static_SSG::should_serve_static_request('/contact-us/','GET'),'Contact Us remains excluded from static delivery');
    $result=Rockpack_Static_SSG::generate($target_pages['contact-us']->ID);
    $check(is_wp_error($result),'SSG refuses Contact Us snapshot');
    $rollback();
} catch(Throwable $error) { $failure=$error->getMessage(); }
finally {
    $wpdb->query('ROLLBACK'); wp_cache_flush(); $_POST=array();
    unset($GLOBALS['rockpack_refresh_content']);
}
echo wp_json_encode($checks,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
echo count($checks).' executed checks. Database changes rolled back; no real email sent.'."\n";
if($failure) { fwrite(STDERR,$failure."\n"); exit(1); }
