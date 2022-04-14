<?php

namespace AntispamBee\PostProcessors;

trait InitPostProcessor {
	public static function init() {
		add_filter( 'asb_post_processors', [ __CLASS__, 'add_post_processor' ] );
	}

	public static function add_post_processor( $post_processors ) {
		$post_processors[] = self::class;
		return $post_processors;
	}
}
