<?php
/**
 * UpdateSpamCount Post-Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

use AntispamBee\GeneralOptions\Statistics;
use AntispamBee\Helpers\Settings;

/**
 * Post-processor that is responsible for incrementing the spam count and updating the value.
 */
class UpdateSpamCount extends Base {

	/**
	 * Post-processor slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-update-spam-count';

	/**
	 * Option holding the number of spam reactions seen so far.
	 *
	 * The counter lives in its own option rather than in the settings array.
	 * Incrementing it happens on the unauthenticated comment path, and writing
	 * it through the settings array meant reading that whole array, merging, and
	 * writing it back — so an increment that overlapped an administrator saving
	 * the settings page wrote the pre-save array back over their save.
	 *
	 * @var string
	 */
	public const COUNT_OPTION = 'antispam_bee_spam_count';

	/**
	 * Process an item.
	 * Increment the spam counter by 1.
	 *
	 * @param array<string, mixed> $item Item to process.
	 *
	 * @return array<string, mixed> Processed item.
	 */
	public static function process( array $item ): array {
		if ( ! Statistics::is_active() ) {
			return $item;
		}

		update_option( self::COUNT_OPTION, self::get_count() + 1, true );

		return $item;
	}

	/**
	 * Get the number of spam reactions seen so far.
	 *
	 * Falls back to the value inside the settings array, which is where the
	 * counter used to live and where the v2 migration still puts it, so a site
	 * that has not recorded any spam since the move keeps its total.
	 *
	 * @return int The spam count.
	 */
	public static function get_count(): int {
		$count = get_option( self::COUNT_OPTION, null );

		if ( null === $count ) {
			return (int) Settings::get_option( 'spam_count', '' );
		}

		return (int) $count;
	}
}
