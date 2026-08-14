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
	 * The salt `wp_salt()` returns. Shaped like a real one: long, no whitespace.
	 *
	 * @var string
	 */
	private $salt = 'x9!Kq2#Vz7$Lp4%Rn8^Mb6&Tw3*Yh5(Jg1)Fd0-Sa7+Ce2=Vu9~Io4Pj6Zq8Xm3B';

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
	 * make the name unguessable and nothing may be written.
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

	/**
	 * `wp_salt()` recognises only the English placeholder from
	 * `wp-config-sample.php`, so on a localised install that was never configured
	 * it hands back the translated placeholder as though it were a secret. The
	 * log holds comment data and IP addresses, so that must not name a file.
	 */
	public function test_log_writes_nothing_when_the_salt_is_a_placeholder(): void {
		$this->salt = 'füge hier deine einmalig genutzte Zeichenfolge ein'; // spellchecker:disable-line
		self::force_debug_mode( true );

		DebugMode::log( 'should not be written' );

		self::assertSame(
			[],
			self::log_files(),
			'A placeholder phrase is not a secret, so the log file name must not be derived from it'
		);
	}

	public function test_log_file_name_is_derived_from_the_salt(): void {
		self::force_debug_mode( true );

		DebugMode::log( 'first entry' );
		$first = self::log_files();

		self::remove_log_files();
		$this->salt = 'B3mX8qZ6jP4oI9uV2eC+7aS-0dF1gJ)5hY*3wT&6bM^8nR%4pL$7zV#2qK!9x';

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
