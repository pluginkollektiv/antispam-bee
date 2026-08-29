<?php
/**
 * Surface a migration that gave up, and offer a way to retry it.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Handlers\PluginUpdate;

/**
 * Migration failure notice handler.
 */
class MigrationFailureNotice {

	/**
	 * Action name used for the manual retry request.
	 */
	const RETRY_ACTION = 'antispam_bee_retry_migration';

	/**
	 * Register the notice and the retry handler.
	 */
	public static function init(): void {
		add_action( 'admin_notices', [ __CLASS__, 'render' ] );
		add_action( 'admin_post_' . self::RETRY_ACTION, [ __CLASS__, 'handle_retry' ] );
	}

	/**
	 * Render the notice once the migration has given up.
	 *
	 * Shown until the migration succeeds, because a site running on default settings
	 * while its real configuration sits unmigrated is not a state to let pass quietly:
	 * rules the user turned off are active again, and rules they relied on may not be.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$state = PluginUpdate::get_failure_state();
		if ( $state['attempts'] < PluginUpdate::MAX_UPDATE_ATTEMPTS ) {
			return;
		}

		$retry_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::RETRY_ACTION ),
			self::RETRY_ACTION
		);

		echo '<div class="notice notice-error">';

		printf(
			'<p><strong>%s</strong></p>',
			esc_html__( 'Antispam Bee could not migrate your settings.', 'antispam-bee' )
		);

		printf(
			'<p>%s</p>',
			esc_html__( 'The plugin is currently running with its default settings. Your previous settings have not been lost — they are still stored in the database and will be applied as soon as the migration succeeds.', 'antispam-bee' )
		);

		if ( '' !== $state['message'] ) {
			printf(
				'<p><code>%s</code></p>',
				esc_html( $state['message'] )
			);
		}

		printf(
			'<p><a class="button button-primary" href="%s">%s</a></p>',
			esc_url( $retry_url ),
			esc_html__( 'Retry migration', 'antispam-bee' )
		);

		echo '</div>';
	}

	/**
	 * Reset the failure state so the migration is attempted again.
	 *
	 * Clearing the state is all it takes: the database version is still the old one,
	 * so the next read of the settings runs the migration with a fresh set of attempts.
	 */
	public static function handle_retry(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'antispam-bee' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::RETRY_ACTION );

		delete_option( PluginUpdate::FAILURE_OPTION_NAME );

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}
}
