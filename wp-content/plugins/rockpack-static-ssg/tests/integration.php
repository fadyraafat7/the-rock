<?php
/**
 * Staging integration tests for the Rockpack Static Site Generator.
 *
 * Run with PHP from the WordPress root:
 * php wp-content/plugins/rockpack-static-ssg/tests/integration.php
 */

$wordpress_root = dirname( __DIR__, 4 );
$host = getenv( 'ROCKPACK_SSG_TEST_HOST' ) ? getenv( 'ROCKPACK_SSG_TEST_HOST' ) : 'SSG.test';
$scheme = getenv( 'ROCKPACK_SSG_TEST_SCHEME' ) ? getenv( 'ROCKPACK_SSG_TEST_SCHEME' ) : 'http';

if ( ! preg_match( '/(?:^|\.)test$|^localhost$/i', $host ) ) {
	fwrite( STDERR, "Refusing to run integration tests outside a local .test/localhost staging host.\n" );
	exit( 2 );
}

// The explicit trusted URL keeps this disposable staging host separate from the database URL.
putenv( 'ROCKPACK_WP_HOME=' . $scheme . '://' . $host );
putenv( 'ROCKPACK_WP_SITEURL=' . $scheme . '://' . $host );
$_SERVER['REQUEST_SCHEME'] = $scheme;
$_SERVER['HTTP_HOST'] = $host;
define( 'WP_USE_THEMES', false );
require $wordpress_root . DIRECTORY_SEPARATOR . 'wp-load.php';

if ( ! class_exists( 'Rockpack_Static_SSG' ) ) {
	fwrite( STDERR, "The custom static SSG plugin must be active to run these tests.\n" );
	exit( 2 );
}

$fixture_ids = array();
$assertions = 0;
$exit_code = 0;

function ssg_test_assert( $condition, $message ) {
	global $assertions;
	++$assertions;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function ssg_test_path( $relative_path ) {
	return WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'static-site' . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $relative_path );
}

function ssg_test_create_post( $type, $title, $status = 'draft', $parent = 0, $password = '' ) {
	global $fixture_ids;
	$id = wp_insert_post(
		array(
			'post_type'     => $type,
			'post_status'   => $status,
			'post_title'    => $title,
			'post_name'     => sanitize_title( $title ),
			'post_parent'   => $parent,
			'post_password' => $password,
			'post_content'  => 'Rockpack static SSG integration fixture.',
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( $id->get_error_message() );
	}
	$fixture_ids[] = $id;
	return $id;
}

function ssg_test_publish( $post_id ) {
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'publish',
		)
	);
}

function ssg_test_generate( $post_id ) {
	wp_clear_scheduled_hook( Rockpack_Static_SSG::GENERATE_HOOK, array( $post_id ) );
	Rockpack_Static_SSG::generate_scheduled_post( $post_id );
}

function ssg_test_assert_snapshot( $post_id, $needle = '' ) {
	$relative_path = get_post_meta( $post_id, Rockpack_Static_SSG::META_KEY, true );
	$file = ssg_test_path( $relative_path );
	ssg_test_assert( $relative_path && is_file( $file ), 'Expected snapshot file missing for post ' . $post_id . '.' );
	$html = file_get_contents( $file );
	ssg_test_assert( false !== $html, 'Could not read snapshot for post ' . $post_id . '.' );
	if ( '' !== $needle ) {
		ssg_test_assert( false !== strpos( $html, $needle ), 'Expected content was missing from snapshot for post ' . $post_id . '.' );
	}
	return array( $relative_path, $file, $html );
}

