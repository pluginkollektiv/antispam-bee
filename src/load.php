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
use AntispamBee\GeneralOptions\IgnoreLinkbacks;
use AntispamBee\GeneralOptions\Statistics;
use AntispamBee\GeneralOptions\Uninstall;
use AntispamBee\Handlers\Comment;
use AntispamBee\Handlers\PluginStateChangeHandler;
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
	$modules = [
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
		IgnoreLinkbacks::class,
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
	];

	$disallow_ajax = apply_filters( 'antispam_bee_disallow_ajax_calls', true );

	$is_ajax_call = defined( 'DOING_AJAX' ) && DOING_AJAX;

	// Initialize all modules.
	foreach ( $modules as $module ) {
		if ( is_callable( [ $module, 'always_init' ] ) ) {
			call_user_func( [ $module, 'always_init' ] );
		}

		if ( $is_ajax_call && $disallow_ajax ) {
			continue;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			continue;
		}

		if ( is_callable( [ $module, 'init' ] ) ) {
			call_user_func( [ $module, 'init' ] );
		}
	}
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );

// Register the activation, deactivation and uninstall hooks.
register_activation_hook( MAIN_PLUGIN_FILE, [ PluginStateChangeHandler::class, 'activate' ] );
register_deactivation_hook( MAIN_PLUGIN_FILE, [ PluginStateChangeHandler::class, 'deactivate' ] );
register_uninstall_hook( MAIN_PLUGIN_FILE, [ PluginStateChangeHandler::class, 'uninstall' ] );
