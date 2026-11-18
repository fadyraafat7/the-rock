<?php
/** Native WordPress page meta boxes, repeaters, media controls and scoped saves. */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/editor-schema.php';
require_once __DIR__ . '/editor-extra-fields.php';
require_once __DIR__ . '/editor-layout.php';
require_once __DIR__ . '/section-live-preview.php';

function rockpack_page_sections( $post ) {
    $kind=rockpack_page_kind($post);
    $groups=array('home'=>array('hero','about','history','certifications','products','process','localization','planet','news','careers','cta'),'about-us'=>array('about_page'),'history'=>array('history'),'certifications'=>array('certifications'),'product-portfolio'=>array('portfolio_page','process','localization'),'planet'=>array('planet_page'),'contact-us'=>array('contact_page'));
    if(!$kind) return array();
    $registry=rockpack_home_sections();
    $sections=array();
    // Numbered fields have one editor: their replacement repeater.
    $defaults=rockpack_editor_defaults();
    // Header and footer are site-wide settings. Keeping them out of each page
    // editor prevents an edit made while working on one page from looking like
    // page-specific content.
    foreach($groups[$kind] as $group) {
        foreach($registry[$group]['fields']??array() as $f) {
            $key=$f['key'];
            if ( rockpack_is_fixed_title( $key ) ) continue;
            if(rockpack_is_replaced_field($key)) continue;
            if('history'===$group && ('home'===$kind ? preg_match('/^history_(?:hero_|page_)/',$key) : in_array($key,array('history_home_heading','history_learn_text','history_learn_url'),true))) continue;
            if('certifications'===$group && ('home'===$kind ? str_starts_with($key,'certs_page_') : !str_starts_with($key,'certs_page_'))) continue;
            $f['default']=$f['default']??$defaults[$key]??'';
            if(preg_match('/(?:image|_bg)$/',$key) && 'url'===$f['type']) $f['type']='image';
            $section=$group;
            if(in_array($group,array('about_page','portfolio_page','planet_page','contact_page'),true)) {
                $section=$group.' / '.(str_contains($f['label'],' - ')?explode(' - ',$f['label'])[0]:(preg_match('/hero|title$/i',$f['label'])?'Hero and introduction':'Page content'));
            }
            $sections[$section]['fields'][$key]=$f;
        }
    }
    foreach(rockpack_shared_fields() as $key=>$field) {
        // These values are managed in Appearance → Header & Footer and are
        // deliberately shared by every front-end template.
        if(preg_match('/^(?:nav_|footer_|shared_link_)/',$key)) continue;
        if(isset($field['pages']) && !in_array($kind,$field['pages'],true)) continue;
        $sections[isset($field['pages'])?'Images and descriptions':'Shared navigation and footer']['fields'][$key]=$field;
    }
    foreach(rockpack_repeaters() as $key=>$schema) {
        // Shared header/footer link collections are maintained exclusively in
        // Appearance → Header & Footer, never in an individual page editor.
        if(in_array($key,array('navigation_groups','footer_page_links','footer_service_links','footer_social'),true)) continue;
        // Keep the repeatable hero backgrounds with the rest of the Hero
        // controls instead of presenting them as a separate gallery section.
        if('home'===$kind && 'hero_images'===$key) {
            $sections['hero']['repeaters'][]=$key;
            continue;
        }
        if(in_array($kind,$schema['pages'],true)) $sections[$schema['label']]['repeater']=$key;
    }
    return rockpack_order_page_sections( $kind, $sections );
}

function rockpack_shared_fields() {
    return function_exists('rockpack_extra_fields') ? rockpack_extra_fields() : array();
}

