<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\Honeypot as HoneypotField;
use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\DebugMode;
use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\SpamReason;

/**
 * Adds honeypot to comment form and checks if it is filled.
 */
class Honeypot extends ControllableBase implements SpamReason {
	protected static $slug = 'asb-honeypot';
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
				if ( ! static::is_active( ContentTypeHelper::COMMENT_TYPE ) ) {
					return $field_markup;
				}

				return HoneypotField::inject( $field_markup, [ 'field_id' => 'comment' ] );
			}
		);
	}

	public static function verify( $item ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['ab_spam__hidden_field'] ) && 1 === $_POST['ab_spam__hidden_field'] ) {
			return 999;
		}

		return 0;
	}

	public static function precheck() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( is_feed() || is_trackback() || empty( $_POST ) ) {
			return;
		}
		
		$request_uri  = Settings::get_key( $_SERVER, 'REQUEST_URI' );
		$request_path = DataHelper::parse_url( $request_uri, 'path' );

		if ( strpos( $request_path, 'wp-comments-post.php' ) === false ) {
			return;
		}
		$fields = [];
		foreach ( $_POST as $key => $value ) {
			if ( $key === HoneypotField::get_secret_name_for_post() ) {
				$fields['plugin_field'] = $key;
			}
			if ( isset( $fields['plugin_field'] ) ) {
				$fields['hidden_field'] = $key;
				break;
			}
		}

		if ( ! isset( $fields['plugin_field'] ) ) {
			// Honeypot field was not present in $_POST data.
			$_POST['ab_spam__hidden_field'] = 1;
			return;
		}

		if ( ! empty( $_POST[ $fields['hidden_field'] ] ) ) {
			$_POST['ab_spam__hidden_field'] = 1;
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$_POST[ $fields['hidden_field'] ] = $_POST[ $fields['plugin_field'] ];
		unset( $_POST[ HoneypotField::get_secret_name_for_post() ] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	public static function get_name() {
		return _x( 'Honeypot', 'spam-reason-form-name', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Inject hidden field', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'No review of already commented users', 'antispam-bee' );
	}

	public static function get_reason_text() {
		return _x( 'Honeypot', 'spam-reason-text', 'antispam-bee' );
	}
}
