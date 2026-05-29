<?php
/**
 * Register the dashboard widgets.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\GeneralOptions\Statistics;
use AntispamBee\Helpers\DashboardHelper;
use AntispamBee\Helpers\Settings;
/**
 * Class DashboardWidgets
 */
class DashboardWidgets {

	/**
	 * Initialize the dashboard widgets.
	 */
	public static function init(): void {
		if ( DashboardHelper::is_dashboard_page() ) {
			add_action( 'antispam_bee_count', [ __CLASS__, 'the_spam_count' ] );
			add_filter( 'dashboard_glance_items', [ __CLASS__, 'add_dashboard_count' ] );
		}
	}

	/**
	 * Display the spam counts on the dashboard
	 *
	 * @param array $items Initial array with dashboard items.
	 *
	 * @return  array $items  Merged array with dashboard items.
	 * @since  0.1
	 * @since  2.6.5
	 */
	public static function add_dashboard_count( array $items = array() ): array {
		if ( ! current_user_can( 'manage_options' ) || ! Statistics::is_active() ) {
			return $items;
		}

		echo '<style>#dashboard_right_now .ab-count::before {content: "\f117"} #dashboard_right_now .ab-current-spam::before {content: "\f17e"}</style>';

		$comments_blocked = self::get_spam_count();
		$comments_number  = wp_count_comments();

		$items[] = sprintf(
			'<span class="ab-count">%s</span>',
			esc_html(
				sprintf(
				// translators: The number of spam comments Antispam Bee blocked so far.
					_n(
						'%s blocked',
						'%s blocked',
						$comments_blocked,
						'antispam-bee'
					),
					number_format_i18n( $comments_blocked )
				)
			)
		);

		$items[] = sprintf(
			'<a href="%s" class="ab-current-spam">%s</a>',
			esc_url( add_query_arg( 'comment_status', 'spam', admin_url( 'edit-comments.php' ) ) ),
			esc_html(
				sprintf(
				// translators: The number of spam comments in the local spam database.
					_n(
						'%s comment in the local spam db',
						'%s comments in the local spam db',
						$comments_number->spam,
						'antispam-bee'
					),
					number_format_i18n( $comments_number->spam )
				)
			)
		);

		return $items;
	}

	/**
	 * Return the number of spam comments
	 *
	 * @since  0.1
	 * @since  2.4
	 */
	private static function get_spam_count(): string {
		return intval( Settings::get_option( 'spam_count', 0 ) );
	}

	/**
	 * Output the number of spam comments
	 *
	 * @since  0.1
	 * @since  2.4
	 */
	public static function the_spam_count(): void {
		echo esc_html( number_format_i18n( self::get_spam_count() ) );
	}
}
