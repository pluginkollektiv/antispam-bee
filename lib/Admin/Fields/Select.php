<?php

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Text field.
 */
class Select extends Field implements RenderElement {

	protected $options;

	protected $multiple;

	/**
	 * Get HTML.
	 *
	 * @return string Elment HTML.
	 */
	public function render() {
		echo '<select name="' . $this->get_name() . '" ' . ($this->multiple ? 'multiple' : '') . '>';
		foreach ( $this->options as $option ) {
			echo '<option value="' . $option['value'] . '" >' . $option['label'] . '</option>';
		}
		echo '</select>';
		$this->maybe_show_description();
	}
}
