<?php

namespace AntispamBee\Tests\Unit\PostProcessors;

use AntispamBee\PostProcessors\UpdateSpamLog;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\stubs;

/**
 * An {@see UpdateSpamLog} whose log path and format are injected rather than resolved from
 * constants.
 *
 * Which constant actually supplied the path is {@see UpdateSpamLog::uses_legacy_format()}'s own
 * concern, covered indirectly through {@see UpdateSpamLogTest}; what the pre-3.0 format writes,
 * once that is true, is this one's.
 */
class InjectedLegacyUpdateSpamLog extends UpdateSpamLog {

	/**
	 * Path returned instead of resolving one.
	 *
	 * @var string
	 */
	public static $path;

	/**
	 * Get the injected path.
	 *
	 * @return string|null The injected path.
	 */
	public static function get_log_file(): ?string {
		return self::$path;
	}

	/**
	 * Always use the pre-3.0 format, regardless of which constant is defined.
	 *
	 * @return bool Always true.
	 */
	protected static function uses_legacy_format(): bool {
		return true;
	}
}

/**
 * Unit tests for the pre-3.0 line format {@see UpdateSpamLog} writes for
 * `ANTISPAM_BEE_LOG_FILE`.
 */
class UpdateSpamLogLegacyFormatTest extends TestCase {

	/**
	 * Path the injected log writes to.
	 *
	 * @var string
	 */
	private $log_file;

	public function set_up(): void {
		parent::set_up();

		$this->log_file                       = sys_get_temp_dir() . '/asb-legacy-spam-log-test.log';
		InjectedLegacyUpdateSpamLog::$path     = $this->log_file;
		file_put_contents( $this->log_file, '' );

		stubs(
			[
				'current_time'  => '2026-01-15 10:23:45',
				'validate_file' => 0,
			]
		);
	}

	public function tear_down(): void {
		if ( file_exists( $this->log_file ) ) {
			unlink( $this->log_file );
		}

		parent::tear_down();
	}

	/**
	 * @return string The contents of the log file.
	 */
	private function get_log(): string {
		return (string) file_get_contents( $this->log_file );
	}

	/**
	 * The exact line Antispam Bee 2.x wrote, so a Fail2Ban jail with `marked as spam$`
	 * keeps matching after the upgrade to 3.0.
	 */
	public function test_writes_the_pre_3_0_line(): void {
		InjectedLegacyUpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame(
			'2026-01-15 10:23:45 comment for post=474 from host=192.0.2.42 marked as spam' . PHP_EOL,
			$this->get_log()
		);
	}

	/**
	 * The pre-3.0 format predates both the fields and the reasons; neither one is
	 * part of the line it writes.
	 */
	public function test_ignores_the_spam_reasons(): void {
		InjectedLegacyUpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot', 'asb-too-fast-submit' ],
			]
		);

		self::assertStringNotContainsString( 'asb-honeypot', $this->get_log() );
	}

	/**
	 * The new-format filters are new-format-only features: the promise is that the
	 * old constant keeps writing exactly what it always wrote.
	 */
	public function test_is_not_affected_by_the_new_format_filters(): void {
		\Brain\Monkey\Functions\expect( 'apply_filters' )
			->with( 'antispam_bee_spam_log_entry', \Mockery::any(), \Mockery::any() )
			->never();
		\Brain\Monkey\Functions\expect( 'apply_filters' )
			->with( 'antispam_bee_spam_log_fields', \Mockery::any(), \Mockery::any() )
			->never();

		InjectedLegacyUpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertStringContainsString( 'marked as spam', $this->get_log() );
	}

	/**
	 * A reaction type that never has a comment post, such as a form submission, had
	 * no equivalent in 2.x; `%d` on a missing value writes `0` rather than nothing,
	 * which is what the original `sprintf()` format would have done too.
	 */
	public function test_writes_zero_for_a_reaction_without_a_post(): void {
		InjectedLegacyUpdateSpamLog::process(
			[
				'reaction_type' => 'my_plugin_form',
				'ip'            => '192.0.2.43',
			]
		);

		self::assertStringContainsString( 'post=0', $this->get_log() );
	}
}
