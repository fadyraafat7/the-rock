<?php
/** Home page assembled from the shared, editable section registry. @package Rockpack */
defined( 'ABSPATH' ) || exit;
require_once get_template_directory() . '/includes/data.php';
get_header();
?>
<main class="home-page company-page" id="main-content">
	<?php get_template_part( 'template-parts/inner-navigation' ); ?>
	<?php rockpack_render_home_sections(); ?>
</main>
<?php get_footer(); ?>
