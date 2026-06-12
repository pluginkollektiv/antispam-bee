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
	public function render(): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

		printf(
			'<p><label for="%1$s">%2$s</label></p><p><textarea name="%1$s" id="%1$s" placeholder="%4$s">%3$s</textarea></p>',
			esc_attr( $this->get_name() ),
			$this->get_label(),
			esc_html( $this->get_value() ),
			esc_attr( $this->get_placeholder() )
		);
		$this->maybe_show_description();
	}
}
