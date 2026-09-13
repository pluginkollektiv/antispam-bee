<?php
/**
 * Integration tests for the v2 to v3 option migration.
 *
 * @package AntispamBee\Tests\Integration\Handlers
 */

namespace AntispamBee\Tests\Integration\Handlers;

use AntispamBee\Handlers\PluginUpdate;
use AntispamBee\Helpers\Settings;
use ReflectionClass;
use RuntimeException;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * A migration whose steps fail, standing in for a fatal, a DB error, a timeout or a
 * warning promoted to an exception. `PluginUpdate` reaches the steps through
 * `static::`, so overriding them here is enough to drive the failure path.
 */
final class FailingPluginUpdate extends PluginUpdate {

	/**
	 * Fail before anything is migrated.
	 *
	 * @param string $version_from_db The database revision the install is on.
	 *
	 * @return void
	 *
	 * @throws RuntimeException Always.
	 */
	protected static function run_migration_steps( string $version_from_db ): void {
		throw new RuntimeException( 'A migration step failed at revision ' . $version_from_db );
	}
}

/**
 * The migration runs once per site and its result becomes the user's configuration,
 * so it is asserted against the stored option rather than against a mock.
 */
final class PluginUpdateTest extends TestCase {

	/**
	 * The legacy option written by Antispam Bee 2.x.
	 *
	 * @var string
	 */
	private const LEGACY_OPTION = 'antispam_bee';

	/**
	 * The option holding the database revision.
	 *
	 * @var string
	 */
	private const DB_VERSION_OPTION = 'antispambee_db_version';

