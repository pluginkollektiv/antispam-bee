<?php
/**
 * GeneralOptions handler.
 *
 * @package AntispamBee\Handlers
 */

namespace AntispamBee\Handlers;

/**
 * GeneralOptions handler.
 */
class GeneralOptions {

	/**
	 * Options type.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Constuctor.
	 *
	 * @param string $type Option type.
	 */
	public function __construct( $type ) {
		$this->type = $type;
	}

	/**
	 * Get controllable items for this option.
	 *
	 * @param string $type Option type.
	 * @return array List of controllable items.
	 */
	public static function get_controllables( $type = 'general' ) {
		if ( 'general' !== $type ) {
			return [];
		}

		return apply_filters( 'antispam_bee_general_options', [] );
	}
}
