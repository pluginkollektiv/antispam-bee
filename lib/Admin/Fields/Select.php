<?php

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Text field.
 */
class Select extends Field implements RenderElement {

	protected $options;

	protected $multiple;

	public function __construct( $name, $label, $description = '', $options = [], $multiple = false ) {
		parent::__construct( $name, $label, $description );

		$this->options = $options;
		$this->multiple = $multiple;
	}

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
