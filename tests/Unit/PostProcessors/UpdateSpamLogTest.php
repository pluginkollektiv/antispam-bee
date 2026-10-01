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
			'2026-01-15T10:23:45+00:00 ip=192.0.2.42 type=comment post=474 reasons=asb-honeypot' . PHP_EOL,
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

		self::assertStringContainsString( 'reasons=asb-honeypot,asb-too-fast-submit', $this->get_log() );
	}

	/**
	 * A field that does not apply is written as `-` rather than left out, so every
	 * line carries the same field set and a parser never has to cope with a missing
	 * key.
	 */
	public function test_writes_a_placeholder_when_there_are_no_reasons(): void {
		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [],
			]
		);

		self::assertSame(
			'2026-01-15T10:23:45+00:00 ip=192.0.2.42 type=comment post=474 reasons=-' . PHP_EOL,
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
			'2026-01-15T10:23:45+00:00 ip=192.0.2.42 type=my_plugin_form post=- reasons=asb-honeypot' . PHP_EOL,
			$this->get_log()
		);
	}

	/**
	 * The offset is what makes the timestamp usable for `findtime` on a site whose
	 * timezone differs from the server's.
	 */
	public function test_appends_the_utc_offset_of_the_site(): void {
		stubs(
			[
				'current_time' => static function ( $type, $gmt = false ) {
					return $gmt ? '2026-01-15 09:23:45' : '2026-01-15 10:23:45';
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

		self::assertStringStartsWith( '2026-01-15T10:23:45+01:00 ', $this->get_log() );
	}

	public function test_appends_a_negative_utc_offset(): void {
		stubs(
			[
				'current_time' => static function ( $type, $gmt = false ) {
					return $gmt ? '2026-01-15 10:23:45' : '2026-01-15 05:53:45';
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

		self::assertStringStartsWith( '2026-01-15T05:53:45-04:30 ', $this->get_log() );
	}

	/**
	 * A reaction type and rule slugs can come from a third-party integration, and a
	 * value containing a space would break the `key=value` shape for every parser.
	 */
	public function test_strips_characters_that_would_break_the_line(): void {
		UpdateSpamLog::process(
			[
				'reaction_type' => 'my plugin/form!',
				'ip'            => '192.0.2.42',
				'asb_reasons'   => [ 'weird slug()', '' ],
			]
		);

		self::assertSame(
			'2026-01-15T10:23:45+00:00 ip=192.0.2.42 type=mypluginform post=- reasons=weirdslug,unknown' . PHP_EOL,
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

	/**
	 * The point of the fields filter: adding a field should not mean rebuilding the
	 * whole line and re-implementing its shape.
	 */
	public function test_a_filter_can_append_a_field(): void {
		expectApplied( 'antispam_bee_spam_log_fields' )
			->once()
			->andReturnUsing(
				static function ( $fields ) {
					$fields['score'] = 7.5;

					return $fields;
				}
			);

		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame(
			'2026-01-15T10:23:45+00:00 ip=192.0.2.42 type=comment post=474 reasons=asb-honeypot score=7.5' . PHP_EOL,
			$this->get_log()
		);
	}

	public function test_a_filter_can_remove_a_field(): void {
		expectApplied( 'antispam_bee_spam_log_fields' )
			->once()
			->andReturnUsing(
				static function ( $fields ) {
					unset( $fields['post'] );

					return $fields;
				}
			);

		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame(
			'2026-01-15T10:23:45+00:00 ip=192.0.2.42 type=comment reasons=asb-honeypot' . PHP_EOL,
			$this->get_log()
		);
	}

	/**
	 * A value with a space in it would split into two fields and quietly corrupt
	 * every line the filter touches.
	 */
	public function test_a_filtered_value_cannot_break_the_line(): void {
		expectApplied( 'antispam_bee_spam_log_fields' )
			->once()
			->andReturnUsing(
				static function ( $fields ) {
					$fields['note']      = "two words\nand a newline";
					$fields['bad key!']  = 'dropped';
					$fields['tags']      = [ 'one', 'two' ];
					$fields['empty']     = '';

					return $fields;
				}
			);

		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame(
			'2026-01-15T10:23:45+00:00 ip=192.0.2.42 type=comment post=474 reasons=asb-honeypot'
			. ' note=two_words_and_a_newline badkey=dropped tags=one,two empty=-' . PHP_EOL,
			$this->get_log()
		);
	}

	/**
	 * Dropping the IP breaks the Fail2Ban filter in the readme, but a log kept for
	 * statistics rather than for banning has good reason not to retain addresses,
	 * and the filter has to be able to say so.
	 */
	public function test_a_filter_can_drop_the_ip(): void {
		expectApplied( 'antispam_bee_spam_log_fields' )
			->once()
			->andReturnUsing(
				static function ( $fields ) {
					unset( $fields['ip'] );

					return $fields;
				}
			);

		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame(
			'2026-01-15T10:23:45+00:00 type=comment post=474 reasons=asb-honeypot' . PHP_EOL,
			$this->get_log()
		);
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

	/*
	 * The tests below guard the shipped Fail2Ban filter against the log format drifting
	 * away from it. A jail whose filter stops matching reports no error on either side:
	 * Fail2Ban simply stops banning, and the site owner finds out from a rise in spam.
	 * These assertions are the only place where such a mismatch becomes visible.
	 */

	public function test_the_shipped_fail2ban_filter_matches_a_logged_comment(): void {
		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame( '192.0.2.42', $this->host_the_filter_bans_for( $this->get_log() ) );
	}

	public function test_the_shipped_fail2ban_filter_matches_a_line_full_of_placeholders(): void {
		UpdateSpamLog::process(
			[
				'reaction_type' => 'form',
				'ip'            => '192.0.2.43',
			]
		);

		self::assertSame(
			'192.0.2.43',
			$this->host_the_filter_bans_for( $this->get_log() ),
			'A reaction with no post and no reasons writes `-` for both, which must not stop the filter matching.'
		);
	}

	public function test_the_shipped_fail2ban_filter_matches_an_ipv6_address(): void {
		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '2001:db8::42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame( '2001:db8::42', $this->host_the_filter_bans_for( $this->get_log() ) );
	}

	public function test_the_shipped_fail2ban_filter_survives_a_field_appended_by_a_filter(): void {
		expectApplied( 'antispam_bee_spam_log_fields' )
			->once()
			->andReturnUsing(
				static function ( $fields ) {
					$fields['agent'] = 'some-bot/1.0';

					return $fields;
				}
			);

		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame(
			'192.0.2.42',
			$this->host_the_filter_bans_for( $this->get_log() ),
			'The filter is anchored on the field name, so appending a field must not break it.'
		);
	}

	public function test_the_shipped_fail2ban_filter_ignores_a_later_field_containing_an_address(): void {
		expectApplied( 'antispam_bee_spam_log_fields' )
			->once()
			->andReturnUsing(
				static function ( $fields ) {
					$fields['note'] = 'see-ip=203.0.113.9';

					return $fields;
				}
			);

		UpdateSpamLog::process(
			[
				'reaction_type'     => 'comment',
				'comment_post_ID'   => 474,
				'comment_author_IP' => '192.0.2.42',
				'asb_reasons'       => [ 'asb-honeypot' ],
			]
		);

		self::assertSame(
			'192.0.2.42',
			$this->host_the_filter_bans_for( $this->get_log() ),
			'The expression is non-greedy, so a later field whose value contains `ip=` must not decide who gets banned.'
		);
	}

	/**
	 * Apply the shipped `failregex` to a log line the way Fail2Ban does.
	 *
	 * Read from the shipped file rather than restated here, so that editing the filter
	 * without editing the log format — or the other way round — fails these tests.
	 *
	 * Fail2Ban strips the timestamp it detected before applying `failregex`, which is why
	 * it is removed here too. Verified against the real `fail2ban-regex`, which picks
	 * `{^LN-BEG}...Zone offset` for this format, so no `datepattern` is needed.
	 *
	 * @param string $log The contents of the log file.
	 *
	 * @return string|null The address the jail would ban, or null if nothing matched.
	 */
	private function host_the_filter_bans_for( string $log ): ?string {
		$line = trim( $log );

		self::assertNotSame( '', $line, 'Nothing was logged, so there is nothing to match.' );
		self::assertStringNotContainsString( PHP_EOL, $line, 'The helper expects a single log line.' );

		// What Fail2Ban's date detection consumes: the ISO 8601 timestamp and the space after it.
		$line = (string) preg_replace( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}\s*/', '', $line );

		// `<HOST>` stands for an address in Fail2Ban's own expressions; the character class
		// covers IPv4 and IPv6 without reimplementing its full definition.
		$pattern = str_replace( '<HOST>', '(?P<host>[0-9A-Fa-f.:]+)', $this->shipped_failregex() );

		if ( ! preg_match( '/' . $pattern . '/', $line, $matches ) ) {
			return null;
		}

		return $matches['host'];
	}

	/**
	 * Read `failregex` out of the Fail2Ban filter shipped with the plugin.
	 *
	 * @return string The expression.
	 */
	private function shipped_failregex(): string {
		$path = dirname( __DIR__, 3 ) . '/fail2ban/filter.d/antispam-bee.conf';

		self::assertFileExists( $path, 'The Fail2Ban filter has to ship with the plugin.' );

		$contents = (string) file_get_contents( $path );

		self::assertSame(
			1,
			preg_match( '/^failregex\s*=\s*(?P<expression>.+)$/m', $contents, $matches ),
			'The shipped filter has to define exactly one failregex.'
		);

		return trim( $matches['expression'] );
	}
}
