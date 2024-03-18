<?php
/**
 * The Select Field for the admin UI.
 *
 * @package AntispamBee\Admin\Fields
 */

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Text field.
 */
class Select extends Field implements RenderElement {

	/**
	 * Get HTML.
	 */
	public function render() {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

		$name     = $this->get_name();
		$multiple = isset( $this->option['multiple'] ) && $this->option['multiple'] ? 'multiple' : '';
		echo "<select name='$name' $multiple>";
		foreach ( $this->options['options'] as $key => $value ) {
			echo "<option value='{$key}'>$value</option>";
		}
		echo '</select>';
		$this->maybe_show_description();
	}
}
