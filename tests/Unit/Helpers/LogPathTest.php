<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\LogPath;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', '/var/www/wp-content' );
}
/*
 * The salt deliberately comes from a mocked `wp_salt()` rather than a defined `NONCE_SALT`.
 * A constant cannot be undefined again, and `Salt::get()` prefers it over `wp_salt()`, so
 * defining one here would leak into every other test file in the run — including
 * {@see DebugModeTest}, which asserts that changing the salt changes the log file name.
 */

// The resolver reads constants by name, and a constant cannot be undefined again, so each
// case gets its own constant rather than redefining a shared one.
define( 'ASB_TEST_LOG_TRUE', true );
define( 'ASB_TEST_LOG_FALSE', false );
define( 'ASB_TEST_LOG_EMPTY', '' );
define( 'ASB_TEST_LOG_PATH', '/var/log/antispam-bee.log' );
define( 'ASB_TEST_DIR', '/srv/logs' );
define( 'ASB_TEST_DIR_TRAILING', '/srv/logs/' );
define( 'ASB_TEST_DIR_EMPTY', '' );

/**
 * Unit tests for {@see LogPath}.
 */
class LogPathTest extends TestCase {

	/**
	 * The salt every generated name in these tests is derived from.
	 *
	 * Long enough and free of whitespace, so `Salt` treats it as a real salt rather than an
	 * unconfigured placeholder.
	 *
	 * @var string
	 */
	private const SALT = 'aFixedSaltForTestsLongEnoughToCountAsGenerated0123456789';

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		when( 'wp_salt' )->justReturn( self::SALT );
	}

	/**
	 * An undefined log constant means the log is off.
	 *
	 * @return void
	 */
	public function test_undefined_constant_disables_the_log(): void {
		$this->assertNull(
			LogPath::resolve( 'ASB_TEST_LOG_NOT_DEFINED', 'ASB_TEST_DIR', 'asb-spam' )
		);
	}

	/**
	 * `false` and an empty string mean the log is off.
	 *
	 * @return void
	 */
	public function test_falsey_constant_disables_the_log(): void {
		$this->assertNull( LogPath::resolve( 'ASB_TEST_LOG_FALSE', 'ASB_TEST_DIR', 'asb-spam' ) );
		$this->assertNull( LogPath::resolve( 'ASB_TEST_LOG_EMPTY', 'ASB_TEST_DIR', 'asb-spam' ) );
	}

	/**
	 * A string is used verbatim, without a salted suffix — a Fail2Ban jail has to be told
	 * the file name in advance.
	 *
	 * @return void
	 */
	public function test_string_constant_is_used_verbatim(): void {
		$this->assertSame(
			'/var/log/antispam-bee.log',
			LogPath::resolve( 'ASB_TEST_LOG_PATH', 'ASB_TEST_DIR', 'asb-spam' )
		);
	}

	/**
	 * `true` generates a salted name inside the directory constant.
	 *
	 * @return void
	 */
	public function test_true_generates_a_salted_name_in_the_directory(): void {
		$path = LogPath::resolve( 'ASB_TEST_LOG_TRUE', 'ASB_TEST_DIR', 'asb-spam' );

		$this->assertSame( '/srv/logs/asb-spam.' . LogPath::suffix( 'asb-spam' ) . '.log', $path );
		$this->assertMatchesRegularExpression( '#/asb-spam\.[0-9a-f]{12}\.log$#', (string) $path );
	}

	/**
	 * The spam log name carries no date, so a Fail2Ban `logpath` keeps matching it.
	 *
	 * @return void
	 */
	public function test_undated_name_has_no_date_component(): void {
		$path = LogPath::resolve( 'ASB_TEST_LOG_TRUE', 'ASB_TEST_DIR', 'asb-spam' );

		$this->assertDoesNotMatchRegularExpression( '#\d{4}-\d{2}-\d{2}#', (string) $path );
	}

	/**
	 * The debug log name carries the date, as it did before.
	 *
	 * @return void
	 */
	public function test_dated_name_carries_the_date(): void {
		$path = LogPath::generate( 'ASB_TEST_DIR', 'asb-debug', true );

		$this->assertSame(
			'/srv/logs/asb-debug.' . date( 'Y-m-d' ) . '.' . LogPath::suffix( 'asb-debug' ) . '.log',
			$path
		);
	}

	/**
	 * The generated debug name matches what 3.0.0-beta.2 wrote, so an existing debug log
	 * keeps its file name across the constant rename.
	 *
	 * @return void
	 */
	public function test_debug_suffix_is_unchanged_from_the_previous_implementation(): void {
		$this->assertSame(
			substr( sha1( 'asb-debug' . self::SALT ), 0, 12 ),
			LogPath::suffix( 'asb-debug' )
		);
	}

	/**
	 * Each log gets its own suffix, so one file name cannot be derived from the other.
	 *
	 * @return void
	 */
	public function test_suffix_differs_per_prefix(): void {
		$this->assertNotSame( LogPath::suffix( 'asb-debug' ), LogPath::suffix( 'asb-spam' ) );
	}

	/**
	 * The file name is derived from the salt, so two sites cannot be guessed from one
	 * another's.
	 *
	 * @return void
	 */
	public function test_name_is_derived_from_the_salt(): void {
		$first = LogPath::generate( 'ASB_TEST_DIR', 'asb-debug', true );

		when( 'wp_salt' )->justReturn( 'a-completely-different-salt-that-is-long-enough' );

		$this->assertNotSame(
			$first,
			LogPath::generate( 'ASB_TEST_DIR', 'asb-debug', true ),
			'A different salt must produce a different, unguessable file name'
		);
	}

	/**
	 * Without a secret there is nothing to make a generated name unguessable, so no path
	 * is produced at all and the caller skips logging. `Salt::get()` normally always
	 * returns a secret, so this is a safety net rather than an expected case.
	 *
	 * @return void
	 */
	public function test_no_generated_path_without_a_secret_salt(): void {
		when( 'wp_salt' )->justReturn( '' );

		$this->assertNull( LogPath::suffix( 'asb-spam' ) );
		$this->assertNull( LogPath::generate( 'ASB_TEST_DIR', 'asb-spam' ) );
		$this->assertNull( LogPath::resolve( 'ASB_TEST_LOG_TRUE', 'ASB_TEST_DIR', 'asb-spam' ) );
	}

	/**
	 * An unset or empty directory constant falls back to `WP_CONTENT_DIR`, and a trailing
	 * separator does not produce a doubled one.
	 *
	 * @return void
	 */
	public function test_directory_fallback_and_trailing_separator(): void {
		$this->assertStringStartsWith(
			WP_CONTENT_DIR . '/',
			(string) LogPath::resolve( 'ASB_TEST_LOG_TRUE', 'ASB_TEST_DIR_NOT_DEFINED', 'asb-spam' )
		);
		$this->assertStringStartsWith(
			WP_CONTENT_DIR . '/',
			(string) LogPath::resolve( 'ASB_TEST_LOG_TRUE', 'ASB_TEST_DIR_EMPTY', 'asb-spam' )
		);
		$this->assertStringStartsWith(
			'/srv/logs/asb-spam.',
			LogPath::generate( 'ASB_TEST_DIR_TRAILING', 'asb-spam' )
		);
	}

	/**
	 * A file that does not exist yet is writable when its directory is, since a generated
	 * log is only created on the first write.
	 *
	 * @return void
	 */
	public function test_absent_file_is_writable_when_its_directory_is(): void {
		$dir = sys_get_temp_dir() . '/asb-logpath-' . uniqid();
		mkdir( $dir );

		$this->assertTrue( LogPath::is_writable( $dir . '/does-not-exist.log' ) );
		$this->assertFalse( LogPath::is_writable( $dir . '/missing-dir/nope.log' ) );

		rmdir( $dir );
	}
}
