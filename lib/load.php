<?php
/**
 * Main plugin file to load other classes
 *
 * @package AntispamBee
 */

namespace AntispamBee;

use AntispamBee\Admin\SettingsPage;
use AntispamBee\Fields\Honeypot as HoneypotField;
use AntispamBee\Rules\Honeypot as HoneypotRule;
use AntispamBee\Handlers\Comment;
use AntispamBee\Handlers\Trackback;
use AntispamBee\Helpers\AssetsLoader;
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
use AntispamBee\Rules\LangSpam;
use AntispamBee\Rules\RegexpSpam;
use AntispamBee\Rules\ShortestTime;
use AntispamBee\Rules\TrackbackFromMyself;
use AntispamBee\Rules\TrackbackPostTitleIsBlogName;
use AntispamBee\Rules\ValidGravatar;
use AntispamBee\Helpers\CommentsColumns;
use AntispamBee\Helpers\Installer;
use AntispamBee\Helpers\OptionsHelper;

/**
 * Init function of the plugin
 */
function init() {
	// Construct all modules to initialize.
	$modules = [
		'helpers_assets_loader' => new AssetsLoader(),
		'settings_page' => new SettingsPage(),
    'helpers_comments_columns' => new CommentsColumns(),
		'approved_email_rule' => ApprovedEmail::class,
		'bbcode_rule' => BBCode::class,
		'country_spam_rule' => CountrySpam::class,
		'db_spam_rule' => DbSpam::class,
		'lang_spam_rule' => LangSpam::class,
		'regexp_spam_rule' => RegexpSpam::class,
		'shortest_time_rule' => ShortestTime::class,
		'trackback_from_myself_rule' => TrackbackFromMyself::class,
		'trackback_post_title_is_blog_name_rule' => TrackbackPostTitleIsBlogName::class,
		'valid_gravatar_rule' => ValidGravatar::class,
		'honeypot_rule' => HoneypotRule::class,
		'delete_post_processor' => Delete::class,
		'delete_for_reasons_post_processor' => DeleteForReasons::class,
		'save_reason_post_processor' => SaveReason::class,
		'send_email_post_processor' => SendEmail::class,
		'update_daily_stats_post_processor' => UpdateDailyStats::class,
		'update_spam_count_post_processor' => UpdateSpamCount::class,
		'update_spam_log_post_processor' => UpdateSpamLog::class,
		'comment_handler' => Comment::class,
		'trackback_handler' => Trackback::class,
		'helpers_options_helper'   => new OptionsHelper(),
	];

	// Initialize all modules.
	foreach ( $modules as $module ) {
		if ( is_callable( [ $module, 'init' ] ) ) {
			call_user_func( [ $module, 'init' ] );
		}
	}

	add_filter(
		'comment_form_field_comment',
		function( $field_markup ) {
			return HoneypotField::inject( $field_markup, [ 'field_id' => 'comment' ] );
		}
	);
}

add_action( 'plugins_loaded', 'AntispamBee\init' );

// Register the activation, deactivation and uninstall hooks.
register_activation_hook( ANTISPAM_BEE_FILE, [ Installer::class, 'activate' ] );
register_deactivation_hook( ANTISPAM_BEE_FILE, [ Installer::class, 'deactivate' ] );
register_uninstall_hook( ANTISPAM_BEE_FILE, [ Installer::class, 'uninstall' ] );
