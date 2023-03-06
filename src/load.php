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
use AntispamBee\GeneralOptions\IgnorePings;
use AntispamBee\GeneralOptions\Statistics;
use AntispamBee\GeneralOptions\Uninstall;
use AntispamBee\Handlers\Comment;
use AntispamBee\Handlers\PluginStateChangeHandler;
use AntispamBee\Handlers\PluginUpdate;
use AntispamBee\Handlers\Linkback;
use AntispamBee\Helpers\Settings;
use AntispamBee\Helpers\SpamReasonTextHelper;
use AntispamBee\PostProcessors\Delete;
use AntispamBee\PostProcessors\DeleteForReasons;
use AntispamBee\PostProcessors\SaveReason;
use AntispamBee\PostProcessors\SendEmail;
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
use AntispamBee\Rules\LinkbackFromMyself;
use AntispamBee\Rules\LinkbackPostTitleIsBlogName;
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
		// Handlers
		Comment::class,
		Linkback::class,
		// Helpers
		SpamReasonTextHelper::class,
		// Post Processors
		DeleteOldSpam::class,
		Statistics::class,
		IgnorePings::class,
		Uninstall::class,
		Delete::class,
		DeleteForReasons::class,
		SaveReason::class,
		SendEmail::class,
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
		LinkbackFromMyself::class,
		LinkbackPostTitleIsBlogName::class,
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
register_activation_hook( ANTISPAM_BEE_FILE, [ PluginStateChangeHandler::class, 'activate' ] );
register_deactivation_hook( ANTISPAM_BEE_FILE, [ PluginStateChangeHandler::class, 'deactivate' ] );
register_uninstall_hook( ANTISPAM_BEE_FILE, [ PluginStateChangeHandler::class, 'uninstall' ] );
