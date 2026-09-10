<?php
/**
 * PHPUnit bootstrap for the integration test suite.
 *
 * Boots a real WordPress against a real database. Deliberately does NOT reuse
 * `tests/bootstrap.php`: that file loads the function stubs from `tests/_stubs/`,
 * which would shadow the real WordPress functions this suite exists to exercise.
 *
 * The WordPress test library is located through the `WP_TESTS_DIR` environment
 * variable, which `wp-env` sets to `/wordpress-phpunit` inside its `tests-cli`
 * container. Run the suite with `npm run test:integration`.
 *
 * @package AntispamBee
 */

namespace AntispamBee\Tests;

use Yoast\WPTestUtils\WPIntegration;

require_once dirname( __DIR__ ) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

$asb_tests_dir = WPIntegration\get_path_to_wp_test_dir();

if ( false === $asb_tests_dir ) {
	echo PHP_EOL, 'ERROR: Could not find the WordPress test library. Set the WP_TESTS_DIR ';
	echo 'environment variable, or run the suite via `npm run test:integration`.', PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter().
require_once $asb_tests_dir . 'includes/functions.php';

/*
 * Load the plugin as a mu-plugin so it is active for every test without going
 * through the activation hooks.
 */
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__ ) . '/antispam_bee.php';
	}
);

WPIntegration\bootstrap_it();
