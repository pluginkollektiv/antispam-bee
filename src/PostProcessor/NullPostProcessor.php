<?php
/**
 * The Null Post Processor.
 *
 * @package Antispam Bee PostProcessor
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

/**
 * Class NullPostProcessor
 *
 * @package Pluginkollektiv\AntispamBee\PostProcessor
 */
class NullPostProcessor implements PostProcessorInterface {

	/**
	 * The Option factory.
	 *
	 * @var OptionFactory
	 */
	private $option_factory;

	/**
	 * NullPostProcessor constructor.
	 *
	 * @param OptionFactory $option_factory The option factory.
	 */
	public function __construct( OptionFactory $option_factory ) {
		$this->option_factory = $option_factory;
	}

	/**
	 * Executes the post procession.
	 *
	 * @param ReasonsRepository $reason The reason repository.
	 * @param DataInterface     $data The current data.
	 *
	 * @return bool
	 */
	public function execute( ReasonsRepository $reason, DataInterface $data ) : bool {
		return false;
	}

	/**
	 * The ID of the processor.
	 *
	 * @return string
	 */
	public function id() : string {
		return '';
	}

	/**
	 * Registers the processor.
	 *
	 * @return bool
	 */
	public function register() : bool {
		return true;
	}

	/**
	 * Returns the NullOption.
	 *
	 * @return OptionInterface
	 */
	public function options() : OptionInterface {
		return $this->option_factory->null();
	}
}
