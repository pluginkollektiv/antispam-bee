<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\PostProcessor;

abstract class Base implements PostProcessor {

	protected static $slug;
	protected static $supported_types = [ ContentTypeHelper::COMMENT_TYPE, ContentTypeHelper::LINKBACK_TYPE ];
	protected static $marks_as_delete = false;

	/**
	 * Add post processor to the list of post processors.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'antispam_bee_post_processors', [ static::class, 'add_post_processor' ] );
	}

	/**
	 * Adds post processor class to array of post processors.
	 *
	 * @param array $post_processors
	 *
	 * @return mixed
	 */
	public static function add_post_processor( $post_processors ) {
		$post_processors[] = static::class;

		return $post_processors;
	}

	public static function get_slug() {
		return static::$slug;
	}

	public static function get_supported_types() {
		// @todo: add filter
		return static::$supported_types;
	}

	public static function marks_as_delete() {
		return static::$marks_as_delete;
	}
}
