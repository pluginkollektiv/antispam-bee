<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

class RestInPeace implements PostProcessorInterface {

	private $options;
	private $option_factory;

	public function __construct( OptionFactory $option_factory ) {
		$this->option_factory = $option_factory;
	}

	public function execute( string $reason, DataInterface $data ) : bool {

		return false !== add_action(
			'pre_comment_approved',
			function() {
				status_header( 403 );
				die( 'Spam deleted.' );
			}
		);
	}

	public function id() : string {
		return 'rest_in_peace';
	}

	public function register() : bool {
		return true;
	}

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
