<?php
/**
 * Plugin Name: Language API Override
 *
 * Hooks the existing antispam_bee_detected_lang filter to query the local
 * asb-lang-api Docker container instead of the remote API. wp_remote_post
 * (without "safe") is used because wp_safe_remote_post rejects internal
 * Docker hostnames that have no public TLD.
 */
add_filter(
	'antispam_bee_detected_lang',
	function ( $detected_language, string $comment_text ) {
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
			// 'und' means franc could not determine the language (text too short
			// or ambiguous). Return null so the original LangSpam code path
			// takes over, which applies the 10-word minimum before flagging.
			return $detected_language;
		}

		return \AntispamBee\Helpers\LangHelper::map( $body->code );
	},
	10,
	2
);
