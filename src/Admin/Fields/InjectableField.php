<?php
/**
 * Interface for fields that can be embedded inside another field's label.
 *
 * @package AntispamBee\Admin\Fields
 */

namespace AntispamBee\Admin\Fields;

/**
 * Marks a field as injectable into an Inline field wrapper.
 */
interface InjectableField {

	/**
	 * Get the name attribute for the field.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Get the raw HTML markup for the input element.
	 *
	 * @return string
	 */
	public function get_injectable_markup(): string;
}
