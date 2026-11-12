<?php
/** News index, selected through WordPress's Posts page setting. */
defined( 'ABSPATH' ) || exit;
get_template_part( 'template-parts/content-listing', null, array( 'kind' => 'news' ) );