	/**
	 * Reset the plugin state that outlives a single request.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->reset_plugin_update_state();
	}

	/**
	 * Reset the plugin state again so a failing test cannot poison the next one.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$this->reset_plugin_update_state();

		parent::tear_down();
	}

	public function test_a_complete_v2_option_array_migrates_to_the_v3_structure(): void {
		$this->seed_legacy_install();

		PluginUpdate::maybe_run_plugin_updated_logic();

		$options = get_option( Settings::OPTION_NAME );

		self::assertSame(
			[
				'post_processor_asb_delete_spam_active' => 'on',
				'post_processor_asb_send_email_active' => 'on',
				'post_processor_asb_save_reason_active' => '',
				'rule_asb_regexp_active'               => '',
				'rule_asb_honeypot_active'             => 'on',
				'rule_asb_db_spam_active'              => 'on',
				'rule_asb_approved_email_active'       => '',
				'rule_asb_too_fast_submit_active'      => 'on',
				'post_processor_asb_delete_for_reasons_active' => 'on',
				'post_processor_asb_delete_for_reasons_reasons' => [
					'asb-honeypot'  => 'on',
					'asb-empty'     => 'on',
					'asb-lang-spam' => 'on',
				],
				'rule_asb_bbcode_active'               => '',
				'rule_asb_valid_gravatar_active'       => 'on',
				'rule_asb_country_spam_active'         => 'on',
				'rule_asb_country_spam_denied'         => 'RU,CN',
				'rule_asb_country_spam_allowed'        => 'DE',
				'rule_asb_lang_spam_active'            => 'on',
				'rule_asb_lang_spam_allowed'           => [
					'de' => 'on',
					'en' => 'on',
				],
			],
			$options['comment'],
			'The comment reaction type was not migrated as expected.'
		);

		self::assertSame(
			[
				'post_processor_asb_delete_spam_active' => 'on',
				'post_processor_asb_send_email_active' => 'on',
				'post_processor_asb_save_reason_active' => '',
				'rule_asb_regexp_active'               => '',
				'rule_asb_db_spam_active'              => 'on',
				'post_processor_asb_delete_for_reasons_active' => 'on',
				'post_processor_asb_delete_for_reasons_reasons' => [
					'asb-honeypot'  => 'on',
					'asb-empty'     => 'on',
					'asb-lang-spam' => 'on',
				],
				'rule_asb_bbcode_active'               => '',
				'rule_asb_valid_gravatar_active'       => 'on',
				'rule_asb_country_spam_active'         => 'on',
				'rule_asb_country_spam_denied'         => 'RU,CN',
				'rule_asb_country_spam_allowed'        => 'DE',
				'rule_asb_lang_spam_active'            => 'on',
				'rule_asb_lang_spam_allowed'           => [
					'de' => 'on',
					'en' => 'on',
				],
			],
			$options['linkback'],
			'The linkback reaction type was not migrated as expected.'
		);

		self::assertSame(
			[
				'general_delete_spam_cronjob_enabled_active' => 'on',
				'general_delete_spam_cronjob_enabled_delete_spam_cronjob_days' => 14,
				'general_statistics_on_dashboard_active' => 'on',
				'general_ignore_linkbacks_active' => 'on',
				'general_delete_data_on_uninstall_active' => '',
			],
			$options['general'],
			'The general options were not migrated as expected.'
		);

		self::assertSame( 4711, $options['spam_count'], 'The spam count was not carried over.' );
	}

	public function test_spam_reasons_are_mapped_and_removed_reasons_are_dropped(): void {
		$this->seed_legacy_install(
			[
				'reasons_enable' => 1,
				'ignore_reasons' => [ 'css', 'time', 'empty', 'localdb', 'server', 'country', 'bbcode', 'lang', 'regexp', 'title_is_name', 'manually' ],
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$options = get_option( Settings::OPTION_NAME );

		self::assertSame(
			[
				'asb-honeypot'                        => 'on',
				'asb-too-fast-submit'                 => 'on',
				'asb-empty'                           => 'on',
				'asb-db-spam'                         => 'on',
				'asb-country-spam'                    => 'on',
				'asb-bbcode'                          => 'on',
				'asb-lang-spam'                       => 'on',
				'asb-regexp'                          => 'on',
				'asb-linkback-post-title-is-blogname' => 'on',
				'asb-marked-manually'                 => 'on',
			],
			$options['comment']['post_processor_asb_delete_for_reasons_reasons'],
			'The `server` reason maps to null and must not end up in the migrated reasons.'
		);
	}

	public function test_a_fresh_install_records_the_version_without_migrating(): void {
		delete_option( self::DB_VERSION_OPTION );
		delete_option( Settings::OPTION_NAME );
		wp_cache_delete( Settings::OPTION_NAME );

		PluginUpdate::maybe_run_plugin_updated_logic();

		self::assertNotFalse(
			get_option( self::DB_VERSION_OPTION ),
			'A fresh install has to record the current database version.'
		);
		self::assertFalse(
			get_option( Settings::OPTION_NAME ),
			'A fresh install must not write migrated options.'
		);
	}

	public function test_the_iphash_comment_meta_is_removed_below_db_version_1_01(): void {
		$this->seed_legacy_install( [], '1.0' );

		$comment_id = self::factory()->comment->create();
		add_comment_meta( $comment_id, 'antispam_bee_iphash', 'deadbeef' );
		add_comment_meta( $comment_id, 'antispam_bee_reason', 'css' );

		PluginUpdate::maybe_run_plugin_updated_logic();

		self::assertSame(
			'',
			get_comment_meta( $comment_id, 'antispam_bee_iphash', true ),
			'The unused iphash meta has to be deleted.'
		);
		self::assertSame(
			'css',
			get_comment_meta( $comment_id, 'antispam_bee_reason', true ),
			'Only the iphash meta may be deleted.'
		);
	}

	public function test_the_country_options_are_renamed_below_db_version_1_02(): void {
		$this->seed_legacy_install(
			[
				'country_black' => 'RU',
				'country_white' => 'DE',
			],
			'1.01'
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$legacy = get_option( self::LEGACY_OPTION );

		self::assertSame( 'RU', $legacy['country_denied'], '`country_black` has to become `country_denied`.' );
		self::assertSame( 'DE', $legacy['country_allowed'], '`country_white` has to become `country_allowed`.' );
		self::assertArrayNotHasKey( 'country_black', $legacy, 'The old key has to be removed.' );
		self::assertArrayNotHasKey( 'country_white', $legacy, 'The old key has to be removed.' );
	}

	public function test_the_iphash_meta_survives_at_db_version_1_02(): void {
		$this->seed_legacy_install( [], '1.02' );

		$comment_id = self::factory()->comment->create();
		add_comment_meta( $comment_id, 'antispam_bee_iphash', 'deadbeef' );

		PluginUpdate::maybe_run_plugin_updated_logic();

		self::assertSame(
			'deadbeef',
			get_comment_meta( $comment_id, 'antispam_bee_iphash', true ),
			'The iphash cleanup must not run again for installs that already passed it.'
		);
	}

	public function test_an_install_already_on_3_0_is_not_migrated_again(): void {
		$this->seed_legacy_install( [], '3.0.0-alpha.1' );

		PluginUpdate::maybe_run_plugin_updated_logic();

		self::assertFalse(
			get_option( Settings::OPTION_NAME ),
			'An install at or above 3.0.0-alpha.1 must not run the option migration again.'
		);
	}

	public function test_the_migration_is_idempotent(): void {
		$this->seed_legacy_install();

		PluginUpdate::maybe_run_plugin_updated_logic();
		$first_run = get_option( Settings::OPTION_NAME );

		$this->reset_plugin_update_state();
		PluginUpdate::maybe_run_plugin_updated_logic();

		self::assertSame(
			$first_run,
			get_option( Settings::OPTION_NAME ),
			'Running the update logic twice must not change the migrated options.'
		);
	}

	public function test_a_failed_migration_step_leaves_the_database_version_untouched(): void {
		$this->seed_legacy_install();

		$this->expectException( RuntimeException::class );

		try {
			FailingPluginUpdate::maybe_run_plugin_updated_logic();
		} finally {
			self::assertSame(
				'1.02',
				get_option( self::DB_VERSION_OPTION ),
				'A migration that fails must not leave the database version bumped, or it is never retried.'
			);
			self::assertFalse(
				get_option( Settings::OPTION_NAME ),
				'Nothing was migrated, so the v3 option must not exist.'
			);
		}
	}

	public function test_a_failed_migration_is_retried_on_the_next_request(): void {
		$this->seed_legacy_install();

		try {
			FailingPluginUpdate::maybe_run_plugin_updated_logic();
		} catch ( RuntimeException $exception ) {
			unset( $exception );
		}

		// The next request starts with the per-request guards cleared.
		$this->reset_plugin_update_state();

		PluginUpdate::maybe_run_plugin_updated_logic();

		self::assertNotFalse(
			get_option( Settings::OPTION_NAME ),
			'The retry has to migrate the options the failed run never wrote.'
		);
		self::assertSame(
			4711,
			get_option( Settings::OPTION_NAME )['spam_count'],
			'The retry has to migrate from the legacy option, not from defaults.'
		);
		self::assertNotSame(
			'1.02',
			get_option( self::DB_VERSION_OPTION ),
			'A successful retry has to record the current database version.'
		);
	}

	/**
	 * Store a legacy install: the v2 option array plus the database revision it was left at.
	 *
	 * @param array<string, mixed> $overrides   Values replacing the v2 defaults.
	 * @param string               $db_version  The database revision the install is on.
	 *
	 * @return void
	 */
	private function seed_legacy_install( array $overrides = [], string $db_version = '1.02' ): void {
		update_option( self::LEGACY_OPTION, array_merge( $this->legacy_options(), $overrides ) );
		update_option( self::DB_VERSION_OPTION, $db_version );

		delete_option( Settings::OPTION_NAME );
		wp_cache_delete( Settings::OPTION_NAME );
	}

