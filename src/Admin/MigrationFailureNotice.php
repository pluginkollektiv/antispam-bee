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
	 * How many affected sites the network notice names before summarising the rest.
	 */
	const MAX_LISTED_SITES = 20;

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

		/*
		 * The network screens are served by the main site, so the per-site failure state
		 * read below describes that one site and nothing else. Reporting it there would
		 * answer a question nobody asked while hiding the one they did: which site failed.
		 */
		if ( is_network_admin() ) {
			self::render_network_summary();

			return;
		}

		if ( self::render_success() ) {
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
	 * List the sites in this network whose migration failed.
	 *
	 * Each site keeps its own failure state, which the network screens cannot read, so
	 * the sites register themselves in a network option as they fail. Only the sites that
	 * actually failed are looked up here, which keeps this proportional to the problem
	 * rather than to the size of the network.
	 */
	private static function render_network_summary(): void {
		$site_ids = PluginUpdate::get_failed_sites();
		if ( empty( $site_ids ) ) {
			return;
		}

		echo '<div class="notice notice-error">';

		printf(
			'<p><strong>%s</strong></p>',
			esc_html(
				sprintf(
					/* translators: %d: number of sites whose migration failed. */
					_n(
						'Antispam Bee could not migrate the settings on %d site in this network.',
						'Antispam Bee could not migrate the settings on %d sites in this network.',
						count( $site_ids ),
						'antispam-bee'
					),
					count( $site_ids )
				)
			)
		);

		printf(
			'<p>%s</p>',
			esc_html__( 'Each site reports the details on its own dashboard, where the migration can be retried or the current settings kept.', 'antispam-bee' )
		);

		$shown = array_slice( $site_ids, 0, self::MAX_LISTED_SITES );

		echo '<ul>';
		foreach ( $shown as $site_id ) {
			$site = get_site( $site_id );
			if ( ! $site ) {
				continue;
			}

			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( get_admin_url( $site_id ) ),
				esc_html( $site->blogname ? $site->blogname : $site->domain . $site->path )
			);
		}
		echo '</ul>';

		$remaining = count( $site_ids ) - count( $shown );
		if ( $remaining > 0 ) {
			printf(
				'<p>%s</p>',
				esc_html(
					sprintf(
						/* translators: %d: number of further affected sites not listed. */
						_n( 'and %d more site.', 'and %d more sites.', $remaining, 'antispam-bee' ),
						$remaining
					)
				)
			);
		}

		echo '</div>';
	}

	/**
	 * Report a migration that completed, once.
	 *
	 * Reported wherever the admin happens to land, because the request that ran the
	 * migration is usually not one anybody was watching: a WP-CLI update, a cron run,
	 * or a front-end hit that read a setting. Without this the user has no way to tell
	 * their migrated settings apart from the defaults.
	 *
	 * @return bool Whether a success notice was rendered.
	 */
	private static function render_success(): bool {
		$migrated_from = PluginUpdate::get_pending_migration_notice();
		if ( null === $migrated_from ) {
			return false;
		}

		PluginUpdate::clear_pending_migration_notice();

		echo '<div class="notice notice-success is-dismissible">';

		printf(
			'<p><strong>%s</strong></p>',
			esc_html__( 'Antispam Bee migrated your settings.', 'antispam-bee' )
		);

		printf(
			'<p>%s</p>',
			esc_html__( 'Your previous configuration has been carried over to the new settings. Please check that everything is as you expect.', 'antispam-bee' )
		);

		echo '</div>';

		return true;
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
