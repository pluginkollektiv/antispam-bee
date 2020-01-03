<?php
/**
 * The Post Processor repository.
 *
 * @package Antispam Bee Repository
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Repository;

use Pluginkollektiv\AntispamBee\Config\AntispamBeeConfig;
use Pluginkollektiv\AntispamBee\Exceptions\Runtime;
use Pluginkollektiv\AntispamBee\PostProcessor\PostProcessorInterface;

/**
 * Class PostProcessorRepository
 *
 * @package Pluginkollektiv\AntispamBee\Repository
 */
class PostProcessorRepository {

	/**
	 * The Antispam Bee configuration.
	 *
	 * @var AntispamBeeConfig
	 */
	private $config;

	/**
	 * All Post Processors.
	 *
	 * @var PostProcessorInterface[]
	 */
	private $post_processors;

	/**
	 * PostProcessorRepository constructor.
	 *
	 * @param AntispamBeeConfig      $config The configuration.
	 * @param PostProcessorInterface ...$post_processors All processors.
	 */
	public function __construct( AntispamBeeConfig $config, PostProcessorInterface ...$post_processors ) {

		$this->config          = $config;
		$this->post_processors = $post_processors;
	}

	/**
	 * Returns the Post Processor for a given ID.
	 *
	 * @param string $id The ID.
	 * @throws Runtime When processor was not found.
	 *
	 * @return PostProcessorInterface
	 */
	public function from_id( string $id ) : PostProcessorInterface {
		foreach ( $this->registered_processors() as $processor ) {
			if ( $processor->id() === $id ) {
				return $processor;
			}
		}
		throw new Runtime( 'Post Processor not found.' );
	}

	/**
	 * Returns all active Post Processors.
	 *
	 * @return PostProcessorInterface[]
	 */
	public function active_processors() : array {

		$config = $this->config;
		return array_filter(
			$this->registered_processors(),
			function ( PostProcessorInterface $processor ) use ( $config ) : bool {
				return $config->has( 'active_processors' ) && isset( $config->get( 'active_processors' )[ $processor->id() ] );
			}
		);
	}
	/**
	 * Returns all registered Post Processors.
	 *
	 * @return PostProcessorInterface[]
	 */
	public function registered_processors() : array {
		return $this->post_processors;
	}
}
