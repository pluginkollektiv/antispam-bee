<?php

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\Components;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\PostProcessor;

class PostProcessors {
	public static function apply( $type, $item, $reasons = [] ) {
		$post_processors = self::get( $type, true );

		$item['asb_reasons']   = $reasons;
		$item['asb_item_type'] = $type;

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

	public static function get( $type = null, $only_active = false ) {
		return self::filter( [
			'type' => $type,
			'only_active' => $only_active,
			'implements' => PostProcessor::class,
		] );
	}

	public static function get_controllables( $type = null, $only_active = false ) {
		return self::filter( [
			'type' => $type,
			'only_active' => $only_active,
			'implements' => [ PostProcessor::class, Controllable::class ],
		] );
	}

	private static function filter( $options ) {
		return Components::filter( apply_filters( 'asb_post_processors', [] ), $options );
	}
}
