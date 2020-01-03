<?php
/**
 * Creates Post Processors.
 *
 * @package Antispam Bee PostProcessor.
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Logger\LoggerInterface;

/**
 * Class PostProcessorFactory
 *
 * @package Pluginkollektiv\AntispamBee\PostProcessor
 */
class PostProcessorFactory {

	/**
	 * The option factory.
	 *
	 * @var OptionFactory
	 */
	private $option_factory;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * PostProcessorFactory constructor.
	 *
	 * @param OptionFactory   $option_factory The option factory.
	 * @param LoggerInterface $logger The logger.
	 */
	public function __construct(
		OptionFactory $option_factory,
		LoggerInterface $logger
	) {
		$this->option_factory = $option_factory;
		$this->logger         = $logger;
	}

	/**
	 * Returns post processors by a given type.
	 *
	 * @param string $type The type.
	 *
	 * @return PostProcessorInterface
	 */
	public function from_id( string $type ) {

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
