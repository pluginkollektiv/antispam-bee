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

	public function __construct( $name, $label, $description = '', $placeholder = '' ) {
		parent::__construct( $name, $label, $description );

		$this->placeholder = $placeholder;
	}

	/**
	 * Get HTML.
	 */
	public function render() {
		printf(
			'<input class="regular-text" name="%s" value="%s" placeholder="%s" />',
			esc_attr( $this->get_name() ),
			esc_attr( $this->get_value() ),
			esc_attr( $this->placeholder )
		);
		$this->maybe_show_description();
	}
}