function rockpack_editor_input( $field, $value, $name, $values=array() ) {
    $id=wp_unique_id('rp-field-'); $type=$field['type']; $key=$field['key'];
    echo '<div class="rp-field" data-key="'.esc_attr($key).'">';
    if('hidden'!==$type) echo '<label for="'.esc_attr($id).'">'.esc_html($field['label']).'</label>';
    if('repeater'===$type) {
        rockpack_editor_repeater($field,is_array($value)?$value:array(),$name);
    } elseif('textarea'===$type) {
        echo '<textarea id="'.esc_attr($id).'" name="'.esc_attr($name).'" rows="3" class="large-text">'.esc_textarea($value).'</textarea>';
    } elseif('select'===$type) {
        echo '<select id="'.esc_attr($id).'" name="'.esc_attr($name).'">';
        foreach($field['choices'] as $v=>$label) echo '<option value="'.esc_attr($v).'" '.selected($value,$v,false).'>'.esc_html($label).'</option>';
        echo '</select>';
    } else {
        $html_type=in_array($type,array('email','number','hidden'),true)?$type:'text'; // Text permits relative and fragment URLs.
        echo '<input id="'.esc_attr($id).'" type="'.esc_attr($html_type).'" name="'.esc_attr($name).'" value="'.esc_attr($value).'" class="large-text"'.('image'===$type?' data-rp-image':'').'>';
        if('image'===$type) {
            echo '<div class="rp-media-actions"><button type="button" class="button rp-media">Select image</button> <button type="button" class="button rp-media-clear">Clear image</button></div>';
            echo '<img class="rp-media-preview"'.($value?' src="'.esc_url($value).'"':' hidden').' alt="">';
        }
    }
    echo '</div>';
}

function rockpack_editor_repeater( $schema, $rows, $prefix ) {
    echo '<div class="rp-repeater" data-prefix="'.esc_attr($prefix).'"><div class="rp-items">';
    foreach($rows as $i=>$row) rockpack_editor_row($schema,$row,$prefix.'['.$i.']');
    echo '</div><template>';
    rockpack_editor_row($schema,array(),$prefix.'[0]');
    echo '</template><button type="button" class="button rp-add">Add Item</button><span class="rp-repeater-status screen-reader-text" aria-live="polite"></span></div>';
}

function rockpack_editor_row( $schema, $row, $prefix ) {
    echo '<div class="rp-item"><div class="rp-item-toolbar"><strong>Item</strong><button type="button" class="button rp-up" aria-label="Move item up">Move up</button><button type="button" class="button rp-down" aria-label="Move item down">Move down</button><button type="button" class="button rp-remove">Remove</button></div><div class="rp-fields">';
    if(!empty($schema['cpt'])) rockpack_editor_input(array('key'=>'id','label'=>'Record ID','type'=>'hidden'),$row['id']??0,$prefix.'[id]');
    foreach($schema['fields'] as $key=>$field) rockpack_editor_input($field,$row[$key]??('select'===$field['type']?array_key_first($field['choices']):''),$prefix.'['.$key.']',$row);
    echo '</div></div>';
}

function rockpack_editor_section_preview( $title ) {
    $slug = sanitize_title( $title );
    $dir  = get_template_directory() . '/assets/images/section-previews/';
    $uri  = get_template_directory_uri() . '/assets/images/section-previews/';
    foreach ( array( 'jpg', 'jpeg', 'png', 'webp' ) as $ext ) {
        if ( file_exists( $dir . $slug . '.' . $ext ) ) {
            echo '<div class="rp-section-preview" data-slug="' . esc_attr( $slug ) . '">';
            echo '<img src="' . esc_url( $uri . $slug . '.' . $ext ) . '" alt="' . esc_attr( $title ) . '" class="rp-section-preview-img">';
            echo '<div class="rp-section-preview-actions">';
            echo '<label class="button rp-section-preview-edit">Replace<input type="file" accept="image/*" hidden></label>';
            echo '<button type="button" class="button rp-section-preview-delete">Delete</button>';
            echo '</div>';
            echo '</div>';
            return;
        }
    }
    echo '<div class="rp-section-preview rp-section-preview--empty" data-slug="' . esc_attr( $slug ) . '">';
    echo '<p class="rp-section-preview-missing">No preview image for <code>' . esc_html( $slug ) . '</code></p>';
    echo '<label class="button rp-section-preview-edit">Add image<input type="file" accept="image/*" hidden></label>';
    echo '</div>';
}

