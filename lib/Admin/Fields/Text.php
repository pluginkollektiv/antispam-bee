<?php

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Text field.
 */
class Text extends Field implements RenderElement {

	protected $placeholder;

	public function __construct( $name, $label, $description = '', $placeholder = '' ) {
		parent::__construct( $name, $label, $description );

		$this->placeholder = $placeholder;
	}

	/**
	 * Get HTML.
	 *
	 * @return string Elment HTML.
	 */
	public function render() {
		echo '<input class="regular-text" name="' . $this->get_name() . '" value="' . $this->get_value() . '" placeholder="' . $this->placeholder . '" />';
		$this->maybe_show_description();
	}
}
