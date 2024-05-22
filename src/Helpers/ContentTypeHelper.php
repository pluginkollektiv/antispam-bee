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
	 * Get huam-readable item type name.
	 *
	 * @param string $item_type Type name.
	 * @return string Readable type name.
	 */
	public static function get_type_name( $item_type ) {
		$type_names = [
			self::GENERAL_TYPE  => __( 'General', 'antispam-bee' ),
			self::COMMENT_TYPE  => __( 'Comment', 'antispam-bee' ),
			self::LINKBACK_TYPE => __( 'Linkback', 'antispam-bee' ),
		];

		// Todo: Write a doc how to add custom types.
		$type_names = array_merge( apply_filters( 'antispam_bee_item_types', [] ), $type_names );

		return isset( $type_names[ $item_type ] ) ? $type_names[ $item_type ] : $item_type;
	}

	/**
	 * Checks if a given reaction type matches the types provided in the second parameter.
	 *
	 * @param array  $reaction       Reaction data array, reaction type needs to be provided as `reaction_type`.
	 * @param array  $reaction_types Array of reaction types to check for.
	 * @param string $context        Optional context.
	 *
	 * @return bool
	 */
	public static function reaction_is_one_of( $reaction, $reaction_types, $context = '' ) {
		// This `comment_type` is set from WordPress.
		$reaction_type = $reaction['comment_type'] ?? '';

		$is_one_of = in_array( $reaction_type, $reaction_types );

		/**
		 * Filters if a reaction is from a provided list of reaction types.
		 *
		 * @param array  $reaction       Reaction data array, reaction type needs to be provided as `comment_type`.
		 * @param array  $reaction_types Array of reaction types to check for.
		 * @param string $context        Optional context.
		 *
		 * @return bool
		 */
		$is_one_of = (bool) apply_filters( 'antispam_bee_reaction_is_one_of', $is_one_of, $reaction, $reaction_types, $context );

		return $is_one_of;
	}
}
