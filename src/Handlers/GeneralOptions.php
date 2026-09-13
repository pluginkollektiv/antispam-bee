<?php
/**
 * GeneralOptions handler.
 *
 * @package AntispamBee\Handlers
 */

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\ComponentsHelper;
use AntispamBee\Interfaces\Controllable;

/**
 * GeneralOptions handler.
 */
class GeneralOptions {

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
		$options = apply_filters( 'antispam_bee_general_options', [] );

		/*
		 * Validated like the Rules and PostProcessors handlers validate theirs. This is
		 * a public extension point, so the entries have to be checked before they reach
		 * Sanitize::sanitize_controllables(), which calls static methods on them: an
		 * entry that is not a Controllable would raise an uncaught Error inside the
		 * `register_setting()` sanitize callback, breaking both the saving and the
		 * rendering of the settings screen.
		 */
		return ComponentsHelper::filter(
			$options,
			[
				'reaction_type' => $reaction_type,
				'implements'    => Controllable::class,
			]
		);
	}
}
