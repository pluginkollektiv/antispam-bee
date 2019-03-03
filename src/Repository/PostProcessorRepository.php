<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Repository;

use Pluginkollektiv\AntispamBee\Config\AntispamBeeConfig;
use Pluginkollektiv\AntispamBee\Exceptions\Runtime;
use Pluginkollektiv\AntispamBee\PostProcessor\NullPostProcessor;
use Pluginkollektiv\AntispamBee\PostProcessor\PostProcessorInterface;

class PostProcessorRepository {

	private $config;
	private $post_processors;
	public function __construct( AntispamBeeConfig $config, PostProcessorInterface ... $post_processors ) {

		$this->config          = $config;
		$this->post_processors = $post_processors;
	}

	public function from_id( string $id ) : PostProcessorInterface {
		foreach ( $this->registered_processors() as $processor ) {
			if ( $processor->id() === $id ) {
				return $processor;
			}
		}
		throw new Runtime( 'Post Processor not found.' );
	}

	/**
	 * @return PostProcessorInterface[]
	 */
	public function active_processors() : array {

		$config = $this->config;
		return array_filter(
			$this->registered_processors(),
			function( PostProcessorInterface $processor ) use ( $config ) : bool {
				return $config->has( 'active_processors' ) && isset( $config->get( 'active_processors' )[ $processor->id() ] );
			}
		);
	}
	/**
	 * @return PostProcessorInterface[]
	 */
	public function registered_processors() : array {
		return $this->post_processors;
	}
}
