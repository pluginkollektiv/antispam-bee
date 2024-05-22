<?php
/**
 * Post processors.
 *
 * @package AntispamBee\Handlers
 */

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\ComponentsHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\PostProcessor;

/**
 * Post processors.
 */
class PostProcessors {
	/**
	 * Apply post processors.
	 *
	 * @param string $reaction_type One of the supported content types.
	 * @param array  $item          Item to process.
	 * @param array  $reasons       List of reasons.
	 *
	 * @return mixed
	 */
	public static function apply( $reaction_type, $item, $reasons = [] ) {
		$post_processors = self::get( $reaction_type, true );

		$item['asb_reasons']   = $reasons;
		$item['reaction_type'] = $reaction_type;

		// Move the post processors that mark an item as to delete to front,
		// so that following processors know if they handle an item that will be deleted.
		$pp_count = count( $post_processors );
		for ( $i = 0; $i < $pp_count; $i++ ) {
			if ( $post_processors[ $i ]::marks_as_delete() ) {
				$post_processor = $post_processors[ $i ];
				unset( $post_processors[ $i ] );
				array_unshift( $post_processors, $post_processor );
			}
		}

		foreach ( $post_processors as $post_processor ) {
			$item = $post_processor::process( $item );
		}

		return $item;
	}

	/**
	 * Get a post processor.
	 *
	 * @param string|null $reaction_type Reaction type.
	 * @param bool        $only_active   Get only active post processors.
	 * @return array List of suitable post processors.
	 */
	public static function get( $reaction_type = null, $only_active = false ) {
		return self::filter(
			[
				'reaction_type' => $reaction_type,
				'only_active'   => $only_active,
				'implements'    => PostProcessor::class,
			]
		);
	}

	/**
	 * Get controllable items.
	 *
	 * @param string $reaction_type Reaction type.
	 * @param bool   $only_active   Get only active items.
	 * @return array List of suitable controllables.
	 */
	public static function get_controllables( $reaction_type = null, $only_active = false ) {
		return self::filter(
			[
				'reaction_type' => $reaction_type,
				'only_active'   => $only_active,
				'implements'    => [ PostProcessor::class, Controllable::class ],
			]
		);
	}

	/**
	 * Filter items.
	 *
	 * @param array $options Filter options.
	 * @return array List of filtered elements.
	 */
	private static function filter( $options ) {
		return ComponentsHelper::filter( apply_filters( 'antispam_bee_post_processors', [] ), $options );
	}
}
