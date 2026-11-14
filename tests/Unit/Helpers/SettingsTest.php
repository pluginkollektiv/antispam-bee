<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Settings;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

if ( ! defined( 'AntispamBee\MAIN_PLUGIN_FILE' ) ) {
	define( 'AntispamBee\MAIN_PLUGIN_FILE', dirname( __DIR__, 3 ) . '/antispam_bee.php' );
}

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
		// Isolated from the default-backfilling behaviour covered elsewhere: this
		// test is only about the option itself not being altered by a cache layer.
		expectApplied( 'antispam_bee_default_options' )->andReturn( [] );

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

	/**
	 * Stub the stored options.
	 *
	 * The object cache functions are already stubbed globally in
	 * `tests/_stubs/includes.php`, where `wp_cache_get()` always misses.
	 * `Settings::get_options()` also runs the plugin update check, which needs the
	 * plugin version and the stored database version to match so it stays a no-op.
	 *
	 * @param array<string, mixed> $stored The stored options.
	 */
	private function stub_stored_options( array $stored ): void {
		when( 'get_file_data' )->justReturn( [ 'Version' => '1.0' ] );
		when( 'get_option' )->alias(
			static function ( $name, $default_value = false ) use ( $stored ) {
				return Settings::OPTION_NAME === $name ? $stored : $default_value;
			}
		);
	}

	public function test_defaults_apply_to_reaction_types_that_were_never_saved(): void {
		$this->stub_stored_options(
			[
				'comment' => [ 'rule_asb_bbcode_active' => 'on' ],
			]
		);

		expectApplied( 'antispam_bee_default_options' )
			->andReturn(
				[
					'my_form' => [ 'rule_asb_regexp_active' => 'on' ],
				]
			);

		self::assertSame(
			'on',
			Settings::get_option( 'rule_asb_regexp_active', 'my_form' ),
			'a reaction type absent from the stored options should fall back to its defaults'
		);
	}

	/**
	 * An unticked checkbox is removed from the stored options, so a saved reaction
	 * type must win over the defaults — otherwise a rule an administrator disabled
	 * would come back on the next request.
	 */
	public function test_stored_reaction_type_wins_over_defaults(): void {
		$this->stub_stored_options(
			[
				'my_form' => [ 'rule_asb_bbcode_active' => 'on' ],
			]
		);

		expectApplied( 'antispam_bee_default_options' )
			->andReturn(
				[
					'my_form' => [
						'rule_asb_bbcode_active' => 'on',
						'rule_asb_regexp_active' => 'on',
					],
				]
			);

		self::assertSame(
			'on',
			Settings::get_option( 'rule_asb_bbcode_active', 'my_form' ),
			'the stored value should still be returned'
		);
		self::assertNull(
			Settings::get_option( 'rule_asb_regexp_active', 'my_form' ),
			'a rule missing from a saved reaction type must stay inactive'
		);
	}

	public function test_built_in_defaults_are_exposed_through_the_filter(): void {
		when( 'apply_filters' )->returnArg( 2 );

		$defaults = Settings::get_defaults();

		self::assertArrayHasKey( 'comment', $defaults, 'the comment defaults should be exposed' );
		self::assertArrayHasKey( 'linkback', $defaults, 'the linkback defaults should be exposed' );
		self::assertSame( 'on', $defaults['comment']['rule_asb_bbcode_active'], 'unexpected comment default' );
	}
}
