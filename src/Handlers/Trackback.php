<?php

namespace AntispamBee\Handlers;

use AntispamBee\GeneralOptions\IgnorePings;
use AntispamBee\Helpers\ContentTypeHelper;

class Trackback {
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

	public static function process( $trackback ) {
		if ( ! ContentTypeHelper::reaction_is_one_of( $trackback, [ 'pingback', 'trackback', 'pings' ] ) ) {
			return $trackback;
		}

		if ( IgnorePings::is_active() ) {
			return $trackback;
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$rules   = new Rules( 'trackback' );
		$is_spam = $rules->apply( $trackback );

		if ( $is_spam ) {
			$item = PostProcessors::apply( 'trackback', $trackback, $rules->get_spam_reasons() );
			if ( ! isset( $item['asb_marked_as_delete'] ) ) {
				add_filter(
					'pre_comment_approved',
					function () {
						return 'spam';
					}
				);

				return $trackback;
			}

			status_header( 403 );
			die( 'Spam deleted.' );
		}

		return $trackback;
		// todo: Maybe store no-spam-reasons (to discuss)
	}
}
