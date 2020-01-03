<?php
/**
 * The Spam Logger Post processor logs spam using a LoggerInterface implementation.
 *
 * @package Antispam Bee PostProcessor
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;
use Pluginkollektiv\AntispamBee\Logger\LoggerInterface;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

/**
 * Class SpamLogger
 *
 * @package Pluginkollektiv\AntispamBee\PostProcessor
 */
class SpamLogger implements PostProcessorInterface {

	/**
	 * The logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * The option factory.
	 *
	 * @var OptionFactory
	 */
	private $option_factory;

	/**
	 * SpamLogger constructor.
	 *
	 * @param LoggerInterface $logger The logger.
	 * @param OptionFactory   $option_factory The option factory.
	 */
	public function __construct( LoggerInterface $logger, OptionFactory $option_factory ) {
		$this->logger         = $logger;
		$this->option_factory = $option_factory;
	}

	/**
	 * Executes the processor.
	 *
	 * @param ReasonsRepository $reason The reason repository.
	 * @param DataInterface     $data The current data.
	 *
	 * @return bool
	 */
	public function execute( ReasonsRepository $reason, DataInterface $data ) : bool {

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

	/**
	 * The ID of the spam logger.
	 *
	 * @return string
	 */
	public function id() : string {
		return 'spamlog';
	}

	/**
	 * Registers the logger.
	 *
	 * @return bool
	 */
	public function register() : bool {
		return true;
	}

	/**
	 * Returns the options for the spam logger.
	 *
	 * @return OptionInterface
	 */
	public function options() : OptionInterface {
		return $this->option_factory->null();
	}
}
