<?php
/**
 * Honeypot status for the admin.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\Honeypot as HoneypotField;
use AntispamBee\Rules\Honeypot as HoneypotRule;

/**
 * Whether the honeypot reaches the comment form, and how to explain it when not.
 *
 * Shared by every place that reports the state to an admin, so they agree on
 * when to speak up and on what to say.
 */
class HoneypotStatus {

	/**
	 * Whether an admin should be told that the honeypot does not reach the form.
	 *
	 * Only while the rule is active: an admin who switched it off chose to go
	 * without it.
	 *
	 * @return bool True if the active honeypot is missing from the comment form.
	 */
	public static function needs_attention(): bool {
		return (bool) HoneypotRule::is_active( ContentTypeHelper::COMMENT_TYPE )
			&& HoneypotField::protection_missing();
	}

	/**
	 * What went wrong, and what it costs.
	 *
	 * @return string The explanation, as plain text.
	 */
	public static function get_explanation(): string {
		$what = HoneypotField::INJECTION_STATE_FAILED === get_option( HoneypotField::INJECTION_STATE_OPTION )
			? __( 'The last time a comment form was displayed, Antispam Bee could not add its honeypot to it.', 'antispam-bee' )
			: __( 'Comments were submitted, but no comment form displayed so far carried the Antispam Bee honeypot.', 'antispam-bee' );

		return $what . ' ' . __( 'Without it, Antispam Bee cannot recognize bots that post comments without using the form, which is its most reliable check. Comments still arrive, but more spam gets through.', 'antispam-bee' );
	}

	/**
	 * What the admin can do about it.
	 *
	 * @return string The advice, as plain text.
	 */
	public static function get_advice(): string {
		$advice = HoneypotRule::uses_output_buffering()
			? __( '“Inject through output buffering” is already enabled, so the comment field could not be found in the page either. The honeypot needs a textarea named “comment”.', 'antispam-bee' )
			: __( 'Enable “Inject through output buffering” for the honeypot on the Comments tab of the Antispam Bee settings. It adds the honeypot to comment forms that are not built with comment_form(), as some themes and page builders do.', 'antispam-bee' );

		return $advice . ' ' . __( 'This status is updated the next time a page with a comment form is displayed.', 'antispam-bee' );
	}

	/**
	 * The settings tab holding the honeypot options.
	 *
	 * @return string The URL of the Comments tab of the settings page.
	 */
	public static function get_settings_url(): string {
		return admin_url( 'options-general.php?page=' . SettingsPage::SETTINGS_PAGE_SLUG . '&tab=' . ContentTypeHelper::COMMENT_TYPE );
	}
}
