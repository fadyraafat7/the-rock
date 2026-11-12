<?php
/** Apply the reviewed eight-page SEO copy to the local installation only. */
if (PHP_SAPI !== 'cli') exit(2);
$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'figma-htmx-site.test';
$_SERVER['REQUEST_SCHEME'] = 'http';
require dirname(__DIR__) . '/wp-load.php';
if (home_url() !== 'http://figma-htmx-site.test' || get_template() !== 'rockpack') {
    fwrite(STDERR, "Expected local Rockpack installation.\n"); exit(1);
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$copy = json_decode(file_get_contents(dirname(__DIR__) . '/tmp/seo-content-hostinger.json'), true, 512, JSON_THROW_ON_ERROR);
$targets = [];
foreach ($copy['pages'] as $row) {
    $id = $row['path'] === '/' ? (int)get_option('page_on_front') : (int)(get_page_by_path(trim($row['path'], '/'))->ID ?? 0);
    $post = get_post($id);
    if (!$post || $post->post_type !== 'page' || $post->post_status !== 'publish') throw new RuntimeException('Published local page missing: ' . $row['path']);
    $targets[$id] = $row;
    echo $id . ' ' . $row['path'] . ' => ' . $row['title'] . "\n";
}
if (count($targets) !== 8) throw new RuntimeException('Expected eight distinct pages.');
$plugin = 'portable-seo/portable-seo.php';
echo 'Portable SEO active: ' . (is_plugin_active($plugin) ? 'yes' : 'no') . "\n";
if (!in_array('--apply', $argv, true)) exit(0);
$backup = ['created_at'=>gmdate('c'), 'site'=>home_url(), 'active_plugins'=>get_option('active_plugins'), 'settings'=>get_option('portable_seo_settings', null), 'pages'=>[]];
foreach ($targets as $id=>$row) {
    foreach (['title','description'] as $key) {
        $name = '_portable_seo_' . $key;
        $backup['pages'][$id][$name] = ['exists'=>metadata_exists('post',$id,$name), 'values'=>get_post_meta($id,$name,false)];
    }
}
$backupPath = dirname(__DIR__) . '/tmp/seo-local-backup-' . gmdate('Ymd-His') . '.json';
if (file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) throw new RuntimeException('Backup failed.');
if (!is_plugin_active($plugin)) {
    $result = activate_plugin($plugin);
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
}
if (!class_exists('Portable_SEO') || !Portable_SEO::enabled()) throw new RuntimeException('Portable SEO is unavailable or paused by another SEO provider.');
foreach ($targets as $id=>$row) {
    update_post_meta($id, '_portable_seo_title', sanitize_text_field($row['title']));
    update_post_meta($id, '_portable_seo_description', sanitize_textarea_field($row['description']));
    if (get_post_meta($id, '_portable_seo_title', true) !== $row['title'] || get_post_meta($id, '_portable_seo_description', true) !== $row['description']) throw new RuntimeException('Metadata verification failed for ' . $id);
    clean_post_cache($id);
}
if (class_exists('Rockpack_Local_Page_Cache')) Rockpack_Local_Page_Cache::flush();
echo "Saved and verified eight local pages. Backup: $backupPath\n";
