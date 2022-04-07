<?php
/**
 * The Textarea Field for the admin UI.
 *
 * @package AntispamBee\Admin\Fields
 */

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Text field.
 */
class Textarea extends Field implements RenderElement {
	/**
	 * Render HTML.
	 */
	public function render() {
		printf(
			'<textarea name="%s">%s</textarea>',
			esc_attr( $this->get_name() ),
			esc_html( $this->get_value() )
		);
		$this->maybe_show_description();
	}
}
