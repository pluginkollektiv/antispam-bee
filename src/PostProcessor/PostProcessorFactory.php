<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Logger\LoggerInterface;

class PostProcessorFactory {

	private $option_factory;
	private $logger;

	public function __construct(
		OptionFactory $option_factory,
		LoggerInterface $logger
	) {
		$this->option_factory = $option_factory;
		$this->logger         = $logger;
	}

	public function from_id( string $type ) : PostProcessorInterface {

		switch ( $type ) {
			case 'spamlog':
				$post_processor = new SpamLogger( $this->logger, $this->option_factory );
				break;
			case 'savereason':
				$post_processor = new SaveReason( $this->option_factory );
				break;
			case 'rest_in_peace':
				$post_processor = new RestInPeace( $this->option_factory );
				break;
			default:
				$post_processor = new NullPostProcessor( $this->option_factory );
		}

		return $post_processor;
	}
}
