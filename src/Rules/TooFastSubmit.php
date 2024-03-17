<?php
/**
 * Too Fast Submit Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\SpamReason;

/**
 * Rule that is responsible for checking that at least a certain timespan has passed so that the comment won‘t be marked as invalid.
 */
class TooFastSubmit extends ControllableBase implements SpamReason {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-too-fast-submit';

	/**
	 * Only comments are supported.
	 *
	 * @var array
	 */
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

				$unique_id = uniqid( 'antispam-bee-time-' );
				$script    = sprintf(
					'<script>(function() {
						var time = Math.floor(Date.now() / 1000),
							timeField = document.querySelector(\'input[data-unique-id="%s"]\');

						if (timeField) {
							timeField.value = time;
						}
					}());</script>',
					$unique_id
				);

				return $field_markup . sprintf(
					'<input type="hidden" name="ab_init_time" data-unique-id="%s" value="%d" />%s',
					$unique_id,
					time(),
					$script
				);
			}
		);
	}

	/**
	 * Verify an item.
	 *
	 * Test for time between page initialization and reaction.
	 *
	 * @param array $item Item to verify.
	 * @return int Numeric result.
	 */
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

	/**
	 * Get rule name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return __( 'Comment time', 'antispam-bee' );
	}

	/**
	 * Get rule label.
	 *
	 * @return string|null
	 */
	public static function get_label() {
		return __( 'Consider the comment time', 'antispam-bee' );
	}

	/**
	 * Get rule description.
	 *
	 * @return string|null
	 */
	public static function get_description() {
		return __( 'Not recommended when using page caching', 'antispam-bee' );
	}

	/**
	 * Get human-readable spam reason.
	 *
	 * @return string
	 */
	public static function get_reason_text() {
		return _x( 'Created too quickly', 'spam-reason-text', 'antispam-bee' );
	}
}
