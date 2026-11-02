<?php
/**
 * Factory for building typed field options.
 *
 * @package AntispamBee\Admin\Fields
 */

namespace AntispamBee\Admin\Fields;

/**
 * Fluent factory for field options.
 *
 * Each static method returns a FieldOptions object pre-populated with the
 * right type, ready for the chainable setters on FieldOptions.
 */
class FieldBuilder {

	/**
	 * Build a checkbox field option.
	 *
	 * @return FieldOptions The new field options.
	 */
	public static function checkbox(): FieldOptions {
		return new FieldOptions( FieldType::CHECKBOX );
	}

	/**
	 * Build a checkbox-group field option.
	 *
	 * @return FieldOptions The new field options.
	 */
	public static function checkbox_group(): FieldOptions {
		return new FieldOptions( FieldType::CHECKBOX_GROUP );
	}

	/**
	 * Build an input field option.
	 *
	 * @return FieldOptions The new field options.
	 */
	public static function input(): FieldOptions {
		return new FieldOptions( FieldType::INPUT );
	}

	/**
	 * Build an inline field option.
	 *
	 * @return FieldOptions The new field options.
	 */
	public static function inline(): FieldOptions {
		return new FieldOptions( FieldType::INLINE );
	}

	/**
	 * Build a select field option.
	 *
	 * @return FieldOptions The new field options.
	 */
	public static function select(): FieldOptions {
		return new FieldOptions( FieldType::SELECT );
	}

	/**
	 * Build a textarea field option.
	 *
	 * @return FieldOptions The new field options.
	 */
	public static function textarea(): FieldOptions {
		return new FieldOptions( FieldType::TEXTAREA );
	}
}
