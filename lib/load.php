<?php
/**
 * Main plugin file to load other classes
 *
 * @package AntispamBee
 */

namespace AntispamBee;

use AntispamBee\Admin\DashboardWidgets;
use AntispamBee\Admin\SettingsPage;
use AntispamBee\Crons\DeleteSpamCron;
use AntispamBee\GeneralOptions\DeleteOldSpam;
use AntispamBee\GeneralOptions\Pings;
use AntispamBee\GeneralOptions\Statistics;
use AntispamBee\GeneralOptions\Uninstall;
use AntispamBee\Handlers\Comment;
use AntispamBee\Handlers\Trackback;
use AntispamBee\Helpers\AssetsLoader;
use AntispamBee\Helpers\CommentsColumns;
use AntispamBee\Helpers\Installer;
use AntispamBee\Helpers\Settings;
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
use AntispamBee\Rules\ShortestTime;
use AntispamBee\Rules\TrackbackFromMyself;
use AntispamBee\Rules\TrackbackPostTitleIsBlogName;
use AntispamBee\Rules\ValidGravatar;

/**
 * Init function of the plugin
 */
function init() {
	// Construct all modules to initialize.
	$modules = array(
		'admin_dashboard_helper'                  => new DashboardWidgets(),
		'admin_settings_page'                     => new SettingsPage(),
		'handlers_delete_spam_cron_handler'       => new DeleteSpamCron(),
		'handlers_comment_handler'                => new Comment(),
		'handlers_trackback_handler'              => new Trackback(),
		'helpers_assets_loader'                   => new AssetsLoader(),
		'helpers_comments_columns'                => new CommentsColumns(),
		'helpers_options_helper'                  => new Settings(),
		'general_option_delete_old_spam'          => new DeleteOldSpam(),
		'general_option_statistics'               => new Statistics(),
		'general_option_pings'                    => new Pings(),
		'general_option_uninstall'                => new Uninstall(),
		'post_processor_delete'                   => new Delete(),
		'post_processor_delete_for_reasons'       => new DeleteForReasons(),
		'post_processor_save_reason'              => new SaveReason(),
		'post_processor_send_email'               => new SendEmail(),
		'post_processor_update_daily_stats'       => new UpdateDailyStats(),
		'post_processor_update_spam_count'        => new UpdateSpamCount(),
		'post_processor_update_spam_log'          => new UpdateSpamLog(),
		'rules_approved_email'                    => new ApprovedEmail(),
		'rules_bbcode'                            => new BBCode(),
		'rules_country_spam'                      => new CountrySpam(),
		'rules_db_spam'                           => new DbSpam(),
		'rules_honeypot'                          => new HoneypotRule(),
		'rules_lang_spam'                         => new LangSpam(),
		'rules_regexp_spam'                       => new RegexpSpam(),
		'rules_shortest_time'                     => new ShortestTime(),
		'rules_trackback_from_myself'             => new TrackbackFromMyself(),
		'rules_trackback_post_title_is_blog_name' => new TrackbackPostTitleIsBlogName(),
		'rules_valid_gravatar'                    => new ValidGravatar(),
	);

	// Initialize all modules.
	foreach ( $modules as $module ) {
		if ( is_callable( [ $module, 'init' ] ) ) {
			call_user_func( [ $module, 'init' ] );
		}
	}
}

add_action( 'plugins_loaded', 'AntispamBee\init' );

// Register the activation, deactivation and uninstall hooks.
register_activation_hook( ANTISPAM_BEE_FILE, [ Installer::class, 'activate' ] );
register_deactivation_hook( ANTISPAM_BEE_FILE, [ Installer::class, 'deactivate' ] );
register_uninstall_hook( ANTISPAM_BEE_FILE, [ Installer::class, 'uninstall' ] );
