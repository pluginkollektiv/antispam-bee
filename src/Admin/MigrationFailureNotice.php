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

		/*
		 * A network-activated install puts an administrator on the network screens, where
		 * `admin_notices` never fires, so without this the people most likely to be running
		 * a migration across many sites are the ones who would never hear that one failed.
		 */
		add_action( 'network_admin_notices', [ __CLASS__, 'render' ] );
		add_action( 'admin_post_' . self::RETRY_ACTION, [ __CLASS__, 'handle_retry' ] );
		add_action( 'admin_post_' . self::DISMISS_ACTION, [ __CLASS__, 'handle_dismiss' ] );
	}

	/**
	 * Render the notice for a migration that failed.
	 *
	 * Shown from the first recorded failure, not only once the attempts are spent. The
	 * state is what the user acts on: a retry they asked for has to report back in the
	 * same page load, and a migration killed by a fatal is worth knowing about before
	 * the third one. It stays up until the migration succeeds, because a site running
	 * on settings that are not the ones it was configured with is not a state to let
	 * pass quietly: rules the user turned off are active again, and rules they relied
	 * on may not be.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$state = PluginUpdate::get_failure_state();
		if ( $state['attempts'] < 1 ) {
			return;
		}

		$gave_up = $state['attempts'] >= PluginUpdate::MAX_UPDATE_ATTEMPTS;

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
			$gave_up
				? esc_html__( 'Antispam Bee could not migrate your settings.', 'antispam-bee' )
				: esc_html__( 'Antispam Bee could not migrate your settings yet.', 'antispam-bee' )
		);

		/*
		 * The migration, and so this state, belongs to one site. On the network screens
		 * there is no current site from the reader's point of view, so the notice has to
		 * name the one it is reporting on, or it reads as a statement about the network.
		 * It reports the site being browsed rather than scanning every site in the network,
		 * for the same reason the migration itself does not: that does not scale.
		 */
		if ( is_network_admin() ) {
			printf(
				'<p>%s</p>',
				esc_html(
					sprintf(
						/* translators: %s: name of the site the failed migration belongs to. */
						__( 'This concerns the site %s. Other sites in the network migrate separately and report separately.', 'antispam-bee' ),
						get_bloginfo( 'name' )
					)
				)
			);
		}

		/*
		 * What is running now and what happens next are two separate questions, and the
		 * answer to the second one flips at the cap. Folding them into a single sentence is
		 * how the give-up state came to promise settings that would be applied "as soon as
		 * the migration succeeds" when nothing was ever going to try again.
		 */
		if ( PluginUpdate::has_stored_settings() ) {
			$state_line = __( 'The plugin is running with the settings currently stored.', 'antispam-bee' );
		} else {
			$state_line = __( 'The plugin is currently running with its default settings.', 'antispam-bee' );
		}

		printf(
			'<p>%s %s</p>',
			esc_html( $state_line ),
			esc_html__( 'Your previous settings have not been lost — they are still in the database, unmigrated.', 'antispam-bee' )
		);

		if ( $gave_up ) {
			$next_line = sprintf(
				/* translators: %d: number of attempts that were made before giving up. */
				__( 'Antispam Bee stopped after %d attempts and will not try again on its own.', 'antispam-bee' ),
				PluginUpdate::MAX_UPDATE_ATTEMPTS
			);
		} else {
			$next_line = sprintf(
				/* translators: 1: number of the attempt that just failed, 2: total number of attempts. */
				__( 'Antispam Bee will try again on the next page load. Attempt %1$d of %2$d.', 'antispam-bee' ),
				$state['attempts'],
				PluginUpdate::MAX_UPDATE_ATTEMPTS
			);
		}

		printf( '<p>%s</p>', esc_html( $next_line ) );

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

		wp_safe_redirect( $referer ?: admin_url() );
		exit;
	}
}
