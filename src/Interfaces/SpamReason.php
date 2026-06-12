<?php
/**
 * SpamReason interface.
 *
 * @package AntispamBee\Interfaces
 */

namespace AntispamBee\Interfaces;

/**
 * Spam reason interface.
 */
interface SpamReason {

	/**
	 * Get human-readable spam reason.
	 *
	 * @return string
	 */
	public static function get_reason_text(): string;
}
