<?php
/**
 * The inline input field for the admin UI.
 *
 * @package AntispamBee\Admin\Fields
 */

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Inline input field.
 */
class Inline extends Field implements RenderElement {

	/**
	 * Get the HTML for the field.
	 *
	 * @return void
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

		$inject_field_object = $this->option->get_input();
		if ( ! $inject_field_object instanceof InjectableField ) {
			echo '';

			return;
		}
		$inject_markup           = $inject_field_object->get_injectable_markup();
		$label_with_inline_field = sprintf(
			$this->get_label(),
			'</label>' . $inject_markup . sprintf(
				'<label for="%s">',
				$inject_field_object->get_name()
			)
		);
		printf(
			'<label for="%1$s">%2$s</label>',
			$this->get_name(),
			$label_with_inline_field
		);
	}
}
