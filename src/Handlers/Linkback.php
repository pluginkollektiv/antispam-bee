<?php

namespace AntispamBee\Handlers;

use AntispamBee\GeneralOptions\IgnorePings;
use AntispamBee\Helpers\ContentTypeHelper;

class Linkback {
	public static function init() {
		add_filter(
			'preprocess_comment',
			[
				__CLASS__,
				'process',
			],
			1
		);
	}

	public static function process( $linkback ) {
		if ( ! ContentTypeHelper::reaction_is_one_of( $linkback, [ 'pingback', 'trackback', 'pings' ] ) ) {
			return $linkback;
		}

		if ( IgnorePings::is_active() ) {
			return $linkback;
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$rules   = new Rules( 'linkback' );
		$is_spam = $rules->apply( $linkback );

		if ( $is_spam ) {
			$item = PostProcessors::apply( 'linkback', $linkback, $rules->get_spam_reasons() );
			if ( ! isset( $item['asb_marked_as_delete'] ) ) {
				add_filter(
					'pre_comment_approved',
					function () {
						return 'spam';
					}
				);

				return $linkback;
			}

			status_header( 403 );
			die( 'Spam deleted.' );
		}

		return $linkback;
		// todo: Maybe store no-spam-reasons (to discuss)
	}
}
