<?php

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Checkbox field.
 */
class Checkbox extends Field implements RenderElement {
	/**
	 * Get HTML.
	 */
	public function render() {
		echo '<input type="checkbox" name="' . $this->get_name() . '" value="1" ' . checked( 1, $this->get_value(), false ) . ' />';
		$this->maybe_show_description();
	}
}
