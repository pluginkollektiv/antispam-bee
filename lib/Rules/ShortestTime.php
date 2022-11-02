<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;

/**
 * Rule that is responsible for checking that at least a certain timespan has passed so that the comment won‘t be marked as invalid.
 */
class ShortestTime extends ControllableBase {
	protected static $slug = 'asb-shortest-time';

	/**
	 * Initialize the rule.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'antispam_bee_rules', [ __CLASS__, 'add_rule' ] );

		add_filter(
			'comment_form_field_comment',
			function ( $field_markup ) {
				if ( ! self::is_active( ContentTypeHelper::COMMENT_TYPE ) ) {
					return $field_markup;
				}
				// Todo: use JS to add the timestamp, so it also works with page caching.
				return $field_markup . sprintf(
					'<input type="hidden" name="ab_init_time" value="%d" />',
					time()
				);
			}
		);
	}

	public static function verify( $item ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Everybody can Post.
		if ( ! isset( $_POST['ab_init_time'] ) ) {
			return 0;
		}
		$init_time = (int) $_POST['ab_init_time'];
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( 0 === $init_time ) {
			return 0;
		}

		// @todo: maybe rename this filter to start with `asb` and add a deprecation message.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		if ( time() - $init_time < apply_filters( 'antispam_bee_action_time_limit', 5 ) ) {
			return 1;
		}

		return 0;
	}

	public static function get_name() {
		return __( 'Comment time', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Consider the comment time', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'Not recommended when using page caching', 'antispam-bee' );
	}
}
