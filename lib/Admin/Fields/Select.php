<?php

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Text field.
 */
class Select extends Field implements RenderElement {

	/**
	 * Get HTML.
	 *
	 * @return string Elment HTML.
	 */
	public function render() {
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
