<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\SpamReason;

/**
 * Rule that is responsible for checking that at least a certain timespan has passed so that the comment won‘t be marked as invalid.
 */
class TooFastSubmit extends ControllableBase implements SpamReason {
	protected static $slug = 'asb-too-fast-submit';

	protected static $supported_types = [ ContentTypeHelper::COMMENT_TYPE ];

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

	public static function get_reason_text() {
		return _x( 'Created too quickly', 'spam-reason-text', 'antispam-bee' );
	}
}
