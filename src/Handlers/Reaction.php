<?php

namespace AntispamBee\Handlers;

use AntispamBee\GeneralOptions\IgnoreLinkbacks;
use AntispamBee\Helpers\ContentTypeHelper;

abstract class Reaction {
	protected static $content_type = 'comment';
	public static function init() {
		add_filter(
			'preprocess_comment',
			[
				static::class,
				'process',
			],
			1
		);
	}

	public static function process( $reaction ) {
		$tmp = static::$content_type;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$rules   = new Rules( static::$content_type );
		$is_spam = $rules->apply( $reaction );

		if ( $is_spam ) {
			$item = PostProcessors::apply( static::$content_type, $reaction, $rules->get_spam_reasons() );
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

		return $reaction;
		// todo: Maybe store no-spam-reasons (to discuss)
	}
}
