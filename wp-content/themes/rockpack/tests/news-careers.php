<?php
/** Local integration checks: fixture content and metadata are rolled back. */
if ( PHP_SAPI !== 'cli' ) { http_response_code(403); exit; }
$_SERVER['REQUEST_SCHEME']='http'; $_SERVER['HTTP_HOST']=$_SERVER['SERVER_NAME']='figma-htmx-site.test';
require dirname(__DIR__,4).'/wp-load.php';
if ( ! str_ends_with((string)wp_parse_url(home_url(),PHP_URL_HOST),'.test') ) exit('Local .test site required.');
global $wpdb;
foreach ( array($wpdb->posts,$wpdb->postmeta,$wpdb->options) as $table ) {
    $status=$wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s',$wpdb->esc_like($table)));
    if ( ! $status || 'InnoDB' !== $status->Engine ) exit('InnoDB rollback support required.');
}
// Do not generate public snapshots of transaction-only test fixtures.
remove_action('transition_post_status',array('Rockpack_Static_SSG','on_transition_post_status'),10);
remove_action('wp_after_insert_post',array('Rockpack_Static_SSG','on_page_structure_change'),10);
$admin=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
if ( ! $admin ) exit('A local administrator is required.');
$checks=0; $failure=null;
$check=static function($ok,$label)use(&$checks){ if(!$ok) throw new RuntimeException($label); $checks++; echo 'PASS '.$label.PHP_EOL; };
$render=static function($args,$file){
    global $wp_query,$wp_the_query,$post;
    $wp_query=new WP_Query(); $wp_the_query=$wp_query; $wp_query->query($args);
    $post=$wp_query->post;
    ob_start(); include get_template_directory().'/'.$file; return ob_get_clean();
};
$wpdb->query('START TRANSACTION');
try {
    wp_set_current_user($admin[0]);
    $news_ids=array();
    for($i=1;$i<=11;$i++) $news_ids[]=wp_insert_post(array('post_type'=>'post','post_status'=>'publish','post_title'=>'Dynamic news fixture '.$i,'post_content'=>'Published news body '.$i,'post_date'=>'2000-01-'.str_pad((string)$i,2,'0',STR_PAD_LEFT).' 12:00:00'));
    $draft_news=wp_insert_post(array('post_type'=>'post','post_status'=>'draft','post_title'=>'Draft news fixture','post_content'=>'Draft news secret'));
    $job=wp_insert_post(array('post_type'=>'rp_job','post_status'=>'publish','post_title'=>'Packaging engineer fixture','post_content'=>'Job requirements fixture'));
    $closed=wp_insert_post(array('post_type'=>'rp_job','post_status'=>'publish','post_title'=>'Closed role fixture'));
    update_post_meta($closed,'_rp_job_status','closed');
    $draft=wp_insert_post(array('post_type'=>'rp_job','post_status'=>'draft','post_title'=>'Draft role fixture'));
    $protected=wp_insert_post(array('post_type'=>'rp_job','post_status'=>'publish','post_title'=>'Protected role fixture','post_password'=>'fixture-password'));
    update_post_meta($protected,'_rp_job_location','Protected location secret');
    $submit=static function($id,$fields,$nonce=true){
        $_POST=wp_slash(array('rockpack_job_nonce'=>$nonce?wp_create_nonce('rockpack_job_'.$id):'invalid','rp_job'=>$fields));
        do_action('save_post_rp_job',$id,get_post($id),true);
    };
    $submit($job,array('location'=>'Cairo','employment'=>'Full-time','status'=>'open','application'=>''));
    $check('Cairo'===get_post_meta($job,'_rp_job_location',true),'job details save through nonce-protected editor handler');
    $check(str_contains(rockpack_job_application_url($job),'job='.$job),'default application destination includes the job ID');
    $_GET['job']=$job;
    $check(str_contains(rockpack_job_enquiry_message(),'Packaging engineer fixture'),'Contact Us message identifies the selected job');
    $_GET['job']=$draft;
    $check(''===rockpack_job_enquiry_message(),'draft job cannot populate a public enquiry');
    $_GET['job']=$closed;
    $check(''===rockpack_job_enquiry_message(),'closed job cannot populate a public enquiry');
    $_GET=array();
    $submit($job,array('location'=>'Unauthorized','employment'=>'Contract','status'=>'closed','application'=>''),false);
    $check('Cairo'===get_post_meta($job,'_rp_job_location',true),'invalid job nonce cannot change metadata');
    $submit($job,array('location'=>'Wrong','employment'=>'Full-time','status'=>'open','application'=>'javascript:alert(1)'));
    $check('Cairo'===get_post_meta($job,'_rp_job_location',true),'unsafe application link rejects the complete metadata patch');
    $check(is_wp_error(rockpack_sanitize_job_fields(array('application'=>'mailto:invalid'))),'invalid application email is rejected');
    $check(is_wp_error(rockpack_sanitize_job_fields(array('status'=>'unknown'))),'invalid job status is rejected');
    $check(!is_wp_error(rockpack_sanitize_job_fields(array('application'=>'mailto:jobs@example.com'))),'valid email application link is supported');
    $submit($job,array('location'=>'Cairo','employment'=>'Full-time','status'=>'open','application'=>'https://example.com/apply'));
    $check('https://example.com/apply'===rockpack_job_application_url($job),'configured application URL is used');
    wp_set_current_user(0);
    $submit($job,array('location'=>'Anonymous','employment'=>'Contract','status'=>'closed','application'=>''));
    $check('Cairo'===get_post_meta($job,'_rp_job_location',true),'anonymous metadata updates are rejected');
    $news=array_column(rockpack_latest_news(100),'ID');
    $check(in_array($news_ids[0],$news,true)&&!in_array($draft_news,$news,true)&&!in_array(rockpack_sample_news_id(),$news,true),'news uses real published posts and excludes draft/sample content');
    $jobs=array_column(rockpack_latest_jobs(100),'ID');
    $check(in_array($job,$jobs,true)&&!in_array($closed,$jobs,true)&&!in_array($draft,$jobs,true)&&!in_array($protected,$jobs,true),'job previews contain only published open public roles');
    $news_html=$render(array('post_type'=>'post','post__in'=>$news_ids,'paged'=>1),'home.php');
    $check(str_contains($news_html,'Dynamic news fixture')&&str_contains($news_html,'rockpack-content-pages-css'),'news archive renders articles with shared site styling');
    $check(2===$GLOBALS['wp_query']->max_num_pages&&str_contains($news_html,'next page-numbers'),'news archive paginates published posts');
    $check(6===$GLOBALS['wp_query']->post_count&&array_slice(array_reverse($news_ids),0,6)===array_column($GLOBALS['wp_query']->posts,'ID'),'news archive shows six articles ordered newest first');
    $check(str_contains($news_html,esc_url(add_query_arg('page',2,rockpack_news_url()))),'news pagination links include the page number in the URL');
    $page_two=apply_filters('request',array('pagename'=>get_page_uri((int)get_option('page_for_posts')),'page'=>'2'));
    $check(2===$page_two['paged']&&!isset($page_two['page']),'News page parameter maps to the native archive query');
    $second=$render(array_merge($page_two,array('post_type'=>'post','post__in'=>$news_ids)),'home.php');
    $check(5===$GLOBALS['wp_query']->post_count&&array_slice(array_reverse($news_ids),6)===array_column($GLOBALS['wp_query']->posts,'ID'),'news second page renders the remaining articles without overlaps');
    $check(add_query_arg('page',2,rockpack_news_url())===apply_filters('redirect_canonical',home_url('/news/page/2/'),add_query_arg('page',2,rockpack_news_url())),'canonical URL retains the News page query parameter');
    $check(str_contains($second,'aria-current="page" class="page-numbers current">2')&&str_contains($second,'prev page-numbers'),'news second page highlights page two and links back');
    $other=array('pagename'=>'contact-us','page'=>'2');
    $check($other===apply_filters('request',$other),'other pages keep their native pagination variables');
    $third=$render(array('post_type'=>'post','post__in'=>$news_ids,'paged'=>3),'home.php');
    $check(0===$GLOBALS['wp_query']->post_count,'out-of-range news pagination returns no articles');
    $article=$render(array('post_type'=>'post','p'=>$news_ids[0]),'single.php');
    $check(str_contains($article,'Published news body 1')&&str_contains($article,'All news'),'news detail page renders the real article body and archive link');
    $archive=$render(array('post_type'=>'rp_job','post__in'=>array($job,$closed,$draft,$protected)),'archive-rp_job.php');
    $check(str_contains($archive,'Packaging engineer fixture')&&!str_contains($archive,'Closed role fixture')&&!str_contains($archive,'Protected role fixture'),'Careers archive applies open/public filters');
    $detail=$render(array('post_type'=>'rp_job','p'=>$job),'single-rp_job.php');
    $check(str_contains($detail,'Job requirements fixture')&&str_contains($detail,'Cairo')&&str_contains($detail,'https://example.com/apply'),'job detail page shows metadata, requirements and application link');
    $closed_html=$render(array('post_type'=>'rp_job','p'=>$closed),'single-rp_job.php');
    $check(str_contains($closed_html,'Applications for this opportunity are closed.')&&!str_contains($closed_html,'class="rp-content-button"'),'closed job detail replaces application button with closed status');
    $protected_html=$render(array('post_type'=>'rp_job','p'=>$protected),'single-rp_job.php');
    $check(str_contains($protected_html,'post_password')&&!str_contains($protected_html,'Protected location secret'),'protected job does not disclose details');
    $search=$render(array('s'=>'role fixture'),'search.php');
    $check(!str_contains($search,'Closed role fixture'),'public search excludes closed opportunities');
    $home=$render(array('page_id'=>(int)get_option('page_on_front')),'front-page.php');
    $latest=rockpack_latest_news(3);
    $home_news=new DOMDocument(); @$home_news->loadHTML('<?xml encoding="UTF-8">'.$home);
    $links=(new DOMXPath($home_news))->query('//section[@id="news"]//h3/a');
    $home_urls=array(); foreach($links as $link) $home_urls[]=$link->getAttribute('href');
    $check(array_map('get_permalink',$latest)===$home_urls&&3===count($home_urls),'homepage displays exactly the three newest articles in date order');
    $check(str_contains($home,'Packaging engineer fixture')&&str_contains($home,esc_html(get_the_title($latest[0]))),'homepage automatically displays published news and opportunities');
    $check(!str_contains($home,'Closed role fixture')&&!str_contains($home,'Draft role fixture'),'homepage excludes closed and draft jobs');
    $empty=$render(array('post_type'=>'rp_job','post__in'=>array($closed)),'archive-rp_job.php');
    $check(str_contains($empty,'No open opportunities right now'),'Careers empty state is truthful');
    $check(str_contains(rockpack_news_url(),'/news/')&&str_contains(rockpack_careers_url(),'/careers/'),'public listing destinations are configured');
    $check(!apply_filters('rockpack_static_ssg_is_public_content',true,get_post((int)get_option('page_for_posts'))),'news archive remains dynamic instead of becoming a stale snapshot');
} catch(Throwable $error) { $failure=$error->getMessage(); }
finally {
    $wpdb->query('ROLLBACK'); wp_cache_flush(); $_POST=$_GET=array();
    unset($GLOBALS['rockpack_refresh_content']);
}
echo $checks.' checks passed; fixture content rolled back. No applications or email sent.'.PHP_EOL;
if($failure){fwrite(STDERR,$failure.PHP_EOL);exit(1);}
