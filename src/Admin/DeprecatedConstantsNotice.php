<?php
/**
 * Warn about deprecated wp-config.php constants on the settings page.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

/**
 * Show a notice on the settings page while a deprecated logging constant is defined.
 *
 * `_doing_it_wrong()` only surfaces with `WP_DEBUG`, which a production server
 * running Fail2Ban against these logs will typically not have. This notice is
 * the way an administrator who never turns that on still finds out before the
 * constant stops being read.
 */
class DeprecatedConstantsNotice {

	/**
	 * Deprecated constant names mapped to the constant that replaces them.
	 *
	 * @var array<string, string>
	 */
	const REPLACEMENTS = [
		'ANTISPAM_BEE_LOG_FILE'           => 'ANTISPAM_BEE_SPAM_LOG',
		'ANTISPAM_BEE_DEBUG_MODE_ENABLED' => 'ANTISPAM_BEE_DEBUG_LOG',
	];

	/**
	 * Register the notice.
	 */
	public static function init(): void {
		add_action( 'admin_notices', [ __CLASS__, 'admin_notices' ] );
	}

	/**
	 * Render the notice, reading the page WordPress does not pass along.
	 *
	 * `admin_notices` invokes callbacks without the hook suffix, so it is read
	 * from the global that WordPress sets for admin pages.
	 */
	public static function admin_notices(): void {
		global $hook_suffix;

		self::maybe_render( (string) $hook_suffix );
	}

	/**
	 * Render a notice for every deprecated constant that is currently defined.
	 *
	 * Scoped to the settings page: that is where the logging constants are
	 * acted on, and nagging every wp-admin page would train administrators to
	 * ignore the one page where it matters.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public static function maybe_render( string $hook_suffix = '' ): void {
		if ( 'settings_page_' . SettingsPage::SETTINGS_PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( self::REPLACEMENTS as $deprecated => $replacement ) {
			if ( defined( $deprecated ) && constant( $deprecated ) ) {
				self::render( $deprecated, $replacement );
			}
		}
	}

	/**
	 * Render the notice for a single deprecated constant.
	 *
	 * Deliberately not dismissible: the constant is still defined in
	 * `wp-config.php` after a dismissal, so hiding the notice would hide the
	 * only place this is surfaced on a site that does not run with `WP_DEBUG`.
	 *
	 * @param string $deprecated The deprecated constant's name.
	 * @param string $replacement The constant that replaces it.
	 */
	private static function render( string $deprecated, string $replacement ): void {
		echo '<div class="notice notice-warning">';

		printf(
			'<p>%s</p>',
			wp_kses(
				sprintf(
					/* translators: 1: deprecated constant name, 2: replacement constant name, both wrapped in code tags. */
					__( 'The constant %1$s is deprecated and will be removed in a future version of Antispam Bee. Please use %2$s instead.', 'antispam-bee' ),
					'<code>' . esc_html( $deprecated ) . '</code>',
					'<code>' . esc_html( $replacement ) . '</code>'
				),
				[ 'code' => [] ]
			)
		);

		echo '</div>';
	}
}
