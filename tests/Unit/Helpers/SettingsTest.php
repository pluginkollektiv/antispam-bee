<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Settings;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see Settings}.
 */
class SettingsTest extends TestCase {

	/**
	 * The option is autoloaded, so core already caches it. The plugin used to
	 * keep a second copy, which had to be invalidated on every write and delete
	 * — including uninstall, where the hooks were never registered at all, so a
	 * deleted option came back from the stale copy.
	 */
	public function test_options_are_read_straight_from_the_option(): void {
		$stored = [ 'general' => [ 'some_option' => 'on' ] ];

		when( 'get_file_data' )->justReturn( [ 'Version' => '3.0.0' ] );
		when( 'get_option' )->alias(
			function ( $name, $default = false ) use ( $stored ) {
				if ( 'antispambee_db_version' === $name ) {
					return '3.0.0';
				}

				return Settings::OPTION_NAME === $name ? $stored : $default;
			}
		);

		self::assertSame( $stored, Settings::get_options(), 'the stored option should be returned as-is' );
		self::assertSame(
			$stored,
			Settings::get_options(),
			'a second read should return the same thing without a cache in between'
		);
	}

	/**
	 * The walkers take a reference into each path segment, and doing that to a
	 * scalar is an uncatchable fatal. The settings save walks paths built from
	 * submitted keys, so a stored scalar where an array is expected must not be
	 * able to bring the request down.
	 */
	public function test_setting_a_value_below_a_scalar_replaces_it(): void {
		$options = [ 'comment' => 'a scalar where a section is expected' ];

		Settings::set_array_value_by_path( 'comment.rule_asb_honeypot_active', 'on', $options );

		self::assertSame(
			[ 'comment' => [ 'rule_asb_honeypot_active' => 'on' ] ],
			$options,
			'the scalar should be replaced by the section it was blocking'
		);
	}

	public function test_removing_a_key_below_a_scalar_is_a_no_op(): void {
		$options = [ 'comment' => 'a scalar where a section is expected' ];

		Settings::remove_array_key_by_path( 'comment.rule_asb_honeypot_active', $options );

		self::assertSame(
			[ 'comment' => 'a scalar where a section is expected' ],
			$options,
			'there is nothing below a scalar to remove, so it should be left alone'
		);
	}
}
