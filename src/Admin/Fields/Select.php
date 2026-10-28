<?php
/**
 * The Select Field for the admin UI.
 *
 * @package AntispamBee\Admin\Fields
 */

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Select field.
 */
class Select extends Field implements RenderElement {

	/**
	 * Get the HTML.
	 */
	public function render(): void {
		$value    = $this->get_value();
		$multiple = $this->option->is_multiple();

		printf(
			'<select name="%s"%s>',
			esc_attr( $this->get_name() . ( $multiple ? '[]' : '' ) ),
			$multiple ? ' multiple' : ''
		);
		foreach ( $this->option->get_choices() as $key => $label ) {
			$is_selected = is_array( $value )
				? in_array( (string) $key, array_map( 'strval', $value ), true )
				: (string) $key === (string) $value;
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $is_selected, true, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		$this->maybe_show_description();
	}
}
