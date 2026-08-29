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
	 * Action name used to stop retrying and keep the current settings.
	 */
	const DISMISS_ACTION = 'antispam_bee_dismiss_migration';

	/**
	 * Register the notice and its handlers.
	 */
	public static function init(): void {
		add_action( 'admin_notices', [ __CLASS__, 'render' ] );
		add_action( 'admin_post_' . self::RETRY_ACTION, [ __CLASS__, 'handle_retry' ] );
		add_action( 'admin_post_' . self::DISMISS_ACTION, [ __CLASS__, 'handle_dismiss' ] );
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

		$dismiss_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::DISMISS_ACTION ),
			self::DISMISS_ACTION
		);

		echo '<div class="notice notice-error">';

		printf(
			'<p><strong>%s</strong></p>',
			esc_html__( 'Antispam Bee could not migrate your settings.', 'antispam-bee' )
		);

		if ( PluginUpdate::has_stored_settings() ) {
			$intro = __( 'The plugin is running with the settings currently stored. Your previous settings have not been lost — they are still in the database, unmigrated.', 'antispam-bee' );
		} else {
			$intro = __( 'The plugin is currently running with its default settings. Your previous settings have not been lost — they are still stored in the database and will be applied as soon as the migration succeeds.', 'antispam-bee' );
		}

		printf( '<p>%s</p>', esc_html( $intro ) );

		if ( '' !== $state['message'] ) {
			printf(
				'<p><code>%s</code></p>',
				esc_html( $state['message'] )
			);
		}

		/*
		 * The wording has to follow what the button actually does. Once settings are
		 * stored — because the user gave up and configured the plugin by hand — a retry
		 * can only get the old settings back by replacing them, and saying "retry" while
		 * quietly leaving them in place would be a lie: the migration would skip its work
		 * and simply report success.
		 */
		if ( PluginUpdate::has_stored_settings() ) {
			$retry_label = __( 'Discard the current settings and migrate again', 'antispam-bee' );
			$explanation = __( 'Migrating again replaces the settings currently stored with the result of migrating your previous ones. Choose “Keep the current settings” to stop retrying and leave your settings exactly as they are.', 'antispam-bee' );
		} else {
			$retry_label = __( 'Migrate again', 'antispam-bee' );
			$explanation = __( 'Choose “Keep the current settings” to stop retrying for good and configure the plugin yourself.', 'antispam-bee' );
		}

		printf(
			'<p><a class="button button-primary" href="%s">%s</a> <a class="button" href="%s">%s</a></p>',
			esc_url( $retry_url ),
			esc_html( $retry_label ),
			esc_url( $dismiss_url ),
			esc_html__( 'Keep the current settings', 'antispam-bee' )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html( $explanation )
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
		self::authorize( self::RETRY_ACTION );

		PluginUpdate::reset_for_retry();

		self::redirect_back();
	}

	/**
	 * Stop retrying and keep whatever is configured now.
	 *
	 * For a site whose migration cannot be made to work: the user configures the
	 * plugin by hand and takes the notice away for good. Nothing is overwritten —
	 * the database is simply recorded as migrated.
	 */
	public static function handle_dismiss(): void {
		self::authorize( self::DISMISS_ACTION );

		PluginUpdate::mark_as_migrated();

		self::redirect_back();
	}

	/**
	 * Make sure the current request may act on the migration state.
	 *
	 * @param string $action The action being performed.
	 */
	private static function authorize( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'antispam-bee' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( $action );
	}

	/**
	 * Return to the page the notice was shown on.
	 */
	private static function redirect_back(): void {
		$referer = wp_get_referer();

		wp_safe_redirect( $referer ? $referer : admin_url() );
		exit;
	}
}
