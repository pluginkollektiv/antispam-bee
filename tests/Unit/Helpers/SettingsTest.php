<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Settings;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Actions\expectAdded;

/**
 * Unit tests for {@see Settings}.
 */
class SettingsTest extends TestCase {

	/**
	 * The cache has to be invalidated on every mutation of the option.
	 *
	 * `update_option()` routes to `add_option()` when the option row does not
	 * exist yet, and that path fires `add_option_{$option}` rather than
	 * `update_option_{$option}`. Hooking the update alone therefore misses the
	 * very first save: the cache keeps the defaults that were read before the
	 * write, and with a persistent object cache every later read serves them
	 * instead of the settings the admin just saved.
	 */
	public function test_init_hooks_the_option_being_created(): void {
		expectAdded( 'add_option_' . Settings::OPTION_NAME )
			->once()
			->with( [ Settings::class, 'add_cache' ], 1, 2 );

		Settings::init();
	}

	public function test_init_hooks_the_option_being_updated(): void {
		expectAdded( 'update_option_' . Settings::OPTION_NAME )
			->once()
			->with( [ Settings::class, 'update_cache' ], 1, 2 );

		Settings::init();
	}

	public function test_init_hooks_the_option_being_deleted(): void {
		expectAdded( 'delete_option_' . Settings::OPTION_NAME )
			->once()
			->with( [ Settings::class, 'delete_cache' ], 1 );

		Settings::init();
	}

	public function test_cache_callbacks_are_callable_with_the_hook_signatures(): void {
		// `add_option_{$option}` passes ( $option, $value ), `delete_option_{$option}` passes ( $option ).
		Settings::add_cache( Settings::OPTION_NAME, [ 'foo' => 'bar' ] );
		Settings::update_cache( [ 'foo' => 'old' ], [ 'foo' => 'new' ] );
		Settings::delete_cache();

		self::assertTrue( true, 'the cache callbacks should accept the arguments their hooks pass' );
	}
}