	/**
	 * A complete v2 option array.
	 *
	 * The keys mirror the defaults of Antispam Bee 2.x. Values deliberately deviate from
	 * those defaults so that a mapping pointing at the wrong source key is visible.
	 *
	 * @return array<string, mixed> The v2 options.
	 */
	private function legacy_options(): array {
		return [
			'regexp_check'             => 0,
			'spam_ip'                  => 1,
			'already_commented'        => 0,
			'gravatar_check'           => 1,
			'time_check'               => 1,
			'ignore_pings'             => 1,
			'dashboard_chart'          => 0,
			'dashboard_count'          => 1,
			'country_code'             => 1,
			'country_denied'           => 'RU,CN',
			'country_allowed'          => 'DE',
			'translate_api'            => 1,
			'translate_lang'           => [ 'de', 'en' ],
			'bbcode_check'             => 0,
			'flag_spam'                => 0,
			'email_notify'             => 1,
			'no_notice'                => 1,
			'cronjob_enable'           => 1,
			'cronjob_interval'         => 14,
			'ignore_filter'            => 0,
			'ignore_type'              => 0,
			'reasons_enable'           => 1,
			'ignore_reasons'           => [ 'css', 'empty', 'server', 'lang' ],
			'delete_data_on_uninstall' => 0,
			'spam_count'               => 4711,
		];
	}

	/**
	 * Clear the private static caches that make the migration run at most once per request.
	 *
	 * @return void
	 */
	private function reset_plugin_update_state(): void {
		$reflection = new ReflectionClass( PluginUpdate::class );

		$triggered = $reflection->getProperty( 'db_update_triggered' );
		$triggered->setAccessible( true );
		$triggered->setValue( null, false );

		$is_current = $reflection->getProperty( 'db_version_is_current' );
		$is_current->setAccessible( true );
		$is_current->setValue( null, null );

		wp_cache_delete( Settings::OPTION_NAME );
	}
}
