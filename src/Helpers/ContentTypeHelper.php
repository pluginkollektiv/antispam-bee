<?php

namespace AntispamBee\Helpers;

class ContentTypeHelper {

	const GENERAL_TYPE = 'general';
	const COMMENT_TYPE = 'comment';
	const TRACKBACK_TYPE = 'trackback';
	const PINGBACK_TYPE = 'pingback';

	public static function get_type_name( $item_type ) {
		$type_names = [
			self::GENERAL_TYPE   => __( 'General', 'antispam-bee' ),
			self::COMMENT_TYPE   => __( 'Comment', 'antispam-bee' ),
			self::TRACKBACK_TYPE => __( 'Trackback', 'antispam-bee' ),
			self::PINGBACK_TYPE  => __( 'Pingback', 'antispam-bee' ),
		];

		// Todo: Write a doc how to add custom types.
		$type_names = array_merge( apply_filters( 'antispam_bee_item_types', [] ), $type_names );

		return isset( $type_names[ $item_type ] ) ? $type_names[ $item_type ] : $item_type;
	}

	/**
	 * Checks if a given comment is a ping.
	 *
	 * @param array $comment Comment data
	 *
	 * @return mixed|void
	 */
	public static function is_ping( $comment ) {
		$ping_types = [ 'pingback', 'trackback', 'pings' ];
		$comment_type = $comment['comment_type'] ?? '';

		$is_ping = apply_filters( 'antispam_bee_is_ping', in_array( $comment_type, $ping_types ), $comment );
		return $is_ping;
	}
}
