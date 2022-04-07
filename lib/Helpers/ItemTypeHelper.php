<?php

namespace AntispamBee\Helpers;

class ItemTypeHelper {

	const COMMENT_TYPE   = 'comment';
	const TRACKBACK_TYPE = 'trackback';
	const PINGBACK_TYPE  = 'pingback';

	public static function get_type_name( $item_type ) {
		$type_names = [
			self::COMMENT_TYPE   => __( 'Comment', 'antispam-bee' ),
			self::TRACKBACK_TYPE => __( 'Trackback', 'antispam-bee' ),
			self::PINGBACK_TYPE  => __( 'Pingback', 'antispam-bee' ),
		];

		// Todo: Write a doc how to add custom types.
		$type_names = array_merge( apply_filters( 'asb_item_types', [] ), $type_names );
		return isset( $type_names[ $item_type ] ) ? $type_names[ $item_type ] : $item_type;
	}
}
