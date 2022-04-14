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
		for ( $i = 0; $i < count( $post_processors ); $i ++ ) {
			$post_processor           = $post_processors[ $i ];
			$marks_as_delete_function = isset( $post_processor['post_processor'] ) ? [ $post_processor['post_processor'], 'marks_as_delete' ] : $post_processor['marks_as_delete'];
			if ( call_user_func( $marks_as_delete_function ) ) {
				unset( $post_processors[ $i ] );
				array_unshift( $post_processors, $post_processor );
			}
		}

		foreach ( $post_processors as $post_processor ) {
			$process_function = isset( $post_processor['post_processor'] ) ? [ $post_processor['post_processor'], 'process' ] : $post_processor['process'];
			$item             = call_user_func( $process_function, $item );
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
