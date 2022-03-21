<?php

namespace AntispamBee\Fields;

use DOMDocument;
use DOMXPath;

class Honeypot {
	protected $salt;

	/**
	 * $options['form_id'];
	$options['form_name'];
	$options['field_type'];
	$options['field_id'];
	$options['field_name'];
	 * @param $markup
	 * @param $options
	 */
	public static function inject( $markup, $options ) {
		$dom = new DOMDocument();
		$dom->loadHTML( $markup, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$xpath = new DOMXPath( $dom );
		$input = $xpath->query( '//*[@id="' . $options['field_id'] . '"]' )->item( 0 );
		if ( ! $input ) {
			return;
		}

		$input_type = $input->nodeName;
		$honeypot_id = $input->attributes->getNamedItem( 'id' )->textContent;
		$honeypot_name = $input->attributes->getNamedItem( 'name' )->textContent;
		$attributes_string = sprintf(
			'id="%s" name="%s" aria-hidden="true" aria-label="hp-comment" autocomplete="new-password" tabindex="-1" style="padding:0 !important;clip:rect(1px, 1px, 1px, 1px) !important;position:absolute !important;white-space:nowrap !important;height:1px !important;width:1px !important;overflow:hidden !important;"',
			$honeypot_id,
			$honeypot_name
		);
		switch ( $input_type ) {
			case 'textarea':
				$item = sprintf(
				'<textarea %s></textarea>',
					$attributes_string
				);

				$regex = str_replace(
					[ '{{HONEYPOT_ID}}', '{{HONEYPOT_NAME}}' ],
					[ $honeypot_id, $honeypot_name ],
					'/(?P<all>
						<textarea
						(
							(?P<before1>[^>]*)
							(?P<id1>id=["\']{{HONEYPOT_ID}}["\'])
							(?P<between1>[^>]*)
							name=["\']{{HONEYPOT_NAME}}["\']
							|
							(?P<before2>[^>]*)
							name=["\']{{HONEYPOT_NAME}}["\']
							(?P<between2>[^>]*)
							(?P<id2>id=["\']{{HONEYPOT_ID}}["\'])
							|
							(?P<before3>[^>]*)
							name=["\']{{HONEYPOT_NAME}}["\']
							(?P<between3>[^>]*)
						)
						(?P<after>[^>]*)
						>
						(?s)(?P<content>.*?)
						<\/textarea>
					)/x'
				);

				$markup = preg_replace_callback( $regex, function( $matches ) use ( $honeypot_id, $honeypot_name, $attributes_string ) {
					$output = '<textarea autocomplete="new-password" ' . $matches['before1'] . $matches['before2'] . $matches['before3'];

					$id_script = '';
					if ( ! empty( $matches['id1'] ) || ! empty( $matches['id2'] ) ) {
						$output .= 'id="' . self::get_secret_id_for_post() . '" ';
						if ( ! self::_is_amp() ) {
							$id_script = '<script data-noptimize type="text/javascript">document.getElementById("' . $honeypot_id . '").setAttribute( "id", "a' . substr( esc_js( md5( time() ) ), 0, 31 ) . '" );document.getElementById("' . esc_js( self::get_secret_id_for_post() ) . '").setAttribute( "id", "' . $honeypot_id . '" );</script>';
						}
					}

					$output .= ' name="' . esc_attr( self::get_secret_name_for_post() ) . '" ';
					$output .= $matches['between1'] . $matches['between2'] . $matches['between3'];
					$output .= $matches['after'] . '>';
					$output .= $matches['content'];
					$output .= '</textarea><textarea ' . $attributes_string . '></textarea>';

					$output .= $id_script;
					return $output;
				}, $markup );
				break;
			case 'input':
				$item = sprintf(
					'<input %s>',
					$attributes_string
				);

				break;
			default:
				break;
		}

		return $markup;
	}


	/**
	 * Returns the secret of a post used in the textarea id attribute.
	 *
	 * @since 2.10.0 Modify secret generation because `always_allowed` option not longer exists
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string
	 */
	public static function get_secret_id_for_post() {
		$secret = substr( sha1( md5( 'comment-id' . self::get_salt() ) ), 0, 10 );
		return self::ensure_secret_starts_with_letter( $secret );
	}

	/**
	 * Returns the secret of a post used in the textarea name attribute.
	 *
	 * @since 2.10.0 Modify secret generation because `always_allowed` option not longer exists
	 *
	 * @param int $post_id The Post ID.
	 *
	 * @return string
	 */
	public static function get_secret_name_for_post() {
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
	public static function ensure_secret_starts_with_letter( $secret ) {

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
	private static function _is_amp() {
		return ( function_exists( 'amp_is_request' ) && amp_is_request() ) || ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() );
	}

	private static function get_salt() {
		$salt = defined( 'NONCE_SALT' ) ? NONCE_SALT : ABSPATH;
		return substr( sha1( $salt ), 0, 10 );
	}
}
