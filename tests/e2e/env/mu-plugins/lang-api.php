<?php
/**
 * Plugin Name: Language API Override
 *
 * Points the Antispam Bee language API at the local asb-lang-api Docker
 * container and allows wp_safe_remote_post to reach it. Two filters:
 *
 * 1. `antispam_bee_lang_api_url`      — overrides the endpoint URL.
 * 2. `http_request_host_is_external`  — tells wp_http_validate_url() that the
 *    internal Docker hostname is an allowed external host.
 *
 * Port 8080 is used because it is in WordPress's built-in safe-port list
 * (80, 443, 8080) on all supported WP versions, including 5.6.
 *
 * All production logic in LangSpam::verify() (word-count guard, error
 * handling, language mapping) runs unchanged.
 */
add_filter(
	'antispam_bee_lang_api_url',
	function () {
		return 'http://asb-lang-api:8080/';
	}
);

add_filter(
	'http_request_host_is_external',
	function ( $is_external, $host ) {
		if ( 'asb-lang-api' === $host ) {
			return true;
		}

		return $is_external;
	},
	10,
	2
);
