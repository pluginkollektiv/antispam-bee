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
	 * @return string Element HTML.
	 */
	public function render() {
		echo '<textarea name="' . $this->get_name() . '">' . $this->get_value() . '</textarea><br>';
		$this->maybe_show_description();
	}
}
