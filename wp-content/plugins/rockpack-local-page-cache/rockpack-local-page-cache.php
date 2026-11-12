<?php
/**
 * Plugin Name: Rockpack Local Page Cache
 * Description: A conservative file-backed full-page cache for anonymous public page requests in local development.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Rock Pack
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rockpack_Local_Page_Cache {
	const CACHE_DIRECTORY = 'cache/rockpack-full-page';
	const ENABLED_FILE    = '.enabled';
	const GENERATION_FILE = '.generation';
	const LOCK_FILE       = '.lock';

	/**
	 * Register cache storage and WordPress invalidation hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), PHP_INT_MAX );
		add_action( 'save_post', array( __CLASS__, 'flush' ), 10, 0 );
		add_action( 'deleted_post', array( __CLASS__, 'flush' ), 10, 0 );
		add_action( 'trashed_post', array( __CLASS__, 'flush' ), 10, 0 );
		add_action( 'untrashed_post', array( __CLASS__, 'flush' ), 10, 0 );
		add_action( 'wp_update_nav_menu', array( __CLASS__, 'flush' ), 10, 0 );
		add_action( 'customize_save_after', array( __CLASS__, 'flush' ), 10, 0 );
		add_action( 'switch_theme', array( __CLASS__, 'flush' ), 10, 0 );
		add_action( 'rockpack_static_ssg_snapshot_changed', array( __CLASS__, 'flush' ), 10, 0 );
		add_action( 'updated_option', array( __CLASS__, 'flush_after_relevant_option_change' ), 10, 1 );
		add_action( 'added_option', array( __CLASS__, 'flush_after_relevant_option_change' ), 10, 1 );
		add_action( 'deleted_option', array( __CLASS__, 'flush_after_relevant_option_change' ), 10, 1 );
	}

	/**
	 * Mark the standalone drop-in as active and remove stale entries.
	 */
	public static function activate() {
		$directory = self::cache_directory();
		if ( ! wp_mkdir_p( $directory ) ) {
			wp_die( esc_html__( 'Rockpack Local Page Cache could not create its cache directory.', 'rockpack-local-page-cache' ) );
		}

		if ( false === file_put_contents( self::enabled_file(), "enabled\n", LOCK_EX ) ) {
			wp_die( esc_html__( 'Rockpack Local Page Cache could not enable its cache drop-in.', 'rockpack-local-page-cache' ) );
		}

		self::flush();
	}

	/**
	 * Disable the drop-in before clearing its files.
	 */
	public static function deactivate() {
		if ( file_exists( self::enabled_file() ) ) {
			unlink( self::enabled_file() );
		}

		self::flush();
	}

	/**
	 * Begin buffering only an anonymous, cacheable public page response.
	 */
	public static function start_buffer() {
		if ( ! self::is_cacheable_request() ) {
			header( 'X-Rockpack-Page-Cache: BYPASS' );
			return;
		}

		header( 'X-Rockpack-Page-Cache: MISS' );
		header( 'Cache-Control: no-cache, max-age=0, must-revalidate', true );
		// Keep the generation from request start. A flush that happens while this
		// response renders makes its eventual cache write a no-op.
		$GLOBALS['rockpack_local_page_cache_generation'] = self::read_generation();
		ob_start( array( __CLASS__, 'store_response' ) );
	}

	/**
	 * Persist a successful public HTML response atomically.
	 *
	 * @param string $html Response body.
	 * @return string
	 */
	public static function store_response( $html ) {
		if ( ! self::is_cacheable_response() || '' === $html ) {
			return $html;
		}

		$generation = $GLOBALS['rockpack_local_page_cache_generation'] ?? self::read_generation();
		if ( ! is_string( $generation ) ) {
			return $html;
		}

		self::with_cache_lock(
			static function () use ( $generation, $html ) {
				$current_generation = self::read_generation();

				// Existing cache directories may predate generation tracking. Establish a
				// token under the same lock before caching their first new response.
				if ( '' === $generation ) {
					if ( '' !== $current_generation || ! self::write_generation() ) {
						return;
					}

					$generation = self::read_generation();
				}

				if ( '' === $generation || ! hash_equals( self::read_generation(), $generation ) ) {
					return;
				}

				$path      = self::cache_file_for_current_request();
				$temporary = $path . '.' . wp_generate_uuid4() . '.tmp';
				if ( false !== file_put_contents( $temporary, $html, LOCK_EX ) ) {
					@rename( $temporary, $path );
					if ( file_exists( $temporary ) ) {
						@unlink( $temporary );
					}
				}
			}
		);

		return $html;
	}

	/**
	 * Clear every local page-cache entry. This is intentionally broad for a small local site.
	 */
	public static function flush() {
		$directory = self::cache_directory();
		if ( ! wp_mkdir_p( $directory ) ) {
			return;
		}

		self::with_cache_lock(
			static function () use ( $directory ) {
				if ( ! self::write_generation() ) {
					return;
				}
				foreach ( glob( trailingslashit( $directory ) . '*.html' ) ?: array() as $file ) {
					@unlink( $file );
				}
			}
		);
	}

	/**
	 * Flush after site settings that can change rendered Rockpack output.
	 *
	 * @param string $option Option name.
	 */
	public static function flush_after_relevant_option_change( $option ) {
		if (
			0 === strpos( $option, 'rockpack_' ) ||
			in_array( $option, array( 'show_on_front', 'page_on_front', 'page_for_posts', 'permalink_structure' ), true )
		) {
			self::flush();
		}
	}

	/**
	 * Check request state after WordPress has resolved the queried object.
	 *
	 * @return bool
	 */
	private static function is_cacheable_request() {
		if ( ! self::enabled() || is_admin() || is_user_logged_in() || ! ( is_front_page() || is_page() ) || is_page( 'contact-us' ) ) {
			return false;
		}

		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
			return false;
		}

		return self::is_anonymous_get_request();
	}

	/**
	 * Confirm the response remains safe for a shared page cache.
	 *
	 * @return bool
	 */
	private static function is_cacheable_response() {
		if ( 200 !== http_response_code() || headers_sent() ) {
			return false;
		}

		foreach ( headers_list() as $header ) {
			if ( 0 === stripos( $header, 'Set-Cookie:' ) || ( 0 === stripos( $header, 'Cache-Control:' ) && preg_match( '/(?:private|no-store)/i', $header ) ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Cache only cookie-free GET or HEAD requests without a query string or authorization.
	 *
	 * @return bool
	 */
	private static function is_anonymous_get_request() {
		$method = strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' );
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) || ! empty( $_GET ) || ! empty( $_SERVER['HTTP_COOKIE'] ) || ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) || ! empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) || ! empty( $_SERVER['HTTP_X_ROCKPACK_SSG_RENDER'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	private static function enabled() {
		return file_exists( self::enabled_file() );
	}

	/**
	 * Read the cache generation token used to reject stale response writers.
	 *
	 * @return string
	 */
	private static function read_generation() {
		$generation = @file_get_contents( self::generation_file() );
		return is_string( $generation ) ? trim( $generation ) : '';
	}

	/**
	 * Advance the cache generation before removing entries.
	 *
	 * @return bool
	 */
	private static function write_generation() {
		return false !== file_put_contents( self::generation_file(), wp_generate_uuid4() . "\n", LOCK_EX );
	}

	/**
	 * Serialize cache commits and flushes so an earlier response cannot write
	 * after an invalidation completes.
	 *
	 * @param callable $operation Operation to run while locked.
	 * @return void
	 */
	private static function with_cache_lock( $operation ) {
		$handle = @fopen( trailingslashit( self::cache_directory() ) . self::LOCK_FILE, 'c' );
		if ( false === $handle ) {
			return;
		}
		if ( ! flock( $handle, LOCK_EX ) ) {
			fclose( $handle );
			return;
		}

		try {
			call_user_func( $operation );
		} finally {
			flock( $handle, LOCK_UN );
			fclose( $handle );
		}
	}

	/**
	 * @return string
	 */
	private static function cache_directory() {
		return WP_CONTENT_DIR . '/' . self::CACHE_DIRECTORY;
	}

	/**
	 * @return string
	 */
	private static function enabled_file() {
		return trailingslashit( self::cache_directory() ) . self::ENABLED_FILE;
	}

	/**
	 * @return string
	 */
	private static function generation_file() {
		return trailingslashit( self::cache_directory() ) . self::GENERATION_FILE;
	}

	/**
	 * @return string
	 */
	private static function cache_file_for_current_request() {
		$host = strtolower( preg_replace( '/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? '' ) );
		$path = $_SERVER['REQUEST_URI'] ?? '/';
		return trailingslashit( self::cache_directory() ) . hash( 'sha256', $host . '|' . $path ) . '.html';
	}
}

Rockpack_Local_Page_Cache::init();
register_activation_hook( __FILE__, array( 'Rockpack_Local_Page_Cache', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Rockpack_Local_Page_Cache', 'deactivate' ) );
