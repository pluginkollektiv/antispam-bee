<?php

namespace AntispamBee\Handlers;

class GeneralOptions {
	protected $type;

	public function __construct( $type ) {
		$this->type = $type;
	}

	public static function get_controllables( $type = 'general' ) {
		if ( $type !== 'general' ) {
			return [];
		}

		return apply_filters( 'antispam_bee_general_options', [] );
	}
}
