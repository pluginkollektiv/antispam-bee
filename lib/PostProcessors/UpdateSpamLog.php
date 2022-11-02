<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\ContentTypeHelper;

/**
 * Post Processor that is responsible for updating the spam log file.
 */
class UpdateSpamLog extends Base {

	protected static $slug = 'asb-update-spam-log';

	public static function process( $item ) {
		if ( ! isset( $item['comment_post_ID'] ) || ! isset( $item['comment_author_IP'] ) ) {
			return $item['asb_post_processors_failed'][] = self::get_slug();
		}

		if (
			! defined( 'ANTISPAM_BEE_LOG_FILE' )
			|| ! ANTISPAM_BEE_LOG_FILE
			|| ! is_writable( ANTISPAM_BEE_LOG_FILE )
			|| validate_file( ANTISPAM_BEE_LOG_FILE ) === 1
		) {
			return $item;
		}

		$entry = sprintf(
			'%s comment for post=%d from host=%s marked as spam%s',
			current_time( 'mysql' ),
			$item['comment_post_ID'],
			$item['comment_author_IP'],
			PHP_EOL
		);

		file_put_contents(
			ANTISPAM_BEE_LOG_FILE,
			$entry,
			FILE_APPEND | LOCK_EX
		);

		return $item;
	}
}

