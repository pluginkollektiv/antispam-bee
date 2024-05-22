<?php
/**
 * Post Processor Base.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\PostProcessor;

/**
 * Abstract base class for post processors.
 */
abstract class Base implements PostProcessor {

	/**
	 * Post processor slug.
	 *
	 * @var string
	 */
	protected static $slug;

	/**
	 * List of supported reaction types.
	 *
	 * @var string[]
	 */
	protected static $supported_types = [ ContentTypeHelper::COMMENT_TYPE, ContentTypeHelper::LINKBACK_TYPE ];

	/**
	 * Does this post processor mark an item as deleted?
	 *
	 * @var bool
	 */
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
	 * @param PostProcessor[] $post_processors Currently registered post processors.
	 *
	 * @return PostProcessor[] Updated list of post processors.
	 */
	public static function add_post_processor( $post_processors ) {
		$post_processors[] = static::class;

		return $post_processors;
	}

	/**
	 * Get post processor slug.
	 *
	 * @return string The slug.
	 */
	public static function get_slug() {
		return static::$slug;
	}

	/**
	 * Get a list of supported types.
	 *
	 * @return string[]
	 */
	public static function get_supported_types() {
		// @todo: add filter
		return static::$supported_types;
	}

	/**
	 * Does this processor mark am element as deleted?
	 *
	 * @return bool
	 */
	public static function marks_as_delete() {
		return static::$marks_as_delete;
	}
}
