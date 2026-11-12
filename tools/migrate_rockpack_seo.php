<?php
/** One-time local Rockpack-to-Portable-SEO migration. Original metadata is retained. */
if (PHP_SAPI !== 'cli') exit(2);
require dirname(__DIR__).'/wp-load.php';
if (!class_exists('Portable_SEO') || !Portable_SEO::enabled() || !str_ends_with((string)wp_parse_url(home_url(),PHP_URL_HOST),'.test') || 'rockpack' !== get_template()) exit("Active Rockpack and Portable SEO on a local .test installation required.\n");
global $wpdb, $wp_query;
foreach (array($wpdb->posts,$wpdb->postmeta,$wpdb->options) as $table) { $status=$wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s',$wpdb->esc_like($table))); if (!$status || 'InnoDB'!==$status->Engine) exit("InnoDB required.\n"); }
$wpdb->query('START TRANSACTION');
try {
    $map=array('_rp_seo_title'=>'title','_rp_seo_description'=>'description','_rp_seo_og_image'=>'image','_rp_seo_noindex'=>'noindex'); $copied=0;
    foreach ($map as $old=>$key) {
        $ids=$wpdb->get_col($wpdb->prepare("SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s",$old));
        foreach ($ids as $id) if (!metadata_exists('post',$id,'_portable_seo_'.$key)) { update_post_meta($id,'_portable_seo_'.$key,get_post_meta($id,$old,true)); $copied++; }
    }
    // Preserve existing page-specific theme defaults if no editorial override exists.
    $previous=$wp_query;
    foreach (array('about-us','history','certifications','planet','product-portfolio','contact-us') as $slug) {
        $page=get_page_by_path($slug,OBJECT,'page'); if (!$page || 'publish'!==$page->post_status) continue;
        $wp_query=new WP_Query(array('page_id'=>$page->ID));
        if (!metadata_exists('post',$page->ID,'_portable_seo_title')) { $title=apply_filters('portable_seo_document_title',''); if($title) {update_post_meta($page->ID,'_portable_seo_title',$title);$copied++;} }
        if (!metadata_exists('post',$page->ID,'_portable_seo_description')) { $desc=apply_filters('portable_seo_section_description','',$page->ID); if($desc) {update_post_meta($page->ID,'_portable_seo_description',$desc);$copied++;} }
    }
    $wp_query=$previous;
    $front=(int)get_option('page_on_front'); $front_desc=rp_option('seo_home_description','');
    if ($front && $front_desc && !metadata_exists('post',$front,'_portable_seo_description')) { update_post_meta($front,'_portable_seo_description',$front_desc); $copied++; }
    if (false===get_option(Portable_SEO::OPTION,false)) {
        $settings=array('home_title'=>apply_filters('portable_seo_default_home_title',''),'description'=>apply_filters('portable_seo_default_description',get_bloginfo('description')),'image'=>apply_filters('portable_seo_default_image',''),'organization_name'=>get_bloginfo('name'),'organization_logo'=>apply_filters('portable_seo_default_organization_logo',''),'organization_phone'=>rp_option('contact_phone',''),'organization_email'=>rp_option('contact_email',''),'organization_address'=>rp_option('contact_address',''),'socials'=>apply_filters('portable_seo_default_socials',''));
        update_option(Portable_SEO::OPTION,Portable_SEO::sanitize_settings($settings));
    } else {
        $settings=get_option(Portable_SEO::OPTION,array());
        foreach(array('organization_phone'=>'contact_phone','organization_email'=>'contact_email','organization_address'=>'contact_address') as $key=>$source) if (!array_key_exists($key,$settings)) $settings[$key]=rp_option($source,'');
        update_option(Portable_SEO::OPTION,Portable_SEO::sanitize_settings($settings));
    }
    if($wpdb->last_error) throw new RuntimeException($wpdb->last_error);
    $wpdb->query('COMMIT');
    if(class_exists('Rockpack_Local_Page_Cache')) Rockpack_Local_Page_Cache::flush();
    echo "Copied $copied SEO values. Existing Portable SEO values and all legacy data were preserved.\n";
} catch(Throwable $e) { $wpdb->query('ROLLBACK'); fwrite(STDERR,$e->getMessage()."\n"); exit(1); }
