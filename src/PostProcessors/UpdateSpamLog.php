<?php
/**
 * Update Spam Log Post Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

/**
 * Post Processor that is responsible for updating the spam log file.
 */
class UpdateSpamLog extends Base {

	/**
	 * Post processor slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-update-spam-log';

	/**
	 * Process an item.
	 * Append a line to the spam log file.
	 *
	 * @param array $item Item to process.
	 * @return array Processed item.
	 */
	public static function process( $item ) {
		if ( ! isset( $item['comment_post_ID'] ) || ! isset( $item['comment_author_IP'] ) ) {
			$item['asb_post_processors_failed'][] = self::get_slug();
			return $item;
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
