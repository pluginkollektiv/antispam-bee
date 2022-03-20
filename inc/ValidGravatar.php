<?php

namespace AntispamBee\Rules;

class ValidGravatar implements Verifiable, Controllable {

	use InitRule;

	public static function init() {
		add_filter( 'asb_rules', function( $rules ) {
			$rules[] = [
				'weight' => self::get_weight(),
				'name' => self::get_name(),
				'callable' => array( self::class, 'verify' ),
			];
		} );
	}

	public static function verify( $data ) {
		if ( ! isset( $data['email'] ) ) {
			return;
		}

		$response = wp_safe_remote_get(
			sprintf(
				'https://www.gravatar.com/avatar/%s?d=404',
				md5( strtolower( trim( $data['email'] ) ) )
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		if ( wp_remote_retrieve_response_code( $response ) === 200 ) {
			return true;
		}

		return false;
	}

	public static function get_name() {
		return '';
	}

	public static function get_weight() {
		return 1;
	}

	public static function get_slug() {
		return 'asb-valid-gravatar';
	}

	public static function is_final() {
		return false;
	}

	public static function get_label() {
		__( 'Trust commenters with a Gravatar', 'antispam-bee' );
	}

	public static function get_description() {
		$link1 = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">',
			esc_url(
				__( 'https://antispambee.pluginkollektiv.org/documentation/#trust-commenters-with-a-gravatar', 'antispam-bee' ),
				'https'
			)
		);
		printf(
		/* translators: 1: opening <a> tag with link to documentation. 2: closing </a> tag */
			esc_html__( 'Check if commenter has a Gravatar image. Please note the %1$sprivacy notice%2$s for this option.', 'antispam-bee' ),
			wp_kses_post( $link1 ),
			'</a>'
		);
	}

	public static function render() {
		return '';
	}
}