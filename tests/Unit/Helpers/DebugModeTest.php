<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\DebugMode;
use ReflectionProperty;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * A {@see DebugMode} whose log path is injected rather than resolved from a constant.
 *
 * The real resolution goes through constants, which cannot be undefined again and would
 * therefore leak into every other test file in the run — enabling debug logging for tests
 * that never asked for it. Where the path comes from is {@see LogPathTest}'s subject; what
 * gets written to it is this one's.
 */
class InjectedDebugMode extends DebugMode {

	/**
	 * Path returned instead of resolving one, or null for "no log configured".
	 *
	 * @var string|null
	 */
	public static $path = null;

	/**
	 * Get the debug log file path.
	 *
	 * @return string|null The injected path.
	 */
	public static function get_log_file(): ?string {
		return self::$path;
	}
}

/**
 * Unit tests for {@see DebugMode}.
 */
class DebugModeTest extends TestCase {

	/**
	 * Path the injected log writes to.
	 *
	 * @var string
	 */
	private $log_file;

	public function test_log_writes_a_single_line_per_entry(): void {
		self::force_debug_mode( true );

		InjectedDebugMode::log( "first\nsecond\r\nthird" );

		$contents = (string) file_get_contents( $this->log_file );

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

		InjectedDebugMode::log( 'should not be written' );

		self::assertFileDoesNotExist(
			$this->log_file,
			'Nothing should be logged while debug mode is off'
		);
	}

	/**
	 * Without a resolvable path there is nothing to write to, so logging is skipped. That
	 * happens when no log constant is defined, and also when no secret salt is available to
	 * make a generated file name unguessable — see {@see LogPathTest}.
	 */
	public function test_log_writes_nothing_without_a_log_file(): void {
		self::force_debug_mode( true );
		InjectedDebugMode::$path = null;

		InjectedDebugMode::log( 'should not be written' );

		self::assertFileDoesNotExist(
			$this->log_file,
			'Without a log file path nothing may be written'
		);
	}

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->log_file          = sys_get_temp_dir() . '/asb-debug-mode-test-' . uniqid() . '.log';
		InjectedDebugMode::$path = $this->log_file;
	}

	/**
	 * Tear down the test environment.
	 *
	 * @return void
	 */
	protected function tear_down() {
		if ( file_exists( $this->log_file ) ) {
			unlink( $this->log_file );
		}

		InjectedDebugMode::$path = null;
		self::force_debug_mode( null );

		parent::tear_down();
	}

	/**
	 * Override the cached debug mode state, which is otherwise derived from the log path.
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
}
