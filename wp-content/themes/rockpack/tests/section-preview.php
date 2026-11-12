<?php
/** Read-only integration check for every mapped section. Run with local PHP. */
$_SERVER['REQUEST_SCHEME'] = 'http';
$_SERVER['HTTP_HOST'] = 'figma-htmx-site.test';
$_SERVER['SERVER_NAME'] = 'figma-htmx-site.test';
require dirname(__DIR__, 4) . '/wp-load.php';
$users = get_users(array('role'=>'administrator', 'number'=>1, 'fields'=>'ID'));
if (!$users) throw new RuntimeException('A local administrator is required.');
wp_set_current_user($users[0]);
class RockpackPreviewFinished extends Exception {}
add_filter('wp_die_handler', static function() { return static function() { throw new RockpackPreviewFinished(); }; });
add_filter('wp_die_ajax_handler', static function() { return static function() { throw new RockpackPreviewFinished(); }; });
$count = 0;
foreach (rockpack_preview_sections() as $kind=>$mapping) {
    $test_page = 'home' === $kind ? get_post(get_option('page_on_front')) : get_page_by_path($kind);
    if (!$test_page) throw new RuntimeException('Missing page '.$kind);
    $sections = rockpack_page_sections($test_page);
    foreach ($sections as $title=>$section) {
        if (!isset($mapping[$title])) throw new RuntimeException('Unmapped section '.$kind.' / '.$title);
        $fields = array();
        foreach ($section['fields'] ?? array() as $key=>$field) $fields[$key] = rp_option($key, $field['default'] ?? '');
        $lists = array();
        foreach ($section['repeaters'] ?? array() as $key) $lists[$key] = rockpack_collection($key, 'certs' === $key && 'certifications' === $kind ? 'certifications' : '');
        $expected = '';
        if ('planet' === $kind && 'Sustainable sourcing' === $title) {
            $expected = 'Temporary preview text 723';
            $fields['planet_page_sourcing_text'] = $expected;
        }
        if ('home' === $kind && 'Statistics' === $title && !empty($lists['stats'])) {
            $expected = 'Temporary statistic 724';
            $lists['stats'][0]['title'] = $expected;
        }
        $stored_before = get_option('rockpack_settings');
        $_POST = array('post_id'=>$test_page->ID,'section'=>$title,'nonce'=>wp_create_nonce('rockpack_section_preview_'.$test_page->ID),'rp_content'=>$fields,'rp_lists'=>$lists);
        $_REQUEST = $_POST;
        ob_start();
        try { rockpack_render_section_preview(); } catch (RockpackPreviewFinished $e) {}
        $result = ob_get_clean();
        if (strpos($result, 'rp-live-preview__frame') === false || strpos($result, $mapping[$title]) === false) throw new RuntimeException('Preview failed: '.$kind.' / '.$title.' '.substr(wp_strip_all_tags($result),0,200));
        if ($expected && strpos($result, $expected) === false) throw new RuntimeException('Unsaved value missing: '.$kind.' / '.$title);
        remove_all_filters('option_rockpack_settings');
        if ($stored_before !== get_option('rockpack_settings')) throw new RuntimeException('Preview modified saved settings.');
        $count++;
        unset($GLOBALS['rockpack_preview_collections']);
    }
}
echo 'PASS '.$count." section previews\n";
