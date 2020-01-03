<?php
/**
 * The logger interface.
 *
 * @package Antispam Bee Logger
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Logger;

/**
 * Interface LoggerInterface
 *
 * @package Pluginkollektiv\AntispamBee\Logger
 */
interface LoggerInterface {

	/**
	 * Add a new entry to log.
	 *
	 * @param string $log The entry.
	 *
	 * @return bool
	 */
	public function log( string $log) : bool;

	/**
	 * Whether the logger is ready to log entries.
	 *
	 * @return bool
	 */
	public function is_ready() : bool;
}
