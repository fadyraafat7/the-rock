<?php
/** Published news and job opportunities. */
defined( 'ABSPATH' ) || exit;

function rockpack_register_jobs() {
    register_post_type( 'rp_job', array(
        'labels' => array( 'name' => 'Careers', 'singular_name' => 'Job opportunity', 'add_new_item' => 'Add job opportunity', 'edit_item' => 'Edit job opportunity', 'all_items' => 'All opportunities' ),
        'public' => true, 'exclude_from_search' => true, 'show_in_rest' => true, 'menu_icon' => 'dashicons-businessperson',
        'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
        'has_archive' => 'careers', 'rewrite' => array( 'slug' => 'careers', 'with_front' => false ),
    ) );
}
add_action( 'init', 'rockpack_register_jobs' );

function rockpack_news_url() {
    $page_id = (int) get_option( 'page_for_posts' );
    return $page_id && 'publish' === get_post_status( $page_id ) ? get_permalink( $page_id ) : home_url( '/news/' );
}

function rockpack_careers_url() {
    return get_post_type_archive_link( 'rp_job' ) ?: home_url( '/careers/' );
}

add_action( 'template_redirect', static function () {
    if ( is_singular( 'rp_job' ) || ( is_post_type_archive( 'rp_job' ) && is_paged() ) ) {
        wp_safe_redirect( rockpack_careers_url() );
        exit;
    }
}, 1 );

/** Translate the News URL's ?page=n before WordPress parses its reserved page variable. */
function rockpack_news_pagination_request( $vars ) {
    $news_id = (int) get_option( 'page_for_posts' );
    if ( is_admin() || ! $news_id || empty( $vars['page'] ) || ! is_scalar( $vars['page'] ) ) return $vars;
    $is_news = (int) ( $vars['page_id'] ?? 0 ) === $news_id
        || ( $vars['pagename'] ?? '' ) === get_page_uri( $news_id );
    if ( $is_news ) {
        $vars['paged'] = max( 1, absint( $vars['page'] ) );
        unset( $vars['page'] );
    }
    return $vars;
}
add_filter( 'request', 'rockpack_news_pagination_request' );

// Keep query-string pagination in the address bar instead of redirecting to /page/n/.
add_filter( 'redirect_canonical', static function ( $redirect ) {
    if ( ! is_home() || is_feed() || get_query_var( 'paged' ) < 2 ) return $redirect;
    return add_query_arg( 'page', (int) get_query_var( 'paged' ), rockpack_news_url() );
} );

function rockpack_sample_news_id() {
    $sample = get_page_by_path( 'hello-world', OBJECT, 'post' );
    return $sample && 'Hello world!' === $sample->post_title ? (int) $sample->ID : 0;
}

function rockpack_latest_news( $limit = 3 ) {
    return get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => $limit, 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'has_password' => false, 'post__not_in' => array_filter( array( rockpack_sample_news_id() ) ) ) );
}

function rockpack_open_job_condition() {
    return array( 'relation' => 'OR', array( 'key' => '_rp_job_status', 'compare' => 'NOT EXISTS' ), array( 'key' => '_rp_job_status', 'value' => 'open' ) );
}

function rockpack_latest_jobs( $limit = 3 ) {
    return get_posts( array( 'post_type' => 'rp_job', 'post_status' => 'publish', 'numberposts' => $limit, 'has_password' => false, 'meta_query' => array( rockpack_open_job_condition() ) ) );
}

function rockpack_content_page() {
    return is_home() || is_singular( array( 'post', 'rp_job' ) ) || is_post_type_archive( 'rp_job' );
}