add_action('add_meta_boxes_page',function($post){
    $sections=rockpack_page_sections($post);
    $kind=rockpack_page_kind($post);
    if(!$sections) return;
    add_meta_box('rockpack-editor-help','Rockpack page content',function($post){
        wp_nonce_field('rockpack_page_'.$post->ID,'rockpack_page_nonce');
        echo '<p>Edit the sections below, then use <strong>Update / Publish</strong> to save. Shared sections update every page that uses them. Remove an item to hide it; removed CPT records go to Trash and can be restored from their admin menu. Blank optional fields stay blank.</p><p>Page and section headings are fixed in the theme. Card titles and other content remain editable. The ordinary editor body is not used by this page design. Existing SEO plugins continue to manage SEO.</p>';
    },'page','normal','high');
    foreach($sections as $title=>$section) add_meta_box('rockpack-'.sanitize_title($title),ucwords(str_replace('_',' ',$title)),function($post)use($section,$kind,$title){
        rockpack_editor_live_preview( $post, $title );
        if ( 'contact-us' === $kind && 'Contact form' === $title ) {
            echo '<p>Manage form fields, validation messages and email delivery in <a href="' . esc_url(admin_url('admin.php?page=wpcf7')) . '">Contact &gt; Contact Forms</a>. Select the form used on this page below.</p>';
        }
        if ( 'home' === $kind && in_array($title,array('News','Careers'),true) ) {
            $jobs = 'Careers' === $title;
            echo '<p><a class="button" href="' . esc_url(admin_url($jobs ? 'edit.php?post_type=rp_job' : 'edit.php')) . '">' . esc_html($jobs ? __('Manage job opportunities','rockpack') : __('Manage news posts','rockpack')) . '</a></p><p>' . esc_html__('Published entries appear here automatically.','rockpack') . '</p>';
        }
        foreach($section['fields']??array() as $key=>$field) {
            rockpack_editor_input($field,rp_option($key,$field['default']),'rp_content['.$key.']');
            if('image'===$field['type']) { $opts=get_option('rockpack_settings',array()); rockpack_editor_input(array('key'=>$key.'_id','label'=>'Attachment ID','type'=>'hidden'),$opts['_media'][$key]??0,'rp_content['.$key.'_id]'); }
        }
        foreach($section['repeaters']??(isset($section['repeater'])?array($section['repeater']):array()) as $key) {
            $schema=rockpack_repeaters()[$key];
            if(!empty($schema['description'])) echo '<p>'.esc_html($schema['description']).'</p>';
            echo '<input type="hidden" name="rp_present['.esc_attr($key).']" value="1">';
            echo '<input type="hidden" name="rp_versions['.esc_attr($key).']" value="'.esc_attr(rockpack_collection_version($key)).'">';
            rockpack_editor_repeater($schema,rockpack_collection($key,'certs'===$key && 'certifications'===$kind?'certifications':''),'rp_lists['.$key.']');
        }
    },'page','normal','default');
});

