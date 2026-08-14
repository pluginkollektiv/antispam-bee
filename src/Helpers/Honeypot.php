<?php
/**
 * The Honeypot field.
 *
 * @package AntispamBee\Fields
 */

namespace AntispamBee\Helpers;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Honeypot field.
 */
class Honeypot {
	/**
	 * Option that holds the salt the field names are derived from.
	 *
	 * @var string
	 */
	public const SALT_OPTION = 'antispam_bee_honeypot_salt';

	/**
	 * Inject the honeypot field.
	 *
	 * @param string                $markup  The field markup.
	 * @param array<string, string> $options {
	 *                           The field options.
	 *
	 * @type string  $form_id    The form id.
	 * @type string  $form_name  The form name.
	 * @type string  $field_type The field type.
	 * @type string  $field_id   The field id.
	 * @type string  $field_name The field name.
	 *                           }
	 *
	 * @return string The markup with the injected honeypot field.
	 */
	public static function inject( string $markup, array $options ): string {
		$dom = new DOMDocument();
		// Malformed or HTML5-only markup must not bubble up as PHP warnings.
		$use_internal_errors = libxml_use_internal_errors( true );
		$dom->loadHTML( $markup, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $use_internal_errors );

		$xpath     = new DOMXPath( $dom );
		$node_list = $xpath->query( '//*[@id="' . $options['field_id'] . '"]' );
		$input     = $node_list ? $node_list->item( 0 ) : null;
		if ( ! $input instanceof DOMElement ) {
			return $markup;
		}

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$id_attr   = $input->attributes->getNamedItem( 'id' );
		$name_attr = $input->attributes->getNamedItem( 'name' );
		if ( null === $id_attr || null === $name_attr ) {
			return $markup;
		}

		$input_type    = $input->nodeName;
		$honeypot_id   = $id_attr->textContent;
		$honeypot_name = $name_attr->textContent;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		/**
		 * Filter to change the inline styles of the honeypot field.
		 *
		 * This filter can also be used to use an empty value and load the
		 * styles from an external file if a strict CSP is used for the site.
		 *
		 * @see: https://wordpress.org/support/topic/honeypot-textarea-visible-with-strict-csp-header/
		 *
		 * @since 3.0.0
		 *
		 * @param string $honeypot_styles The inline styles for the honeypot.
		 */
		$honeypot_styles = apply_filters( 'antispam_bee_honeypot_styles', 'padding:0 !important;clip:rect(1px, 1px, 1px, 1px) !important;position:absolute !important;white-space:nowrap !important;height:1px !important;width:1px !important;overflow:hidden !important;' );

		$attributes_string = sprintf(
			'id="%s" name="%s" aria-hidden="true" aria-label="hp-comment" autocomplete="new-password" tabindex="-1" style="%s"',
			$honeypot_id,
			$honeypot_name,
			$honeypot_styles
		);
		switch ( $input_type ) {
			case 'textarea':
				$regex = str_replace(
					[ '{{HONEYPOT_ID}}', '{{HONEYPOT_NAME}}' ],
					[ $honeypot_id, $honeypot_name ],
					'/(?P<all>                                    (?# match the whole textarea tag )
						<textarea                                        (?# the opening of the textarea and some optional attributes )
						(                                                (?# match a id attribute followed by some optional ones and the name attribute )
							(?P<before1>[^>]*)
							(?P<id1>id=["\']?{{HONEYPOT_ID}}["\']?)
							(?P<between1>[^>]*)
							name=["\']?{{HONEYPOT_NAME}}["\']?
							|                                            (?# match same as before, but with the name attribute before the id attribute )
							(?P<before2>[^>]*)
							name=["\']?{{HONEYPOT_NAME}}["\']?
							(?P<between2>[^>]*)
							(?P<id2>id=["\']?{{HONEYPOT_ID}}["\']?)
							|                                            (?# match same as before, but with no id attribute )
							(?P<before3>[^>]*)
							name=["\']?{{HONEYPOT_NAME}}["\']?
							(?P<between3>[^>]*)
						)
						(?P<after>[^>]*)                                 (?# match any additional optional attributes )
						>                                                (?# the closing of the textarea opening tag )
						(?s)(?P<content>.*?)                             (?# any textarea content )
						<\/textarea>                                     (?# the closing textarea tag )
					)/x'
				);

				$markup = preg_replace_callback(
					$regex,
					function ( array $matches ) use ( $honeypot_id, $attributes_string ) {
						$output = '<textarea autocomplete="new-password" ' . $matches['before1'] . $matches['before2'] . $matches['before3'];

						$id_script = '';
						if ( ! empty( $matches['id1'] ) || ! empty( $matches['id2'] ) ) {
							$output .= 'id="' . self::get_secret_id_for_post() . '" ';
							if ( ! self::is_amp() ) {
								$id_script = sprintf(
									'<script data-noptimize>document.getElementById("%1$s").setAttribute( "id", "a%2$s" );document.getElementById("%3$s").setAttribute( "id", "%1$s" );</script>',
									$honeypot_id,
									esc_js( substr( md5( (string) time() ), 0, 31 ) ),
									esc_js( self::get_secret_id_for_post() )
								);
							}
						}

						$output .= ' name="' . esc_attr( self::get_secret_name_for_post() ) . '" ';
						$output .= $matches['between1'] . $matches['between2'] . $matches['between3'];
						$output .= $matches['after'] . '>';
						$output .= $matches['content'];
						$output .= '</textarea><textarea ' . $attributes_string . '></textarea>';

						$output .= $id_script;

						return $output;
					},
					$markup
				) ?? $markup;
				break;
			default:
				break;
		}

		return $markup;
	}


	/**
	 * Return the secret of a post used in the textarea id attribute.
	 *
	 * @return string The secret used in the textarea id attribute.
	 */
	public static function get_secret_id_for_post(): string {
		$secret = substr( sha1( md5( 'comment-id' . self::get_salt() ) ), 0, 10 );

		return self::ensure_secret_starts_with_letter( $secret );
	}

	/**
	 * Get the current salt.
	 *
	 * The honeypot field names are derived from this, and they are rendered into
	 * the comment form. A page cache serving a form whose field name no longer
	 * matches makes the plugin treat a genuine comment as an invalid request, so
	 * the salt is stored rather than derived on every call: deriving it from the
	 * `wp-config.php` salts would change every field name whenever those are
	 * rotated or the site is migrated.
	 *
	 * @return string The current salt.
	 */
	private static function get_salt(): string {
		$salt = get_option( self::SALT_OPTION );

		if ( ! is_string( $salt ) || '' === $salt ) {
			$salt = self::store_salt();
		}

		/**
		 * Filters the salt the honeypot field names are derived from.
		 *
		 * The stored salt never changes on its own, which is what keeps cached
		 * comment forms valid. Filtering it rotates every field name, so a form
		 * already sitting in a page cache stops matching until that cache is
		 * cleared.
		 *
		 * @since 3.0.0
		 *
		 * @param string $salt The stored salt.
		 */
		return (string) apply_filters( 'antispam_bee_honeypot_salt', $salt );
	}

	/**
	 * Create and store the salt.
	 *
	 * @return string The stored salt.
	 */
	private static function store_salt(): string {
		$salt = self::initial_salt();

		// A concurrent request may have stored one first; that one wins.
		if ( ! add_option( self::SALT_OPTION, $salt ) ) {
			$stored = get_option( self::SALT_OPTION );

			if ( is_string( $stored ) && '' !== $stored ) {
				return $stored;
			}
		}

		return $salt;
	}

	/**
	 * The value to seed the stored salt with.
	 *
	 * Seeding from `NONCE_SALT` reproduces the field names a site is already
	 * serving, so storing the salt does not invalidate forms that are already in
	 * a page cache. A configured salt is only used when it looks generated; a
	 * random value is generated otherwise, which is the case where the previous
	 * derivation was predictable.
	 *
	 * @return string The initial salt.
	 */
	private static function initial_salt(): string {
		if ( defined( 'NONCE_SALT' ) && is_string( \NONCE_SALT ) && Salt::is_generated( \NONCE_SALT ) ) {
			return substr( sha1( \NONCE_SALT ), 0, 10 );
		}

		return substr( sha1( Salt::generate() ), 0, 10 );
	}


	/**
	 * Ensure that the secret starts with a letter.
	 *
	 * @param string $secret The secret.
	 *
	 * @return string The secret starting with a letter.
	 */
	public static function ensure_secret_starts_with_letter( string $secret ): string {
		$first_char = substr( $secret, 0, 1 );
		if ( is_numeric( $first_char ) ) {
			return chr( ( (int) $first_char % 10 ) + 97 ) . substr( $secret, 1 );
		}

		return $secret;
	}

	/**
	 * Test if we are on an AMP site.
	 *
	 * Starting with v2.0, amp_is_request() is the preferred method to check,
	 * but we fall back to the then deprecated is_amp_endpoint() as needed.
	 *
	 * @return bool Whether we are on an AMP site.
	 */
	private static function is_amp(): bool {
		return ( function_exists( 'amp_is_request' ) && amp_is_request() ) || ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() );
	}

	/**
	 * Return the secret of a post used in the textarea name attribute.
	 *
	 * @return string The secret used in the textarea name attribute.
	 */
	public static function get_secret_name_for_post(): string {
		$secret = substr( sha1( md5( 'comment-id' . self::get_salt() ) ), 0, 10 );

		return self::ensure_secret_starts_with_letter( $secret );
	}
}
