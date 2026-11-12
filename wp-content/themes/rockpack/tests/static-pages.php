<?php
/** Local live HTTP checks; refreshes public snapshots but never enables static delivery. */
if(PHP_SAPI!=='cli') { http_response_code(403); exit; }
$_SERVER['REQUEST_SCHEME']='http'; $_SERVER['HTTP_HOST']='figma-htmx-site.test';
require dirname(__DIR__,4).'/wp-load.php';
if(!str_ends_with((string)parse_url(home_url(),PHP_URL_HOST),'.test') || !class_exists('Rockpack_Static_SSG')) exit("Active SSG plugin on a local .test host required.\n");
$checks=0; $failed=0; $routes=hash_file('sha256',ABSPATH.'.htaccess');
foreach(get_pages(array('post_status'=>'publish')) as $target) {
    $kind=rockpack_page_kind($target); if(!$kind) continue;
    $result=Rockpack_Static_SSG::generate($target->ID);
    $ok='contact-us'===$kind ? is_wp_error($result) && 'rockpack_ssg_not_public'===$result->get_error_code() : !is_wp_error($result) && is_file(WP_CONTENT_DIR.'/static-site/'.$result);
    echo ($ok?'PASS':'FAIL').': '.$kind.' '.('contact-us'===$kind?'snapshot excluded':'live HTTP snapshot refreshed')."\n";
    $checks++; if(!$ok) $failed++;
    if('contact-us'===$kind) {
        $response=wp_remote_get(get_permalink($target));
        $ok=!is_wp_error($response) && 200===wp_remote_retrieve_response_code($response) && 'BYPASS'===wp_remote_retrieve_header($response,'x-rockpack-page-cache') && str_contains(wp_remote_retrieve_header($response,'cache-control'),'private') && str_contains(wp_remote_retrieve_header($response,'cache-control'),'no-store');
        echo ($ok?'PASS':'FAIL').': contact HTTP response remains private, no-store and BYPASS'."\n";
        $checks++; if(!$ok) $failed++;
    }
}
$ok=$routes===hash_file('sha256',ABSPATH.'.htaccess'); $checks++; if(!$ok) $failed++;
echo ($ok?'PASS':'FAIL').': routing configuration unchanged'."\n";
echo $checks.' live checks; '.$failed.' failed.'."\n";
exit($failed?1:0);
