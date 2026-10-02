<?php
/**
 * The supported field types for the admin UI.
 *
 * @package AntispamBee\Admin\Fields
 */

namespace AntispamBee\Admin\Fields;

/**
 * Enum-like list of supported field types.
 *
 * PHP 7.4 has no real enums, so this is a plain class with named constants
 * and a `from()` factory that maps a config type string back to a constant.
 */
class FieldType {

	const CHECKBOX       = 'checkbox';
	const CHECKBOX_GROUP = 'checkbox-group';
	const INPUT          = 'input';
	const INLINE         = 'inline';
	const SELECT         = 'select';
	const TEXTAREA       = 'textarea';

	/**
	 * Turn a config type string into a field type constant.
	 *
	 * @param string $type The raw type from a field option.
	 *
	 * @return string The matching field type constant, or an empty string if unknown.
	 */
	public static function from( string $type ): string {
		$types = [
			self::CHECKBOX,
			self::CHECKBOX_GROUP,
			self::INPUT,
			self::INLINE,
			self::SELECT,
			self::TEXTAREA,
		];

		return in_array( $type, $types, true ) ? $type : '';
	}
}
