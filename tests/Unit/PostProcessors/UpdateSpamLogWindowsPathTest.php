<?php

namespace AntispamBee\Tests\Unit\PostProcessors;

use AntispamBee\PostProcessors\UpdateSpamLog;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\stubs;

/**
 * Covers the deprecated `ANTISPAM_BEE_LOG_FILE` constant's path guard.
 *
 * `validate_file()` exists to constrain a relative path a theme/plugin editor
 * writes; a resolved log path is already fully trusted `wp-config.php` input,
 * so rejecting every code it reports — including the one for a Windows
 * absolute drive path (`validate_file( 'C:\...' )` returns 2) — broke spam
 * logging on every Windows/IIS install without ever running on Windows: the
 * guard was dropped entirely, so any path shape a real filesystem accepts is
 * now written to. A literal `C:\...` path cannot be exercised end-to-end on
 * the Linux test runner, so this covers the one invalid `validate_file()`
 * code that *can* be realised on both: a path containing an interior `../`.
 *
 * A constant cannot be undefined once it is set, so this needs its own
 * process, which is why it lives in its own file rather than alongside
 * {@see UpdateSpamLogTest}.
 */
class UpdateSpamLogWindowsPathTest extends TestCase {

	/**
	 * The constant is trusted input, so a path containing `../` is not a
	 * traversal attempt to guard against — it is simply how some layouts
	 * build `WP_CONTENT_DIR` (`__DIR__ . '/../..'`), which PHP does not
	 * normalise. `validate_file()` reported this as code 1 and used to
	 * reject it outright.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_writes_to_a_path_containing_a_parent_reference(): void {
		$dir = sys_get_temp_dir() . '/asb-spam-log-parent-ref-' . uniqid();
		mkdir( $dir . '/sub', 0777, true );
		$log_file = $dir . '/sub/../asb-spam.log';

		// is_writable() checks the file itself, not its directory, for one that
		// does not exist yet — matches how the sibling UpdateSpamLogTest pre-seeds
		// its own log file in set_up().
		file_put_contents( $log_file, '' );

		define( 'ANTISPAM_BEE_LOG_FILE', $log_file );

		stubs( [ 'current_time' => '2026-01-15 10:23:45' ] );

		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		// The deprecated constant writes the pre-3.0 line, unaffected by this change.
		self::assertSame(
			'2026-01-15 10:23:45 comment for post=474 from host=192.0.2.42 marked as spam' . PHP_EOL,
			(string) file_get_contents( $dir . '/asb-spam.log' ),
			'a path containing ../ should no longer be rejected outright'
		);
	}
}
