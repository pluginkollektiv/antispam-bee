<?php
/**
 * Plugin Name: Language API Override
 *
 * Points the Antispam Bee language API at the local asb-lang-api Docker
 * container and allows wp_safe_remote_post to reach it. Three filters:
 *
 * 1. `antispam_bee_lang_api_url`      — overrides the endpoint URL.
 * 2. `http_request_host_is_external`  — tells wp_http_validate_url() that the
 *    internal Docker hostname is an allowed external host.
 * 3. `http_allowed_safe_ports`        — adds port 3000 to the safe-port list,
 *    which wp_http_validate_url() checks after the host check.
 *
 * All production logic in LangSpam::verify() (word-count guard, error
 * handling, language mapping) runs unchanged.
 */
add_filter(
	'antispam_bee_lang_api_url',
	function () {
		return 'http://asb-lang-api:3000/';
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

add_filter(
	'http_allowed_safe_ports',
	function ( $ports, $host ) {
		if ( 'asb-lang-api' === $host ) {
			$ports[] = 3000;
		}

		return $ports;
	},
	10,
	2
);
