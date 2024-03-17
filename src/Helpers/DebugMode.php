<?php
/**
 * Debug Mode.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Debug Mode.
 */
class DebugMode {
	/**
	 * Is debug mode enabled?
	 *
	 * @var bool|null
	 */
	protected static $debug_mode_enabled = null;

	/**
	 * Is debug mode enabled?
	 *
	 * @return bool
	 */
	public static function enabled() {
		if ( null === static::$debug_mode_enabled ) {
			static::$debug_mode_enabled = defined( 'ANTISPAM_BEE_DEBUG_MODE_ENABLED' ) ? \ANTISPAM_BEE_DEBUG_MODE_ENABLED : false;
		}

		return static::$debug_mode_enabled;
	}

	/**
	 * Generate a log message, if debug mode is enabled.
	 *
	 * @param string $message Log message.
	 * @return void
	 */
	public static function log( string $message ) {
		if ( ! static::enabled() ) {
			return;
		}

		$date        = date( 'Y-m-d' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$time        = date( 'H-i-s' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$content_dir = \WP_CONTENT_DIR;

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug use.
		error_log( "[{$date} {$time}] {$message}\n", 3, "{$content_dir}/asb-debug.{$date}.log" );
	}
}
