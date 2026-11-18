<?php
/** Page editing schema. Existing options and CPTs remain the sources of truth. */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/editor-defaults.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/navigation-defaults.php';

function rockpack_page_kind( $post ) {
    $post = get_post( $post );
    if ( ! $post || 'page' !== $post->post_type ) return '';
    if ( (int) get_option( 'page_on_front' ) === $post->ID ) return 'home';
    $kinds = array( 'about-us', 'history', 'certifications', 'product-portfolio', 'planet', 'contact-us' );
    // Match WordPress's assigned-template, then slug-template precedence.
    foreach ( $kinds as $kind ) if ( 'page-' . $kind . '.php' === get_page_template_slug( $post ) ) return $kind;
    if ( in_array( $post->post_name, $kinds, true ) ) return $post->post_name;
    return '';
}

function rockpack_item_fields( $definitions ) {
    $fields = array();
    foreach ( $definitions as $key => $definition ) {
        $fields[$key] = array( 'key' => $key, 'label' => $definition[0], 'type' => $definition[1] ?? 'text' );
        if ( 'image' === $fields[$key]['type'] ) $fields[$key . '_id'] = array( 'key' => $key . '_id', 'label' => 'Attachment ID', 'type' => 'hidden' );
        if ( isset( $definition[2] ) ) $fields[$key]['choices'] = $definition[2];
    }
    return $fields;
}

/** Repeater controls replace these old scalar inputs; values remain fallback data. */
function rockpack_is_replaced_field( $key ) {
    return (bool) preg_match( '/^(hero_(?:eyebrow|title|desc|btn_text|btn_url|bg_image(?:_[23])?)|about_bullet[12]|about_tab[12](?:_label)?|about_page_(?:bullet_[12]|tab_[12]_(?:label|text)|foundation_label_\d+|partnership_step_\d+|growth_card_\d+)|certs_page_step_\d+|portfolio_page_step_\d+_(?:title|text)|planet_page_(?:(?:environmental|social|governance)(?:_title|_\d+))|process_(?:card[12]_(?:label|text|image)|year_(?:number|label))|local_(?:card[12]_(?:label|text)|image)|planet_item[12]_(?:title|text)|news_article_\d+_\w+|products_image_\d+|partner_image_\d+|history_milestone_image_\d+)$/', $key );
}

