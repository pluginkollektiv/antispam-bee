<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;
use Pluginkollektiv\AntispamBee\Logger\LoggerInterface;

class SpamLogger implements PostProcessorInterface {

	private $logger;
	private $option_factory;


	public function __construct( LoggerInterface $logger, OptionFactory $option_factory ) {
		$this->logger         = $logger;
		$this->option_factory = $option_factory;
	}

	public function execute( string $reason, DataInterface $data ) : bool {

		if ( ! $this->logger->is_ready() ) {
			return false;
		}
		$log = sprintf(
			'%s comment for post=%d from host=%s marked as spam',
			current_time( 'mysql' ),
			$data->post(),
			$data->ip()
		);
		return $this->logger( $log );
	}

	public function id() : string {
		return 'spamlog';
	}

	public function register() {
		return true;
	}

	public function options() : OptionInterface {
		return $this->option_factory->null();
	}
}
