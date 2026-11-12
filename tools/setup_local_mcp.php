<?php
if (PHP_SAPI !== 'cli') exit(2);
$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'figma-htmx-site.test';
$_SERVER['REQUEST_SCHEME'] = 'http';
require dirname(__DIR__) . '/wp-load.php';
if (home_url() !== 'http://figma-htmx-site.test') exit("Local site required.\n");
require_once ABSPATH . 'wp-admin/includes/plugin.php';
foreach (['wsp-mcp-ai-agents-connector/wsp-mcp-ai-agents-connector.php','portable-seo-mcp/portable-seo-mcp.php'] as $plugin) {
    $result = activate_plugin($plugin);
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
}
// Advertise only the two scoped Portable SEO tools; retain the original settings for recovery.
if (false === get_option('portable_seo_mcp_previous_wsp_settings',false)) add_option('portable_seo_mcp_previous_wsp_settings',get_option(WSP_MCP_OPTION, []),'',false);
$settings = array_fill_keys(array_keys(wsp_mcp_ability_registry()), false);
update_option(WSP_MCP_OPTION, $settings);
update_option('portable_seo_mcp_enabled', true, false);
WSP_MCP_Auth::get_api_key(); // Keep the credential inside WordPress; never print it.
echo "Local MCP plugins activated; only scoped Portable SEO tools enabled.\n";
