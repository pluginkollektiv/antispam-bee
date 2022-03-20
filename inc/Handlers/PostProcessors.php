<?php

namespace AntispamBee\Handlers;

use AntispamBee\Interfaces\PostProcessor;

class PostProcessors {
	public static function apply( $type, $item, $reasons = [] ) {
		$post_processors = self::get( $type, true );
		$item['asb_reasons'] = $reasons;
		$item['asb_item_type'] = $type;
		for ( $i = 0; $i < count( $post_processors ); $i++ ) {
			$post_processor = $post_processors[$i];
			$marks_as_delete_function = isset( $post_processor['post_processor'] ) ? [ $post_processor['post_processor'], 'marks_as_delete' ] : $post_processor['marks_as_delete'];
			if ( call_user_func( $marks_as_delete_function ) ) {
				unset( $post_processors[$i] );
				array_unshift( $post_processor );
			}
		}

		foreach ( $post_processors as $post_processor ) {
			$process_function = isset( $post_processor['post_processor'] ) ? [ $post_processor['post_processor'], 'process' ] : $post_processor['process'];
			$item = call_user_func( $process_function, $item );
		}
	}

	public static function get( $type = null, $only_active = false ) {
		$all_post_processors = apply_filters( 'asb_post_processors', [] );
		$post_processors = [];
		foreach ( $all_post_processors as $key => $post_processor ) {
			if ( ! self::is_valid_post_processor( $post_processor ) ) {
				continue;
			}

			$supported_types_function = isset( $post_processor['post_processor'] ) ? [ $post_processor['post_processor'], 'get_supported_types' ] : $post_processor['get_supported_types'];
			$supported_types = (array) call_user_func( $supported_types_function, $type );
			if ( ! in_array( $type, $supported_types ) ) {
				continue;
			}

			if ( ! $only_active ) {
				$post_processors[] = $post_processor;
				continue;
			}

			$is_active_function = isset( $post_processor['post_processor'] ) ? [ $post_processor['post_processor'], 'is_active' ] : $post_processor['is_active'];
			$is_active = call_user_func( $is_active_function, $type );

			if ( ! $is_active ) {
				continue;
			}

			$post_processors[] = $post_processor;
		}

		return $post_processors;
	}

	private static function is_valid_post_processor( $post_processor ) {
		if ( isset( $post_processor['post_processor'] ) ) {
			$interfaces = class_implements( $post_processor['post_processor'] );
			if ( false === $interfaces || empty( $interfaces ) ) {
				return false;
			}

			if ( ! in_array( PostProcessor::class, $interfaces, true ) ) {
				return false;
			}

			return true;
		}

		$post_processor_callables = [
			'is_active',
			'process',
			'get_slug',
			'get_supported_types',
		];

		foreach ( $post_processor_callables as $key ) {
			if ( ! isset( $post_processor[ $key ] ) || ! is_callable( $post_processor[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}
}
