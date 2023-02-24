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
// @todo: add current spam count to at a glance widget with a link to the spam list
/**
 * Class DashboardWidgets
 */
class DashboardWidgets {

	/**
	 * Initialize the dashboard widgets.
	 */
	public static function init() {
		if ( DashboardHelper::is_dashboard_page() ) {
			add_action( 'antispam_bee_count', [ __CLASS__, 'the_spam_count' ] );
			add_filter( 'dashboard_glance_items', [ __CLASS__, 'add_dashboard_count' ] );
		}
	}

	/**
	 * Display the spam counter on the dashboard
	 *
	 * @param array $items Initial array with dashboard items.
	 *
	 * @return  array $items  Merged array with dashboard items.
	 * @since  0.1
	 * @since  2.6.5
	 */
	public static function add_dashboard_count( $items = array() ) {
		if ( ! current_user_can( 'manage_options' ) || ! Settings::get_option( Statistics::get_option_name( Statistics::DASHBOARD_COUNT_OPTION ) ) ) {
			return $items;
		}

		echo '<style>#dashboard_right_now .ab-count:before {content: "\f117"}</style>';

		$items[] = '<span class="ab-count">' . esc_html(
				sprintf(
				// translators: The number of spam comments Antispam Bee blocked so far.
					__( '%s Blocked', 'antispam-bee' ),
					self::get_spam_count()
				)
			) . '</span>';

		return $items;
	}

	/**
	 * Return the number of spam comments
	 *
	 * @since  0.1
	 * @since  2.4
	 */
	private static function get_spam_count() {
		$count = Settings::get_option( 'spam_count', '' );

		return ( get_locale() === 'de_DE' ? number_format( $count, 0, '', '.' ) : number_format_i18n( $count ) );
	}

	/**
	 * Output the number of spam comments
	 *
	 * @since  0.1
	 * @since  2.4
	 */
	public static function the_spam_count() {
		echo esc_html( self::get_spam_count() );
	}
}
