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
 * Unit tests for the default handling in {@see Settings}.
 */
class SettingsTest extends TestCase {

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
