<?php
/**
 * Log into a file.
 *
 * @package Antispam Bee Logger
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Logger;

// phpcs:disable WordPress.VIP.FileSystemWritesDisallow.file_ops_is_writeable

/**
 * Class FileLogger
 *
 * @package Pluginkollektiv\AntispamBee\Logger
 */
class FileLogger implements LoggerInterface {

	/**
	 * The path to the log file.
	 *
	 * @var string
	 */
	private $log_file;

	/**
	 * FileLogger constructor.
	 *
	 * @param string $log_file The path to the log file.
	 */
	public function __construct( string $log_file ) {
		$this->log_file = $log_file;
	}


	/**
	 * Add a new entry to log.
	 *
	 * @param string $log The entry.
	 *
	 * @return bool
	 */
	public function log( string $log ) : bool {
		if ( ! $this->is_ready() ) {
			return false;
		}
		$log = rtrim( $log, PHP_EOL ) . PHP_EOL;
		return false !== file_put_contents( $this->log_file, $log );
	}

	/**
	 * Whether the logger is ready to log entries.
	 *
	 * @return bool
	 */
	public function is_ready() : bool {
		return is_writeable( $this->log_file );
	}
}
