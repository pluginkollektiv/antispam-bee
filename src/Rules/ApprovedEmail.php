<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\DataHelper;

/**
 * Checks if the email is from an already approved commenter.
 */
class ApprovedEmail extends ControllableBase {

	protected static $supported_types = [ ContentTypeHelper::COMMENT_TYPE ];
	protected static $slug            = 'asb-approved-email';

	// Todo: Discuss if this (and gravatar) should be final rules, and also surpass the Honeypot
	public static function verify( $item ) {
		$email = DataHelper::get_values_where_key_contains( [ 'email' ], $item );
		if ( empty( $email ) ) {
			return 0;
		}

		$email = array_shift( $email );

		$approved_comments_count = get_comments(
			[
				'status'       => 'approve',
				'count'        => true,
				'author_email' => $email,
			]
		);

		if ( 0 === $approved_comments_count ) {
			return 0;
		}

		return -100;
	}

	public static function get_name() {
		return __( 'Approved Email', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Trust approved commenters', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'No review of already commented users', 'antispam-bee' );
	}
}
