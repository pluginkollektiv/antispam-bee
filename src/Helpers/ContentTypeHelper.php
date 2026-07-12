<?php
/**
 * Content type helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Content Type Helper.
 */
class ContentTypeHelper {

	const GENERAL_TYPE  = 'general';
	const COMMENT_TYPE  = 'comment';
	const LINKBACK_TYPE = 'linkback';

	/**
	 * Get a human-readable reaction type name.
	 *
	 * @param string $reaction_type Reaction type.
	 *
	 * @return string Readable reaction type name.
	 */
	public static function get_reaction_type_name( string $reaction_type ): string {
		$type_names = [
			self::GENERAL_TYPE  => __( 'General', 'antispam-bee' ),
			self::COMMENT_TYPE  => __( 'Comment', 'antispam-bee' ),
			self::LINKBACK_TYPE => __( 'Linkback', 'antispam-bee' ),
		];

		/**
		 * Filters the additional reaction types and their human-readable names.
		 *
		 * Use this filter to register custom reaction types (beyond the built-in
		 * general, comment and linkback types) together with the label shown in the
		 * backend. The array key is the reaction type slug, the value its label.
		 *
		 * @param array $type_names A map of reaction type slugs to readable names.
		 *
		 * @return array The map of reaction type slugs to readable names.
		 * @since 3.0.0
		 */
		$type_names = array_merge( apply_filters( 'antispam_bee_reaction_types', [] ), $type_names );

		return $type_names[ $reaction_type ] ?? $reaction_type;
	}

	/**
	 * Check if a given reaction type matches the types provided in the second parameter.
	 *
	 * @param array<string, mixed> $reaction       Reaction data array, reaction type needs to be provided as `reaction_type`.
	 * @param string[]             $reaction_types An array of reaction types to check for.
	 * @param string               $context        Optional context.
	 *
	 * @return bool Whether the reaction is one of the given types.
	 */
	public static function reaction_is_one_of( array $reaction, array $reaction_types, string $context = '' ): bool {
		// This `comment_type` is set from WordPress.
		$reaction_type = $reaction['comment_type'] ?? '';

		$is_one_of = in_array( $reaction_type, $reaction_types, true );

		/**
		 * Filters if a reaction is from a provided list of reaction types.
		 *
		 * @param bool   $is_one_of      Whether the reaction is one of the provided types.
		 * @param array  $reaction       Reaction data array, reaction type needs to be provided as `comment_type`.
		 * @param array  $reaction_types An array of reaction types to check for.
		 * @param string $context        Optional context.
		 *
		 * @return bool Whether the reaction is one of the provided types.
		 * @since 3.0.0
		 */
		return (bool) apply_filters( 'antispam_bee_reaction_is_one_of', $is_one_of, $reaction, $reaction_types, $context );
	}
}
