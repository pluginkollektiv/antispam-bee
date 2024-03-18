<?php
/**
 * Valid Gravator Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\DataHelper;

/**
 * Rule that is responsible for checking if the commenter has a valid gravatar.
 */
class ValidGravatar extends ControllableBase {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-valid-gravatar';

	/**
	 * Only comments are supported.
	 *
	 * @var array
	 */
	protected static $supported_types = [ ContentTypeHelper::COMMENT_TYPE ];

	/**
	 * Verify an item.
	 *
	 * Test if author's email points to a valid Gravatar.
	 *
	 * @param array $item Item to verify.
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		$email = DataHelper::get_values_where_key_contains( [ 'email' ], $item );
		if ( empty( $email ) ) {
			return 0;
		}
		$email = array_shift( $email );

		$response = wp_safe_remote_get(
			sprintf(
				'https://www.gravatar.com/avatar/%s?d=404',
				md5( strtolower( trim( $email ) ) )
			)
		);

		if ( is_wp_error( $response ) ) {
			return 0;
		}

		if ( wp_remote_retrieve_response_code( $response ) === 200 ) {
			return - 1;
		}

		return 0;
	}

	/**
	 * Get rule name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Valid Gravatar', 'antispam-bee' );
	}

	/**
	 * Get rule label.
	 *
	 * @return string|null
	 */
	public static function get_label(): ?string {
		return __( 'Trust commenters with a Gravatar', 'antispam-bee' );
	}

	/**
	 * Get rule description.
	 *
	 * @return string|null
	 */
	public static function get_description(): ?string {
		$link1 = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">',
			esc_url(
				__( 'https://antispambee.pluginkollektiv.org/documentation/#trust-commenters-with-a-gravatar', 'antispam-bee' ),
				'https'
			)
		);

		return sprintf(
		/* translators: 1: opening <a> tag with link to documentation. 2: closing </a> tag */
			esc_html__( 'Check if commenter has a Gravatar image. Please note the %1$sprivacy notice%2$s for this option.', 'antispam-bee' ),
			wp_kses_post( $link1 ),
			'</a>'
		);
	}
}
