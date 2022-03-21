<?php
/**
 * A helper providing some conditional functions for the dashboard.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Class DashboardHelper
 */
class DashboardHelper {

	/**
	 * Check, if we are on the dashboard page.
	 *
	 * @return bool
	 */
	public static function is_dashboard_page() {
		return ( empty( $GLOBALS['pagenow'] ) || ( ! empty( $GLOBALS['pagenow'] ) && 'index.php' === $GLOBALS['pagenow'] ) );
	}

	/**
	 * Check, if we are on the options page.
	 *
	 * @return bool
	 */
	public static function is_options_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return ( ! empty( $_GET['page'] ) && 'antispam_bee' === $_GET['page'] );
	}

	/**
	 * Check, if we are on the plugins page.
	 *
	 * @return bool
	 */
	public static function is_plugins_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return ( ! empty( $_GET['page'] ) && 'antispam_bee' === $_GET['page'] );
	}

	/**
	 * Check, if we are on the admin post page.
	 *
	 * @return bool
	 */
	public static function is_admin_post_page() {
		return ( ! empty( $GLOBALS['pagenow'] ) && 'admin-post.php' === $GLOBALS['pagenow'] );
	}

	/**
	 * Check, if we are on the edit comments page.
	 *
	 * @return bool
	 */
	public static function is_edit_comments_page() {
		return ( ! empty( $GLOBALS['pagenow'] ) && 'edit-comments.php' === $GLOBALS['pagenow'] );
	}

	/**
	 * Check, if we are on the edit comments page on the spam comment status listing.
	 *
	 * @return bool
	 */
	public static function is_edit_spam_comments_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return self::is_edit_comments_page() && ! empty( $_GET['comment_status'] ) && 'spam' === $_GET['comment_status'];
	}
}