try {
	$readme = file_get_contents( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'README.md' );
	ssg_test_assert( false !== strpos( $readme, 'wp-json(?:/|$)' ), 'Apache example must exclude the REST API route.' );
	ssg_test_assert( false !== strpos( $readme, 'index\\.php/' ), 'Apache example must cover index.php-prefixed routes.' );
	ssg_test_assert( false !== strpos( $readme, '%{HTTP:Cookie} ^$' ), 'Apache example must bypass requests with cookies.' );
	ssg_test_assert( false !== strpos( $readme, '%{HTTP:Authorization} ^$' ), 'Apache example must bypass Authorization requests.' );
	ssg_test_assert( false !== strpos( $readme, '[R=404,END,NC]' ), 'Apache example must deny direct output-directory requests.' );
	foreach ( array( Rockpack_Static_SSG::GENERATE_HOOK, Rockpack_Static_SSG::REBUILD_HOOK, Rockpack_Static_SSG::DESCENDANTS_HOOK, Rockpack_Static_SSG::CLEANUP_HOOK, Rockpack_Static_SSG::RECONCILE_HOOK ) as $hook ) {
		ssg_test_assert( ! wp_next_scheduled( $hook ), 'The plugin must not leave scheduled background work: ' . $hook );
	}

	$dynamic_routes = array(
		'/contact-us/'           => array( 'GET', '', '' ),
		'/index.php/contact-us/' => array( 'GET', '', '' ),
		'/wp-admin/'             => array( 'GET', '', '' ),
		'/index.php/wp-admin/'   => array( 'GET', '', '' ),
		'/wp-json/'              => array( 'GET', '', '' ),
		'/index.php/wp-json/'    => array( 'GET', '', '' ),
		'/wp-cron.php'           => array( 'GET', '', '' ),
		'/wp-comments-post.php'  => array( 'POST', '', '' ),
		'/xmlrpc.php'            => array( 'POST', '', '' ),
		'/wp-content/static-site/index.html' => array( 'GET', '', '' ),
	);
	foreach ( $dynamic_routes as $uri => $request ) {
		ssg_test_assert(
			! Rockpack_Static_SSG::should_serve_static_request( $uri, $request[0], $request[1], $request[2] ),
			'Route should remain dynamic: ' . $uri
		);
	}
	ssg_test_assert( ! Rockpack_Static_SSG::should_serve_static_request( '/?rest_route=/', 'GET' ), 'Query-based REST requests must remain dynamic.' );
	ssg_test_assert( ! Rockpack_Static_SSG::should_serve_static_request( '/about/', 'POST' ), 'POST requests must remain dynamic.' );
	ssg_test_assert( ! Rockpack_Static_SSG::should_serve_static_request( '/about/', 'GET', 'wordpress_logged_in=1' ), 'Cookie-bearing requests must remain dynamic.' );
	ssg_test_assert( ! Rockpack_Static_SSG::should_serve_static_request( '/about/', 'GET', '', 'Bearer test' ), 'Authorization requests must remain dynamic.' );
	ssg_test_assert( Rockpack_Static_SSG::should_serve_static_request( '/', 'GET' ), 'Anonymous homepage GET should be eligible.' );
	ssg_test_assert( Rockpack_Static_SSG::should_serve_static_request( '/index.php/2026/10/post/', 'HEAD' ), 'Anonymous nested HEAD should be eligible.' );

	$front_page_id = absint( get_option( 'page_on_front' ) );
	ssg_test_assert( $front_page_id > 0, 'Staging site must use a configured static front page for these tests.' );
	$feed_marker = 'SSG_FEED_MARKER_' . wp_generate_password( 10, false );
	$post_id = ssg_test_create_post( 'post', 'SSG feed ' . $feed_marker );
	ssg_test_publish( $post_id );
	ssg_test_assert_snapshot( $post_id );
	ssg_test_assert_snapshot( $front_page_id, $feed_marker );
	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $feed_marker,
		)
	);
	ssg_test_assert_snapshot( $front_page_id, $feed_marker );
	ssg_test_assert_snapshot( $front_page_id, $feed_marker );
	ssg_test_assert_snapshot( $post_id, $feed_marker );

	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);
	ssg_test_assert( ! get_post_meta( $post_id, Rockpack_Static_SSG::META_KEY, true ), 'Unpublishing a post should remove its snapshot mapping.' );
	ssg_test_assert( false === strpos( file_get_contents( ssg_test_path( 'index.html' ) ), $feed_marker ), 'Homepage regeneration should remove an unpublished post from the snapshot.' );
	ssg_test_publish( $post_id );
	ssg_test_assert_snapshot( $post_id );
	$post_snapshot = get_post_meta( $post_id, Rockpack_Static_SSG::META_KEY, true );
	wp_delete_post( $post_id, true );
	ssg_test_assert( ! file_exists( ssg_test_path( $post_snapshot ) ), 'Deleting a published post should remove its snapshot.' );
	ssg_test_assert( false === strpos( file_get_contents( ssg_test_path( 'index.html' ) ), $feed_marker ), 'Deleting a post should immediately refresh the homepage snapshot.' );

	$parent_id = ssg_test_create_post( 'page', 'SSG parent ' . wp_generate_password( 8, false ) );
	ssg_test_publish( $parent_id );
	ssg_test_generate( $parent_id );
	$child_id = ssg_test_create_post( 'page', 'SSG child ' . wp_generate_password( 8, false ), 'draft', $parent_id );
	ssg_test_publish( $child_id );
	ssg_test_generate( $child_id );
	$old_child_path = get_post_meta( $child_id, Rockpack_Static_SSG::META_KEY, true );

	wp_update_post(
		array(
			'ID'          => $parent_id,
			'post_status' => 'draft',
		)
	);
	ssg_test_assert( ! file_exists( ssg_test_path( $old_child_path ) ), 'Unpublishing a parent should remove descendant snapshots.' );
	ssg_test_assert( ! get_post_meta( $child_id, Rockpack_Static_SSG::META_KEY, true ), 'Unpublishing a parent should clear descendant snapshot mappings.' );

	ssg_test_publish( $parent_id );
	ssg_test_generate( $parent_id );
	ssg_test_generate( $child_id );
	$old_parent_path = get_post_meta( $parent_id, Rockpack_Static_SSG::META_KEY, true );
	$old_child_path = get_post_meta( $child_id, Rockpack_Static_SSG::META_KEY, true );
	wp_update_post(
		array(
			'ID'        => $parent_id,
			'post_name' => 'ssg-parent-renamed-' . wp_generate_password( 7, false, false ),
		)
	);
		$new_parent_path = get_post_meta( $parent_id, Rockpack_Static_SSG::META_KEY, true );
		ssg_test_assert( $new_parent_path !== $old_parent_path, 'Changing a page slug should immediately update its own snapshot path.' );
		ssg_test_assert( ! file_exists( ssg_test_path( $old_parent_path ) ), 'Changing a page slug should remove its own obsolete snapshot.' );
		$new_child_path = get_post_meta( $child_id, Rockpack_Static_SSG::META_KEY, true );
	ssg_test_assert( $new_child_path !== $old_child_path, 'A descendant snapshot path should follow the changed parent slug.' );
	ssg_test_assert( ! file_exists( ssg_test_path( $old_child_path ) ), 'The old descendant path should be deleted after a slug change.' );

	$other_parent_id = ssg_test_create_post( 'page', 'SSG other parent ' . wp_generate_password( 8, false, false ) );
	ssg_test_publish( $other_parent_id );
	ssg_test_generate( $other_parent_id );
	$old_child_path = $new_child_path;
	wp_update_post(
		array(
			'ID'          => $child_id,
			'post_parent' => $other_parent_id,
		)
	);
	$new_child_path = get_post_meta( $child_id, Rockpack_Static_SSG::META_KEY, true );
	ssg_test_assert( $new_child_path !== $old_child_path, 'A child snapshot path should follow a changed parent relationship.' );
	ssg_test_assert( ! file_exists( ssg_test_path( $old_child_path ) ), 'The old child path should be removed after a parent change.' );

	$old_child_path = $new_child_path;
	$deleted_parent_path = get_post_meta( $other_parent_id, Rockpack_Static_SSG::META_KEY, true );
	wp_delete_post( $other_parent_id, true );
	ssg_test_assert( ! file_exists( ssg_test_path( $deleted_parent_path ) ), 'Deleting a parent should immediately remove its own snapshot.' );
	$new_child_path = get_post_meta( $child_id, Rockpack_Static_SSG::META_KEY, true );
	ssg_test_assert( $new_child_path !== $old_child_path, 'Deleting a parent should update the descendant permalink snapshot.' );
	ssg_test_assert( ! file_exists( ssg_test_path( $old_child_path ) ), 'Deleting a parent should remove the obsolete descendant file.' );
	ssg_test_assert_snapshot( $child_id );

	$password_page_id = ssg_test_create_post( 'page', 'SSG protected ' . wp_generate_password( 8, false, false ) );
	ssg_test_publish( $password_page_id );
	wp_update_post(
		array(
			'ID'            => $password_page_id,
			'post_password' => 'test-password',
		)
	);
	ssg_test_assert( is_wp_error( Rockpack_Static_SSG::generate( $password_page_id ) ), 'Password-protected content must not be generated.' );
	ssg_test_assert( ! get_post_meta( $password_page_id, Rockpack_Static_SSG::META_KEY, true ), 'Protected content must not retain a snapshot mapping.' );

	$orphan_path = 'ssg-orphan-test-' . wp_generate_password( 8, false, false ) . '/index.html';
	wp_mkdir_p( dirname( ssg_test_path( $orphan_path ) ) );
	file_put_contents( ssg_test_path( $orphan_path ), '<html>orphan</html>' );
	ssg_test_assert( true === Rockpack_Static_SSG::run_manual_operation( 'reconcile' ), 'Manual reconciliation should run through the maintenance operation.' );
	ssg_test_assert( ! file_exists( ssg_test_path( $orphan_path ) ), 'Manual reconciliation should remove orphan HTML files.' );

	$old_cleanup_queue = get_option( Rockpack_Static_SSG::CLEANUP_OPTION, array() );
	update_option(
		Rockpack_Static_SSG::CLEANUP_OPTION,
		array(
			'../unsafe-test.html' => array(
				'post_id'   => 0,
				'attempts'  => 0,
				'queued_at' => current_time( 'mysql' ),
			),
		),
		false
	);
	Rockpack_Static_SSG::run_manual_operation( 'cleanup' );
	$retry_queue = get_option( Rockpack_Static_SSG::CLEANUP_OPTION, array() );
	ssg_test_assert( isset( $retry_queue['../unsafe-test.html'] ), 'Failed deletion should remain in the durable cleanup queue.' );
	ssg_test_assert( ! empty( $retry_queue['../unsafe-test.html']['attempts'] ), 'Failed deletion should record its retry count.' );
	ssg_test_assert( ! wp_next_scheduled( Rockpack_Static_SSG::CLEANUP_HOOK ), 'Failed cleanup must wait for a manual retry.' );
	update_option( Rockpack_Static_SSG::CLEANUP_OPTION, $old_cleanup_queue, false );

	$valid_cleanup_page_id = ssg_test_create_post( 'page', 'SSG valid cleanup ' . wp_generate_password( 8, false, false ) );
	ssg_test_publish( $valid_cleanup_page_id );
	$valid_cleanup_path = get_post_meta( $valid_cleanup_page_id, Rockpack_Static_SSG::META_KEY, true );
	update_option(
		Rockpack_Static_SSG::CLEANUP_OPTION,
		array(
			$valid_cleanup_path => array(
				'post_id'   => $valid_cleanup_page_id,
				'attempts'  => 0,
				'queued_at' => current_time( 'mysql' ),
			),
		),
		false
	);
	ssg_test_assert( true === Rockpack_Static_SSG::run_manual_operation( 'cleanup' ), 'Manual cleanup should process queued paths.' );
	ssg_test_assert( is_file( ssg_test_path( $valid_cleanup_path ) ), 'Cleanup must preserve a path still used by public content.' );
	ssg_test_assert( ! isset( get_option( Rockpack_Static_SSG::CLEANUP_OPTION, array() )[ $valid_cleanup_path ] ), 'A now-valid queued path should be removed from cleanup queue.' );
	update_option( Rockpack_Static_SSG::CLEANUP_OPTION, $old_cleanup_queue, false );

	$failed_generation_id = ssg_test_create_post( 'page', 'SSG retry ' . wp_generate_password( 8, false, false ) );
	ssg_test_publish( $failed_generation_id );
	$failure_filter = static function () {
		return array(
			'headers'  => array( 'content-type' => 'text/html' ),
			'body'     => '<html>temporary failure</html>',
			'response' => array( 'code' => 503, 'message' => 'Unavailable' ),
			'cookies'  => array(),
			'filename' => null,
		);
	};
	add_filter( 'pre_http_request', $failure_filter, 10, 3 );
	Rockpack_Static_SSG::generate_scheduled_post( $failed_generation_id );
	remove_filter( 'pre_http_request', $failure_filter, 10 );
	$retry_counts = get_option( Rockpack_Static_SSG::GENERATION_RETRY_OPTION, array() );
	ssg_test_assert( 1 === absint( $retry_counts[ $failed_generation_id ]['attempts'] ?? 0 ), 'Failed generation should persist retry state.' );
	ssg_test_assert( ! wp_next_scheduled( Rockpack_Static_SSG::GENERATE_HOOK, array( $failed_generation_id ) ), 'Failed generation must wait for a manual retry.' );
	ssg_test_assert( empty( get_option( Rockpack_Static_SSG::STATUS_OPTION, array() )['success'] ), 'A later successful operation must not hide the generation failure.' );
	remove_filter( 'pre_http_request', $failure_filter, 10 );
	ssg_test_assert( true === Rockpack_Static_SSG::run_manual_operation( 'retry' ), 'The manual retry operation should run.' );
	$retry_counts = get_option( Rockpack_Static_SSG::GENERATION_RETRY_OPTION, array() );
	ssg_test_assert( ! isset( $retry_counts[ $failed_generation_id ] ), 'A successful manual retry should clear its failure record.' );

	$nonce_page_id = ssg_test_create_post( 'page', 'SSG nonce ' . wp_generate_password( 8, false, false ) );
	ssg_test_publish( $nonce_page_id );
	$render_header_seen = false;
	$nonce_filter = static function ( $preempt, $args ) use ( &$render_header_seen ) {
		$headers = array_change_key_case( $args['headers'] ?? array(), CASE_LOWER );
		$render_header_seen = '1' === ( $headers['x-rockpack-ssg-render'] ?? '' );
		return array(
			'headers'  => array( 'content-type' => 'text/html' ),
			'body'     => '<html><form><input type="hidden" name="_wpnonce" value="expired"></form></html>',
			'response' => array( 'code' => 200, 'message' => 'OK' ),
			'cookies'  => array(),
			'filename' => null,
		);
	};
	add_filter( 'pre_http_request', $nonce_filter, 10, 3 );
	$nonce_result = Rockpack_Static_SSG::generate( $nonce_page_id );
	remove_filter( 'pre_http_request', $nonce_filter, 10 );
	ssg_test_assert( is_wp_error( $nonce_result ) && 'rockpack_ssg_nonce_response' === $nonce_result->get_error_code(), 'Nonce-bearing HTML must be rejected.' );
	ssg_test_assert( $render_header_seen, 'Generation requests must carry the Apache static-delivery bypass header.' );
	ssg_test_assert( true === Rockpack_Static_SSG::run_manual_operation( 'generate_all' ), 'Full generation should start through the manual maintenance operation.' );
	$rebuild_state = get_option( Rockpack_Static_SSG::REBUILD_OPTION, array() );
	ssg_test_assert( in_array( $rebuild_state['status'] ?? '', array( 'running', 'complete' ), true ), 'A manual full generation should persist its progress.' );
	if ( 'running' === ( $rebuild_state['status'] ?? '' ) ) {
		ssg_test_assert( true === Rockpack_Static_SSG::run_manual_operation( 'generate_next' ), 'A later manual request should process the next full-generation batch.' );
	}
	ssg_test_assert( ! wp_next_scheduled( Rockpack_Static_SSG::REBUILD_HOOK ), 'Full generation must not schedule background batches.' );

	echo 'PASS: ' . $assertions . " staging assertions.\n";
} catch ( Throwable $error ) {
	fwrite( STDERR, 'FAIL after ' . $assertions . ' assertions: ' . $error->getMessage() . "\n" );
	$exit_code = 1;
} finally {
	foreach ( $fixture_ids as $fixture_id ) {
		if ( get_post( $fixture_id ) ) {
			wp_delete_post( $fixture_id, true );
		}
		delete_post_meta( $fixture_id, Rockpack_Static_SSG::META_KEY );
	}
	update_option( Rockpack_Static_SSG::GENERATION_RETRY_OPTION, array(), false );
	update_option( Rockpack_Static_SSG::REBUILD_OPTION, array(), false );
	$front_page_id = absint( get_option( 'page_on_front' ) );
	if ( $front_page_id ) {
		$front_result = Rockpack_Static_SSG::generate( $front_page_id );
		if ( is_wp_error( $front_result ) ) {
			fwrite( STDERR, 'Could not restore the staging homepage snapshot: ' . $front_result->get_error_message() . "\n" );
		}
	}

	exit( $exit_code );
}
