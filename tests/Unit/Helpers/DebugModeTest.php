<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\DebugMode;
use ReflectionProperty;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', sys_get_temp_dir() . '/asb-debug-mode-test' );
}

/**
 * Unit tests for {@see DebugMode}.
 */
class DebugModeTest extends TestCase {

	/**
	 * The salt `wp_salt()` returns.
	 *
	 * @var string
	 */
	private $salt = 'test-salt';

	public function test_log_writes_a_single_line_per_entry(): void {
		self::force_debug_mode( true );

		DebugMode::log( "first\nsecond\r\nthird" );

		$contents = self::read_log();

		self::assertCount(
			1,
			array_filter( explode( "\n", $contents ) ),
			'A message containing line breaks must not become several log entries'
		);
		self::assertStringContainsString(
			'first second third',
			$contents,
			'Runs of line breaks should be collapsed into a single space'
		);
	}

	public function test_log_writes_nothing_when_debug_mode_is_disabled(): void {
		self::force_debug_mode( false );

		DebugMode::log( 'should not be written' );

		self::assertSame(
			[],
			self::log_files(),
			'Nothing should be logged while debug mode is off'
		);
	}

	/**
	 * The file name is derived from the salt, so without one there is nothing to
	 * make the name unguessable and nothing may be written. `wp_salt()` normally
	 * always returns a secret, so this is a safety net rather than a case that is
	 * expected to occur.
	 */
	public function test_log_writes_nothing_without_a_secret_salt(): void {
		$this->salt = '';
		self::force_debug_mode( true );

		DebugMode::log( 'should not be written' );

		self::assertSame(
			[],
			self::log_files(),
			'Without a salt the file name would be guessable, so nothing may be logged'
		);
	}

	public function test_log_file_name_is_derived_from_the_salt(): void {
		self::force_debug_mode( true );

		DebugMode::log( 'first entry' );
		$first = self::log_files();

		self::remove_log_files();
		$this->salt = 'another-salt';

		DebugMode::log( 'second entry' );
		$second = self::log_files();

		self::assertNotSame(
			$first,
			$second,
			'A different salt must produce a different, unguessable file name'
		);
	}

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		when( 'wp_salt' )->alias(
			function () {
				return $this->salt;
			}
		);

		if ( ! is_dir( WP_CONTENT_DIR ) ) {
			mkdir( WP_CONTENT_DIR, 0777, true );
		}

		self::remove_log_files();
	}

	/**
	 * Tear down the test environment.
	 *
	 * @return void
	 */
	protected function tear_down() {
		self::remove_log_files();
		self::force_debug_mode( null );

		parent::tear_down();
	}

	/**
	 * Override the cached debug mode state, which is otherwise read from a constant.
	 *
	 * @param bool|null $enabled Whether debug mode is enabled, or null to reset.
	 *
	 * @return void
	 */
	private static function force_debug_mode( ?bool $enabled ): void {
		$property = new ReflectionProperty( DebugMode::class, 'debug_mode_enabled' );
		$property->setAccessible( true );
		$property->setValue( null, $enabled );
	}

	/**
	 * All log files the helper may have written.
	 *
	 * @return string[] List of file paths.
	 */
	private static function log_files(): array {
		return glob( WP_CONTENT_DIR . '/asb-debug.*.log' ) ?: [];
	}

	/**
	 * Read the single log file that was written.
	 *
	 * @return string The log file contents.
	 */
	private static function read_log(): string {
		$files = self::log_files();

		self::assertCount( 1, $files, 'Exactly one log file should have been written' );

		return (string) file_get_contents( $files[0] );
	}

	/**
	 * Remove any log files left behind.
	 *
	 * @return void
	 */
	private static function remove_log_files(): void {
		foreach ( self::log_files() as $file ) {
			unlink( $file );
		}
	}
}
