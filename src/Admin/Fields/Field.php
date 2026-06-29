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
	 * Reaction type.
	 *
	 * @var string
	 */
	protected $reaction_type;

	/**
	 * Field options.
	 *
	 * @var array
	 */
	protected $option;

	/**
	 * Type of controllable.
	 *
	 * @var string
	 */
	protected $controllable_option_name;

	/**
	 * Initializing field
	 *
	 * @param string $reaction_type Reaction type.
	 * @param array  $option        Field options.
	 * @param string $controllable  The related controllable (class name).
	 */
	public function __construct( string $reaction_type, array $option, string $controllable ) {
		$this->reaction_type            = $reaction_type;
		$this->option                   = $option;
		$this->controllable_option_name = $controllable::get_option_name( $this->option['option_name'] );
	}

	/**
	 * Get Name.
	 *
	 * @return string Name of the field.
	 */
	public function get_name(): string {
		$option_name = Settings::OPTION_NAME;
		$name        = "{$option_name}[{$this->reaction_type}][{$this->controllable_option_name}]";

		return str_replace( '-', '_', $name );
	}

	/**
	 * Get label.
	 *
	 * @return string Label of the field.
	 */
	public function get_label(): string {
		$kses  = $this->option['label_kses'] ?? [];
		$label = $this->option['label'] ?? '';
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
	public function get_placeholder(): string {
		return $this->option['placeholder'] ?? '';
	}

	/**
	 * Get Description.
	 *
	 * @return string Description of the field.
	 */
	public function get_description(): string {
		return $this->option['description'] ?? '';
	}

	/**
	 * Get Value.
	 *
	 * @return mixed Value stored in database.
	 */
	protected function get_value() {
		return Settings::get_option( $this->controllable_option_name, $this->reaction_type );
	}

	/**
	 * Get the option payload.
	 *
	 * @return array
	 */
	public function get_option(): array {
		return $this->option;
	}

	/**
	 * Show description if not empty.
	 */
	protected function maybe_show_description(): void {
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
	 * @return void
	 */
	abstract public function render(): void;
}