function rockpack_repeaters() {
    $text = rockpack_item_fields( array( 'text' => array( 'Text', 'textarea' ) ) );
    $cards = rockpack_item_fields( array( 'title' => array('Title'), 'text' => array('Description','textarea'), 'image' => array('Image','image'), 'alt' => array('Image description'), 'url' => array('Link','url') ) );
    $icons = rockpack_item_fields( array( 'text' => array('Text','textarea'), 'image' => array('Icon','image') ) );
    $tabs = rockpack_item_fields( array( 'title' => array('Tab label'), 'text' => array('Tab content','textarea') ) );
    $all_pages=array('home','about-us','history','certifications','product-portfolio','planet','contact-us');
    $links=rockpack_item_fields(array('title'=>array('Label'),'url'=>array('Destination','url')));
    $repeaters = array(
        'navigation_groups'=>array('label'=>'Shared navigation dropdowns','pages'=>$all_pages,'fields'=>array_merge(rockpack_item_fields(array('title'=>array('Dropdown label'),'menu_key'=>array('Dropdown appearance','select',array('company'=>'Company','products'=>'Products','planet'=>'Planet')))),array('items'=>array('key'=>'items','label'=>'Links','type'=>'repeater','fields'=>$links)))),
        'footer_page_links'=>array('label'=>'Shared footer page links','pages'=>$all_pages,'fields'=>$links),
        'footer_service_links'=>array('label'=>'Shared footer service links','pages'=>$all_pages,'fields'=>$links),
        'hero_images' => array('label'=>'Hero slides','description'=>'Add, remove, and reorder slides; the first slide is shown initially. The heading is fixed in the theme.','pages'=>array('home'),'fields'=>rockpack_item_fields(array('image'=>array('Background image','image'),'eyebrow'=>array('Eyebrow text'),'description'=>array('Description','textarea'),'button_text'=>array('Button text'),'button_url'=>array('Button URL','url')))),
        'home_bullets' => array('label'=>'Introduction bullets','pages'=>array('home'),'fields'=>$text),
        'home_tabs' => array('label'=>'Capability tabs','pages'=>array('home'),'fields'=>$tabs),
        'about_bullets' => array('label'=>'Introduction bullets','pages'=>array('about-us'),'fields'=>$text),
        'about_tabs' => array('label'=>'Capability tabs','pages'=>array('about-us'),'fields'=>$tabs),
        'foundation_features' => array('label'=>'Technical foundation features','pages'=>array('about-us'),'fields'=>$icons),
        'partnership_points' => array('label'=>'Partnership points','pages'=>array('about-us'),'fields'=>$icons),
        'growth_cards' => array('label'=>'Regional growth cards','pages'=>array('about-us'),'fields'=>$icons),
        'quality_steps' => array('label'=>'Quality Assurance steps','pages'=>array('certifications'),'fields'=>$text),
        'material_steps' => array('label'=>'Process and materials steps','pages'=>array('product-portfolio'),'fields'=>rockpack_item_fields(array('title'=>array('Title'),'text'=>array('Description','textarea')))),
        'process_cards' => array('label'=>'Process cards (shared with Home and Portfolio)','pages'=>array('home','product-portfolio'),'fields'=>rockpack_item_fields(array('title'=>array('Title'),'text'=>array('Description','textarea'),'image'=>array('Image','image'),'number'=>array('Statistic'),'unit'=>array('Statistic label'),'style'=>array('Card appearance','select',array('white'=>'White / image','green'=>'Green / statistic'))))),
        'local_cards' => array('label'=>'Localization cards (shared with Home and Portfolio)','pages'=>array('home','product-portfolio'),'fields'=>rockpack_item_fields(array('title'=>array('Title'),'text'=>array('Description','textarea'),'image'=>array('Image','image'),'style'=>array('Card appearance','select',array('dark'=>'Dark','light'=>'Light'))))),
        'home_planet_cards' => array('label'=>'Sustainability features','pages'=>array('home'),'fields'=>$cards),
        'esg_pillars' => array('label'=>'ESG pillars and points','pages'=>array('planet'),'fields'=>array_merge(rockpack_item_fields(array('title'=>array('Pillar title'))),array('points'=>array('key'=>'points','label'=>'Points','type'=>'repeater','fields'=>$text)))),
        'footer_social' => array('label'=>'Shared footer social links','pages'=>array('home','about-us','history','certifications','product-portfolio','planet','contact-us'),'fields'=>rockpack_item_fields(array('title'=>array('Label'),'url'=>array('Destination','url'),'icon'=>array('Icon','select',array('facebook'=>'Facebook','linkedin'=>'LinkedIn','instagram'=>'Instagram','youtube'=>'YouTube'))))),
        'stats' => array('label'=>'Statistics (shared)','pages'=>array('home'),'cpt'=>'rp_stat','fields'=>rockpack_item_fields(array('title'=>array('Main label'),'sub'=>array('Sub-label'),'icon'=>array('Icon','select',array('expertise'=>'Expertise','location'=>'Location','markets'=>'Markets','standards'=>'Standards'))))),
        'history' => array('label'=>'History slides (shared)','description'=>'Placement preserves the different Home and History timelines. New slides can appear on both.','pages'=>array('home','history'),'cpt'=>'rp_history','fields'=>rockpack_item_fields(array('title'=>array('Title'),'text'=>array('Description','textarea'),'image'=>array('Milestone image','image'),'icon_image'=>array('Milestone icon','image'),'scope'=>array('Placement','select',array('both'=>'Home and History','home'=>'Home only','history'=>'History only'))))),
        'certs' => array('label'=>'Certifications (shared)','pages'=>array('home','certifications'),'cpt'=>'rp_cert','fields'=>rockpack_item_fields(array('title'=>array('Name'),'image'=>array('Certificate icon','image')))),
        'products' => array('label'=>'Product gallery (shared)','pages'=>array('home','product-portfolio'),'cpt'=>'rp_product','fields'=>rockpack_item_fields(array('title'=>array('Title / image description'),'image'=>array('Product image','image'),'url'=>array('Product link','url')))),
        'logos' => array('label'=>'Partner logos (shared)','pages'=>array('home'),'cpt'=>'rp_logo','fields'=>rockpack_item_fields(array('title'=>array('Partner name'),'image'=>array('Logo','image'),'url'=>array('Partner link','url')))),
    );
    foreach(array('process_cards','local_cards') as $key) $repeaters[$key]['fields']['alt']=array('key'=>'alt','label'=>'Image description','type'=>'text');
    $repeaters['stats']['fields']=array_merge($repeaters['stats']['fields'],rockpack_item_fields(array('image'=>array('Custom icon (optional)','image'))));
    return $repeaters;
}

