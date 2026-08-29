<?php

namespace AntispamBee\Tests\Unit\Handlers;

use AntispamBee\Handlers\PluginUpdate;
use AntispamBee\Helpers\Settings;
use ReflectionProperty;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Functions\when;

if ( ! defined( 'AntispamBee\MAIN_PLUGIN_FILE' ) ) {
	define( 'AntispamBee\MAIN_PLUGIN_FILE', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR . 'antispam_bee.php' );
}

/**
 * Unit tests for the v2 to v3 option migration in {@see PluginUpdate}.
 */
class PluginUpdateTest extends TestCase {

	/**
	 * Options captured from `update_option()` calls, keyed by option name.
	 *
	 * @var array<string, mixed>
	 */
	private $written_options = [];

	/**
	 * Names of the options written, in the order `update_option()` was called.
	 *
	 * @var string[]
	 */
	private $write_order = [];

	/**
	 * Reset the memoized migration state and stub the option and plugin-file functions.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->reset_static( 'db_update_triggered', false );
		$this->reset_static( 'db_version_is_current', null );

		$this->written_options = [];

		when( 'get_file_data' )->justReturn( [ 'Version' => '3.0.0-beta.1' ] );
		$this->write_order = [];

		when( 'update_option' )->alias(
			function ( $name, $value ) {
				$this->written_options[ $name ] = $value;
				$this->write_order[]            = $name;

				return true;
			}
		);
	}

	/**
	 * Reset a private static property of the handler between tests.
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value Value to reset it to.
	 *
	 * @return void
	 */
	private function reset_static( string $name, $value ): void {
		$property = new ReflectionProperty( PluginUpdate::class, $name );
		$property->setAccessible( true );
		$property->setValue( null, $value );
	}

	/**
	 * Stub `get_option()` with a fixed set of stored options.
	 *
	 * @param array<string, mixed> $stored Option values keyed by option name.
	 *
	 * @return void
	 */
	private function stub_options( array $stored ): void {
		when( 'get_option' )->alias(
			function ( $name, $default = false ) use ( $stored ) {
				return array_key_exists( $name, $stored ) ? $stored[ $name ] : $default;
			}
		);
	}

