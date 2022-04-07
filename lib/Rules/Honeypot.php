<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\Verifiable;
use AntispamBee\Helpers\Settings;
use \AntispamBee\Fields\Honeypot as HoneypotField;

class Honeypot implements Verifiable, Controllable {

	use IsActive;
	use InitRule;

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
				return HoneypotField::inject( $field_markup, [ 'field_id' => 'comment' ] );
			}
		);
	}

	public static function verify( $data ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['ab_spam__hidden_field'] ) && 1 === $_POST['ab_spam__hidden_field'] ) {
			return 1;
		}

		return - 1;
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
			if ( isset( $fields['plugin_field'] ) ) {
				$fields['hidden_field'] = $key;
				break;
			}
			if ( $key === HoneypotField::get_secret_name_for_post() ) {
				$fields['plugin_field'] = $key;
			}
		}
		if ( ! empty( $_POST[ $fields['hidden_field'] ] ) ) {
			$_POST['ab_spam__hidden_field'] = 1;
		} else {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$_POST[ $fields['hidden_field'] ] = $_POST[ $fields['plugin_field'] ];
			unset( $_POST[ HoneypotField::get_secret_name_for_post() ] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	public static function get_name() {
		return __( 'Approved Email', 'antispam-bee' );
	}

	public static function get_label() {
		return '';
	}

	public static function get_description() {
		return __( 'No review of already commented users', 'antispam-bee' );
	}

	public static function get_weight() {
		return 1.0;
	}

	public static function get_slug() {
		return 'asb-honeypot';
	}

	public static function is_final() {
		return false;
	}

	public static function get_options() {
		return null;
	}

	public static function get_supported_types() {
		return [ ItemTypeHelper::COMMENT_TYPE ];
	}
}
