<?php

namespace AntispamBee\Tests\Unit\PostProcessors;

use AntispamBee\PostProcessors\UpdateSpamLog;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\stubs;

if ( ! defined( 'ANTISPAM_BEE_LOG_FILE' ) ) {
	define( 'ANTISPAM_BEE_LOG_FILE', sys_get_temp_dir() . '/asb-spam-log-test.log' );
}

/**
 * Unit tests for {@see UpdateSpamLog}.
 */
class UpdateSpamLogTest extends TestCase {

	public function set_up(): void {
		parent::set_up();

		file_put_contents( ANTISPAM_BEE_LOG_FILE, '' );

		stubs(
			[
				'current_time'  => '2026-01-15 10:23:45',
				'validate_file' => 0,
			]
		);
	}

	public function tear_down(): void {
		if ( file_exists( ANTISPAM_BEE_LOG_FILE ) ) {
			unlink( ANTISPAM_BEE_LOG_FILE );
		}

		parent::tear_down();
	}

	/**
	 * @return string The contents of the log file.
	 */
	private function get_log(): string {
		return (string) file_get_contents( ANTISPAM_BEE_LOG_FILE );
	}

	public function test_logs_a_comment_with_its_spam_reason(): void {
		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame(
			'2026-01-15 10:23:45 comment for post=474 from host=192.0.2.42 marked as spam (asb-honeypot)' . PHP_EOL,
			$this->get_log()
		);
	}

	public function test_logs_every_reason_it_was_given(): void {
		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot', 'asb-too-fast-submit' ],
			]
		);

		self::assertStringContainsString( '(asb-honeypot,asb-too-fast-submit)', $this->get_log() );
	}

	/**
	 * Without reasons the line has to stay byte-identical to the one the log has
	 * used since 2.5.7, so existing Fail2Ban filters keep matching.
	 */
	public function test_keeps_the_legacy_line_when_there_are_no_reasons(): void {
		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [],
			]
		);

		self::assertSame(
			'2026-01-15 10:23:45 comment for post=474 from host=192.0.2.42 marked as spam' . PHP_EOL,
			$this->get_log()
		);
	}

	public function test_logs_a_reaction_that_is_not_a_comment(): void {
		UpdateSpamLog::process(
			[
				'reaction_type' => 'my_plugin_form',
				'ip'            => '192.0.2.42',
				'asb_reasons'   => [ 'asb-honeypot' ],
			]
		);

		self::assertSame(
			'2026-01-15 10:23:45 my_plugin_form from host=192.0.2.42 marked as spam (asb-honeypot)' . PHP_EOL,
			$this->get_log()
		);
	}

	public function test_reports_a_failure_for_an_item_without_a_usable_ip(): void {
		$processed = UpdateSpamLog::process(
			[
				'reaction_type' => 'my_plugin_form',
				'ip'            => 'not-an-ip',
				'asb_reasons'   => [ 'asb-honeypot' ],
			]
		);

		self::assertSame( [ 'asb-update-spam-log' ], $processed['asb_post_processors_failed'] );
		self::assertSame( '', $this->get_log() );
	}

	public function test_writes_what_the_filter_returns(): void {
		expectApplied( 'antispam_bee_spam_log_entry' )
			->once()
			->andReturn( '192.0.2.42,asb-honeypot' );

		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame( '192.0.2.42,asb-honeypot' . PHP_EOL, $this->get_log() );
	}

	public function test_skips_the_item_when_the_filter_returns_an_empty_string(): void {
		expectApplied( 'antispam_bee_spam_log_entry' )->andReturn( '' );

		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame( '', $this->get_log() );
	}

	/**
	 * A multi-line entry would break every parser reading the file line by line.
	 */
	public function test_forces_a_filtered_entry_onto_a_single_line(): void {
		expectApplied( 'antispam_bee_spam_log_entry' )->andReturn( "first\nsecond\n" );

		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame( 'first second' . PHP_EOL, $this->get_log() );
	}

	public function test_appends_to_an_existing_log(): void {
		$item = [
			'reaction_type'     => 'comment',
			'comment_post_ID'   => 474,
			'comment_author_IP' => '192.0.2.42',
			'asb_reasons'       => [ 'asb-honeypot' ],
		];

		UpdateSpamLog::process( $item );
		UpdateSpamLog::process( $item );

		self::assertCount( 2, array_filter( explode( PHP_EOL, $this->get_log() ) ) );
	}
}
