<?php

namespace AntispamBee\Tests\Unit\PostProcessors;

use AntispamBee\PostProcessors\UpdateSpamLog;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\stubs;

/**
 * Covers the `_doing_it_wrong()` call on the deprecated `ANTISPAM_BEE_LOG_FILE` path.
 *
 * A constant cannot be undefined once it is set, so this needs its own process, which is why
 * it lives in its own file rather than alongside {@see UpdateSpamLogTest}.
 */
class UpdateSpamLogDeprecatedConstantTest extends TestCase {

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_resolving_the_deprecated_constant_triggers_doing_it_wrong(): void {
		$log_file = sys_get_temp_dir() . '/asb-spam-log-deprecated-' . uniqid() . '.log';
		file_put_contents( $log_file, '' );

		define( 'ANTISPAM_BEE_LOG_FILE', $log_file );

		$notices = [];
		stubs(
			[
				'current_time'    => '2026-01-15 10:23:45',
				'_doing_it_wrong' => static function ( $function, $message ) use ( &$notices ): void {
					$notices[] = $function;
				},
			]
		);

		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame( [ 'ANTISPAM_BEE_LOG_FILE' ], $notices );

		unlink( $log_file );
	}
}
