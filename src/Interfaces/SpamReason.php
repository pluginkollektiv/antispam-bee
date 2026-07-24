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
	 * Get a human-readable spam reason.
	 *
	 * @return string A human-readable spam reason.
	 */
	public static function get_reason_text(): string;
}