add_action('admin_enqueue_scripts',function($hook){
    if(!in_array($hook,array('post.php','post-new.php'),true)) return;
    $screen=get_current_screen();
    if(!$screen || 'page'!==$screen->post_type || !rockpack_page_kind(get_post())) return;
    wp_enqueue_media();
    wp_enqueue_script('rockpack-admin-htmx', get_template_directory_uri().'/assets/vendor/htmx/htmx.min.js', array(), '2.0.11', true);
    foreach(array('js','css') as $extension) {
        $path='/assets/'.$extension.'/page-editor.'.$extension;
        if('js'===$extension) { wp_enqueue_script('rockpack-page-editor',get_template_directory_uri().$path,array('media-editor'),filemtime(get_template_directory().$path),true); wp_localize_script('rockpack-page-editor','rpPreview',array('ajaxurl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('rp_preview_action'))); }
        else wp_enqueue_style('rockpack-page-editor',get_template_directory_uri().$path,array(),filemtime(get_template_directory().$path));
    }
});

function rockpack_sanitize_editor_field( $value, $field ) {
    if(!is_scalar($value)) return new WP_Error('shape','Invalid value for '.$field['label']);
    $value=(string)$value; $type=$field['type'];
    if ( 'contact_page_map_url' === ( $field['key'] ?? '' ) ) return rockpack_sanitize_contact_map_url( $value );
    if('select'===$type) return isset($field['choices'][$value])?$value:new WP_Error('choice','Choose a valid '.$field['label']);
    if('hidden'===$type || 'number'===$type) return ''===$value?'':absint($value);
    if(in_array($type,array('image','url'),true)) {
        $clean=esc_url_raw($value);
        return ''!==trim($value) && ''===$clean ? new WP_Error('url','Invalid URL in '.$field['label']) : $clean;
    }
    if('email'===$type) return ''!==$value && !is_email($value)?new WP_Error('email','Invalid email in '.$field['label']):sanitize_email($value);
    return 'textarea'===$type?sanitize_textarea_field($value):sanitize_text_field($value);
}

function rockpack_collection_version( $key ) {
    return hash_hmac('sha256',wp_json_encode(rockpack_collection($key)),wp_salt('nonce'));
}

function rockpack_sanitize_editor_rows( $rows, $fields, $depth=0 ) {
    if(!is_array($rows) || count($rows)>150 || $depth>3) return new WP_Error('rows','Invalid repeater or more than 150 items.');
    $clean=array();
    foreach($rows as $row) {
        if(!is_array($row)) return new WP_Error('row','Invalid repeater item.');
        $out=array();
        foreach($fields as $key=>$field) {
            $val=$row[$key]??('repeater'===$field['type']?array():'');
            $out[$key]='repeater'===$field['type']?rockpack_sanitize_editor_rows($val,$field['fields'],$depth+1):rockpack_sanitize_editor_field($val,$field);
            if(is_wp_error($out[$key])) return $out[$key];
            if('image'===$field['type'] && !empty($row[$key.'_id']) && (!wp_attachment_is_image(absint($row[$key.'_id'])) || !current_user_can('upload_files'))) return new WP_Error('image','Select a valid image from the Media Library.');
        }
        if(isset($row['id'])) $out['id']=absint($row['id']);
        $clean[]=$out;
    }
    return $clean;
}

/** Validate the complete request before changing shared data. Unsubmitted groups are preserved. */
function rockpack_save_page_content( $post_id ) {
    if(wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post',$post_id)) return;
    if(!isset($_POST['rockpack_page_nonce']) || !is_string($_POST['rockpack_page_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rockpack_page_nonce'])),'rockpack_page_'.$post_id)) return;
    $sections=rockpack_page_sections($post_id);
    if(!$sections) return;
    $input=wp_unslash($_POST); $patch=array(); $media=array(); $lists=array();
    foreach(array('rp_content','rp_lists','rp_present','rp_versions') as $group) if(isset($input[$group]) && !is_array($input[$group])) return rockpack_editor_error('Invalid content fields.');
    if(isset($input['rp_content']) && !is_array($input['rp_content'])) return rockpack_editor_error('Invalid content fields.');
    foreach($sections as $section) foreach($section['fields']??array() as $key=>$field) {
        if(!array_key_exists($key,$input['rp_content']??array())) continue;
        $val=rockpack_sanitize_editor_field($input['rp_content'][$key],$field);
        if(is_wp_error($val)) return rockpack_editor_error($val->get_error_message());
        $patch[$key]=$val;
        if('image'===$field['type']) {
            $id=absint($input['rp_content'][$key.'_id']??0);
            if($id && (!wp_attachment_is_image($id) || !current_user_can('upload_files'))) return rockpack_editor_error('Select a valid image.');
            $media[$key]=$id;
        }
    }
    foreach($sections as $section) foreach($section['repeaters']??(isset($section['repeater'])?array($section['repeater']):array()) as $key) {
        if(empty($input['rp_present'][$key])) continue;
        if(isset($input['rp_versions'][$key]) && (!is_string($input['rp_versions'][$key]) || !hash_equals(rockpack_collection_version($key),$input['rp_versions'][$key]))) return rockpack_editor_error('A shared section changed after you opened this editor. Reload the page before saving.');
        $schema=rockpack_repeaters()[$key];
        $rows=rockpack_sanitize_editor_rows($input['rp_lists'][$key]??array(),$schema['fields']);
        if(is_wp_error($rows)) return rockpack_editor_error($rows->get_error_message());
        if(!empty($schema['cpt'])) {
            $type=get_post_type_object($schema['cpt']);
            if(!current_user_can($type->cap->create_posts) || !current_user_can($type->cap->publish_posts)) return rockpack_editor_error('You cannot manage shared items.');
            $seen=array();
            foreach($rows as $row) if(''===trim($row['title'])) return rockpack_editor_error('Every item in '.$schema['label'].' needs a title.');
            foreach($rows as $row) if(!empty($row['id'])) {
                if(isset($seen[$row['id']]) || get_post_type($row['id'])!==$schema['cpt'] || 'publish'!==get_post_status($row['id']) || !current_user_can('edit_post',$row['id'])) return rockpack_editor_error('An item changed or cannot be edited. Reload this page.');
                $seen[$row['id']]=true;
            }
            foreach(rockpack_collection($key) as $existing) if(!empty($existing['id']) && !isset($seen[$existing['id']]) && !current_user_can('delete_post',$existing['id'])) return rockpack_editor_error('You cannot remove this shared item.');
        }
        $lists[$key]=$rows;
    }
    // The final marker guards PHP max_input_vars truncation, including nested repeaters.
    if(empty($input['rockpack_complete'])) return rockpack_editor_error('The form was incomplete. No Rockpack content was saved. Reduce items or ask your host to increase max_input_vars.');
    $opts=get_option('rockpack_settings',array()); if(!is_array($opts)) $opts=array();
    foreach($lists as $key=>$rows) {
        $schema=rockpack_repeaters()[$key];
        if(empty($schema['cpt'])) { $opts['_repeaters'][$key]=$rows; continue; }
        $kept=array(); $existing=rockpack_collection($key);
        foreach($rows as $i=>$row) {
            $post=array('post_type'=>$schema['cpt'],'post_status'=>'publish','post_title'=>$row['title'],'menu_order'=>$i);
            if(!empty($row['id'])) $post['ID']=$row['id'];
            $id=wp_insert_post(wp_slash($post),true);
            if(is_wp_error($id)) return rockpack_editor_error('Could not save an item: '.$id->get_error_message());
            $kept[]=$id;
            foreach($schema['fields'] as $f=>$field) if('title'!==$f) update_post_meta($id,rockpack_cpt_meta_map($schema['cpt'])[$f]??'rp_item_'.$f,wp_slash($row[$f]??''));
            if(!empty($row['image_id'])) set_post_thumbnail($id,$row['image_id']); else delete_post_thumbnail($id);
        }
        foreach($existing as $row) if(!empty($row['id']) && !in_array($row['id'],$kept,true)) wp_trash_post($row['id']);
        $opts['_collections'][$key]=true;
    }
    $opts=array_replace($opts,$patch);
    $opts['_media']=array_replace($opts['_media']??array(),$media);
    // Track intentional empty values separately from legacy blank-means-default settings.
    foreach($patch as $key=>$value) $opts['_explicit'][$key]=true;
    update_option('rockpack_settings',$opts);
    do_action('rockpack_content_saved',$post_id);
}
add_action('save_post_page','rockpack_save_page_content',20);

function rockpack_editor_error( $message ) {
    set_transient('rockpack_editor_error_'.get_current_user_id(),$message,120);
}
add_action('admin_notices',function(){
    $key='rockpack_editor_error_'.get_current_user_id(); $error=get_transient($key);
    if($error) { delete_transient($key); echo '<div class="notice notice-error"><p>Rockpack content: '.esc_html($error).'</p></div>'; }
});
add_action('edit_form_after_editor',function($post){ if(rockpack_page_kind($post)) echo '<p class="description">Use the Rockpack content sections below to edit this page.</p>'; });
// Both editors submit this last meta box field with their native save operation.
add_action('add_meta_boxes_page',function($post){ if(rockpack_page_kind($post)) add_meta_box('rockpack-complete','Rockpack save status',function(){ echo '<input type="hidden" name="rockpack_complete" value="1"><p>Content changes save with Update / Publish. Media selection and repeaters require JavaScript.</p>'; },'page','normal','low'); },100);

add_action('init',function(){
    foreach(rockpack_repeaters() as $schema) if(!empty($schema['cpt'])) foreach($schema['fields'] as $key=>$field) {
        if('title'===$key) continue;
        register_post_meta($schema['cpt'],rockpack_cpt_meta_map($schema['cpt'])[$key]??'rp_item_'.$key,array('single'=>true,'type'=>'string','show_in_rest'=>false,'sanitize_callback'=>function($value)use($field){$clean=rockpack_sanitize_editor_field($value,$field);return is_wp_error($clean)?'':$clean;},'auth_callback'=>function($allowed,$meta,$post_id){return current_user_can('edit_post',$post_id);}));
    }
});

/** Refresh dependencies after all options and nested CPT records have finished saving. */
function rockpack_queue_content_refresh() { $GLOBALS['rockpack_refresh_content']=true; }
add_action('rockpack_content_saved','rockpack_queue_content_refresh');
add_action('updated_option',function($key){if('rockpack_settings'===$key) rockpack_queue_content_refresh();});
add_action('added_option',function($key){if('rockpack_settings'===$key) rockpack_queue_content_refresh();});
add_action('save_post',function($id){if(in_array(get_post_type($id),array('rp_stat','rp_history','rp_cert','rp_product','rp_logo','rp_job'),true)) rockpack_queue_content_refresh();},100);
add_action('trashed_post','rockpack_queue_content_refresh');
add_action('untrashed_post','rockpack_queue_content_refresh');
add_action('deleted_post',function($id,$post){if(in_array($post->post_type,array('rp_stat','rp_history','rp_cert','rp_product','rp_logo','rp_job'),true)) rockpack_queue_content_refresh();},10,2);
add_action('wp_ajax_rp_preview_delete', function() {
    check_ajax_referer('rp_preview_action','nonce');
    if(!current_user_can('edit_theme_options')) wp_send_json_error('Unauthorized');
    $slug = sanitize_file_name(wp_unslash($_POST['slug']??''));
    if(!$slug) wp_send_json_error('Invalid slug');
    $dir = get_template_directory() . '/assets/images/section-previews/';
    foreach(array('jpg','jpeg','png','webp') as $ext) {
        $file = $dir . $slug . '.' . $ext;
        if(file_exists($file)) { wp_delete_file($file); wp_send_json_success(); }
    }
    wp_send_json_error('File not found');
});

add_action('wp_ajax_rp_preview_upload', function() {
    check_ajax_referer('rp_preview_action','nonce');
    if(!current_user_can('edit_theme_options')) wp_send_json_error('Unauthorized');
    $slug = sanitize_file_name(wp_unslash($_POST['slug']??''));
    if(!$slug || empty($_FILES['file'])) wp_send_json_error('Invalid request');
    $file = $_FILES['file'];
    $allowed = array('image/jpeg','image/png','image/webp','image/gif');
    if(!in_array($file['type'],$allowed,true)) wp_send_json_error('Invalid file type');
    $dir = get_template_directory() . '/assets/images/section-previews/';
    $ext = pathinfo($file['name'],PATHINFO_EXTENSION);
    $dest = $dir . $slug . '.' . strtolower($ext);
    foreach(array('jpg','jpeg','png','webp') as $old_ext) { $old = $dir.$slug.'.'.$old_ext; if(file_exists($old) && $old !== $dest) wp_delete_file($old); }
    if(!move_uploaded_file($file['tmp_name'],$dest)) wp_send_json_error('Upload failed');
    wp_send_json_success(array('url'=>get_template_directory_uri().'/assets/images/section-previews/'.$slug.'.'.strtolower($ext)));
});

add_filter('rockpack_static_ssg_is_public_content',function($public,$post){
    return $public && 'contact-us'!==rockpack_page_kind($post) && (int)$post->ID !== (int)get_option('page_for_posts');
},10,2);
add_action('shutdown',function(){
    if(empty($GLOBALS['rockpack_refresh_content'])) return;
    if(class_exists('Rockpack_Local_Page_Cache')) Rockpack_Local_Page_Cache::flush();
    if(!class_exists('Rockpack_Static_SSG')) return;
    foreach(get_pages(array('post_status'=>'publish')) as $page) if(rockpack_page_kind($page) && 'contact-us'!==rockpack_page_kind($page)) Rockpack_Static_SSG::generate_scheduled_post($page->ID);
},5);
