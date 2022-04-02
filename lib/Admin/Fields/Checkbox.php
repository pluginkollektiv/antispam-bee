<?php
/**
 * The Checkbox Field for the admin UI.
 *
 * @package AntispamBee\Admin\Fields
 */

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
		printf(
			'<input type="checkbox" name="%s" value="1" %s />',
			esc_attr( $this->get_name() ),
			checked( 1, $this->get_value(), false )
		);
		$this->maybe_show_description();
	}
}
