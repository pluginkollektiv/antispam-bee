<?php
/**
 * Salt helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Resolve the secret the plugin derives identifiers from.
 */
class Salt {
	/**
	 * Shortest configured salt still treated as generated.
	 *
	 * @var int
	 */
	private const MIN_LENGTH = 32;

	/**
	 * Get the secret to derive identifiers from.
	 *
	 * `NONCE_SALT` is preferred over `wp_salt()` so the derived values stay the
	 * same as in Antispam Bee 2.x, where the honeypot field names were hashed
	 * from that constant alone. `wp_salt( 'nonce' )` returns `NONCE_KEY` and
	 * `NONCE_SALT` concatenated, which is a different input and would therefore
	 * rename every honeypot field once, on upgrade, for every site.
	 *
	 * The constant is read with `constant()` rather than imported with
	 * `use const`, because an import has to resolve during static analysis and
	 * would need `NONCE_SALT` declared in `phpstan-bootstrap.php` to do so.
	 *
	 * @return string The secret, empty if none is available.
	 */
	public static function get(): string {
		$configured = defined( 'NONCE_SALT' ) ? constant( 'NONCE_SALT' ) : null;

		/*
		 * `wp-config.php` can define the constant as anything, and the file is not
		 * validated, so a value that is not a string counts as not configured. The
		 * check lives here rather than in the signature below, because without
		 * `strict_types` an array would raise a `TypeError` on the way in.
		 */
		return self::resolve( is_string( $configured ) ? $configured : null );
	}

	/**
	 * Choose between a configured salt and the one WordPress manages.
	 *
	 * Split from {@see self::get()} so the choice can be tested without defining
	 * a constant, which a test cannot undo for the tests that follow it.
	 *
	 * @param string|null $configured The configured salt, or null if there is none
	 *                                or it was not defined as a string.
	 *
	 * @return string The secret, empty if none is available.
	 */
	public static function resolve( ?string $configured ): string {
		if ( null !== $configured && self::is_generated( $configured ) ) {
			return $configured;
		}

		return wp_salt( 'nonce' );
	}

	/**
	 * Whether a configured salt looks like a generated one.
	 *
	 * The placeholders `wp-config-sample.php` ships are natural-language phrases,
	 * and localised WordPress packages translate them, so comparing against the
	 * English `put your unique phrase here` would miss a German or French install
	 * that was never configured. Core's own `wp_salt()` has that same blind spot:
	 * it seeds its list of non-secrets with the English phrase only, so it hashes
	 * a translated placeholder as if it were a secret.
	 *
	 * Generated salts — both the ones api.wordpress.org hands out and the ones
	 * WordPress stores itself — are printable ASCII containing no whitespace.
	 * Requiring some length and rejecting whitespace therefore recognises a real
	 * salt whatever the locale, and rejects a placeholder phrase in any language.
	 *
	 * A hand-written passphrase is rejected too. That is deliberate: such a value
	 * is not guessable, but the value `wp_salt()` falls back to is no worse.
	 *
	 * @param string $salt The configured salt.
	 *
	 * @return bool Whether the salt looks generated.
	 */
	public static function is_generated( string $salt ): bool {
		return strlen( $salt ) >= self::MIN_LENGTH && ! preg_match( '/\s/', $salt );
	}
}
