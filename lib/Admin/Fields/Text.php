<?php
/**
 * The Text Field for the admin UI.
 *
 * @package AntispamBee\Admin\Fields
 */

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Text field.
 */
class Text extends Field implements RenderElement {

	protected $placeholder;

	public function get_placeholder() {
		return $this->placeholder;
	}

	/**
	 * Get HTML.
	 */
	public function render() {
		printf(
			'<p><label for="%1$s">%2$s</label></p><p><input class="regular-text" id="%1$s" name="%1$s" value="%3$s" placeholder="%4$s"></p>',
			esc_attr( $this->get_name() ),
			esc_html( $this->get_label() ),
			esc_attr( $this->get_value() ),
			esc_attr( $this->get_placeholder() )
		);
		$this->maybe_show_description();
	}
}