add_action( 'pre_get_posts', static function ( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;
    if ( $query->is_home() ) {
        $excluded = array_merge( (array) $query->get('post__not_in'), array( rockpack_sample_news_id() ) );
        $query->set( 'post__not_in', array_filter( $excluded ) );
        $query->set( 'has_password', false );
        $query->set( 'posts_per_page', 6 );
        $query->set( 'ignore_sticky_posts', true );
        $query->set( 'orderby', array( 'date' => 'DESC', 'ID' => 'DESC' ) );
    }
    if ( $query->is_post_type_archive( 'rp_job' ) || $query->is_search() ) {
        $meta = $query->get( 'meta_query' );
        $query->set( 'meta_query', array_filter( array( 'relation' => 'AND', is_array($meta) ? $meta : array(), rockpack_open_job_condition() ) ) );
    }
    if ( $query->is_post_type_archive( 'rp_job' ) ) $query->set('has_password',false);
    if ( $query->is_post_type_archive( 'rp_job' ) ) $query->set( 'posts_per_page', 9 );
} );

function rockpack_job_fields() {
    return array(
        'location' => array( 'label' => 'Location', 'type' => 'text' ),
        'employment' => array( 'label' => 'Employment type', 'type' => 'select', 'choices' => array( '' => 'Not specified', 'Full-time' => 'Full-time', 'Part-time' => 'Part-time', 'Contract' => 'Contract', 'Internship' => 'Internship' ) ),
        'status' => array( 'label' => 'Applications', 'type' => 'select', 'choices' => array( 'open' => 'Open', 'closed' => 'Closed' ) ),
        'application' => array( 'label' => 'Application URL or mailto link (optional; defaults to Contact Us)', 'type' => 'text' ),
    );
}

function rockpack_sanitize_job_fields( $input ) {
    $output = array();
    foreach ( rockpack_job_fields() as $key => $field ) {
        $value = $input[$key] ?? ( 'status' === $key ? 'open' : '' );
        if ( ! is_scalar($value) ) return new WP_Error('shape','Invalid job field.');
        $value = trim((string)$value);
        if ( 'select' === $field['type'] && ! array_key_exists($value,$field['choices']) ) return new WP_Error('choice','Choose a valid ' . strtolower($field['label']) . '.');
        if ( 'application' === $key && '' !== $value ) {
            $scheme = strtolower((string)wp_parse_url($value,PHP_URL_SCHEME));
            if ( ! in_array($scheme,array('http','https','mailto'),true) ) return new WP_Error('url','Use an HTTP(S) application URL or a mailto link.');
            if ( 'mailto' === $scheme ? ! is_email(rawurldecode(strtok(substr($value,7),'?'))) : ! wp_parse_url($value,PHP_URL_HOST) ) return new WP_Error('url','Enter a valid application URL or email address.');
            $value = esc_url_raw($value,array('http','https','mailto'));
            if ( ! $value ) return new WP_Error('url','Enter a valid application link.');
        } else $value = sanitize_text_field($value);
        $output[$key] = $value;
    }
    return $output;
}

add_action( 'add_meta_boxes_rp_job', static function () {
    add_meta_box( 'rockpack-job-details', 'Opportunity details', static function ( $post ) {
        wp_nonce_field('rockpack_job_' . $post->ID,'rockpack_job_nonce');
        echo '<p>Use the main editor for the job description and requirements. Only published, open opportunities appear in listings.</p>';
        foreach ( rockpack_job_fields() as $key => $field ) {
            $value = get_post_meta($post->ID,'_rp_job_' . $key,true);
            if ( 'status' === $key && '' === $value ) $value = 'open';
            echo '<p><label for="rp-job-' . esc_attr($key) . '"><strong>' . esc_html($field['label']) . '</strong></label><br>';
            if ( 'select' === $field['type'] ) {
                echo '<select id="rp-job-' . esc_attr($key) . '" name="rp_job[' . esc_attr($key) . ']">';
                foreach ( $field['choices'] as $choice => $label ) echo '<option value="' . esc_attr($choice) . '" ' . selected($value,$choice,false) . '>' . esc_html($label) . '</option>';
                echo '</select>';
            } else echo '<input class="large-text" id="rp-job-' . esc_attr($key) . '" name="rp_job[' . esc_attr($key) . ']" value="' . esc_attr($value) . '">';
            echo '</p>';
        }
    }, 'rp_job', 'normal', 'default' );
} );

