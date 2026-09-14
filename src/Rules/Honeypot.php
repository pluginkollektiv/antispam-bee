<?php
/**
 * Honeypot Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\Honeypot as HoneypotField;
use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\SpamReason;

/**
 * Adds honeypot to comment form and checks if it is filled.
 */
class Honeypot extends ControllableBase implements SpamReason {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-honeypot';

	/**
	 * A filled honeypot is a definitive spam signal.
	 *
	 * @var bool
	 */
	protected static $is_final = true;

	/**
	 * Only comments are supported.
	 *
	 * @var string[]
	 */
	protected static $supported_types = [ ContentTypeHelper::COMMENT_TYPE ];

	/**
	 * Initialize the rule.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'antispam_bee_rules', [ __CLASS__, 'add_rule' ] );

		add_filter( 'comment_form_field_comment', [ static::class, 'inject_honeypot_field' ], 99 );
	}

	/**
	 * Inject the honeypot field into the comment form.
	 *
	 * @param string $field_markup Markup of the comment field.
	 *
	 * @return string The markup, with the honeypot field injected.
	 */
	public static function inject_honeypot_field( $field_markup ) {
		if ( ! static::is_active( ContentTypeHelper::COMMENT_TYPE ) ) {
			return $field_markup;
		}

		return HoneypotField::inject( $field_markup, [ 'field_id' => 'comment' ] );
	}

	/**
	 * Verify an item.
	 *
	 * Check if request contains data from the honeypot field.
	 *
	 * Consumes no payload attributes; reads the request (`$_POST`) directly.
	 *
	 * @param array<string, mixed> $item Normalized payload to verify.
	 *
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['ab_spam__hidden_field'] ) && 1 === $_POST['ab_spam__hidden_field'] ) {
			return 999;
		}

		return 0;
	}

	/**
	 * Apply pre-checks during initialization.
	 *
	 * @return void
	 */
	public static function precheck(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( is_feed() || is_trackback() || empty( $_POST ) ) {
			return;
		}

		$request_uri  = Settings::get_key( $_SERVER, 'SCRIPT_NAME' );
		$request_path = DataHelper::parse_url( $request_uri, 'path' );

		if ( strpos( $request_path, 'wp-comments-post.php' ) === false ) {
			return;
		}

		$plugin_field_name = HoneypotField::get_secret_name_for_post();

		$hidden_field = Settings::get_key( $_POST, 'comment' );
		$plugin_field = Settings::get_key( $_POST, $plugin_field_name );

		// The secret comment field was not present in $_POST data.
		if ( is_null( $plugin_field ) ) {
			$_POST['ab_spam__invalid_request'] = 1;

			return;
		}

		// The honeypot field was not present in $_POST data or was filled out.
		if ( is_null( $hidden_field ) || ! empty( $hidden_field ) ) {
			$_POST['ab_spam__hidden_field'] = 1;

			return;
		}

		$_POST['comment'] = $plugin_field;
		unset( $_POST[ $plugin_field_name ] );
	}

	/**
	 * Get the rule name.
	 *
	 * @return string The rule name.
	 */
	public static function get_name(): string {
		return _x( 'Honeypot', 'spam-reason-form-name', 'antispam-bee' );
	}

	/**
	 * Get the rule label.
	 *
	 * @return string|null The rule label, or null.
	 */
	public static function get_label(): ?string {
		return __( 'Inject hidden field', 'antispam-bee' );
	}

	/**
	 * Get the rule description.
	 *
	 * @return string|null The rule description, or null.
	 */
	public static function get_description(): ?string {
		return __( 'No review of already commented users', 'antispam-bee' );
	}

	/**
	 * Get a human-readable spam reason.
	 *
	 * @return string The human-readable spam reason.
	 */
	public static function get_reason_text(): string {
		return _x( 'Honeypot', 'spam-reason-text', 'antispam-bee' );
	}
}
