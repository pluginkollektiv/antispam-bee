<?php
/**
 * Log file path resolution.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Resolves the log file paths from their `wp-config.php` constants.
 */
class LogPath {

	/**
	 * Resolve a log file path from its constants.
	 *
	 * The log constant doubles as the switch that turns the log on, following the shape of
	 * `WP_DEBUG_LOG`:
	 *
	 * - undefined, `false` or an empty string — the log is off
	 * - `true` — on, written to a generated file name inside the directory constant, or
	 *   `WP_CONTENT_DIR` when that is unset
	 * - a string — on, written to exactly that path
	 *
	 * A generated name carries a salted suffix, because the default directory is usually
	 * served publicly and both logs contain personal data. An explicit path is used verbatim
	 * and never salted: a Fail2Ban jail has to be told the name in advance.
	 *
	 * @param string $log_constant Name of the constant that enables the log.
	 * @param string $dir_constant Name of the constant holding the target directory.
	 * @param string $prefix       File name prefix, also salted into the suffix.
	 * @param bool   $dated        Whether a generated name carries the current date.
	 *
	 * @return string|null Absolute path, or null when the log is off.
	 */
	public static function resolve( string $log_constant, string $dir_constant, string $prefix, bool $dated = false ): ?string {
		if ( ! defined( $log_constant ) ) {
			return null;
		}

		$value = constant( $log_constant );

		if ( is_string( $value ) ) {
			return '' === $value ? null : $value;
		}

		if ( true !== $value ) {
			return null;
		}

		return self::generate( $dir_constant, $prefix, $dated );
	}

	/**
	 * Build a generated log file path.
	 *
	 * @param string $dir_constant Name of the constant holding the target directory.
	 * @param string $prefix       File name prefix, also salted into the suffix.
	 * @param bool   $dated        Whether the name carries the current date.
	 *
	 * @return string Absolute path.
	 */
	public static function generate( string $dir_constant, string $prefix, bool $dated = false ): ?string {
		$suffix = self::suffix( $prefix );

		if ( null === $suffix ) {
			return null;
		}

		$name = $prefix;

		if ( $dated ) {
			$name .= '.' . date( 'Y-m-d' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- File name, matches the timestamps written inside.
		}

		$name .= '.' . $suffix . '.log';

		return self::directory( $dir_constant ) . '/' . $name;
	}

	/**
	 * Get the directory a generated log file is written to.
	 *
	 * @param string $dir_constant Name of the constant holding the target directory.
	 *
	 * @return string Directory without a trailing separator.
	 */
	private static function directory( string $dir_constant ): string {
		$dir = defined( $dir_constant ) ? constant( $dir_constant ) : null;

		if ( ! is_string( $dir ) || '' === $dir ) {
			$dir = WP_CONTENT_DIR;
		}

		return rtrim( $dir, '/\\' );
	}

	/**
	 * Get the salted file name suffix.
	 *
	 * The logs contain personal data and `WP_CONTENT_DIR` is usually served publicly, so a
	 * generated file name must not be guessable.
	 *
	 * {@see Salt::get()} prefers a configured `NONCE_SALT` and otherwise falls back to
	 * `wp_salt()`, so a secret is normally always available. Should it ever yield nothing,
	 * this returns `null` and the caller skips logging rather than writing to a file name
	 * derived from a predictable value.
	 *
	 * @param string $prefix File name prefix.
	 *
	 * @return string|null Twelve hexadecimal characters, or null if no secret salt is available.
	 */
	public static function suffix( string $prefix ): ?string {
		$salt = Salt::get();

		if ( '' === $salt ) {
			return null;
		}

		return substr( sha1( $prefix . $salt ), 0, 12 );
	}

	/**
	 * Whether a log file can be written to.
	 *
	 * A generated file does not exist before the first write, so an absent file is writable
	 * when its directory is.
	 *
	 * @param string $path Log file path.
	 *
	 * @return bool Whether the path can be written to.
	 */
	public static function is_writable( string $path ): bool {
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- WP_Filesystem cannot perform an atomic FILE_APPEND | LOCK_EX write to the log file.
		if ( file_exists( $path ) ) {
			return is_writable( $path );
		}

		return is_writable( dirname( $path ) );
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
	}
}
