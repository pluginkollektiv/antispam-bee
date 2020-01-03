<?php
/**
 * If this post processor is active the PHP process will die, once a spam comment has been detected.
 *
 * @package Antispam Bee PostProcessor
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

/**
 * Class RestInPeace
 *
 * @package Pluginkollektiv\AntispamBee\PostProcessor
 */
class RestInPeace implements PostProcessorInterface {

	/**
	 * The options for the rest in peace processor.
	 *
	 * @var OptionInterface
	 */
	private $options;

	/**
	 * The option factory.
	 *
	 * @var OptionFactory
	 */
	private $option_factory;

	/**
	 * RestInPeace constructor.
	 *
	 * @param OptionFactory $option_factory The option factory.
	 */
	public function __construct( OptionFactory $option_factory ) {
		$this->option_factory = $option_factory;
	}

	/**
	 * Executes the rest in peace post processor.
	 *
	 * @param ReasonsRepository $reason The reason why the current data is spam.
	 * @param DataInterface     $data The current data.
	 *
	 * @return bool
	 */
	public function execute( ReasonsRepository $reason, DataInterface $data ) : bool {

		return false !== add_action(
			'pre_comment_approved',
			function () {
				status_header( 403 );
				die( 'Spam deleted.' );
			}
		);
	}

	/**
	 * The ID of the rest in peace post processor.
	 *
	 * @return string
	 */
	public function id() : string {
		return 'rest_in_peace';
	}

	/**
	 * Registers the rest in peace post processor.
	 *
	 * @return bool
	 */
	public function register() : bool {
		return true;
	}

	/**
	 * Returns the options for the rest in peace post processor.
	 *
	 * @return OptionInterface
	 */
	public function options() : OptionInterface {

		if ( $this->options ) {
			return $this->options;
		}
		$args          = [
			'name'        => __( 'Do not save spam', 'antispam-bee' ),
			'description' => __( 'Do not keep spam in the blog.', 'antispam-bee' ),
		];
		$this->options = $this->option_factory->from_args( $args );
		return $this->options;
	}
}
