<?php
/**
 * Honeypot notice on the settings page.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

/**
 * Warn on the Antispam Bee settings page when the honeypot does not reach the
 * comment form.
 *
 * Shown there only, where an admin configures the plugin and can act on it, and
 * not on every admin page. It cannot be dismissed: it goes away once the
 * honeypot is seen in a rendered comment form again.
 */
class HoneypotNotice {

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'load-settings_page_' . SettingsPage::SETTINGS_PAGE_SLUG, [ __CLASS__, 'register' ] );
	}

	/**
	 * Hook the notice in, once the settings page is being loaded.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_notices', [ __CLASS__, 'render' ] );
	}

	/**
	 * Render the notice.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) || ! HoneypotStatus::needs_attention() ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong></p><p>%s</p><p>%s</p><p><a href="%s">%s</a></p></div>',
			esc_html__( 'The Antispam Bee honeypot could not be added to your comment form.', 'antispam-bee' ),
			esc_html( HoneypotStatus::get_explanation() ),
			esc_html( HoneypotStatus::get_advice() ),
			esc_url( HoneypotStatus::get_settings_url() ),
			esc_html__( 'Open the comment settings', 'antispam-bee' )
		);
	}
}
