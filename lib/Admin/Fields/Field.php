<?php

namespace AntispamBee\Admin\Fields;

/**
 * Abstract class for field.
 */
abstract class Field {
	/**
	 * Field Name.
	 *
	 * @param string
	 */
	private $name;

	/**
	 * Field Description.
	 *
	 * @param string
	 */
	private $description;

	/**
	 * Initializing field
	 *
	 * @param string $name        Name of the field.
	 * @param string $label       Label of the field.
	 * @param string $description Description of the field.
	 */
	public function __construct( $name, $label, $description = '' ) {
		$this->name        = $name;
		$this->label       = $label;
		$this->description = $description;
	}

	/**
	 * Get Name.
	 *
	 * @return string Name of the field.
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get label.
	 *
	 * @return string Label of the field.
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Get Description.
	 *
	 * @return string Description of the field.
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Get Value.
	 *
	 * @return mixed Value stored in database.
	 */
	protected function get_value() {
		return get_option( $this->name );
	}

	/**
	 * Show description if not empty.
	 */
	protected function maybe_show_description() {
		if ( ! empty( $this->get_description() ) ) {
			printf(
				'<span>%s</span>',
				wp_kses_post( $this->get_description() )
			);
		}
	}

	/**
	 * Get HTML for field.
	 *
	 * @return string Elment HTML.
	 */
	abstract public function render();
}
