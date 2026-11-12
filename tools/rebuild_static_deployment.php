<?php
/** Rebuild indexable snapshots through the existing generator; local CLI only. */
if (PHP_SAPI !== 'cli') exit(2);
$_SERVER['REQUEST_SCHEME'] = 'http';
$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'figma-htmx-site.test';
require dirname(__DIR__) . '/wp-load.php';
if (!class_exists('Rockpack_Static_SSG') || !str_ends_with((string)wp_parse_url(home_url(), PHP_URL_HOST), '.test')) exit("Local installation and active generator required.\n");
$origin = rtrim($argv[1] ?? '', '/');
if (!filter_var($origin, FILTER_VALIDATE_URL) || 'https' !== wp_parse_url($origin, PHP_URL_SCHEME) || !empty(wp_parse_url($origin, PHP_URL_PATH)) || wp_parse_url($origin, PHP_URL_QUERY) || wp_parse_url($origin, PHP_URL_FRAGMENT) || wp_parse_url($origin, PHP_URL_USER)) exit("Supply a public HTTPS origin without a path.\n");
update_option('rockpack_static_ssg_production_domain', $origin);
if (class_exists('Rockpack_Local_Page_Cache')) Rockpack_Local_Page_Cache::flush();
$manifest = array('origin'=>$origin, 'generated_at'=>gmdate('c'), 'snapshots'=>array(), 'excluded'=>array());
foreach (get_posts(array('post_type'=>array('page','post'), 'post_status'=>'publish', 'posts_per_page'=>-1, 'orderby'=>'ID', 'order'=>'ASC')) as $post) {
    $reason = '';
    if ($post->post_password || !is_post_publicly_viewable($post)) $reason = 'protected or not publicly viewable';
    elseif (class_exists('Portable_SEO') && Portable_SEO::noindex($post->ID)) $reason = 'intentionally noindex';
    elseif (!apply_filters('rockpack_static_ssg_is_public_content', true, $post)) $reason = 'requires WordPress';
    if ($reason) { $manifest['excluded'][]=array('path'=>wp_parse_url(get_permalink($post),PHP_URL_PATH), 'reason'=>$reason); continue; }
    $result = Rockpack_Static_SSG::generate($post->ID);
    if (is_wp_error($result)) { fwrite(STDERR, $result->get_error_message()."\n"); exit(1); }
    $manifest['snapshots'][] = $result;
    echo "Generated $result\n";
}
$result = Rockpack_Static_SSG::generate_seo_files();
if (is_wp_error($result)) { fwrite(STDERR,$result->get_error_message()); exit(1); }
file_put_contents(dirname(__DIR__).'/tmp/static-deployment-manifest.json', wp_json_encode($manifest, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo count($manifest['snapshots'])." snapshots rebuilt; sitemap and robots regenerated.\n";
