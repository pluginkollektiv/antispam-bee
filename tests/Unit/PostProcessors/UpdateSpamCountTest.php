<?php

namespace AntispamBee\Tests\Unit\PostProcessors;

use AntispamBee\PostProcessors\UpdateSpamCount;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see UpdateSpamCount}.
 */
class UpdateSpamCountTest extends TestCase {

	/**
	 * Stub the stored settings, keeping the v2 migration out of the test.
	 *
	 * @param array<string, mixed> $options The stored options array.
	 * @param mixed                $count   Value of the dedicated counter option.
	 *
	 * @return void
	 */
	private function stub_options( array $options, $count = null ): void {
		when( 'get_file_data' )->justReturn( [ 'Version' => '3.0.0' ] );
		when( 'get_option' )->alias(
			function ( $name, $default = false ) use ( $options, $count ) {
				if ( 'antispambee_db_version' === $name ) {
					return '3.0.0';
				}

				if ( UpdateSpamCount::COUNT_OPTION === $name ) {
					return null === $count ? $default : $count;
				}

				if ( 'antispam_bee_options' === $name ) {
					return $options;
				}

				return $default;
			}
		);
	}

	public function test_count_comes_from_the_dedicated_option(): void {
		$this->stub_options( [ 'spam_count' => 5 ], 42 );

		self::assertSame( 42, UpdateSpamCount::get_count(), 'the dedicated option wins once it exists' );
	}

	/**
	 * A site that has not seen spam since the counter moved still has its total
	 * inside the settings array, and the v2 migration still writes it there.
	 */
	public function test_count_falls_back_to_the_settings_array(): void {
		$this->stub_options( [ 'spam_count' => 7 ] );

		self::assertSame( 7, UpdateSpamCount::get_count(), 'the previous location should still be read' );
	}

	public function test_count_is_zero_when_nothing_was_ever_recorded(): void {
		$this->stub_options( [] );

		self::assertSame( 0, UpdateSpamCount::get_count() );
	}

	/**
	 * The increment must not write the settings array: doing so read the whole
	 * option, merged, and wrote it back, so an increment overlapping an
	 * administrator's save reverted that save.
	 */
	public function test_increment_writes_only_the_counter_option(): void {
		$this->stub_options(
			[
				'spam_count' => 7,
				'general'    => [ 'general_statistics_on_dashboard_active' => 'on' ],
			],
			7
		);

		expect( 'update_option' )
			->once()
			->with( UpdateSpamCount::COUNT_OPTION, 8, true );

		UpdateSpamCount::process( [] );
	}
}
