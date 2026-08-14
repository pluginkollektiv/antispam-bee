<?php
/**
 * Debug Mode.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

use const ANTISPAM_BEE_DEBUG_MODE_ENABLED;
use const WP_CONTENT_DIR;

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
	 * Generate a log message, if debug mode is enabled.
	 *
	 * @param string $message Log message.
	 *
	 * @return void
	 */
	public static function log( string $message ): void {
		if ( ! static::enabled() ) {
			return;
		}

		$date        = date( 'Y-m-d' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$time        = date( 'H-i-s' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$content_dir = WP_CONTENT_DIR;
		$suffix      = self::get_log_file_suffix();

		if ( null === $suffix ) {
			return;
		}

		// Comment data reaches the log, so a line break in the message would let
		// an attacker forge additional log entries.
		$message = (string) preg_replace( '/[\r\n]+/', ' ', $message );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug use.
		error_log( "[{$date} {$time}] {$message}\n", 3, "{$content_dir}/asb-debug.{$date}.{$suffix}.log" );
	}

	/**
	 * Get the salted log file suffix.
	 *
	 * The log contains comment data and `WP_CONTENT_DIR` is usually served
	 * publicly, so the file name must not be guessable.
	 *
	 * {@see Salt::get()} prefers a configured `NONCE_SALT` and otherwise falls
	 * back to `wp_salt()`, so a secret is normally always available. Should it
	 * ever yield nothing, this returns `null` and logging is skipped rather than
	 * falling back to a predictable value.
	 *
	 * @return string|null Suffix, or null if no secret salt is available.
	 */
	private static function get_log_file_suffix(): ?string {
		$salt = Salt::get();

		if ( '' === $salt ) {
			return null;
		}

		return substr( sha1( 'asb-debug' . $salt ), 0, 12 );
	}

	/**
	 * Is debug mode enabled?
	 *
	 * @return bool Whether debug mode is enabled.
	 */
	public static function enabled(): bool {
		if ( null === static::$debug_mode_enabled ) {
			static::$debug_mode_enabled = defined( 'ANTISPAM_BEE_DEBUG_MODE_ENABLED' ) ? ANTISPAM_BEE_DEBUG_MODE_ENABLED : false;
		}

		return static::$debug_mode_enabled;
	}
}
