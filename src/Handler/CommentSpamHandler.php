<?php
declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Handler;

use Pluginkollektiv\AntispamBee\Config\AntispamBeeConfig;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Repository\PostProcessorRepository;

/**
 * Class CommentSpamHandler
 *
 * @package Pluginkollektiv\AntispamBee\Handler
 */
class CommentSpamHandler implements SpamHandlerInterface {

	private $config;
	private $repository;

	/**
	 * CommentSpamHandler constructor.
	 *
	 * @param AntispamBeeConfig $config The options.
	 */
	public function __construct( AntispamBeeConfig $config, PostProcessorRepository $repository ) {
		$this->config     = $config;
		$this->repository = $repository;
	}

	/**
	 * Once a spam has been detected, this method will handle the rest.
	 *
	 * @param string        $reason The spam reason.
	 * @param DataInterface $data The spam data.
	 *
	 * @return bool
	 */
	public function execute( string $reason, DataInterface $data ) : bool {

		$success = true;
		foreach ( $this->repository->active_processors() as $processor ) {
			if ( ! $processor->execute( $reason, $data ) ) {
				$success = false;
			};
		}
		return $success;
	}
}
