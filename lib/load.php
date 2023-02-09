<?php
/**
 * Main plugin file to load other classes
 *
 * @package AntispamBee
 */

namespace AntispamBee;

use AntispamBee\Admin\CommentsColumns;
use AntispamBee\Admin\DashboardWidgets;
use AntispamBee\Admin\SettingsPage;
use AntispamBee\Crons\DeleteSpamCron;
use AntispamBee\GeneralOptions\DeleteOldSpam;
use AntispamBee\GeneralOptions\Pings;
use AntispamBee\GeneralOptions\Statistics;
use AntispamBee\GeneralOptions\Uninstall;
use AntispamBee\Handlers\Comment;
use AntispamBee\Handlers\PluginUpdate;
use AntispamBee\Handlers\Trackback;
use AntispamBee\Helpers\AssetsLoader;
use AntispamBee\Helpers\Installer;
use AntispamBee\Helpers\Settings;
use AntispamBee\Helpers\SpamReasonTextHelper;
use AntispamBee\PostProcessors\Delete;
use AntispamBee\PostProcessors\DeleteForReasons;
use AntispamBee\PostProcessors\SaveReason;
use AntispamBee\PostProcessors\SendEmail;
use AntispamBee\PostProcessors\UpdateDailyStats;
use AntispamBee\PostProcessors\UpdateSpamCount;
use AntispamBee\PostProcessors\UpdateSpamLog;
use AntispamBee\Rules\ApprovedEmail;
use AntispamBee\Rules\BBCode;
use AntispamBee\Rules\CountrySpam;
use AntispamBee\Rules\DbSpam;
use AntispamBee\Rules\Honeypot as HoneypotRule;
use AntispamBee\Rules\LangSpam;
use AntispamBee\Rules\RegexpSpam;
use AntispamBee\Rules\TooFastSubmit;
use AntispamBee\Rules\TrackbackFromMyself;
use AntispamBee\Rules\TrackbackPostTitleIsBlogName;
use AntispamBee\Rules\ValidGravatar;

/**
 * Init function of the plugin
 */
function init() {
	// Construct all modules to initialize.
	$modules = array(
		DashboardWidgets::class,
		new SettingsPage(),
		CommentsColumns::class,
		DeleteSpamCron::class,
		Settings::class,
		AssetsLoader::class,
		// Handlers
		Comment::class,
		Trackback::class,
		// Helpers
		SpamReasonTextHelper::class,
		// Post Processors
		DeleteOldSpam::class,
		Statistics::class,
		Pings::class,
		Uninstall::class,
		Delete::class,
		DeleteForReasons::class,
		SaveReason::class,
		SendEmail::class,
		UpdateDailyStats::class,
		UpdateSpamCount::class,
		UpdateSpamLog::class,
		// Rules
		ApprovedEmail::class,
		BBCode::class,
		CountrySpam::class,
		DbSpam::class,
		HoneypotRule::class,
		LangSpam::class,
		RegexpSpam::class,
		TooFastSubmit::class,
		TrackbackFromMyself::class,
		TrackbackPostTitleIsBlogName::class,
		ValidGravatar::class,
	);

	// Initialize all modules.
	foreach ( $modules as $module ) {
		if ( is_callable( [ $module, 'init' ] ) ) {
			call_user_func( [ $module, 'init' ] );
		}
	}
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );

add_action( 'upgrader_process_complete', [ PluginUpdate::class, 'upgrader_process_complete' ], 10, 2 );
add_action( 'upgrader_overwrote_package', [ PluginUpdate::class, 'upgrader_overwrote_package' ], 10, 3 );


// Register the activation, deactivation and uninstall hooks.
register_activation_hook( ANTISPAM_BEE_FILE, [ Installer::class, 'activate' ] );
register_deactivation_hook( ANTISPAM_BEE_FILE, [ Installer::class, 'deactivate' ] );
register_uninstall_hook( ANTISPAM_BEE_FILE, [ Installer::class, 'uninstall' ] );
