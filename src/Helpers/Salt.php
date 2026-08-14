<?php
/**
 * Salt helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Recognise and generate the secrets the plugin derives identifiers from.
 */
class Salt {
	/**
	 * Shortest configured salt still treated as generated.
	 *
	 * @var int
	 */
	private const MIN_LENGTH = 32;

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
	 * {@see self::generate()} produces — are printable ASCII containing no
	 * whitespace. Requiring some length and rejecting whitespace therefore
	 * recognises a real salt whatever the locale, and rejects a placeholder
	 * phrase in any language.
	 *
	 * A hand-written passphrase is rejected too. That is deliberate: such a value
	 * is not guessable, but a generated one is no worse.
	 *
	 * @param string $salt The configured salt.
	 *
	 * @return bool Whether the salt looks generated.
	 */
	public static function is_generated( string $salt ): bool {
		return strlen( $salt ) >= self::MIN_LENGTH && ! preg_match( '/\s/', $salt );
	}

	/**
	 * Generate a salt.
	 *
	 * @return string The generated salt.
	 */
	public static function generate(): string {
		return wp_generate_password( 64, true, true );
	}
}
