<?php

namespace AntispamBee\Tests\Unit\Handlers;

use AntispamBee\GeneralOptions\Statistics;
use AntispamBee\Handlers\GeneralOptions;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see GeneralOptions}.
 */
class GeneralOptionsTest extends TestCase {

	/**
	 * Stub `apply_filters()` so the extension point returns a fixed list.
	 *
	 * @param array<mixed> $options Entries the filter yields.
	 *
	 * @return void
	 */
	private function stub_registered_options( array $options ): void {
		when( 'apply_filters' )->alias(
			function ( $hook, $value = null ) use ( $options ) {
				return 'antispam_bee_general_options' === $hook ? $options : $value;
			}
		);
	}

	/**
	 * An entry that does not implement Controllable must not be returned.
	 *
	 * Sanitize::sanitize_controllables() calls static methods on every entry, so an
	 * unvalidated value raises an uncaught Error inside the `register_setting()`
	 * sanitize callback.
	 *
	 * @return void
	 */
	public function test_entries_that_are_not_controllable_are_dropped(): void {
		when( '_doing_it_wrong' )->justReturn( null );

		$this->stub_registered_options(
			[
				Statistics::class,
				\stdClass::class,
			]
		);

		self::assertSame( [ Statistics::class ], GeneralOptions::get_controllables() );
	}

	/**
	 * A reaction type other than `general` yields no options at all.
	 *
	 * @return void
	 */
	public function test_other_reaction_types_yield_no_options(): void {
		$this->stub_registered_options( [ Statistics::class ] );

		self::assertSame( [], GeneralOptions::get_controllables( 'comment' ) );
	}
}
