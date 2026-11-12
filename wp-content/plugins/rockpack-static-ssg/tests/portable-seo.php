<?php
/** Local end-to-end test: creates and removes one uniquely named fixture post and snapshot. */
if (PHP_SAPI !== 'cli') exit(2);
require dirname(__DIR__,4).'/wp-load.php';
if (!class_exists('Portable_SEO') || !class_exists('Rockpack_Static_SSG') || !str_ends_with((string)wp_parse_url(home_url(),PHP_URL_HOST),'.test')) exit("A local .test installation with both plugins active is required.\n");
$admin=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID')); if (!$admin) exit("Administrator required.\n");
wp_set_current_user($admin[0]); $id=0; $failed=false;
try {
    $created=wp_insert_post(array('post_type'=>'post','post_status'=>'publish','post_title'=>'SEO snapshot fixture '.wp_generate_uuid4(),'post_content'=>'Temporary fixture for snapshot SEO validation.'),true);
    if(is_wp_error($created)) throw new RuntimeException($created->get_error_message());
    $id=(int)$created; $relative=get_post_meta($id,Rockpack_Static_SSG::META_KEY,true);
    if (!$relative) throw new RuntimeException('Fixture snapshot was not created.');
    $_POST=array('portable_seo_nonce'=>wp_create_nonce('portable_seo_'.$id),'portable_seo_title'=>'Fixture SEO title','portable_seo_description'=>'Fixture description','portable_seo_image'=>'','portable_seo_noindex'=>'1');
    $updated=wp_update_post(array('ID'=>$id),true); if(is_wp_error($updated)) throw new RuntimeException($updated->get_error_message());
    $html=file_get_contents(WP_CONTENT_DIR.'/static-site/'.$relative);
    if (!$html || false===strpos($html,'Fixture SEO title') || !preg_match('/<meta name=["\x27]robots["\x27][^>]*noindex/',$html)) throw new RuntimeException('Snapshot does not contain the saved title and noindex.');
    $xml=simplexml_load_file(WP_CONTENT_DIR.'/static-site/sitemap.xml');
    foreach($xml->url as $entry) if ((string)$entry->loc===get_permalink($id)) throw new RuntimeException('Noindex post remains in the generated sitemap.');
    echo "PASS: SEO editor save refreshes the snapshot and excludes noindex content from the static sitemap.\n";
} catch(Throwable $e) { $failed=true; fwrite(STDERR,'FAIL '.$e->getMessage()."\n"); }
finally {
    $_POST=array();
    if($id) { $deleted=wp_delete_post($id,true); if(!$deleted) {fwrite(STDERR,"FAIL: Fixture cleanup failed for ID $id.\n"); $failed=true;} }
    $result=Rockpack_Static_SSG::generate_seo_files(); if(is_wp_error($result)) {fwrite(STDERR,"FAIL: Could not restore sitemap: ".$result->get_error_message()."\n"); $failed=true;}
}
exit($failed?1:0);
