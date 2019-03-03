<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

interface PostProcessorInterface {

	public function execute( string $reason, DataInterface $data) : bool;

	public function id() : string;

	public function register() : bool;

	public function options() : OptionInterface;
}
