<?php
/**
 * Plugin Name: Language API Override
 *
 * Hooks antispam_bee_detected_lang to query the local asb-lang-api Docker
 * container. wp_safe_remote_post rejects internal Docker hostnames, so we
 * must call wp_remote_post directly here rather than using the
 * antispam_bee_lang_api_url filter.
 *
 * The 10-word minimum from LangSpam::verify() is replicated here so that
 * short comments are not sent to franc and bypass language detection, matching
 * the production behaviour.
 */
add_filter(
	'antispam_bee_detected_lang',
	function ( $detected_language, string $comment_text ) {
		$words = preg_split( '/[\n\r\t ]+/', trim( $comment_text ), -1, PREG_SPLIT_NO_EMPTY );
		if ( count( $words ) < 10 ) {
			return $detected_language;
		}

		$response = wp_remote_post(
			'http://asb-lang-api:3000/',
			[ 'body' => wp_json_encode( [ 'body' => $comment_text ] ) ]
		);

		if ( is_wp_error( $response )
			|| wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return $detected_language;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! $body || ! isset( $body->code ) || $body->code === 'und' ) {
			return $detected_language;
		}

		return \AntispamBee\Helpers\LangHelper::map( $body->code );
	},
	10,
	2
);