	/**
	 * A legacy option array that predates most v2 options must migrate without warnings.
	 *
	 * PHPUnit is configured with `convertWarningsToExceptions`, so an `Undefined array key`
	 * warning raised by the migration fails this test.
	 *
	 * @return void
	 */
	public function test_partial_legacy_options_migrate_without_warnings(): void {
		$this->stub_options(
			[
				'antispam_bee'          => [
					'regexp_check' => 1,
					'spam_ip'      => 1,
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertArrayHasKey( Settings::OPTION_NAME, $this->written_options );
	}

	/**
	 * Legacy options that are present and enabled are migrated as enabled, and keys that are
	 * absent from the legacy array are migrated as disabled rather than raising a warning.
	 *
	 * @return void
	 */
	public function test_missing_legacy_keys_migrate_as_disabled(): void {
		$this->stub_options(
			[
				'antispam_bee'          => [
					'regexp_check' => 1,
					'spam_ip'      => 1,
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$comment = $this->written_options[ Settings::OPTION_NAME ]['comment'];

		$this->assertSame( 'on', $comment['rule_asb_regexp_active'], 'A present, enabled legacy option stays enabled.' );
		$this->assertSame( 'on', $comment['rule_asb_db_spam_active'], 'A present, enabled legacy option stays enabled.' );

		$this->assertSame( '', $comment['post_processor_asb_send_email_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $comment['rule_asb_bbcode_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $comment['rule_asb_country_spam_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $comment['rule_asb_lang_spam_active'], 'A missing legacy option migrates as disabled.' );

		$general = $this->written_options[ Settings::OPTION_NAME ]['general'];

		$this->assertSame( '', $general['general_delete_spam_cronjob_enabled_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $general['general_statistics_on_dashboard_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $general['general_ignore_linkbacks_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $general['general_delete_data_on_uninstall_active'], 'A missing legacy option migrates as disabled.' );
	}

	/**
	 * A legacy option that is explicitly disabled must not be migrated as enabled.
	 *
	 * @return void
	 */
	public function test_disabled_legacy_options_stay_disabled(): void {
		$this->stub_options(
			[
				'antispam_bee'          => [
					'regexp_check'  => 0,
					'bbcode_check'  => '',
					'country_code'  => false,
					'cronjob_enable' => 1,
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$comment = $this->written_options[ Settings::OPTION_NAME ]['comment'];

		$this->assertSame( '', $comment['rule_asb_regexp_active'] );
		$this->assertSame( '', $comment['rule_asb_bbcode_active'] );
		$this->assertSame( '', $comment['rule_asb_country_spam_active'] );

		$general = $this->written_options[ Settings::OPTION_NAME ]['general'];

		$this->assertSame( 'on', $general['general_delete_spam_cronjob_enabled_active'] );
	}

	/**
	 * A legacy option value that is not an array at all must not abort the migration.
	 *
	 * @return void
	 */
	public function test_non_array_legacy_options_do_not_abort_the_migration(): void {
		$this->stub_options(
			[
				'antispam_bee'          => 'corrupted',
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$comment = $this->written_options[ Settings::OPTION_NAME ]['comment'];

		$this->assertSame( '', $comment['rule_asb_regexp_active'] );
		$this->assertSame( 'on', $comment['rule_asb_honeypot_active'], 'The honeypot rule is always migrated as enabled.' );
	}

	/**
	 * A legacy `translate_lang` that is still a plain string must not abort the migration.
	 *
	 * The option was a single value before the multiselect rework, so a 2.x install that
	 * never re-saved its settings still holds a string here. Passing it into an `array`
	 * parameter raised an uncaught `TypeError`.
	 *
	 * @return void
	 */
	public function test_scalar_translate_lang_does_not_abort_the_migration(): void {
		$this->stub_options(
			[
				'antispam_bee'           => [
					'translate_lang' => 'de',
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertArrayHasKey( Settings::OPTION_NAME, $this->written_options );
		$this->assertSame(
			[ 'de' => 'on' ],
			$this->written_options[ Settings::OPTION_NAME ]['comment']['rule_asb_lang_spam_allowed'],
			'A single legacy language is migrated as one selected value.'
		);
	}

	/**
	 * A legacy `ignore_reasons` that is still a plain string must migrate through the mapping.
	 *
	 * @return void
	 */
	public function test_scalar_ignore_reasons_does_not_abort_the_migration(): void {
		$this->stub_options(
			[
				'antispam_bee'           => [
					'ignore_reasons' => 'css',
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertSame(
			[ 'asb-honeypot' => 'on' ],
			$this->written_options[ Settings::OPTION_NAME ]['comment']['post_processor_asb_delete_for_reasons_reasons'],
			'The legacy reason slug is mapped to its 3.0 equivalent.'
		);
	}

	/**
	 * An empty legacy multiselect value must migrate to an empty selection, not to an empty key.
	 *
	 * @return void
	 */
	public function test_empty_scalar_multiselect_migrates_to_an_empty_selection(): void {
		$this->stub_options(
			[
				'antispam_bee'           => [
					'translate_lang' => '',
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertSame(
			[],
			$this->written_options[ Settings::OPTION_NAME ]['comment']['rule_asb_lang_spam_allowed']
		);
	}

	/**
	 * The database version must be raised only after the migrated options were written.
	 *
	 * Raising it first means a migration that aborts is never retried while the new option
	 * was never written, so the site silently falls back to the defaults.
	 *
	 * @return void
	 */
	public function test_db_version_is_raised_after_the_options_were_written(): void {
		$this->stub_options(
			[
				'antispam_bee'           => [
					'regexp_check' => 1,
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$options_position = array_search( Settings::OPTION_NAME, $this->write_order, true );
		$version_position = array_search( PluginUpdate::DB_VERSION_OPTION_NAME, $this->write_order, true );

		$this->assertNotFalse( $options_position, 'The migrated options were written.' );
		$this->assertNotFalse( $version_position, 'The database version was written.' );
		$this->assertGreaterThan(
			$options_position,
			$version_position,
			'The database version is raised after the migrated options, so an aborted migration is retried.'
		);
	}
}
