<?php
/**
 * Disable cross-document view transitions on the front end during E2E tests.
 *
 * Ipsum, the default theme of WordPress 7.2, opts into view transitions with
 * `@view-transition { navigation: auto; }`. With it, the page after a navigation
 * from the front end stops rendering frames in headless Chromium, and every
 * Playwright click there times out waiting for the element to be stable. The
 * last `@view-transition` rule wins, so this one is printed at the end of
 * `wp_head`.
 *
 * @package AntispamBee
 */

add_action(
	'wp_head',
	static function (): void {
		echo "<style>@view-transition { navigation: none; }</style>\n";
	},
	PHP_INT_MAX
);
