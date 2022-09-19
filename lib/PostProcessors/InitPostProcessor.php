<?php

namespace AntispamBee\PostProcessors;

/**
 * Contains init functions used by the post processors.
 */
trait InitPostProcessor {
	/**
	 * Add post processor to the list of post processors.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'asb_post_processors', [ __CLASS__, 'add_post_processor' ] );
	}

	/**
	 * Adds post processor class to array of post processors.
	 *
	 * @param array $post_processors
	 *
	 * @return mixed
	 */
	public static function add_post_processor( $post_processors ) {
		$post_processors[] = self::class;
		return $post_processors;
	}
}
