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
			'<p><label for="%s">%s</label></p><p>%s</p>',
			esc_attr( $this->get_name() ),
			$this->get_label(),
			$this->get_injectable_markup()
		);
		$this->maybe_show_description();
	}

	public function get_injectable_markup() {
		return sprintf(
			'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" class="%4$s" placeholder="%5$s">',
			esc_attr( $this->get_type() ),
			esc_attr( $this->get_name() ),
			esc_attr( $this->get_value() ),
			esc_attr( $this->get_class() ),
			esc_attr( $this->get_placeholder() )
		);
	}

	protected function get_class() {
		$classes    = [
			'small'   => 'small-text',
			'regular' => 'regular-text',
		];
		$field_size = isset( $this->option['input_size'] ) ? $this->option['input_size'] : '';

		if ( isset( $classes[ $field_size ] ) ) {
			return $classes[ $field_size ];
		}

		return 'regular-text';
	}

	protected function get_type() {
		return isset( $this->option['input_type'] ) ? $this->option['input_type'] : 'text';
	}
}
