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
	 * File name prefix of the debug log.
	 *
	 * @var string
	 */
	const LOG_PREFIX = 'asb-debug';

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

		$log_file = static::get_log_file();

		if ( null === $log_file ) {
			return;
		}

		$date = date( 'Y-m-d' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$time = date( 'H-i-s' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		// Comment data reaches the log, so a line break in the message would let
		// an attacker forge additional log entries.
		$message = (string) preg_replace( '/[\r\n]+/', ' ', $message );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug use.
		error_log( "[{$date} {$time}] {$message}\n", 3, $log_file );
	}

	/**
	 * Get the debug log file path.
	 *
	 * A generated name carries a salted suffix, because the log contains comment
	 * data and `WP_CONTENT_DIR` is usually served publicly. {@see LogPath::suffix()}
	 * yields nothing when no secret salt is available, in which case this returns
	 * `null` and logging is skipped rather than writing to a guessable file name.
	 *
	 * @return string|null Path, or null when debug logging is off.
	 */
	public static function get_log_file(): ?string {
		$log_file = LogPath::resolve( 'ANTISPAM_BEE_DEBUG_LOG', 'ANTISPAM_BEE_DEBUG_LOG_DIR', self::LOG_PREFIX, true );

		if ( null !== $log_file ) {
			return $log_file;
		}

		// Deprecated since 3.0.0, use `ANTISPAM_BEE_DEBUG_LOG` instead.
		if ( defined( 'ANTISPAM_BEE_DEBUG_MODE_ENABLED' ) && \ANTISPAM_BEE_DEBUG_MODE_ENABLED ) {
			return LogPath::generate( 'ANTISPAM_BEE_DEBUG_LOG_DIR', self::LOG_PREFIX, true );
		}

		return null;
	}

	/**
	 * Is debug mode enabled?
	 *
	 * @return bool Whether debug mode is enabled.
	 */
	public static function enabled(): bool {
		if ( null === static::$debug_mode_enabled ) {
			static::$debug_mode_enabled = null !== static::get_log_file();
		}

		return static::$debug_mode_enabled;
	}
}
