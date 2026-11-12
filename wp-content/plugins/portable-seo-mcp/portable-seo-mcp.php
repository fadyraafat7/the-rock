<?php
/**
 * Plugin Name: Portable SEO MCP (Local)
 * Description: Scoped MCP access to SEO titles and descriptions of the eight local Rockpack pages.
 * Version: 1.0.0
 */
defined('ABSPATH') || exit;

function pseo_mcp_enabled() {
    return home_url() === 'http://figma-htmx-site.test' && (bool)get_option('portable_seo_mcp_enabled', false)
        && class_exists('Portable_SEO') && Portable_SEO::enabled();
}

function pseo_mcp_page($id) {
    $post = get_post($id);
    $slugs = ['about-us','history','certifications','product-portfolio','planet','contact-us','news'];
    if (!$post || $post->post_type !== 'page' || $post->post_status !== 'publish'
        || !empty($post->post_password) || ($post->ID !== (int)get_option('page_on_front') && !in_array($post->post_name, $slugs, true))) {
        return new WP_Error('invalid_page', 'Only the eight published Rockpack pages are supported.');
    }
    if (!current_user_can('edit_post', $post->ID)) return new WP_Error('forbidden', 'You cannot edit this page.');
    return $post;
}

function pseo_mcp_read($args) {
    $post = pseo_mcp_page(absint($args['post_id'] ?? 0));
    if (is_wp_error($post)) return $post;
    return ['post_id'=>$post->ID, 'url'=>get_permalink($post),
        'title'=>Portable_SEO::meta($post->ID, 'title'),
        'description'=>Portable_SEO::meta($post->ID, 'description')];
}

function pseo_mcp_update($args) {
    $post = pseo_mcp_page(absint($args['post_id'] ?? 0));
    if (is_wp_error($post)) return $post;
    foreach (['title','description'] as $key) {
        if (!isset($args[$key]) || !is_string($args[$key]) || trim($args[$key]) === '') return new WP_Error('invalid_value', 'Nonempty title and description strings are required.');
    }
    if (mb_strlen($args['title']) > 150 || mb_strlen($args['description']) > 320) return new WP_Error('invalid_value', 'Title or description exceeds the supported length.');
    $before = pseo_mcp_read(['post_id'=>$post->ID]);
    $title = sanitize_text_field($args['title']);
    $description = sanitize_textarea_field($args['description']);
    update_post_meta($post->ID, '_portable_seo_title', $title);
    update_post_meta($post->ID, '_portable_seo_description', $description);
    $after = pseo_mcp_read(['post_id'=>$post->ID]);
    if ($after['title'] !== $title || $after['description'] !== $description) return new WP_Error('save_failed', 'Could not verify the saved SEO fields.');
    clean_post_cache($post->ID);
    if (class_exists('Rockpack_Local_Page_Cache')) Rockpack_Local_Page_Cache::flush();
    return ['success'=>true, 'changed'=>$before !== $after] + $after;
}

add_action('plugins_loaded', static function () {
    if (!class_exists('WSP_MCP_Server')) return;
    foreach (['get'=>'pseo_mcp_read', 'update'=>'pseo_mcp_update'] as $action=>$callback) {
        $properties = ['post_id'=>['type'=>'integer', 'minimum'=>1]];
        $required = ['post_id'];
        if ($action === 'update') {
            $properties['title'] = ['type'=>'string', 'minLength'=>1, 'maxLength'=>150];
            $properties['description'] = ['type'=>'string', 'minLength'=>1, 'maxLength'=>320];
            $required = ['post_id','title','description'];
        }
        WSP_MCP_Server::register_tool('portable_seo_' . $action, [
            'description'=>($action === 'get' ? 'Read' : 'Save') . ' Portable SEO title and description for one of the eight local published Rockpack pages.',
            'inputSchema'=>['type'=>'object','properties'=>$properties,'required'=>$required,'additionalProperties'=>false],
            'callback'=>$callback, 'capability'=>'edit_pages', 'active_callback'=>'pseo_mcp_enabled',
        ]);
    }
}, 20);
