<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;

class NullPostProcessor implements PostProcessorInterface {

	private $option_factory;


	public function __construct( OptionFactory $option_factory ) {
		$this->option_factory = $option_factory;
	}
	public function execute( string $reason, DataInterface $data ) : bool {
		return false;
	}

	public function id() : string {
		return '';
	}

	public function register() : bool {
		return true;
	}

	public function options() : OptionInterface {
		return $this->option_factory->null();
	}
}
