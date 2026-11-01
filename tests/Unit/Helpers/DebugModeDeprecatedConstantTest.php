<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\DebugMode;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\stubs;

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', sys_get_temp_dir() );
}

/**
 * Covers the `_doing_it_wrong()` call on the deprecated `ANTISPAM_BEE_DEBUG_MODE_ENABLED` path.
 *
 * A constant cannot be undefined once it is set, so this needs its own process, which is why
 * it lives in its own file rather than alongside {@see DebugModeTest}.
 */
class DebugModeDeprecatedConstantTest extends TestCase {

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_resolving_the_deprecated_constant_triggers_doing_it_wrong(): void {
		define( 'ANTISPAM_BEE_DEBUG_MODE_ENABLED', true );

		$notices = [];
		stubs(
			[
				'wp_salt'         => 'test-salt',
				'_doing_it_wrong' => static function ( $function, $message ) use ( &$notices ): void {
					$notices[] = $function;
				},
			]
		);

		$log_file = DebugMode::get_log_file();

		self::assertNotNull( $log_file, 'The deprecated constant must still resolve a log file' );
		self::assertSame( [ 'ANTISPAM_BEE_DEBUG_MODE_ENABLED' ], $notices );
	}
}
