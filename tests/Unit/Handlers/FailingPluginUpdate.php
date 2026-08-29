<?php

namespace AntispamBee\Tests\Unit\Handlers;

use AntispamBee\Handlers\PluginUpdate;
use RuntimeException;

/**
 * A handler whose migration step always fails, to exercise the failure handling.
 *
 * `run_migration_steps()` is called through `static::`, so calling
 * {@see PluginUpdate::maybe_run_plugin_updated_logic()} on this subclass runs the
 * failing step below instead of the real one.
 */
class FailingPluginUpdate extends PluginUpdate {

	/**
	 * How often the step was entered.
	 *
	 * @var int
	 */
	public static $calls = 0;

	/**
	 * The failure state as it was stored at the moment the step ran.
	 *
	 * @var mixed
	 */
	public static $state_during_call = null;

	/**
	 * Fail the way an interrupted migration would.
	 *
	 * @param string $version_from_db The database revision the install is on.
	 *
	 * @return void
	 *
	 * @throws RuntimeException Always.
	 */
	protected static function run_migration_steps( string $version_from_db ): void {
		++self::$calls;
		self::$state_during_call = get_option( self::FAILURE_OPTION_NAME, null );

		throw new RuntimeException( 'Migration exploded' );
	}
}
