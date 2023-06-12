<?php

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\ComponentsHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\PostProcessor;

class PostProcessors {
	/**
	 * @param string $reaction_type one of the supported content types.
	 * @param $item
	 * @param $reasons
	 *
	 * @return mixed
	 */
	public static function apply( $reaction_type, $item, $reasons = [] ) {
		$post_processors = self::get( $reaction_type, true );

		$item['asb_reasons']  = $reasons;
		$item['reaction_type'] = $reaction_type;

		// Move the post processors that mark an item as to delete to front,
		// so that following processors know if they handle an item that will be deleted.
		for ( $i = 0; $i < count( $post_processors ); $i ++ ) {
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

	public static function get( $reaction_type = null, $only_active = false ) {
		return self::filter(
			[
				'reaction_type' => $reaction_type,
				'only_active'   => $only_active,
				'implements'    => PostProcessor::class,
			]
		);
	}

	public static function get_controllables( $reaction_type = null, $only_active = false ) {
		return self::filter(
			[
				'reaction_type' => $reaction_type,
				'only_active'   => $only_active,
				'implements'    => [ PostProcessor::class, Controllable::class ],
			]
		);
	}

	private static function filter( $options ) {
		return ComponentsHelper::filter( apply_filters( 'antispam_bee_post_processors', [] ), $options );
	}
}
