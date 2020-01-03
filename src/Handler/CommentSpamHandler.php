<?php
/**
 * The comment spam handler.
 *
 * Once spam was detected, this controller takes care of how to process the spam now.
 *
 * @package Antispam Bee Handler
 */

declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Handler;

use Pluginkollektiv\AntispamBee\Config\AntispamBeeConfig;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Repository\PostProcessorRepository;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

/**
 * Class CommentSpamHandler
 *
 * @package Pluginkollektiv\AntispamBee\Handler
 */
class CommentSpamHandler implements SpamHandlerInterface {

	/**
	 * The configuration.
	 *
	 * @var AntispamBeeConfig
	 */
	private $config;

	/**
	 * The post processor repository.
	 *
	 * @var PostProcessorRepository
	 */
	private $repository;

	/**
	 * CommentSpamHandler constructor.
	 *
	 * @param AntispamBeeConfig       $config The options.
	 * @param PostProcessorRepository $repository The post processor repository.
	 */
	public function __construct( AntispamBeeConfig $config, PostProcessorRepository $repository ) {
		$this->config     = $config;
		$this->repository = $repository;
	}

	/**
	 * Once a spam has been detected, this method will handle the rest.
	 *
	 * @param ReasonsRepository $reason The spam reason.
	 * @param DataInterface     $data   The spam data.
	 *
	 * @return bool
	 */
	public function execute( ReasonsRepository $reason, DataInterface $data ) : bool {

		$success = true;
		foreach ( $this->repository->active_processors() as $processor ) {
			if ( ! $processor->execute( $reason, $data ) ) {
				$success = false;
			};
		}
		return $success;
	}
}
