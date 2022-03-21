<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\PostProcessor;
use AntispamBee\Helpers\Settings;

class UpdateSpamCount implements PostProcessor {

	use IsActive;
	use InitPostProcessor;

	public static function process( $item ) {
		if ( ! Settings::get_option( 'dashboard_count' ) ) {
			return $item;
		}

		Settings::update_option(
			'spam_count',
			intval( Settings::get_option( 'spam_count' ) + 1 )
		);

		return $item;
	}

	public static function get_slug() {
		return 'asb-send-email';
	}

	public static function get_supported_types() {
		return [ ItemTypeHelper::COMMENT_TYPE, ItemTypeHelper::TRACKBACK_TYPE ];
	}

	public static function marks_as_delete() {
		return false;
	}
}

