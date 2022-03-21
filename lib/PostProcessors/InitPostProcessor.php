<?php

namespace AntispamBee\PostProcessors;

trait InitPostProcessor {
	public static function init() {
		add_filter(
			'asb_post_processors',
			function ( $post_processors ) {
				$post_processors[] = [
					'post_processor' => self::class,
				];

				return $post_processors;
			}
		);
	}
}
