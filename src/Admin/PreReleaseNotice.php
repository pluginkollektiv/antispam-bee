<?php
/**
 * Pre-release notice.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Helpers\StringHelper;
use const AntispamBee\MAIN_PLUGIN_FILE;
use const AntispamBee\PLUGIN_VERSION;

/**
 * Show a dismissible notice while the installed version is a pre-release.
 */
class PreReleaseNotice {

	/**
	 * The user meta key that stores whether the notice was dismissed.
	 *
	 * @var string
	 */
	public const DISMISSED_META_KEY = 'antispam_bee_pre_release_notice_dismissed';

	/**
	 * The nonce action used to dismiss the notice.
	 *
	 * @var string
	 */
	public const DISMISS_ACTION = 'antispam_bee_dismiss_pre_release_notice';

	/**
	 * The admin page hook suffix of the plugins list.
	 *
	 * @var string
	 */
	public const PLUGINS_PAGE = 'plugins.php';

	/**
	 * The URL of the issue tracker used as the feedback channel.
	 *
	 * @var string
	 */
	public const FEEDBACK_URL = 'https://github.com/pluginkollektiv/antispam-bee/issues';

	/**
	 * Register the notice hooks.
	 */
	public static function init(): void {
		add_action( 'admin_notices', [ __CLASS__, 'admin_notices' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'maybe_enqueue_assets' ] );
	}

	/**
	 * Register the dismissal handlers.
	 *
	 * The handlers must be available whenever a request hits them, so they are
	 * registered here rather than in {@see init()}, which the bootloader skips
	 * during AJAX requests.
	 */
	public static function always_init(): void {
		add_action( 'wp_ajax_' . self::DISMISS_ACTION, [ __CLASS__, 'handle_dismiss' ] );
		add_action( 'admin_post_' . self::DISMISS_ACTION, [ __CLASS__, 'handle_dismiss' ] );
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
	 * Render the notice on the settings page and the plugins list, unless the
	 * installed version is stable or the user already dismissed it.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public static function maybe_render( string $hook_suffix = '' ): void {
		if ( ! self::should_show( $hook_suffix ) ) {
			return;
		}

		$dismiss_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::DISMISS_ACTION ),
			self::DISMISS_ACTION
		);

		printf(
			'<div class="notice notice-warning" data-antispam-bee-pre-release-notice>' .
			'<p><strong>%1$s</strong></p>' .
			'<p>%2$s <code>%3$s</code></p>' .
			'<p>%4$s</p>' .
			'<p><a class="button" href="%5$s" target="_blank" rel="noopener noreferrer">%6$s</a> ' .
			'<a class="button-link" href="%7$s" data-antispam-bee-dismiss-link="%7$s">%8$s</a></p>' .
			'<button type="button" class="notice-dismiss" data-antispam-bee-dismiss aria-label="%9$s"><span class="screen-reader-text">%9$s</span></button>' .
			'</div>',
			esc_html__( 'Antispam Bee is a pre-release version', 'antispam-bee' ),
			esc_html__( 'You are running version', 'antispam-bee' ),
			esc_html( PLUGIN_VERSION ),
			esc_html__(
				'This is a pre-release and not intended for production. Please test it and report any issues you find.',
				'antispam-bee'
			),
			esc_url( self::FEEDBACK_URL ),
			esc_html__( 'Report a bug', 'antispam-bee' ),
			esc_url( $dismiss_url ),
			esc_html__( 'Dismiss', 'antispam-bee' ),
			esc_html__( 'Dismiss this notice', 'antispam-bee' )
		);
	}

	/**
	 * Enqueue the dismiss handler on the pages that show the notice.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public static function maybe_enqueue_assets( string $hook_suffix = '' ): void {
		if ( ! self::should_show( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_script(
			'antispam-bee-pre-release-notice',
			plugin_dir_url( MAIN_PLUGIN_FILE ) . 'assets/js/admin-notice.js',
			[ 'wp-util' ],
			PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'antispam-bee-pre-release-notice',
			'antispamBeePreReleaseNotice',
			[
				'action' => self::DISMISS_ACTION,
				'nonce'  => wp_create_nonce( self::DISMISS_ACTION ),
			]
		);
	}

	/**
	 * Whether the notice should render for the current user and page.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return bool Whether to show the notice.
	 */
	private static function should_show( string $hook_suffix ): bool {
		if ( ! StringHelper::is_pre_release( PLUGIN_VERSION ) ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( 'settings_page_' . SettingsPage::SETTINGS_PAGE_SLUG !== $hook_suffix && self::PLUGINS_PAGE !== $hook_suffix ) {
			return false;
		}

		return false === (bool) get_user_meta( get_current_user_id(), self::DISMISSED_META_KEY, true );
	}

	/**
	 * Persist the dismissal, then acknowledge an AJAX request or redirect.
	 *
	 * Both `wp_send_json_success()` and the redirect end the request, so the
	 * non-AJAX branch is the only one that reaches the redirect and `exit`.
	 */
	public static function handle_dismiss(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'antispam-bee' ), 403 );
		}

		$is_ajax = wp_doing_ajax();

		if ( $is_ajax ) {
			check_ajax_referer( self::DISMISS_ACTION );
			update_user_meta( get_current_user_id(), self::DISMISSED_META_KEY, 1 );
			wp_send_json_success();
		} else {
			check_admin_referer( self::DISMISS_ACTION );
			update_user_meta( get_current_user_id(), self::DISMISSED_META_KEY, 1 );

			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );

			exit;
		}
	}
}
