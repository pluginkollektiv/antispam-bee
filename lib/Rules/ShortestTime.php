<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\Verifiable;
use AntispamBee\Helpers\Settings;

class ShortestTime implements Verifiable, Controllable {

	use InitRule;
	use IsActive;

	/**
	 * Initialize the rule.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'asb_rules', [ __CLASS__, 'add_rule' ] );

		add_filter(
			'comment_form_field_comment',
			function ( $field_markup ) {
				if ( ! self::is_active( ItemTypeHelper::COMMENT_TYPE ) ) {
					return $field_markup;
				}
				return $field_markup . sprintf(
					'<input type="hidden" name="ab_init_time" value="%d" />',
					time()
				);
			}
		);
	}

	// Todo: test
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

		// @todo: maybe rename this filter to start with `abs` and add a deprecation message.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		if ( time() - $init_time < apply_filters( 'ab_action_time_limit', 5 ) ) {
			return 1;
		}

		return 0;
	}

	public static function get_name() {
		return __( 'Comment time', 'antispam-bee' );
	}

	public static function get_weight() {
		return 1.0;
	}

	public static function get_slug() {
		return 'asb-shortest-time';
	}

	public static function is_final() {
		return false;
	}

	public static function get_label() {
		return __( 'Consider the comment time', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'Not recommended when using page caching', 'antispam-bee' );
	}

	public static function get_options() {
		return null;
	}

	public static function get_supported_types() {
		return [ ItemTypeHelper::COMMENT_TYPE, ItemTypeHelper::TRACKBACK_TYPE ];
	}
}
