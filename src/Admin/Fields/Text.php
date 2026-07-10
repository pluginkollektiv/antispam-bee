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
class Text extends Field implements RenderElement, InjectableField {

	/**
	 * Get the HTML.
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

		printf(
			'<p><label for="%s">%s</label></p><p>%s</p>',
			esc_attr( $this->get_name() ),
			$this->get_label(),
			$this->get_injectable_markup()
		);
		$this->maybe_show_description();
	}

	/**
	 * Get the HTML markup for the actual input field.
	 *
	 * @return string The HTML markup for the input field.
	 */
	public function get_injectable_markup(): string {
		return sprintf(
			'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" class="%4$s" placeholder="%5$s">',
			esc_attr( $this->get_type() ),
			esc_attr( $this->get_name() ),
			esc_attr( $this->get_value() ),
			esc_attr( $this->get_class() ),
			esc_attr( $this->get_placeholder() )
		);
	}

	/**
	 * Get the type of the input field.
	 *
	 * @return string The type of the input field.
	 */
	protected function get_type(): string {
		return $this->option['input_type'] ?? 'text';
	}

	/**
	 * Get the element class(es).
	 *
	 * @return string The CSS class(es) of the element.
	 */
	protected function get_class(): string {
		$classes    = [
			'small'   => 'small-text',
			'regular' => 'regular-text',
		];
		$field_size = $this->option['input_size'] ?? '';

		if ( isset( $classes[ $field_size ] ) ) {
			return $classes[ $field_size ];
		}

		return 'regular-text';
	}

	/**
	 * Get the placeholder.
	 *
	 * @return string The placeholder of the field.
	 */
	public function get_placeholder(): string {
		return $this->placeholder ?? '';
	}
}
