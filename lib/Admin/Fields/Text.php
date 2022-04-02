<?php

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Text field.
 */
class Text extends Field implements RenderElement {
	/**
	 * Get HTML.
	 *
	 * @return string Elment HTML.
	 */
	public function render() {
		printf(
			'<input type="checkbox" name="%s" value="%s" />',
			esc_attr( $this->get_name() ),
			esc_attr( $this->get_value() )
		);
		$this->maybe_show_description();
	}
}
