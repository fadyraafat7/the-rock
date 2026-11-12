<?php
/**
 * Plugin Name: Rockpack Static Site Generator
 * Description: Generates static HTML snapshots of published WordPress pages and posts.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Site Administration
 * Text Domain: rockpack-static-ssg
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rockpack_Static_SSG {
	const META_KEY = '_rockpack_static_ssg_path';
	const OUTPUT_DIR_NAME = 'static-site';
	const STATUS_OPTION = 'rockpack_static_ssg_last_result';
	const REBUILD_OPTION = 'rockpack_static_ssg_rebuild';
	const GENERATE_HOOK = 'rockpack_static_ssg_generate_post';
	const REBUILD_HOOK = 'rockpack_static_ssg_rebuild_batch';
	const DESCENDANTS_HOOK = 'rockpack_static_ssg_refresh_descendants';
	const CLEANUP_HOOK = 'rockpack_static_ssg_cleanup';
	const RECONCILE_HOOK = 'rockpack_static_ssg_reconcile';
	const CLEANUP_OPTION = 'rockpack_static_ssg_cleanup_queue';
	const GENERATION_RETRY_OPTION = 'rockpack_static_ssg_generation_retries';
	const GENERATION_VERSION_META = '_rockpack_static_ssg_generation_version';
	const LOCK_DIRECTORY = 'cache/rockpack-static-ssg-locks';
	const BATCH_SIZE = 3;
	private static $deleted_descendants = array();

	/**
	 * Register WordPress lifecycle and admin hooks.
	 */
	public static function init() {
		add_action( 'transition_post_status', array( __CLASS__, 'on_transition_post_status' ), 10, 3 );
		add_action( 'wp_after_insert_post', array( __CLASS__, 'on_page_structure_change' ), 10, 4 );
        add_action( 'wp_after_insert_post', array( __CLASS__, 'on_portable_seo_saved' ), 20, 1 );
		add_action( 'before_delete_post', array( __CLASS__, 'on_before_delete_post' ), 10, 2 );
		add_action( 'deleted_post', array( __CLASS__, 'on_deleted_post' ), 10, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );
        add_action( 'parse_request', array( __CLASS__, 'serve_live_sitemap' ) );
        add_filter( 'robots_txt', array( __CLASS__, 'add_live_sitemap_to_robots' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_rockpack_static_ssg_generate', array( __CLASS__, 'handle_generate_request' ) );
		add_action( 'admin_post_rockpack_static_ssg_rebuild', array( __CLASS__, 'handle_rebuild_request' ) );
		add_action( 'admin_post_rockpack_static_ssg_maintenance', array( __CLASS__, 'handle_maintenance_request' ) );
		add_action( 'admin_post_rockpack_static_ssg_save_domain', array( __CLASS__, 'handle_save_domain_request' ) );
		add_action( 'admin_post_rockpack_static_ssg_generate_seo', array( __CLASS__, 'handle_generate_seo_request' ) );
		self::clear_legacy_scheduled_jobs();
	}

	/**
	 * Create the dedicated output directory on activation.
	 */
	public static function activate() {
		$path = self::target_path( 'index.html', true );
		if ( is_wp_error( $path ) ) {
			wp_die( esc_html( $path->get_error_message() ) );
		}
	}

	/**
	 * Remove jobs scheduled by earlier plugin versions.
	 */
	private static function clear_legacy_scheduled_jobs() {
		$cleared = get_option( 'rockpack_static_ssg_scheduled_jobs_removed', false );
		if ( $cleared ) {
			return;
		}

		$all_cleared = true;
		foreach ( array( self::GENERATE_HOOK, self::REBUILD_HOOK, self::DESCENDANTS_HOOK, self::CLEANUP_HOOK, self::RECONCILE_HOOK ) as $hook ) {
			$result = wp_clear_scheduled_hook( $hook );
			if ( false === $result || is_wp_error( $result ) ) {
				$all_cleared = false;
				self::record_error(
					is_wp_error( $result )
						? $result
						: new WP_Error( 'rockpack_ssg_legacy_schedule_clear_failed', sprintf( __( 'Could not clear the legacy scheduled hook %s.', 'rockpack-static-ssg' ), $hook ) )
				);
			}
		}
		if ( $all_cleared ) {
			update_option( 'rockpack_static_ssg_scheduled_jobs_removed', 1, false );
		}
	}

	/**
	 * Remove queued plugin events when the plugin is deactivated.
	 */
	public static function deactivate() {
		foreach ( array( self::GENERATE_HOOK, self::REBUILD_HOOK, self::DESCENDANTS_HOOK, self::CLEANUP_HOOK, self::RECONCILE_HOOK ) as $hook ) {
			$result = wp_clear_scheduled_hook( $hook );
			if ( false === $result || is_wp_error( $result ) ) {
				self::record_error(
					is_wp_error( $result )
						? $result
						: new WP_Error( 'rockpack_ssg_schedule_clear_failed', sprintf( __( 'Could not clear scheduled hook %s during deactivation.', 'rockpack-static-ssg' ), $hook ) )
				);
			}
		}
	}

	/**
	 * Decide whether an anonymous, query-free GET/HEAD request may use a snapshot.
	 *
	 * Mirrors the route and request guards documented for Apache delivery.
	 *
	 * @param string $request_uri Request URI including any query string.
	 * @param string $method      HTTP method.
	 * @param string $cookie      Raw Cookie header.
	 * @return bool
	 */
	public static function should_serve_static_request( $request_uri, $method, $cookie = '', $authorization = '' ) {
		$uri = wp_parse_url( $request_uri );
		$path = rawurldecode( $uri['path'] ?? '' );
		if ( ! in_array( strtoupper( $method ), array( 'GET', 'HEAD' ), true ) || ! empty( $uri['query'] ) || '' !== $cookie || '' !== $authorization ) {
			return false;
		}

		return ! preg_match(
			'#^/(?:index\.php/)?(?:contact-us(?:/|$)|wp-admin(?:/|$)|wp-login\.php$|wp-json(?:/|$)|wp-cron\.php$|wp-comments-post\.php$|xmlrpc\.php$|wp-content(?:/|$)|wp-includes(?:/|$))#i',
			$path
		);
	}

	/**
	 * Confirm content can safely be published in a public static file.
	 *
	 * @param WP_Post $post Post to validate.
	 * @return bool
	 */
	private static function is_public_content( $post ) {
		$is_public = (
			$post instanceof WP_Post &&
			'publish' === $post->post_status &&
			in_array( $post->post_type, array( 'page', 'post' ), true ) &&
			'' === (string) $post->post_password &&
			is_post_publicly_viewable( $post )
		);

		if ( $is_public && 'page' === $post->post_type ) {
			foreach ( get_post_ancestors( $post ) as $ancestor_id ) {
				$ancestor = get_post( $ancestor_id );
				if ( ! $ancestor || 'publish' !== $ancestor->post_status || '' !== (string) $ancestor->post_password ) {
					$is_public = false;
					break;
				}
			}

		}

		return $is_public && (bool) apply_filters( 'rockpack_static_ssg_is_public_content', true, $post );
	}

	/**
	 * Record failed generation for a manual retry.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Error $error  Generation error.
	 */
	private static function retry_generation( $post_id, $error ) {
		$retries = get_option( self::GENERATION_RETRY_OPTION, array() );
		$previous = $retries[ $post_id ] ?? array();
		$attempt = absint( is_array( $previous ) ? ( $previous['attempts'] ?? 0 ) : $previous ) + 1;
		$retries[ $post_id ] = array(
			'attempts' => $attempt,
			'last_error' => $error->get_error_message(),
			'updated_at' => current_time( 'mysql' ),
		);
		update_option( self::GENERATION_RETRY_OPTION, $retries, false );
		self::record_error( $error );
	}

	/**
	 * Clear the generation retry counter after a successful render.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function clear_generation_retry( $post_id ) {
		$retries = get_option( self::GENERATION_RETRY_OPTION, array() );
		if ( isset( $retries[ $post_id ] ) ) {
			unset( $retries[ $post_id ] );
			update_option( self::GENERATION_RETRY_OPTION, $retries, false );
		}
	}

	/**
	 * Notify cache/CDN integrations that a public snapshot changed.
	 *
	 * @param string $url       Canonical public URL.
	 * @param string $path      Relative output path.
	 * @param string $operation "generated" or "deleted".
	 */
	private static function notify_snapshot_change( $url, $path, $operation ) {
		do_action( 'rockpack_static_ssg_snapshot_changed', $url, $path, $operation );
	}

	/**
	 * Reconstruct a page URL from a plugin-owned relative output path.
	 *
	 * @param string $relative_path Relative output path.
	 * @return string
	 */
	private static function url_from_relative_path( $relative_path ) {
		$path = preg_replace( '#/index\.html$#', '', str_replace( '\\', '/', $relative_path ) );
		return home_url( '/' . ( $path ? trailingslashit( $path ) : '' ) );
	}

	/**
	 * Advance the version that identifies the newest requested snapshot for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function advance_generation_version( $post_id ) {
		$version = wp_generate_uuid4();
		update_post_meta( absint( $post_id ), self::GENERATION_VERSION_META, $version );
		return $version;
	}

	/**
	 * Confirm that a render still describes currently public content.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $version Render version captured before requesting HTML.
	 * @return bool
	 */
	private static function generation_is_current( $post_id, $version ) {
		$post = get_post( $post_id );
		$current_version = (string) get_post_meta( $post_id, self::GENERATION_VERSION_META, true );
		return self::is_public_content( $post ) && '' !== $current_version && hash_equals( $current_version, (string) $version );
	}

	/**
	 * Serialize rendering and removal of a single post's snapshot.
	 *
	 * A version check prevents an obsolete writer from winning, while the lock
	 * ensures a withdrawal that starts just after that check removes any earlier
	 * commit before it returns.
	 *
	 * @param int      $post_id Post ID.
	 * @param callable $operation Operation to run while locked.
	 * @return mixed|WP_Error
	 */
	private static function with_generation_lock( $post_id, $operation ) {
		$directory = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . self::LOCK_DIRECTORY;
		if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
			return new WP_Error( 'rockpack_ssg_lock_directory_failed', __( 'Could not create the static generation lock directory.', 'rockpack-static-ssg' ) );
		}
		if ( is_link( $directory ) ) {
			return new WP_Error( 'rockpack_ssg_lock_symlink', __( 'The static generation lock directory must not be a symbolic link.', 'rockpack-static-ssg' ) );
		}

		$lock_path = trailingslashit( $directory ) . 'post-' . absint( $post_id ) . '.lock';
		if ( is_link( $lock_path ) ) {
			return new WP_Error( 'rockpack_ssg_lock_symlink', __( 'The static generation lock file must not be a symbolic link.', 'rockpack-static-ssg' ) );
		}
		$handle = @fopen( $lock_path, 'c' );
		if ( false === $handle ) {
			return new WP_Error( 'rockpack_ssg_lock_open_failed', __( 'Could not open the static generation lock file.', 'rockpack-static-ssg' ) );
		}
		if ( ! flock( $handle, LOCK_EX ) ) {
			fclose( $handle );
			return new WP_Error( 'rockpack_ssg_lock_failed', __( 'Could not lock static generation for this content.', 'rockpack-static-ssg' ) );
		}

		try {
			return call_user_func( $operation );
		} finally {
			flock( $handle, LOCK_UN );
			fclose( $handle );
		}
	}

	/**
	 * Queue a fresh snapshot for published content or remove one when unpublished.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Previous post status.
	 * @param WP_Post $post       Changed post.
	 */
	public static function on_transition_post_status( $new_status, $old_status, $post ) {
		if ( ! in_array( $post->post_type, array( 'page', 'post' ), true ) ) {
			return;
		}

		// Invalidate any render that began before this content transition. The
		// writer rechecks this version while holding its per-post lock.
		self::advance_generation_version( $post->ID );

		if ( 'publish' === $new_status ) {
			if ( ! self::is_public_content( $post ) ) {
				self::remove_post_file( $post->ID );
				if ( 'post' === $post->post_type ) {
					self::queue_homepage_generation();
				}
				if ( 'page' === $post->post_type ) {
					self::remove_descendant_files( $post->ID );
				}
				return;
			}

			self::queue_generation( $post->ID );
			if ( 'post' === $post->post_type ) {
				self::queue_homepage_generation();
			}
			if ( 'page' === $post->post_type && 'publish' !== $old_status ) {
				self::queue_descendant_generation( $post->ID );
			}
			return;
		}

		if ( 'publish' === $old_status ) {
			self::remove_post_file( $post->ID );
			if ( 'post' === $post->post_type ) {
				self::queue_homepage_generation();
			}
			if ( 'page' === $post->post_type ) {
				self::remove_descendant_files( $post->ID );
			}
		}
	}

	/**
	 * Regenerate descendants when a published page's URL structure changes.
	 *
	 * @param int          $post_id    Post ID.
	 * @param WP_Post      $post       Updated post.
	 * @param bool         $update     Whether this is an update.
	 * @param WP_Post|null $post_before Post before the update.
	 */
	public static function on_page_structure_change( $post_id, $post, $update, $post_before ) {
		if (
			! $update ||
			! $post_before ||
			'page' !== $post->post_type ||
			( $post->post_name === $post_before->post_name && (int) $post->post_parent === (int) $post_before->post_parent )
		) {
			return;
		}

		if ( self::is_public_content( $post ) ) {
			self::queue_descendant_generation( $post_id );
		} else {
			self::remove_descendant_files( $post_id );
		}
	}

	/**
	 * Remove a generated snapshot before the WordPress post is deleted.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post being deleted.
	 */
	public static function on_before_delete_post( $post_id, $post ) {
		if ( $post && in_array( $post->post_type, array( 'page', 'post' ), true ) ) {
			self::advance_generation_version( $post_id );
			self::remove_post_file( $post_id );
			if ( 'page' === $post->post_type ) {
				self::$deleted_descendants[ absint( $post_id ) ] = self::get_descendant_ids( $post_id );
			}
		}
	}

	/**
	 * Queue a homepage refresh after a post is deleted.
	 *
	 * @param int     $post_id Deleted post ID.
	 * @param WP_Post $post    Deleted post object.
	 */
	public static function on_deleted_post( $post_id, $post ) {
		if ( $post && 'post' === $post->post_type ) {
			self::queue_homepage_generation();
		}
		if ( $post && 'page' === $post->post_type ) {
			$descendants = self::$deleted_descendants[ absint( $post_id ) ] ?? array();
			unset( self::$deleted_descendants[ absint( $post_id ) ] );
			foreach ( $descendants as $descendant_id ) {
				clean_post_cache( $descendant_id );
			}
			self::refresh_descendants( $descendants );
		}
	}

	/**
	 * Queue public descendants after their parent URL changes or is restored.
	 *
	 * @param int $parent_id Page ID.
	 */
	private static function queue_descendant_generation( $parent_id ) {
		foreach ( self::get_descendant_ids( $parent_id ) as $descendant_id ) {
			if ( self::is_public_content( get_post( $descendant_id ) ) ) {
				self::queue_generation( $descendant_id );
			}
		}
	}

	/**
	 * Remove all generated descendant pages when their parent becomes unpublished.
	 *
	 * @param int $parent_id Page ID.
	 */
	private static function remove_descendant_files( $parent_id ) {
		foreach ( self::get_descendant_ids( $parent_id ) as $descendant_id ) {
			self::remove_post_file( $descendant_id );
		}
	}

	/**
	 * Get all descendants in ID order, including those below unpublished parents.
	 *
	 * @param int $parent_id Page ID.
	 * @return int[]
	 */
	private static function get_descendant_ids( $parent_id ) {
		$descendants = get_pages(
			array(
				'child_of'    => absint( $parent_id ),
				'post_type'   => 'page',
				'post_status' => array_keys( get_post_stati() ),
				'sort_column' => 'ID',
			)
		);
		return array_map(
			static function ( $page ) {
				return absint( $page->ID );
			},
			$descendants
		);
	}

	/**
	 * Refresh descendants after their deleted parent has been removed.
	 *
	 * @param int[] $post_ids Descendant page IDs.
	 */
	public static function refresh_descendants( $post_ids ) {
		foreach ( array_map( 'absint', (array) $post_ids ) as $post_id ) {
			self::queue_generation( $post_id );
		}
	}

	/**
	 * Queue the page WordPress uses as its front page or posts page.
	 */
	private static function queue_homepage_generation() {
		$page_id = 'page' === get_option( 'show_on_front' )
			? absint( get_option( 'page_on_front' ) )
			: absint( get_option( 'page_for_posts' ) );
		$page = $page_id ? get_post( $page_id ) : null;
		if ( $page && self::is_public_content( $page ) ) {
			self::queue_generation( $page_id );
		}
	}

	/**
	 * Generate a queued published post snapshot.
	 *
	 * @param int $post_id Post ID.
	 */
    /** Regenerate snapshots after the SEO box has saved its metadata. */
    public static function on_portable_seo_saved( $post_id ) {
        if ( ! class_exists( 'Portable_SEO' ) || ! Portable_SEO::enabled() || empty( $_POST['portable_seo_nonce'] ) || ! current_user_can( 'edit_post', $post_id ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['portable_seo_nonce'] ) ), 'portable_seo_' . $post_id ) ) return;
        $post = get_post( $post_id );
        if ( self::is_public_content( $post ) && get_post_meta( $post_id, self::META_KEY, true ) ) self::generate_scheduled_post( $post_id );
        $result = self::generate_seo_files();
        if ( is_wp_error( $result ) ) self::record_error( $result );
    }

	public static function generate_scheduled_post( $post_id, $generation_version = null ) {
		$post_id = absint( $post_id );
		$generation_version = null === $generation_version
			? self::advance_generation_version( $post_id )
			: (string) $generation_version;
		$post = get_post( $post_id );
		if ( ! self::is_public_content( $post ) ) {
			self::remove_post_file( $post_id );
			self::clear_generation_retry( $post_id );
			return;
		}

		$result = self::generate( $post_id, $generation_version );
		if ( is_wp_error( $result ) ) {
			if ( 'rockpack_ssg_generation_superseded' === $result->get_error_code() ) {
				self::clear_generation_retry( $post_id );
				return;
			}
			self::retry_generation( $post_id, $result );
			return;
		}

		self::clear_generation_retry( $post_id );
		self::record_success_if_clear( sprintf( __( 'Generated %s.', 'rockpack-static-ssg' ), $result ) );
	}

	/**
	 * Add the manual generation screen under Tools.
	 */
	public static function add_admin_page() {
		add_management_page(
			__( 'Static Site Generation', 'rockpack-static-ssg' ),
			__( 'Static Site Generation', 'rockpack-static-ssg' ),
			'manage_options',
			'rockpack-static-ssg',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Load HTMX only on this plugin's maintenance screen.
	 *
	 * @param string $hook_suffix Current admin screen hook.
	 */
	public static function enqueue_admin_assets( $hook_suffix ) {
		if ( 'tools_page_rockpack-static-ssg' === $hook_suffix ) {
			wp_enqueue_script(
				'rockpack-static-ssg-htmx',
				'https://unpkg.com/htmx.org@2.0.4/dist/htmx.min.js',
				array(),
				'2.0.4',
				true
			);
		}
	}

	/**
	 * Render the manual generation and recovery controls.
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Static Site Generation', 'rockpack-static-ssg' ); ?></h1>
			<p><?php esc_html_e( 'HTML files are written under wp-content/static-site. This does not change the live WordPress routing.', 'rockpack-static-ssg' ); ?></p>
			<?php if ( isset( $_GET['domain_saved'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Production domain saved.', 'rockpack-static-ssg' ); ?></p></div><?php endif; ?>
			<?php if ( isset( $_GET['seo_generated'] ) ) : ?>
				<?php if ( '1' === $_GET['seo_generated'] ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'sitemap.xml and robots.txt generated successfully.', 'rockpack-static-ssg' ); ?></p></div>
				<?php else : ?>
					<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Failed to generate SEO files. Check the error log below.', 'rockpack-static-ssg' ); ?></p></div>
				<?php endif; ?>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Production domain', 'rockpack-static-ssg' ); ?></h2>
			<?php if ( ! get_option( 'rockpack_static_ssg_production_domain', '' ) ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Set the public production domain and rebuild before publishing snapshots. Without it, generated URLs use the current WordPress host.', 'rockpack-static-ssg' ); ?></p></div><?php endif; ?>
			<p><?php esc_html_e( 'When set, the local WordPress domain in every generated HTML file is replaced with this domain. Leave empty to keep local URLs (development only).', 'rockpack-static-ssg' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="rockpack_static_ssg_save_domain">
				<?php wp_nonce_field( 'rockpack_static_ssg_save_domain' ); ?>
				<table class="form-table" style="max-width:600px">
					<tr>
						<th scope="row"><label for="rp-ssg-domain"><?php esc_html_e( 'Production domain', 'rockpack-static-ssg' ); ?></label></th>
						<td>
							<input id="rp-ssg-domain" type="url" name="production_domain"
								value="<?php echo esc_attr( get_option( 'rockpack_static_ssg_production_domain', '' ) ); ?>"
								placeholder="https://www.example.com" style="width:100%">
							<p class="description"><?php esc_html_e( 'Full origin with no trailing slash, e.g. https://www.therockpack.com', 'rockpack-static-ssg' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save domain', 'rockpack-static-ssg' ), 'secondary', 'submit', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Sitemap &amp; robots.txt', 'rockpack-static-ssg' ); ?></h2>
			<p>
				<?php
				$sitemap_path = WP_CONTENT_DIR . '/static-site/sitemap.xml';
				if ( file_exists( $sitemap_path ) ) {
					printf(
						esc_html__( 'Last generated: %s', 'rockpack-static-ssg' ),
						esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( $sitemap_path ) ) )
					);
				} else {
					esc_html_e( 'Not yet generated.', 'rockpack-static-ssg' );
				}
				?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="rockpack_static_ssg_generate_seo">
				<?php wp_nonce_field( 'rockpack_static_ssg_generate_seo' ); ?>
				<?php submit_button( __( 'Generate sitemap &amp; robots.txt', 'rockpack-static-ssg' ), 'secondary', 'submit', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Generate one page', 'rockpack-static-ssg' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="rockpack_static_ssg_generate">
				<?php wp_nonce_field( 'rockpack_static_ssg_generate' ); ?>
				<label for="rockpack-ssg-post"><?php esc_html_e( 'Published page or post ID', 'rockpack-static-ssg' ); ?></label>
				<input id="rockpack-ssg-post" name="post_id" type="number" min="1" step="1" required>
				<p class="description"><?php esc_html_e( 'Find the ID in the editor URL (the value after post=).', 'rockpack-static-ssg' ); ?></p>
				<?php submit_button( __( 'Generate selected page', 'rockpack-static-ssg' ), 'secondary', 'submit', false ); ?>
			</form>

			<?php self::render_maintenance_status(); ?>
		</div>
		<?php
	}

	/**
	 * Render the current recovery state and safe, manually triggered operations.
	 */
	private static function render_maintenance_status() {
		$last_result = get_option( self::STATUS_OPTION, array() );
		$rebuild = get_option( self::REBUILD_OPTION, array() );
		$cleanup_queue = get_option( self::CLEANUP_OPTION, array() );
		$generation_retries = get_option( self::GENERATION_RETRY_OPTION, array() );
		$last_result = is_array( $last_result ) ? $last_result : array();
		$rebuild = is_array( $rebuild ) ? $rebuild : array();
		$cleanup_queue = is_array( $cleanup_queue ) ? $cleanup_queue : array();
		$generation_retries = is_array( $generation_retries ) ? $generation_retries : array();
		$operation = ! empty( $rebuild['status'] ) && 'running' === $rebuild['status'] ? 'generate_next' : 'generate_all';
		$button = 'generate_next' === $operation ? __( 'Run next generation batch', 'rockpack-static-ssg' ) : __( 'Start full generation', 'rockpack-static-ssg' );
		?>
		<section id="rockpack-static-ssg-maintenance">
			<h2><?php esc_html_e( 'Manual maintenance and recovery', 'rockpack-static-ssg' ); ?></h2>
			<p><?php esc_html_e( 'No background runner is used. Each action runs only after an administrator requests it; process full generation in batches until complete.', 'rockpack-static-ssg' ); ?></p>
			<p>
				<?php
				printf(
					esc_html__( 'Generation failures awaiting retry: %1$d. File cleanup failures awaiting retry: %2$d.', 'rockpack-static-ssg' ),
					count( $generation_retries ),
					count( $cleanup_queue )
				);
				?>
			</p>
			<?php if ( ! empty( $last_result['message'] ) ) : ?>
				<div class="notice <?php echo ! empty( $last_result['success'] ) ? 'notice-success' : 'notice-error'; ?>"><p><?php echo esc_html( $last_result['message'] ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! empty( $rebuild['status'] ) ) : ?>
				<p>
					<?php
					printf(
						/* translators: 1: rebuild state, 2: processed count, 3: failure count */
						esc_html__( 'Full generation: %1$s; %2$d items processed; %3$d failures.', 'rockpack-static-ssg' ),
						esc_html( $rebuild['status'] ),
						absint( $rebuild['processed'] ?? 0 ),
						absint( $rebuild['failed'] ?? 0 )
					);
					?>
				</p>
			<?php endif; ?>
			<?php if ( $generation_retries ) : ?>
				<h3><?php esc_html_e( 'Generation failures', 'rockpack-static-ssg' ); ?></h3>
				<ul>
					<?php foreach ( $generation_retries as $post_id => $failure ) : ?>
						<?php
						$failure = is_array( $failure ) ? $failure : array( 'attempts' => absint( $failure ), 'last_error' => '' );
						$title = get_the_title( absint( $post_id ) );
						?>
						<li>
							<?php
							printf(
								esc_html__( '%1$s (ID %2$d; %3$d attempts): %4$s', 'rockpack-static-ssg' ),
								esc_html( $title ? $title : __( 'Deleted or unavailable content', 'rockpack-static-ssg' ) ),
								absint( $post_id ),
								absint( $failure['attempts'] ?? 0 ),
								esc_html( $failure['last_error'] ?? '' )
							);
							?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $cleanup_queue ) : ?>
				<h3><?php esc_html_e( 'File cleanup failures', 'rockpack-static-ssg' ); ?></h3>
				<ul>
					<?php foreach ( $cleanup_queue as $path => $failure ) : ?>
						<?php $failure = is_array( $failure ) ? $failure : array(); ?>
						<li>
							<?php
							printf(
								esc_html__( '%1$s (%2$d attempts): %3$s', 'rockpack-static-ssg' ),
								esc_html( $path ),
								absint( $failure['attempts'] ?? 0 ),
								esc_html( $failure['last_error'] ?? __( 'Not yet retried.', 'rockpack-static-ssg' ) )
							);
							?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="rockpack-static-ssg-actions">
				<?php self::render_maintenance_form( $operation, $button, 'primary' ); ?>
				<?php self::render_maintenance_form( 'cleanup', __( 'Retry failed file cleanup', 'rockpack-static-ssg' ) ); ?>
				<?php self::render_maintenance_form( 'reconcile', __( 'Reconcile snapshots', 'rockpack-static-ssg' ) ); ?>
				<?php self::render_maintenance_form( 'retry', __( 'Retry failed operations', 'rockpack-static-ssg' ) ); ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Render a maintenance action form with standard POST fallback and optional HTMX update.
	 *
	 * @param string $operation Maintenance operation.
	 * @param string $label     Button label.
	 * @param string $style     WordPress button style.
	 */
	private static function render_maintenance_form( $operation, $label, $style = 'secondary' ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			hx-post="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			hx-target="#rockpack-static-ssg-maintenance"
			hx-swap="outerHTML">
			<input type="hidden" name="action" value="rockpack_static_ssg_maintenance">
			<input type="hidden" name="operation" value="<?php echo esc_attr( $operation ); ?>">
			<?php wp_nonce_field( 'rockpack_static_ssg_maintenance' ); ?>
			<?php submit_button( $label, $style, 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * Generate sitemap.xml and robots.txt immediately.
	 */
	public static function handle_generate_seo_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to generate SEO files.', 'rockpack-static-ssg' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'rockpack_static_ssg_generate_seo' );
		$result = self::generate_seo_files();
		if ( is_wp_error( $result ) ) {
			self::record_error( $result );
		}
		wp_safe_redirect( add_query_arg( 'seo_generated', is_wp_error( $result ) ? '0' : '1', admin_url( 'tools.php?page=rockpack-static-ssg' ) ) );
		exit;
	}

	/**
	 * Save the production domain used for URL rewriting in static output.
	 */
	public static function handle_save_domain_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'rockpack-static-ssg' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'rockpack_static_ssg_save_domain' );
		$domain = esc_url_raw( wp_unslash( $_POST['production_domain'] ?? '' ) );
		// Strip trailing slash and only accept http/https.
		$domain = rtrim( $domain, '/' );
		$parsed = wp_parse_url( $domain );
		if ( $domain && ( ! isset( $parsed['scheme'] ) || ! in_array( $parsed['scheme'], array( 'http', 'https' ), true ) ) ) {
			wp_die( esc_html__( 'Please enter a valid http or https URL.', 'rockpack-static-ssg' ) );
		}
		update_option( 'rockpack_static_ssg_production_domain', $domain );
		wp_safe_redirect( add_query_arg( 'domain_saved', '1', admin_url( 'tools.php?page=rockpack-static-ssg' ) ) );
		exit;
	}

	/**
	 * Process a nonce-protected request to generate one page immediately.
	 */
	public static function handle_generate_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to generate static pages.', 'rockpack-static-ssg' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'rockpack_static_ssg_generate' );

		$raw_post_id = wp_unslash( $_POST['post_id'] ?? '' );
		$post_id = is_scalar( $raw_post_id ) ? absint( $raw_post_id ) : 0;
		$result = self::generate( $post_id );
		if ( is_wp_error( $result ) ) {
			if ( self::is_public_content( get_post( $post_id ) ) ) {
				self::retry_generation( $post_id, $result );
			} else {
				self::record_error( $result );
			}
		} else {
			self::clear_generation_retry( $post_id );
			self::record_success_if_clear( sprintf( __( 'Generated %s.', 'rockpack-static-ssg' ), $result ) );
		}

		wp_safe_redirect( admin_url( 'tools.php?page=rockpack-static-ssg' ) );
		exit;
	}

	/**
	 * Start a queued full rebuild after checking capability and nonce.
	 */
	public static function handle_rebuild_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to rebuild static pages.', 'rockpack-static-ssg' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'rockpack_static_ssg_rebuild' );

		self::run_manual_operation( 'generate_all' );

		wp_safe_redirect( admin_url( 'tools.php?page=rockpack-static-ssg' ) );
		exit;
	}

	/**
	 * Authorize and process a manually requested maintenance operation.
	 */
	public static function handle_maintenance_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to maintain static pages.', 'rockpack-static-ssg' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'rockpack_static_ssg_maintenance' );

		$raw_operation = wp_unslash( $_POST['operation'] ?? '' );
		$operation = is_string( $raw_operation ) ? sanitize_key( $raw_operation ) : '';
		$result = self::run_manual_operation( $operation );
		if ( is_wp_error( $result ) ) {
			self::record_error( $result );
		}

		$hx_request = wp_unslash( $_SERVER['HTTP_HX_REQUEST'] ?? '' );
		if ( is_string( $hx_request ) && 'true' === strtolower( sanitize_text_field( $hx_request ) ) ) {
			self::render_maintenance_status();
			exit;
		}

		wp_safe_redirect( admin_url( 'tools.php?page=rockpack-static-ssg' ) );
		exit;
	}

	/**
	 * Run a user-requested operation without scheduling background work.
	 *
	 * @param string $operation Operation name.
	 * @return true|WP_Error
	 */
	public static function run_manual_operation( $operation ) {
		switch ( $operation ) {
			case 'generate_all':
				update_option(
					self::REBUILD_OPTION,
					array(
						'status'    => 'running',
						'last_id'   => 0,
						'processed' => 0,
						'failed'    => 0,
						'started'   => current_time( 'mysql' ),
					),
					false
				);
				self::rebuild_batch();
				return true;

			case 'generate_next':
				$state = get_option( self::REBUILD_OPTION, array() );
				if ( empty( $state['status'] ) || 'running' !== $state['status'] ) {
					return new WP_Error( 'rockpack_ssg_rebuild_not_running', __( 'There is no full generation in progress. Start a new full generation first.', 'rockpack-static-ssg' ) );
				}
				self::rebuild_batch();
				return true;

			case 'cleanup':
				self::retry_file_cleanup();
				return true;

			case 'reconcile':
				self::reconcile_output();
				return true;

			case 'retry':
				self::retry_failed_operations();
				return true;

			default:
				return new WP_Error( 'rockpack_ssg_invalid_operation', __( 'The requested maintenance operation is not supported.', 'rockpack-static-ssg' ) );
		}
	}

	/**
	 * Generate one bounded batch using an ID cursor.
	 */
	public static function rebuild_batch() {
		$state = get_option( self::REBUILD_OPTION, array() );
		if ( empty( $state['status'] ) || 'running' !== $state['status'] ) {
			return;
		}

		global $wpdb;
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE ID > %d AND post_status = %s AND post_type IN (%s, %s) ORDER BY ID ASC LIMIT %d",
				absint( $state['last_id'] ?? 0 ),
				'publish',
				'page',
				'post',
				self::BATCH_SIZE
			)
		);
		if ( ! is_array( $ids ) ) {
			$state['status'] = 'failed';
			update_option( self::REBUILD_OPTION, $state, false );
			self::record_error( new WP_Error( 'rockpack_ssg_rebuild_query_failed', sprintf( __( 'Could not read the next rebuild batch: %s', 'rockpack-static-ssg' ), $wpdb->last_error ) ) );
			return;
		}

		if ( ! empty( $ids ) ) {
			$posts = get_posts(
				array(
					'post__in'               => array_map( 'absint', $ids ),
					'post_type'              => array( 'page', 'post' ),
					'post_status'            => 'publish',
					'numberposts'             => count( $ids ),
					'orderby'                 => 'post__in',
					'update_post_meta_cache'  => false,
					'update_post_term_cache'  => false,
				)
			);
			$posts_by_id = array();
			foreach ( $posts as $post ) {
				$posts_by_id[ $post->ID ] = $post;
			}

			foreach ( $ids as $id ) {
				$id = absint( $id );
				$state['last_id'] = $id;
				if ( ! isset( $posts_by_id[ $id ] ) ) {
					continue;
				}

				$result = self::generate( $id );
				++$state['processed'];
				if ( is_wp_error( $result ) ) {
					++$state['failed'];
					self::retry_generation( $id, $result );
				} else {
					self::clear_generation_retry( $id );
				}
			}
		}

		if ( count( $ids ) < self::BATCH_SIZE ) {
			$seo_result = self::generate_seo_files();
			if ( is_wp_error( $seo_result ) ) {
				$state['status'] = 'failed';
				update_option( self::REBUILD_OPTION, $state, false );
				self::record_error( $seo_result );
				return;
			}
			$state['status'] = 'complete';
			$state['completed'] = current_time( 'mysql' );
			update_option( self::REBUILD_OPTION, $state, false );
			if ( 0 === $state['failed'] ) {
				self::record_success_if_clear( sprintf( __( 'Full generation completed: %d pages and posts generated.', 'rockpack-static-ssg' ), $state['processed'] ) );
			}
			return;
		}

		update_option( self::REBUILD_OPTION, $state, false );
		$progress_message = sprintf(
			__( 'Full generation is in progress: %1$d items processed, %2$d failures. Run the next batch manually.', 'rockpack-static-ssg' ),
			$state['processed'],
			$state['failed']
		);
		if ( $state['failed'] ) {
			self::record_error( new WP_Error( 'rockpack_ssg_rebuild_batch_failures', $progress_message ) );
		} else {
			self::record_success_if_clear( $progress_message );
		}
	}

	/**
	 * Delete a post's recorded output file and remove its path metadata.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function remove_post_file( $post_id ) {
		$post_id = absint( $post_id );
		$result = self::with_generation_lock(
			$post_id,
			static function () use ( $post_id ) {
				return self::remove_post_file_locked( $post_id );
			}
		);
		if ( is_wp_error( $result ) ) {
			$relative_path = get_post_meta( $post_id, self::META_KEY, true );
			if ( $relative_path ) {
				self::queue_file_cleanup( $relative_path, $post_id );
			}
			self::record_error( $result );
		}
	}

	/**
	 * Delete a snapshot while its corresponding render lock is held.
	 *
	 * @param int $post_id Post ID.
	 * @return true|WP_Error
	 */
	private static function remove_post_file_locked( $post_id ) {
		$relative_path = get_post_meta( $post_id, self::META_KEY, true );
		if ( ! $relative_path ) {
			return true;
		}

		$result = self::delete_file( $relative_path );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		delete_post_meta( $post_id, self::META_KEY );
		self::notify_snapshot_change( self::url_from_relative_path( $relative_path ), $relative_path, 'deleted' );
		self::record_success_if_clear( __( 'Removed the static file for unpublished or deleted content.', 'rockpack-static-ssg' ) );
		return true;
	}

	/**
	 * Add a failed or obsolete file to the durable cleanup queue.
	 *
	 * @param string $relative_path Output path to remove.
	 * @param int    $post_id       Owning post ID, if it still exists.
	 */
	private static function queue_file_cleanup( $relative_path, $post_id = 0 ) {
		if ( ! is_string( $relative_path ) || '' === $relative_path ) {
			self::record_error( new WP_Error( 'rockpack_ssg_cleanup_path_invalid', __( 'Refused to queue an invalid static file path for cleanup.', 'rockpack-static-ssg' ) ) );
			return;
		}

		$queue = get_option( self::CLEANUP_OPTION, array() );
		if ( ! is_array( $queue ) ) {
			$queue = array();
		}
		$existing = is_array( $queue[ $relative_path ] ?? null ) ? $queue[ $relative_path ] : array();
		$queue[ $relative_path ] = array(
			'post_id'   => absint( $post_id ),
			'attempts'  => absint( $existing['attempts'] ?? 0 ),
			'queued_at' => $existing['queued_at'] ?? current_time( 'mysql' ),
		);
		update_option( self::CLEANUP_OPTION, $queue, false );
	}

	/**
	 * Retry queued deletions explicitly requested by an administrator.
	 */
	public static function retry_file_cleanup() {
		$queue = get_option( self::CLEANUP_OPTION, array() );
		if ( ! is_array( $queue ) ) {
			self::record_error( new WP_Error( 'rockpack_ssg_cleanup_queue_invalid', __( 'The saved cleanup queue is invalid and was not processed.', 'rockpack-static-ssg' ) ) );
			return;
		}

		foreach ( $queue as $relative_path => $entry ) {
			if ( self::is_current_public_snapshot_path( $relative_path ) ) {
				unset( $queue[ $relative_path ] );
				continue;
			}

			$result = self::delete_file( $relative_path );
			if ( is_wp_error( $result ) ) {
				$entry = is_array( $entry ) ? $entry : array();
				$entry['attempts'] = absint( $entry['attempts'] ?? 0 ) + 1;
				$entry['last_error'] = $result->get_error_message();
				$queue[ $relative_path ] = $entry;
				self::record_error(
					new WP_Error(
						'rockpack_ssg_cleanup_retry_failed',
						sprintf( __( 'Cleanup retry %1$d failed for %2$s: %3$s', 'rockpack-static-ssg' ), $entry['attempts'], $relative_path, $result->get_error_message() )
					)
				);
				continue;
			}

			unset( $queue[ $relative_path ] );
			$post_id = absint( is_array( $entry ) ? ( $entry['post_id'] ?? 0 ) : 0 );
			if ( $post_id && get_post_meta( $post_id, self::META_KEY, true ) === $relative_path ) {
				delete_post_meta( $post_id, self::META_KEY );
			}
			self::notify_snapshot_change( self::url_from_relative_path( $relative_path ), $relative_path, 'deleted' );
		}
		update_option( self::CLEANUP_OPTION, $queue, false );
		if ( ! $queue ) {
			self::record_success_if_clear( __( 'All queued static-file cleanup operations completed.', 'rockpack-static-ssg' ) );
		}
	}

	/**
	 * Rebuild missing snapshots and remove HTML files no longer owned by public content.
	 */
	public static function reconcile_output() {
		global $wpdb;
		$metadata = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
				self::META_KEY
			),
			ARRAY_A
		);
		if ( ! is_array( $metadata ) || $wpdb->last_error ) {
			self::record_error( new WP_Error( 'rockpack_ssg_reconcile_meta_failed', sprintf( __( 'Could not read generated-file metadata: %s', 'rockpack-static-ssg' ), $wpdb->last_error ) ) );
			return;
		}
		$meta_owners = array();
		foreach ( $metadata as $row ) {
			$meta_owners[ $row['meta_value'] ][] = absint( $row['post_id'] );
		}

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status = %s AND post_type IN (%s, %s) ORDER BY ID ASC",
				'publish',
				'page',
				'post'
			)
		);
		if ( ! is_array( $ids ) || $wpdb->last_error ) {
			self::record_error( new WP_Error( 'rockpack_ssg_reconcile_posts_failed', sprintf( __( 'Could not read published pages and posts: %s', 'rockpack-static-ssg' ), $wpdb->last_error ) ) );
			return;
		}

		$expected_paths = array();
		$reconciliation_failed = false;
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			$stored_path = get_post_meta( $id, self::META_KEY, true );
			if ( ! self::is_public_content( $post ) ) {
				if ( $stored_path ) {
					self::queue_file_cleanup( $stored_path, $id );
				}
				continue;
			}

			$url = get_permalink( $post );
			$expected_path = self::relative_path_from_url( $url );
			if ( is_wp_error( $expected_path ) ) {
				self::record_error( $expected_path );
				$reconciliation_failed = true;
				continue;
			}

			$expected_paths[ $expected_path ] = true;
			if ( $stored_path && $stored_path !== $expected_path ) {
				self::queue_file_cleanup( $stored_path, $id );
			}

			$file_path = self::target_path( $expected_path, false );
			if ( is_wp_error( $file_path ) ) {
				if ( 'rockpack_ssg_missing_directory' === $file_path->get_error_code() ) {
					self::queue_generation( $id );
				} else {
					self::record_error( $file_path );
					$reconciliation_failed = true;
				}
			} elseif ( ! is_file( $file_path ) ) {
				self::queue_generation( $id );
			}
		}

		$root = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . self::OUTPUT_DIR_NAME;
		$root_real = realpath( $root );
		if ( false === $root_real || ! is_dir( $root_real ) || ! is_readable( $root_real ) ) {
			self::record_error( new WP_Error( 'rockpack_ssg_reconcile_directory_missing', __( 'The static output directory is missing or unreadable during reconciliation.', 'rockpack-static-ssg' ) ) );
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $root_real, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);
		foreach ( $iterator as $file ) {
			if ( $file->isLink() || ! $file->isFile() ) {
				continue;
			}

			$relative_path = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root_real ) + 1 ) );
			if ( preg_match( '/^\.rockpack-ssg-/', $file->getFilename() ) ) {
				if ( $file->getMTime() < time() - DAY_IN_SECONDS && ! unlink( $file->getPathname() ) ) {
					self::record_error( new WP_Error( 'rockpack_ssg_temp_cleanup_failed', sprintf( __( 'Could not remove an old temporary file: %s', 'rockpack-static-ssg' ), $relative_path ) ) );
					$reconciliation_failed = true;
				}
				continue;
			}

			if ( '.html' !== strtolower( substr( $file->getFilename(), -5 ) ) || isset( $expected_paths[ $relative_path ] ) ) {
				continue;
			}

			$delete_result = self::delete_file( $relative_path );
			if ( is_wp_error( $delete_result ) ) {
				self::queue_file_cleanup( $relative_path );
				self::record_error( $delete_result );
				$reconciliation_failed = true;
			} else {
				foreach ( $meta_owners[ $relative_path ] ?? array() as $owner_id ) {
					if ( get_post_meta( $owner_id, self::META_KEY, true ) === $relative_path ) {
						delete_post_meta( $owner_id, self::META_KEY );
					}
				}
				self::notify_snapshot_change( self::url_from_relative_path( $relative_path ), $relative_path, 'deleted' );
			}
		}

		if ( ! $reconciliation_failed ) {
			self::record_success_if_clear( __( 'Snapshot reconciliation completed. Review any queued failures and run cleanup or retry as needed.', 'rockpack-static-ssg' ) );
		}
	}

	/**
	 * Protect a currently valid public snapshot from a stale cleanup queue entry.
	 *
	 * @param string $relative_path Relative output path.
	 * @return bool
	 */
	private static function is_current_public_snapshot_path( $relative_path ) {
		$owners = get_posts(
			array(
				'post_type'              => array( 'page', 'post' ),
				'post_status'            => 'publish',
				'numberposts'             => -1,
				'fields'                 => 'ids',
				'meta_key'               => self::META_KEY,
				'meta_value'             => $relative_path,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $owners as $owner_id ) {
			$post = get_post( $owner_id );
			if ( ! self::is_public_content( $post ) ) {
				continue;
			}
			$current_path = self::relative_path_from_url( get_permalink( $post ) );
			if ( ! is_wp_error( $current_path ) && $current_path === $relative_path ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Generate a snapshot immediately for a published post.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function queue_generation( $post_id ) {
		self::generate_scheduled_post( $post_id );
	}

	/**
	 * Retry failed generation and deletion operations from their durable queues.
	 */
	private static function retry_failed_operations() {
		$generation_retries = get_option( self::GENERATION_RETRY_OPTION, array() );
		if ( ! is_array( $generation_retries ) ) {
			self::record_error( new WP_Error( 'rockpack_ssg_generation_queue_invalid', __( 'The saved generation retry list is invalid and was not processed.', 'rockpack-static-ssg' ) ) );
		} else {
			foreach ( array_keys( $generation_retries ) as $post_id ) {
				self::generate_scheduled_post( absint( $post_id ) );
			}
		}

		self::retry_file_cleanup();
		$generation_retries = get_option( self::GENERATION_RETRY_OPTION, array() );
		$cleanup_queue = get_option( self::CLEANUP_OPTION, array() );
		if ( ! is_array( $generation_retries ) || ! is_array( $cleanup_queue ) ) {
			self::record_error( new WP_Error( 'rockpack_ssg_manual_retry_invalid_queue', __( 'At least one saved failure queue is invalid; inspect the PHP error log before retrying.', 'rockpack-static-ssg' ) ) );
		} elseif ( $generation_retries || $cleanup_queue ) {
			self::record_error(
				new WP_Error(
					'rockpack_ssg_manual_retry_incomplete',
					sprintf(
						__( 'Manual retry finished with %1$d generation failures and %2$d cleanup failures still pending.', 'rockpack-static-ssg' ),
						is_array( $generation_retries ) ? count( $generation_retries ) : 1,
						is_array( $cleanup_queue ) ? count( $cleanup_queue ) : 1
					)
				)
			);
		} else {
			self::record_success( __( 'All recorded generation and cleanup failures were resolved.', 'rockpack-static-ssg' ) );
		}
	}

	/**
	 * Store a successful operation for visibility in the admin screen.
	 *
	 * @param string $message Status message.
	 */
	private static function record_success( $message ) {
		update_option(
			self::STATUS_OPTION,
			array(
				'success' => true,
				'message' => $message,
				'time'    => current_time( 'mysql' ),
			),
			false
		);
	}

	/**
	 * Avoid replacing a visible failure with success while failures remain queued.
	 *
	 * @param string $message Status message.
	 */
	private static function record_success_if_clear( $message ) {
		$generation_retries = get_option( self::GENERATION_RETRY_OPTION, array() );
		$cleanup_queue = get_option( self::CLEANUP_OPTION, array() );
		if ( ! is_array( $generation_retries ) || ! is_array( $cleanup_queue ) || $generation_retries || $cleanup_queue ) {
			return;
		}
		self::record_success( $message );
	}

	/**
	 * Store and log an operation error instead of silently failing.
	 *
	 * @param WP_Error $error Operation error.
	 */
	private static function record_error( $error ) {
		$message = $error->get_error_message();
		error_log( '[Rockpack Static SSG] ' . wp_strip_all_tags( $message ) );
		update_option(
			self::STATUS_OPTION,
			array(
				'success' => false,
				'message' => $message,
				'time'    => current_time( 'mysql' ),
			),
			false
		);
	}

	/**
	 * Render a published page or post through WordPress and save its HTML snapshot.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return string|WP_Error Relative output file path on success.
	 */
	public static function generate( $post_id, $generation_version = null ) {
		$post_id = absint( $post_id );
		$generation_version = null === $generation_version
			? self::advance_generation_version( $post_id )
			: (string) $generation_version;

		return self::with_generation_lock(
			$post_id,
			static function () use ( $post_id, $generation_version ) {
				return self::generate_locked( $post_id, $generation_version );
			}
		);
	}

	/**
	 * Render and write a snapshot while the per-post generation lock is held.
	 *
	 * @param int    $post_id            WordPress post ID.
	 * @param string $generation_version Version captured when this render was requested.
	 * @return string|WP_Error
	 */
	private static function generate_locked( $post_id, $generation_version ) {
		$post = get_post( $post_id );
		if ( ! self::is_public_content( $post ) ) {
			return new WP_Error( 'rockpack_ssg_not_public', __( 'Only publicly viewable, password-free published pages and posts can be generated.', 'rockpack-static-ssg' ) );
		}

		$url = get_permalink( $post );
		$home = wp_parse_url( home_url( '/' ) );
		$target = wp_parse_url( $url );
		if ( ! $url || ! is_array( $home ) || ! is_array( $target ) ) {
			return new WP_Error( 'rockpack_ssg_invalid_url', __( 'The page URL is missing or invalid.', 'rockpack-static-ssg' ) );
		}

		$home_scheme = strtolower( $home['scheme'] ?? '' );
		$target_scheme = strtolower( $target['scheme'] ?? '' );
		$home_port = $home['port'] ?? ( 'https' === $home_scheme ? 443 : 80 );
		$target_port = $target['port'] ?? ( 'https' === $target_scheme ? 443 : 80 );
		if (
			! in_array( $home_scheme, array( 'http', 'https' ), true ) ||
			$home_scheme !== $target_scheme ||
			strtolower( $home['host'] ?? '' ) !== strtolower( $target['host'] ?? '' ) ||
			$home_port !== $target_port ||
			isset( $target['user'] ) ||
			isset( $target['pass'] )
		) {
			return new WP_Error( 'rockpack_ssg_invalid_url', __( 'The page URL must use the same origin as this site.', 'rockpack-static-ssg' ) );
		}
		if ( isset( $target['query'] ) && '' !== $target['query'] ) {
			return new WP_Error( 'rockpack_ssg_query_permalink', __( 'Query-based page URLs cannot be mapped to unique static files. Enable path-based permalinks first.', 'rockpack-static-ssg' ) );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'             => 15,
				'redirection'         => 0,
				'limit_response_size'  => 10 * MB_IN_BYTES,
				'headers'             => array(
					'Accept'                  => 'text/html',
					'X-Rockpack-SSG-Render'    => '1',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'rockpack_ssg_request_failed', sprintf( __( 'Could not render the page: %s', 'rockpack-static-ssg' ), $response->get_error_message() ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$html = wp_remote_retrieve_body( $response );
		$cache_control = wp_remote_retrieve_header( $response, 'cache-control' );
		$set_cookie = wp_remote_retrieve_header( $response, 'set-cookie' );
		$set_cookie_present = is_array( $set_cookie ) ? ! empty( $set_cookie ) : '' !== (string) $set_cookie;
		if ( 200 !== $status || '' === trim( $html ) || ( $content_type && false === stripos( $content_type, 'text/html' ) ) ) {
			return new WP_Error( 'rockpack_ssg_invalid_response', sprintf( __( 'The page returned an unsuitable response (HTTP %1$d, %2$s).', 'rockpack-static-ssg' ), $status, $content_type ? $content_type : 'unknown content type' ) );
		}
		if ( $set_cookie_present || ( $cache_control && preg_match( '/(?:private|no-store)/i', is_array( $cache_control ) ? implode( ',', $cache_control ) : $cache_control ) ) ) {
			return new WP_Error( 'rockpack_ssg_personalized_response', __( 'The response sets cookies or is marked private/no-store and cannot be published as a shared static file.', 'rockpack-static-ssg' ) );
		}
		if ( preg_match( '/<input\b[^>]*\bname\s*=\s*["\'][^"\']*(?:nonce|_wpnonce)[^"\']*["\']/i', $html ) ) {
			return new WP_Error( 'rockpack_ssg_nonce_response', __( 'The page contains a form nonce that may expire; keep this page dynamic or provide a static-safe form.', 'rockpack-static-ssg' ) );
		}

		$relative_path = self::relative_path_from_url( $url );
		if ( is_wp_error( $relative_path ) ) {
			return $relative_path;
		}

		$target_path = self::target_path( $relative_path, true );
		if ( is_wp_error( $target_path ) ) {
			return $target_path;
		}

		if ( is_link( $target_path ) ) {
			return new WP_Error( 'rockpack_ssg_symlink', __( 'Refusing to replace a symbolic link in the static output directory.', 'rockpack-static-ssg' ) );
		}

		/**
		 * Allow post-processing the rendered HTML before it is written to disk.
		 * The theme uses this to replace the local dev domain with the production domain.
		 */
		$html = apply_filters( 'rockpack_static_ssg_html', $html, $post );

		$temp_path = tempnam( dirname( $target_path ), '.rockpack-ssg-' );
		if ( false === $temp_path ) {
			return new WP_Error( 'rockpack_ssg_temp_failed', __( 'Could not create a temporary file in the static output directory.', 'rockpack-static-ssg' ) );
		}

		if ( false === file_put_contents( $temp_path, $html, LOCK_EX ) ) {
			unlink( $temp_path );
			return new WP_Error( 'rockpack_ssg_write_failed', __( 'Could not write the rendered HTML to disk.', 'rockpack-static-ssg' ) );
		}

		if ( ! self::generation_is_current( $post->ID, $generation_version ) ) {
			unlink( $temp_path );
			return new WP_Error( 'rockpack_ssg_generation_superseded', __( 'The content changed while its static snapshot was rendering.', 'rockpack-static-ssg' ) );
		}

		if ( ! chmod( $temp_path, 0644 ) ) {
			unlink( $temp_path );
			return new WP_Error( 'rockpack_ssg_permissions_failed', __( 'Could not set readable permissions on the static HTML file.', 'rockpack-static-ssg' ) );
		}

		if ( ! rename( $temp_path, $target_path ) ) {
			unlink( $temp_path );
			return new WP_Error( 'rockpack_ssg_replace_failed', __( 'Could not safely move the rendered HTML into place.', 'rockpack-static-ssg' ) );
		}

		$old_path = get_post_meta( $post->ID, self::META_KEY, true );
		if ( $old_path !== $relative_path && ! update_post_meta( $post->ID, self::META_KEY, $relative_path ) ) {
			return new WP_Error( 'rockpack_ssg_meta_failed', __( 'The HTML was written, but WordPress could not store its path for later cleanup.', 'rockpack-static-ssg' ) );
		}
		if ( $old_path && $old_path !== $relative_path ) {
			$delete_result = self::delete_file( $old_path );
			if ( is_wp_error( $delete_result ) ) {
				self::queue_file_cleanup( $old_path, $post->ID );
				self::record_error( $delete_result );
			} else {
				self::notify_snapshot_change( self::url_from_relative_path( $old_path ), $old_path, 'deleted' );
			}
		}

		self::notify_snapshot_change( $url, $relative_path, 'generated' );
		return $relative_path;
	}

	/**
	 * Convert a same-site URL path to a safe, mirrored HTML file path.
	 *
	 * @param string $url Absolute page URL.
	 * @return string|WP_Error
	 */
	private static function relative_path_from_url( $url ) {
		$parsed = wp_parse_url( $url );
		$path = rawurldecode( $parsed['path'] ?? '/' );
		$segments = explode( '/', trim( $path, '/' ) );
		$segments = array_values( array_filter( $segments, 'strlen' ) );

		foreach ( $segments as $segment ) {
			if ( '.' === $segment || '..' === $segment || false !== strpos( $segment, '\\' ) || false !== strpos( $segment, "\0" ) ) {
				return new WP_Error( 'rockpack_ssg_unsafe_path', __( 'The page URL contains an unsafe path.', 'rockpack-static-ssg' ) );
			}
		}

		return ( $segments ? implode( '/', $segments ) . '/' : '' ) . 'index.html';
	}

	/**
	 * Resolve a relative output path and optionally create its parent directories.
	 *
	 * @param string $relative_path Relative path inside the output directory.
	 * @param bool   $create_parent Whether to create the parent directories.
	 * @return string|WP_Error
	 */
	private static function target_path( $relative_path, $create_parent ) {
		$segments = explode( '/', str_replace( '\\', '/', $relative_path ) );
		foreach ( $segments as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment || false !== strpos( $segment, "\0" ) || false !== strpos( $segment, ':' ) || preg_match( '/^(con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\.|$)/i', $segment ) ) {
				return new WP_Error( 'rockpack_ssg_unsafe_path', __( 'The output path is invalid.', 'rockpack-static-ssg' ) );
			}
		}

		$root = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . self::OUTPUT_DIR_NAME;
		if ( is_link( $root ) ) {
			return new WP_Error( 'rockpack_ssg_symlink', __( 'The static output directory must not be a symbolic link.', 'rockpack-static-ssg' ) );
		}
		if ( ! is_dir( $root ) && $create_parent && ! wp_mkdir_p( $root ) ) {
			return new WP_Error( 'rockpack_ssg_mkdir_failed', __( 'Could not create the static output directory.', 'rockpack-static-ssg' ) );
		}
		if ( ! is_dir( $root ) ) {
			return new WP_Error( 'rockpack_ssg_missing_directory', __( 'The static output directory does not exist.', 'rockpack-static-ssg' ) );
		}

		$root_real = realpath( $root );
		if ( false === $root_real || ! is_writable( $root_real ) ) {
			return new WP_Error( 'rockpack_ssg_unwritable_directory', __( 'The static output directory is not writable.', 'rockpack-static-ssg' ) );
		}

		$current = $root_real;
		$parent_segments = array_slice( $segments, 0, -1 );
		foreach ( $parent_segments as $segment ) {
			$current .= DIRECTORY_SEPARATOR . $segment;
			if ( is_link( $current ) ) {
				return new WP_Error( 'rockpack_ssg_symlink', __( 'A symbolic link was found inside the static output directory.', 'rockpack-static-ssg' ) );
			}
			if ( ! is_dir( $current ) && $create_parent && ! wp_mkdir_p( $current ) ) {
				return new WP_Error( 'rockpack_ssg_mkdir_failed', __( 'Could not create a directory for the static page.', 'rockpack-static-ssg' ) );
			}
			if ( ! is_dir( $current ) ) {
				return new WP_Error( 'rockpack_ssg_missing_directory', __( 'A directory for the static page does not exist.', 'rockpack-static-ssg' ) );
			}

			$real = realpath( $current );
			$prefix = trailingslashit( wp_normalize_path( $root_real ) );
			if ( false === $real || 0 !== stripos( wp_normalize_path( $real ), $prefix ) ) {
				return new WP_Error( 'rockpack_ssg_path_escape', __( 'The output path resolves outside the static output directory.', 'rockpack-static-ssg' ) );
			}
		}

		return $current . DIRECTORY_SEPARATOR . end( $segments );
	}

	/**
	 * Remove a previously generated file without following symlinks.
	 *
	 * @param string $relative_path Stored relative path.
	 * @return true|WP_Error
	 */
	private static function delete_file( $relative_path ) {
		if ( ! is_string( $relative_path ) || '' === $relative_path ) {
			return true;
		}

		$path = self::target_path( $relative_path, false );
		if ( is_wp_error( $path ) ) {
			if ( 'rockpack_ssg_missing_directory' === $path->get_error_code() ) {
				return true;
			}
			return $path;
		}
		if ( is_link( $path ) ) {
			return new WP_Error( 'rockpack_ssg_symlink', __( 'Refusing to delete a symbolic link from the static output directory.', 'rockpack-static-ssg' ) );
		}
		if ( file_exists( $path ) && ! unlink( $path ) ) {
			return new WP_Error( 'rockpack_ssg_delete_failed', __( 'Could not remove the old static HTML file.', 'rockpack-static-ssg' ) );
		}

		return true;
	}

	// ── SEO: sitemap.xml and robots.txt ───────────────────────────────────────

	/**
	 * Generate sitemap.xml and robots.txt in the static output root.
	 * Called from the admin page and after a full rebuild completes.
	 *
	 * @return true|WP_Error
	 */
    /** Serve fresh URLs on WordPress deployments without needing a physical root file. */
    public static function serve_live_sitemap( $wp ) {
        if ( 'sitemap.xml' !== trim( $wp->request, '/' ) ) return;
        status_header( 200 );
        header( 'Content-Type: application/xml; charset=UTF-8' );
        if ( ! get_option( 'blog_public' ) || in_array( wp_get_environment_type(), array( 'local', 'development', 'staging' ), true ) ) header( 'X-Robots-Tag: noindex' );
        echo self::generate_seo_files( true );
        exit;
    }

    public static function add_live_sitemap_to_robots( $output, $public ) {
        if ( $public ) $output .= "\nSitemap: " . home_url( '/sitemap.xml' ) . "\n";
        return $output;
    }

	public static function generate_seo_files( $live = false ) {
        if ( ! $live ) {
		    $root = self::target_path( 'index.html', false );
		    if ( is_wp_error( $root ) ) return $root;
		    $root = dirname( $root );
        }

		$production = get_option( 'rockpack_static_ssg_production_domain', '' );
		$base       = ! $live && $production ? rtrim( $production, '/' ) : rtrim( home_url(), '/' );

        // The deployed site is hybrid: dynamic WordPress routes remain public even
        // when they cannot safely be saved as static HTML (e.g. the contact form).
        $local_base = untrailingslashit( home_url( '/' ) );
        $urls = array();
        $add_url = static function ( $url, $modified = '', $priority = '0.8' ) use ( &$urls, $local_base, $base ) {
            if ( ! is_string( $url ) || ( $url !== $local_base && 0 !== strpos( $url, $local_base . '/' ) && 0 !== strpos( $url, $local_base . '?' ) ) ) return;
            $url = $base . substr( $url, strlen( $local_base ) );
            $urls[ $url ] = array( 'loc' => esc_url_raw( $url ), 'lastmod' => $modified, 'priority' => $priority );
        };
        $posts = get_posts( array(
            'post_type' => array( 'page', 'post', 'rp_job' ), 'post_status' => 'publish',
            'posts_per_page' => -1, 'has_password' => false,
        ) );
        foreach ( $posts as $post ) {
            if ( ! is_post_publicly_viewable( $post ) || '' !== (string) $post->post_password || ( class_exists( 'Portable_SEO' ) ? Portable_SEO::noindex( $post->ID ) : '1' === get_post_meta( $post->ID, '_rp_seo_noindex', true ) ) ) continue;
            $public = true;
            foreach ( get_post_ancestors( $post ) as $ancestor_id ) {
                $ancestor = get_post( $ancestor_id );
                if ( ! $ancestor || 'publish' !== $ancestor->post_status || '' !== (string) $ancestor->post_password ) { $public = false; break; }
            }
            if ( ! $public ) continue;
            $modified = $post->post_modified_gmt && '0000-00-00 00:00:00' !== $post->post_modified_gmt ? gmdate( 'Y-m-d', strtotime( $post->post_modified_gmt ) ) : '';
            $add_url( get_permalink( $post ), $modified, (int) get_option( 'page_on_front' ) === $post->ID ? '1.0' : '0.8' );
        }
        // Listing pages have distinct canonical URLs and can contain unique entries.
        if ( function_exists( 'rockpack_news_url' ) ) {
            $news_url = rockpack_news_url();
            $news = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 6, 'post__not_in' => array_filter( array( rockpack_sample_news_id() ) ), 'ignore_sticky_posts' => true ) );
            if ( ! ( class_exists( 'Portable_SEO' ) ? Portable_SEO::noindex( (int) get_option( 'page_for_posts' ) ) : '1' === get_post_meta( (int) get_option( 'page_for_posts' ), '_rp_seo_noindex', true ) ) ) {
                $add_url( $news_url );
                for ( $page = 2; $page <= $news->max_num_pages; $page++ ) $add_url( add_query_arg( 'page', $page, $news_url ) );
            }
        }
        if ( function_exists( 'rockpack_careers_url' ) ) {
            $careers_url = rockpack_careers_url();
            $jobs = new WP_Query( array( 'post_type' => 'rp_job', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 9, 'meta_query' => array( rockpack_open_job_condition() ) ) );
            $add_url( $careers_url );
            for ( $page = 2; $page <= $jobs->max_num_pages; $page++ ) $add_url( trailingslashit( $careers_url ) . user_trailingslashit( 'page/' . $page, 'paged' ) );
        }

		// Build sitemap.xml
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ( $urls as $entry ) {
			$xml .= "  <url>\n";
			$xml .= '    <loc>' . esc_html( $entry['loc'] ) . "</loc>\n";
			if ( $entry['lastmod'] ) $xml .= '    <lastmod>' . esc_html( $entry['lastmod'] ) . "</lastmod>\n";
			$xml .= '    <changefreq>weekly</changefreq>' . "\n";
			$xml .= '    <priority>' . esc_html( $entry['priority'] ) . "</priority>\n";
			$xml .= "  </url>\n";
		}
		$xml .= '</urlset>';
        if ( $live ) return $xml;

		$sitemap_path = $root . DIRECTORY_SEPARATOR . 'sitemap.xml';
		if ( false === file_put_contents( $sitemap_path, $xml, LOCK_EX ) ) {
			return new WP_Error( 'rockpack_ssg_sitemap_write', __( 'Could not write sitemap.xml.', 'rockpack-static-ssg' ) );
		}
		chmod( $sitemap_path, 0644 );

		// Build robots.txt
		$sitemap_url = $base . '/sitemap.xml';
		$robots  = "User-agent: *\n";
        if ( ! get_option( 'blog_public' ) ) $robots .= "Disallow: /\n";
		$robots .= "Disallow: /wp-admin/\n";
		$robots .= "Disallow: /wp-login.php\n";
		if ( get_option( 'blog_public' ) ) $robots .= "Allow: /\n";
		$robots .= "\n";
		$robots .= 'Sitemap: ' . $sitemap_url . "\n";

		$robots_path = $root . DIRECTORY_SEPARATOR . 'robots.txt';
		if ( false === file_put_contents( $robots_path, $robots, LOCK_EX ) ) {
			return new WP_Error( 'rockpack_ssg_robots_write', __( 'Could not write robots.txt.', 'rockpack-static-ssg' ) );
		}
		chmod( $robots_path, 0644 );

		return true;
	}
}

Rockpack_Static_SSG::init();
register_activation_hook( __FILE__, array( 'Rockpack_Static_SSG', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Rockpack_Static_SSG', 'deactivate' ) );
