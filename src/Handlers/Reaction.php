<?php

namespace AntispamBee\Handlers;

use AntispamBee\GeneralOptions\IgnoreLinkbacks;
use AntispamBee\Helpers\ContentTypeHelper;
use WP_Comment;

abstract class Reaction {
	protected static $type = 'comment';
	public static function init() {
		add_filter(
			'preprocess_comment',
			[
				static::class,
				'process',
			],
			1
		);

		add_action( 'transition_comment_status', [ __CLASS__, 'handle_comment_status_changes' ], 10, 3 );

		// Add our manual spam reason to the list of reasons.
		add_filter( 'antispam_bee_additional_spam_reasons', function ( $reasons ) {
			$reasons['asb-marked-manually'] = __( 'Manually', 'antispam-bee' );
			return $reasons;
		} );
	}

	public static function process( $reaction ) {
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$rules   = new Rules( static::$type );
		$is_spam = $rules->apply( $reaction );

		if ( $is_spam ) {
			return self::handle_spam( $reaction, $rules );
		}

		return $reaction;
	}

	protected static function handle_spam( $reaction, $rules ) {
		$item = PostProcessors::apply( static::$type, $reaction, $rules->get_spam_reasons() );
		if ( ! isset( $item['asb_marked_as_delete'] ) ) {
			add_filter(
				'pre_comment_approved',
				function () {
					return 'spam';
				}
			);

			return $reaction;
		}

		status_header( 403 );
		die( 'Spam deleted.' );
	}

	/**
	 * React to changes of comment status.
	 *
	 * @param int|string $new_status The new comment status.
	 * @param int|string $old_status The old comment status.
	 * @param WP_Comment $comment    Comment object.
	 */
	public static function handle_comment_status_changes( $new_status, $old_status, $comment ) {
		if ( 'spam' === $new_status && 'spam' !== $old_status ) {
			update_comment_meta( $comment->comment_ID, 'antispam_bee_reason', 'asb-marked-manually' );
			return;
		}

		if ( 'spam' === $old_status && 'spam' !== $new_status ) {
			delete_comment_meta(
				$comment->comment_ID,
				'antispam_bee_reason'
			);
		}
	}
}
