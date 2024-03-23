<?php
/**
 * The Honeypot field.
 *
 * @package AntispamBee\Fields
 */

namespace AntispamBee\Helpers;

use DOMDocument;
use DOMXPath;

/**
 * Honeypot field.
 */
class Honeypot {
	/**
	 * The salt used for the dynamic field name.
	 *
	 * @var string
	 */
	protected $salt;

	/**
	 * Inject the honeypot field.
	 *
	 * @param string $markup The field markup.
	 * @param array  $options {
	 *       The field options.
	 *
	 * @type string $form_id The form id.
	 * @type string $form_name The form name.
	 * @type string $field_type The field type.
	 * @type string $field_id The field id.
	 * @type string $field_name The field name.
	 * }
	 *
	 * @return string
	 */
	public static function inject( string $markup, array $options ): string {
		$dom = new DOMDocument();
		$dom->loadHTML( $markup, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$xpath = new DOMXPath( $dom );
		$input = $xpath->query( '//*[@id="' . $options['field_id'] . '"]' )->item( 0 );
		if ( ! $input ) {
			return '';
		}

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$input_type    = $input->nodeName;
		$honeypot_id   = $input->attributes->getNamedItem( 'id' )->textContent;
		$honeypot_name = $input->attributes->getNamedItem( 'name' )->textContent;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$attributes_string = sprintf(
			'id="%s" name="%s" aria-hidden="true" aria-label="hp-comment" autocomplete="new-password" tabindex="-1" style="padding:0 !important;clip:rect(1px, 1px, 1px, 1px) !important;position:absolute !important;white-space:nowrap !important;height:1px !important;width:1px !important;overflow:hidden !important;"',
			$honeypot_id,
			$honeypot_name
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
							(?P<id1>id=["\']{{HONEYPOT_ID}}["\'])
							(?P<between1>[^>]*)
							name=["\']{{HONEYPOT_NAME}}["\']
							|                                            (?# match same as before, but with the name attribute before the id attribute )
							(?P<before2>[^>]*)
							name=["\']{{HONEYPOT_NAME}}["\']
							(?P<between2>[^>]*)
							(?P<id2>id=["\']{{HONEYPOT_ID}}["\'])
							|                                            (?# match same as before, but with no id attribute )
							(?P<before3>[^>]*)
							name=["\']{{HONEYPOT_NAME}}["\']
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
					function ( $matches ) use ( $honeypot_id, $honeypot_name, $attributes_string ) {
						$output = '<textarea autocomplete="new-password" ' . $matches['before1'] . $matches['before2'] . $matches['before3'];

						$id_script = '';
						if ( ! empty( $matches['id1'] ) || ! empty( $matches['id2'] ) ) {
							$output .= 'id="' . self::get_secret_id_for_post() . '" ';
							if ( ! self::is_amp() ) {
								$id_script = sprintf(
									'<script data-noptimize>document.getElementById("%1$s").setAttribute( "id", "a%2$s" );document.getElementById("%3$s").setAttribute( "id", "%1$s" );</script>',
									$honeypot_id,
									esc_js( substr( md5( time() ), 0, 31 ) ),
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
				);
				break;
			// Todo: Add the possibility to use an input instead a textarea (for next version).
			default:
				break;
		}

		return $markup;
	}


	/**
	 * Returns the secret of a post used in the textarea id attribute.
	 *
	 * @return string
	 * @since 2.10.0 Modify secret generation because `always_allowed` option no longer exists
	 */
	public static function get_secret_id_for_post(): string {
		$secret = substr( sha1( md5( 'comment-id' . self::get_salt() ) ), 0, 10 );

		return self::ensure_secret_starts_with_letter( $secret );
	}

	/**
	 * Returns the secret of a post used in the textarea name attribute.
	 *
	 * @return string
	 * @since 2.10.0 Modify secret generation because `always_allowed` option no longer exists
	 */
	public static function get_secret_name_for_post(): string {
		$secret = substr( sha1( md5( 'comment-id' . self::get_salt() ) ), 0, 10 );

		return self::ensure_secret_starts_with_letter( $secret );
	}

	/**
	 * Ensures that the secret starts with a letter.
	 *
	 * @param string $secret The secret.
	 *
	 * @return string
	 */
	public static function ensure_secret_starts_with_letter( string $secret ): string {
		$first_char = substr( $secret, 0, 1 );
		if ( is_numeric( $first_char ) ) {
			return chr( $first_char + 97 ) . substr( $secret, 1 );
		}

		return $secret;
	}

	/**
	 * Testing if we are on an AMP site.
	 *
	 * Starting with v2.0, amp_is_request() is the preferred method to check,
	 * but we fall back to the then deprecated is_amp_endpoint() as needed.
	 *
	 * @return bool
	 */
	private static function is_amp(): bool {
		return ( function_exists( 'amp_is_request' ) && amp_is_request() ) || ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() );
	}

	/**
	 * Get the current salt.
	 *
	 * @return string
	 */
	private static function get_salt(): string {
		$salt = defined( 'NONCE_SALT' ) ? NONCE_SALT : ABSPATH;

		return substr( sha1( $salt ), 0, 10 );
	}
}
