<?php
/**
 * GeneralOptions handler.
 *
 * @package AntispamBee\Handlers
 */

namespace AntispamBee\Handlers;

use AntispamBee\Interfaces\Controllable;

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
	 * Get the controllable items for this option.
	 *
	 * @param string $reaction_type Reaction type.
	 *
	 * @return array<class-string<Controllable>> A list of controllable items.
	 */
	public static function get_controllables( string $reaction_type = 'general' ): array {
		if ( 'general' !== $reaction_type ) {
			return [];
		}

		/**
		 * Filters the controllable general options.
		 *
		 * Use this filter to register additional option controls that are rendered
		 * on the plugin’s general settings tab.
		 *
		 * @since 3.0.0
		 *
		 * @param array $options A list of controllable general options.
		 */
		return apply_filters( 'antispam_bee_general_options', [] );
	}
}
