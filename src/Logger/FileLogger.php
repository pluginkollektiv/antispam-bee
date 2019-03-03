<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Logger;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;

class FileLogger implements LoggerInterface {

	private $log_file;
	public function __construct( string $log_file ) {
		$this->log_file = $log_file;
	}

	public function log( string $log ) : bool {
		if ( ! $this->is_ready() ) {
			return false;
		}
		$log = rtrim( $log, PHP_EOL ) . PHP_EOL;
		return false !== file_put_contents( $this->log_file, $log );
	}

	public function is_ready() : bool {
		return is_writeable( $this->log_file );
	}
}
