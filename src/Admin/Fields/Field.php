<?php
/**
 * The base Field class for the admin UI.
 *
 * @package AntispamBee\Admin\Fields
 */

namespace AntispamBee\Admin\Fields;

use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\Controllable;

/**
 * Abstract class for field.
 */
abstract class Field {
	/**
	 * Item type.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Field options.
	 *
	 * @var array
	 */
	protected $option;

	/**
	 * Type of controllable.
	 */
	protected $controllable_option_name;

	/**
	 * Initializing field
	 *
	 * @param string $type Item type.
	 * @param array $option Field options.
	 * @param class-string<Controllable> $controllable The related controllable.
	 */
	public function __construct( $type, $option, $controllable ) {
		$this->type                     = $type;
		$this->option                   = $option;
		$this->controllable_option_name = $controllable::get_option_name( $this->option['option_name'] );
	}

	/**
	 * Get Name.
	 *
	 * @return string Name of the field.
	 */
	public function get_name() {
		$option_name = Settings::OPTION_NAME;
		$name        = "{$option_name}[{$this->type}][{$this->controllable_option_name}]";

		return str_replace( '-', '_', $name );
	}

	/**
	 * Get label.
	 *
	 * @return string Label of the field.
	 */
	public function get_label() {
		$kses = isset( $this->option['label_kses'] ) ? $this->option['label_kses'] : [];
		$label = isset( $this->option['label'] ) ? $this->option['label'] : '';
		if ( ! $kses ) {
			return esc_html( $label );
		}
		return wp_kses( $label, $kses );
	}

	/**
	 * Get placeholder.
	 *
	 * @return string
	 */
	public function get_placeholder() {
		return isset( $this->option['placeholder'] ) ? $this->option['placeholder'] : '';
	}

	/**
	 * Get Description.
	 *
	 * @return string Description of the field.
	 */
	public function get_description() {
		return isset( $this->option['description'] ) ? $this->option['description'] : '';
	}

	/**
	 * Get Value.
	 *
	 * @return mixed Value stored in database.
	 */
	protected function get_value() {
		return Settings::get_option( $this->controllable_option_name, $this->type );
	}

	/**
	 * Get the option payload.
	 *
	 * @return array
	 */
	public function get_option() {
		return $this->option;
	}

	/**
	 * Show description if not empty.
	 */
	protected function maybe_show_description() {
		if ( ! empty( $this->get_description() ) ) {
			printf(
				'<p class="description">%s</p>',
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