/** Old numbered fields are used only until the corresponding repeater is saved. */
function rockpack_repeater_defaults( $key ) {
    $asset = get_template_directory_uri() . '/assets/images/';
    $defaults = rockpack_editor_defaults();
    foreach ( rockpack_home_sections() as $section ) foreach ( $section['fields'] ?? array() as $f ) if ( isset($f['default']) ) $defaults[$f['key']] = $f['default'];
    $value = function($k) use ($defaults) { return rp_option($k, $defaults[$k] ?? ''); };
    $items = array();
    switch ($key) {
        case 'navigation_groups':
            foreach(rockpack_navigation_defaults() as $menu_key=>$group) {
                $links=array();
                foreach($group['items'] as $item) {
                    $page=get_page_by_path($item['slug']); $published=$page && 'publish'===$page->post_status;
                    $url=$published?get_permalink($page):home_url($item['fallback']);
                    if(!$published && !empty($item['anchor_page'])) { $anchor=get_page_by_path($item['anchor_page']); if($anchor && 'publish'===$anchor->post_status) $url=get_permalink($anchor).'#'.$item['anchor']; }
                    $links[]=array('title'=>$item['label'],'url'=>rp_option('nav_destination_'.$item['slug'],$url));
                }
                $items[]=array('title'=>$group['label'],'menu_key'=>$menu_key,'items'=>$links);
            }
            break;
        case 'footer_page_links': case 'footer_service_links':
            $links='footer_page_links'===$key?array('Home'=>'/','Company'=>'/about-us/','About Us'=>'/about-us/','History'=>'/history/','Certifications'=>'/certifications/','News'=>'/news/','Careers'=>'/careers/'):array('Products'=>'/product-portfolio/','Planet & Societal Impact'=>'/planet/','Contact Us'=>'/contact-us/');
            foreach($links as $label=>$path) { $legacy='shared_link_'.trim(preg_replace('/[^a-z0-9]+/','_',strtolower($path)),'_'); if('shared_link_'===$legacy) $legacy='shared_link_home'; $label_key='footer_label_'.trim(preg_replace('/[^a-z0-9]+/','_',strtolower($label)),'_'); $items[]=array('title'=>rp_option($label_key,$label),'url'=>rp_option($legacy,home_url($path))); }
            break;
        case 'hero_images':
            foreach (array('hero_bg_image','hero_bg_image_2','hero_bg_image_3') as $k) {
                $items[]=array(
                    'image'=>$value($k),
                    'eyebrow'=>$value('hero_eyebrow'),
                    'heading'=>$value('hero_title'),
                    'description'=>$value('hero_desc'),
                    'button_text'=>$value('hero_btn_text'),
                    'button_url'=>$value('hero_btn_url'),
                );
            }
            break;
        case 'home_bullets': for($i=1;$i<=2;$i++) $items[]=array('text'=>$value('about_bullet'.$i)); break;
        case 'about_bullets': for($i=1;$i<=2;$i++) $items[]=array('text'=>$value('about_page_bullet_'.$i)); break;
        case 'home_tabs': for($i=1;$i<=2;$i++) $items[]=array('title'=>$value('about_tab'.$i.'_label'),'text'=>$value('about_tab'.$i)); break;
        case 'about_tabs': for($i=1;$i<=2;$i++) $items[]=array('title'=>$value('about_page_tab_'.$i.'_label'),'text'=>$value('about_page_tab_'.$i.'_text')); break;
        case 'foundation_features': foreach(array('experience','precision','control','safety','hygiene','performance') as $i=>$icon) $items[]=array('text'=>$value('about_page_foundation_label_'.($i+1)),'image'=>$asset.'about/'.$icon.'.svg'); break;
        case 'partnership_points': foreach(array('goals','strategy','progress') as $i=>$icon) $items[]=array('text'=>$value('about_page_partnership_step_'.($i+1)),'image'=>$asset.'about/'.$icon.'.svg'); break;
        case 'growth_cards': foreach(array('localized','supply','responsive','agility') as $i=>$icon) $items[]=array('text'=>$value('about_page_growth_card_'.($i+1)),'image'=>$asset.'about/'.$icon.'.svg'); break;
        case 'quality_steps': foreach(array('Material selection','Continuous control','Final output') as $i=>$label) $items[]=array('text'=>rp_option('certs_page_step_'.($i+1),$label)); break;
        case 'material_steps': for($i=1;$i<=4;$i++) $items[]=array('title'=>$value('portfolio_page_step_'.$i.'_title'),'text'=>$value('portfolio_page_step_'.$i.'_text')); break;
        case 'esg_pillars': foreach(array('environmental'=>3,'social'=>2,'governance'=>3) as $p=>$count) { $points=array(); for($i=1;$i<=$count;$i++) $points[]=array('text'=>$value('planet_page_'.$p.'_'.$i)); $items[]=array('title'=>$value('planet_page_'.$p.'_title'),'points'=>$points); } break;
        case 'process_cards': $items=array(array('title'=>$value('process_card1_label'),'text'=>$value('process_card1_text'),'image'=>$value('process_card1_image'),'style'=>'white'),array('title'=>$value('process_card2_label'),'text'=>$value('process_card2_text'),'number'=>$value('process_year_number'),'unit'=>$value('process_year_label'),'style'=>'green')); break;
        case 'local_cards': $items=array(array('title'=>$value('local_card1_label'),'text'=>$value('local_card1_text'),'image'=>$asset.'about/progress.svg','style'=>'dark'),array('title'=>$value('local_card2_label'),'text'=>$value('local_card2_text'),'image'=>$value('local_image'),'style'=>'light')); break;
        case 'home_planet_cards': for($i=1;$i<=2;$i++) $items[]=array('title'=>$value('planet_item'.$i.'_title'),'text'=>$value('planet_item'.$i.'_text')); break;
        case 'footer_social': foreach(array('facebook'=>'facebook','linkedin'=>'LinkedIn','instagram'=>'instagram','youtube'=>'youtube') as $icon=>$title) $items[]=array('title'=>rp_option('footer_label_'.strtolower($title),$title),'icon'=>$icon,'url'=>rp_option('footer_social_'.$icon,'#')); break;
        case 'stats': foreach(rp_default_stats() as $row) { $row['title']=$row['label']; unset($row['label']); $items[]=$row; } break;
        case 'history':
            foreach(rp_default_slides() as $row) { $row['scope']='home'; $items[]=$row; }
            $titles=array('Building Expertise','Mastering Production','Advancing Aseptic Technology');
            $texts=array('Our journey began with a strong specialization in color management, high precision printing and packaging production, where consistency and attention to detail were essential.','Over the years, this expertise evolved alongside the shifting needs of the food and beverage industry, where safety, shelf life and scalability became critical success factors.','Recognizing the growing demand for aseptic solutions, The Rock Pack expanded its capabilities to integrate advanced aseptic technology into its production ecosystem. This transition marked a strategic shift from traditional packaging execution to delivering high performance, aseptic packaging solutions that meet international food safety standards.');
            foreach(array('building-expertise','mastering-production','aseptic-technology') as $i=>$image) $items[]=array('title'=>$titles[$i],'text'=>$texts[$i],'scope'=>'history','image'=>rp_option('history_milestone_image_'.($i+1),$asset.'history/'.$image.'.jpg'),'icon_image'=>$asset.'history/'.array('expertise','production','technology')[$i].'-icon.png');
            break;
        case 'certs': foreach(rp_default_certs() as $name) $items[]=array('title'=>$name,'image'=>$asset.'certifications/certificate-medal.png'); break;
        case 'products': foreach(array('quality'=>'Quality inspection','printing'=>'Packaging printing','production'=>'Production control','filling'=>'Aseptic filling','technology'=>'Processing technology') as $image=>$title) $items[]=array('title'=>$title,'image'=>rp_option('products_image_'.(count($items)+1),$asset.'home/product-'.$image.'.jpg')); break;
        case 'logos': foreach(array('David Smith','Designlovers Geekheaven','Golden Grid','Climb the Mountain','Golden','Avant Garde') as $i=>$name) $items[]=array('title'=>$name,'image'=>rp_option('partner_image_'.($i+1),$asset.'home/partner-'.($i+1).'.png')); break;
    }
    if('process_cards'===$key) $items[0]['alt']='Packaging quality specialists';
    if('local_cards'===$key) $items[1]['alt']='The Rock Pack Fino Pack';
    return $items;
}

