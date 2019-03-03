<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Option;

use Pluginkollektiv\AntispamBee\Exceptions\Runtime;

class NullOption implements OptionInterface {


	public function name() : string {
		return '';
	}

	public function description() : string {
		return '';
	}

	public function activateable() : bool {
		return false;
	}

	public function fields() : array {
		return [];
	}

	public function has( string $key ) : bool {
		return false;
	}

	public function get( string $key ) {
		throw new Runtime( "The field $key is not registered in the NullOption." );
	}

	public function sanitize( $raw_value, string $key ) {
		return null;
	}
}
