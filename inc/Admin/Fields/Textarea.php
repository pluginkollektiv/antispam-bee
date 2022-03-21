<?php

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Text field.
 */
class Textarea extends Field implements RenderElement {
	/**
	 * Get HTML.
	 *
	 * @return string Elment HTML.
	 */
	public function render() {
		echo '<textarea name="' . $this->get_name() . '">' . $this->get_value() . '</textarea>';
		$this->maybe_show_description();
	}
}
