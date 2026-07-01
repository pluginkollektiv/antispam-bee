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
	 * Reaction type.
	 *
	 * @var string
	 */
	protected $reaction_type;

	/**
	 * Constructor.
	 *
	 * @param string $reaction_type Reaction type.
	 */
	public function __construct( string $reaction_type ) {
		$this->reaction_type = $reaction_type;
	}

	/**
	 * Get controllable items for this option.
	 *
	 * @param string $reaction_type Reaction type.
	 * @return array List of controllable items.
	 */
	public static function get_controllables( string $reaction_type = 'general' ): array {
		if ( 'general' !== $reaction_type ) {
			return [];
		}

		return apply_filters( 'antispam_bee_general_options', [] );
	}
}
