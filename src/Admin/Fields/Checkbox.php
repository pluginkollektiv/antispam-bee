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
		$label = ! empty( $this->get_label() ) ? sprintf(
			'<label for="%s">%s</label>',
			esc_attr( $this->get_name() ),
			esc_html( $this->get_label() )
		) : '';

		printf(
			'<input type="checkbox" id="%1$s" name="%1$s" %2$s />%3$s',
			esc_attr( $this->get_name() ),
			checked( 'on', $this->get_value(), false ),
			$label
		);
		$this->maybe_show_description();
	}
}