add_action( 'save_post_rp_job', static function ( $post_id ) {
    if ( wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ! current_user_can('edit_post',$post_id) ) return;
    if ( ! isset($_POST['rockpack_job_nonce']) || ! is_string($_POST['rockpack_job_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rockpack_job_nonce'])),'rockpack_job_' . $post_id) ) return;
    $input = wp_unslash($_POST['rp_job'] ?? array());
    $fields = is_array($input) ? rockpack_sanitize_job_fields($input) : new WP_Error('shape','Invalid opportunity details.');
    if ( is_wp_error($fields) ) { rockpack_editor_error($fields->get_error_message()); return; }
    foreach ( $fields as $key => $value ) update_post_meta($post_id,'_rp_job_' . $key,$value);
} );

function rockpack_job_application_url( $post_id ) {
    if ( 'closed' === get_post_meta($post_id,'_rp_job_status',true) ) return '';
    $url = get_post_meta($post_id,'_rp_job_application',true);
    return $url ? esc_url_raw($url,array('http','https','mailto')) : add_query_arg('job',$post_id,home_url('/contact-us/')) . '#contact-form';
}

function rockpack_job_enquiry_message() {
    $id = isset($_GET['job']) && is_scalar($_GET['job']) ? absint($_GET['job']) : 0;
    $job = $id ? get_post($id) : null;
    if ( ! $job || 'rp_job' !== $job->post_type || 'publish' !== $job->post_status || $job->post_password || 'closed' === get_post_meta($id,'_rp_job_status',true) ) return '';
    return sprintf(__('I would like to apply for the %s opportunity.','rockpack'),wp_strip_all_tags($job->post_title));
}

/** One-time setup for an existing installation, also run on theme activation. */
function rockpack_setup_content_routes() {
    if ( ! post_type_exists('rp_job') ) rockpack_register_jobs();
    if ( ! get_option('page_for_posts') ) {
        $page = get_page_by_path('news');
        $id = $page ? $page->ID : wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'News','post_name'=>'news'),true);
        if ( is_wp_error($id) ) return $id;
        if ( 'publish' !== get_post_status($id) ) return new WP_Error('news_page','Publish the existing News page before assigning it as the posts page.');
        update_option('page_for_posts',$id);
    }
    $options = get_option('rockpack_settings',array());
    $destinations = array(home_url('/#news')=>rockpack_news_url(),home_url('/#careers')=>rockpack_careers_url());
    foreach ( array('nav_destination_news','nav_destination_careers','shared_link_news','shared_link_careers','careers_card_btn_url') as $key ) {
        if ( isset($options[$key],$destinations[$options[$key]]) ) $options[$key] = $destinations[$options[$key]];
    }
    foreach ( array('navigation_groups','footer_page_links','footer_service_links') as $key ) {
        foreach ( $options['_repeaters'][$key] ?? array() as $i => $row ) {
            if ( 'navigation_groups' === $key ) {
                foreach ( $row['items'] ?? array() as $j => $item ) if ( isset($destinations[$item['url'] ?? '']) ) $options['_repeaters'][$key][$i]['items'][$j]['url'] = $destinations[$item['url']];
            } elseif ( isset($destinations[$row['url'] ?? '']) ) $options['_repeaters'][$key][$i]['url'] = $destinations[$row['url']];
        }
    }
    update_option('rockpack_settings',$options);
    if ( class_exists('Rockpack_Static_SSG') ) Rockpack_Static_SSG::generate_scheduled_post((int)get_option('page_for_posts'));
    flush_rewrite_rules(false);
    return true;
}
add_action( 'after_switch_theme', 'rockpack_setup_content_routes' );