function rockpack_cpt_meta_map( $type ) {
    return array('rp_stat'=>array('sub'=>'rp_stat_sub','icon'=>'rp_stat_icon'),'rp_history'=>array('text'=>'rp_history_text','scope'=>'rp_history_scope','icon_image'=>'rp_history_icon_image','icon_image_id'=>'rp_history_icon_image_id'))[$type] ?? array();
}

function rockpack_collection( $key, $placement = '' ) {
    $schema=rockpack_repeaters()[$key] ?? array();
    if ( isset( $GLOBALS['rockpack_preview_collections'] ) && array_key_exists( $key, $GLOBALS['rockpack_preview_collections'] ) ) {
        $rows = rockpack_resolve_media_rows( $GLOBALS['rockpack_preview_collections'][$key], $schema['fields'] ?? array() );
        if ( 'hero_images' === $key ) foreach ( $rows as &$row ) $row['heading'] = rockpack_section_titles()['hero_title'];
        unset( $row );
        if ( $placement ) $rows = array_values( array_filter( $rows, static function ( $row ) use ( $placement ) { return in_array( $row['scope'] ?? 'both', array( 'both', $placement ), true ); } ) );
        return $rows;
    }
    $opts=get_option('rockpack_settings',array());
    if (!empty($schema['cpt'])) {
        $posts=get_posts(array('post_type'=>$schema['cpt'],'post_status'=>'publish','posts_per_page'=>-1,'orderby'=>array('menu_order'=>'ASC','ID'=>'ASC')));
        $rows=array();
        foreach($posts as $i=>$post) {
            $fallback=rockpack_repeater_defaults($key);
            if ('history'===$key) $fallback=array_values(array_filter($fallback,function($r){return 'history'===($r['scope']??'');}));
            $row=array('id'=>$post->ID,'title'=>$post->post_title);
            foreach(rockpack_cpt_meta_map($post->post_type) as $field=>$meta) $row[$field]=get_post_meta($post->ID,$meta,true);
            foreach($schema['fields'] as $field=>$f) {
                if(in_array($field,array('title','id'),true) || isset(rockpack_cpt_meta_map($post->post_type)[$field])) continue;
                $row[$field]=get_post_meta($post->ID,'rp_item_'.$field,true);
            }
            $thumb=get_post_thumbnail_id($post->ID);
            if($thumb) { $row['image_id']=$thumb; $row['image']=wp_get_attachment_image_url($thumb,'large'); }
            if(empty($row['image']) && !metadata_exists('post',$post->ID,'rp_item_image')) $row['image']=$fallback[$i%max(1,count($fallback))]['image']??'';
            if('history'===$key) { $row['scope']=$row['scope']?:'both'; if(empty($row['icon_image']) && !metadata_exists('post',$post->ID,'rp_history_icon_image')) $row['icon_image']=$fallback[$i%3]['icon_image']??''; }
            if('stats'===$key) $row['icon']=$row['icon']?:'expertise';
            $rows[]=$row;
        }
        if(!$posts && empty($opts['_collections'][$key])) {
            $rows=rockpack_repeater_defaults($key);
            if('certs'===$key && 'certifications'===$placement) $rows=array($rows[1],$rows[0],$rows[2]);
        }
    } else {
        $rows=isset($opts['_repeaters'][$key]) && is_array($opts['_repeaters'][$key]) ? $opts['_repeaters'][$key] : rockpack_repeater_defaults($key);
    }
    $rows=rockpack_resolve_media_rows($rows,$schema['fields']??array());
    if ( 'hero_images' === $key ) {
        foreach ( $rows as &$row ) $row['heading'] = rockpack_section_titles()['hero_title'];
        unset( $row );
    }
    if($placement) $rows=array_values(array_filter($rows,function($row)use($placement){return in_array($row['scope']??'both',array('both',$placement),true);}));
    return $rows;
}

function rockpack_resolve_media_rows( $rows, $fields ) {
    $rows=array_values(array_filter($rows,'is_array'));
    foreach($rows as &$row) foreach($fields as $key=>$field) {
        if('repeater'!==$field['type'] && (!isset($row[$key]) || !is_scalar($row[$key]))) $row[$key]='select'===$field['type']?array_key_first($field['choices']):'';
        if('image'===$field['type'] && !empty($row[$key.'_id'])) $row[$key]=wp_get_attachment_image_url(absint($row[$key.'_id']),'large')?:($row[$key]??'');
        if('repeater'===$field['type']) $row[$key]=rockpack_resolve_media_rows(is_array($row[$key]??null)?$row[$key]:array(),$field['fields']);
    }
    return $rows;
}
