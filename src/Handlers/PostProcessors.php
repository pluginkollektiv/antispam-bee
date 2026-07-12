<?php
/**
 * Post-processors.
 *
 * @package AntispamBee\Handlers
 */

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\ComponentsHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\PostProcessor;
use ReflectionException;

/**
 * Post-processors.
 */
class PostProcessors {
	/**
	 * Apply post-processors.
	 *
	 * @param string               $reaction_type One of the supported content types.
	 * @param array<string, mixed> $item          Item to process.
	 * @param string[]             $reasons       A list of reasons.
	 *
	 * @return array<string, mixed> The processed item.
	 */
	public static function apply( string $reaction_type, array $item, array $reasons = [] ): array {
		$post_processors = self::get( $reaction_type, true );

		$item['asb_reasons']   = $reasons;
		$item['reaction_type'] = $reaction_type;

		// Move the post-processors that mark an item as to delete to front,
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
	 * Get a post-processor.
	 *
	 * @param string|null $reaction_type Reaction type.
	 * @param bool        $only_active   Get only active post-processors.
	 *
	 * @return array<class-string> A list of suitable post-processors.
	 * @throws ReflectionException
	 */
	public static function get( ?string $reaction_type = null, bool $only_active = false ): array {
		return self::filter(
			[
				'reaction_type' => $reaction_type,
				'only_active'   => $only_active,
				'implements'    => PostProcessor::class,
			]
		);
	}

	/**
	 * Filter items.
	 *
	 * @param array<string, mixed> $options Filter options.
	 *
	 * @return array<class-string<Controllable>> A list of filtered elements.
	 * @throws ReflectionException
	 */
	private static function filter( array $options ): array {
		/**
		 * Filters the registered post-processors.
		 *
		 * Post-processors are the actions Antispam Bee runs on a reaction after the
		 * rules have classified it (for example deleting spam or sending a
		 * notification). Add or remove fully-qualified class names to change which
		 * post-processors are available.
		 *
		 * @since 3.0.0
		 *
		 * @param array $post_processors A list of post-processor class names.
		 */
		$post_processors = apply_filters( 'antispam_bee_post_processors', [] );

		return ComponentsHelper::filter( $post_processors, $options );
	}

	/**
	 * Get the controllable items.
	 *
	 * @param string|null $reaction_type Reaction type.
	 * @param bool        $only_active   Get only active items.
	 *
	 * @return array<class-string<Controllable>> A list of suitable controllables.
	 * @throws ReflectionException
	 */
	public static function get_controllables( ?string $reaction_type = null, bool $only_active = false ): array {
		return self::filter(
			[
				'reaction_type' => $reaction_type,
				'only_active'   => $only_active,
				'implements'    => [ PostProcessor::class, Controllable::class ],
			]
		);
	}
}
