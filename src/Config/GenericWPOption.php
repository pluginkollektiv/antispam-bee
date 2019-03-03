<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Config;

use Pluginkollektiv\AntispamBee\Exceptions\Runtime;

class GenericWPOption implements ConfigInterface {

	private $option_key;
	private $options;
	public function __construct( string $option_key ) {
		$this->option_key = $option_key;
		$this->options    = get_option( $option_key, [] );
	}

	public function has( string $key ) : bool {
		return isset( $this->options[ $key ] );
	}

	public function get( string $key ) {
		return $this->options[ $key ];
	}

	public function has_config( string $key ) : bool {
		return false;
	}

	public function get_config( string $key ) : ConfigInterface {
		throw new Runtime( 'Config not found.' );
	}

	public function set( string $key, $value ) : bool {
		$this->options[ $key ] = $value;
		return $this->has( $key );
	}

	public function persist() : bool {
		return update_option( $this->option_key, $this->options );
	}
}
