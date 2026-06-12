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
	 * Get HTML for field.
	 *
	 * @return void
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! $this->option['input'] instanceof Field ) {
			echo '';

			return;
		}
		$inject_field_object     = $this->option['input'];
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
