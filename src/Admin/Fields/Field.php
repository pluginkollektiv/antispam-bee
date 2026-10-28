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
	 * @var FieldOptions
	 */
	protected $option;

	/**
	 * Type of controllable.
	 *
	 * @var string
	 */
	protected $controllable_option_name;

	/**
	 * Initialize the field.
	 *
	 * @param string       $reaction_type Reaction type.
	 * @param FieldOptions $options       Field options.
	 * @param string       $controllable  The related controllable (class name).
	 *
	 * @phpstan-param class-string<Controllable> $controllable
	 */
	public function __construct( string $reaction_type, FieldOptions $options, string $controllable ) {
		$this->reaction_type            = $reaction_type;
		$this->option                   = $options;
		$this->controllable_option_name = $controllable::get_option_name( $options->get_option_name() );
	}

	/**
	 * Get the name.
	 *
	 * @return string The name of the field.
	 */
	public function get_name(): string {
		$option_name = Settings::OPTION_NAME;
		$name        = "{$option_name}[{$this->reaction_type}][{$this->controllable_option_name}]";

		return str_replace( '-', '_', $name );
	}

	/**
	 * Get the label.
	 *
	 * @return string The label of the field.
	 */
	public function get_label(): string {
		$kses  = $this->option->get_label_kses();
		$label = $this->option->get_label();
		if ( ! $kses ) {
			return esc_html( $label );
		}

		return wp_kses( $label, $kses );
	}

	/**
	 * Get the placeholder.
	 *
	 * @return string The placeholder of the field.
	 */
	public function get_placeholder(): string {
		return $this->option->get_placeholder();
	}

	/**
	 * Get the HTML for the field.
	 *
	 * @return void
	 */
	abstract public function render(): void;

	/**
	 * Get the value.
	 *
	 * @return mixed The value stored in the database.
	 */
	protected function get_value() {
		return Settings::get_option( $this->controllable_option_name, $this->reaction_type );
	}

	/**
	 * Get the option payload.
	 *
	 * @return FieldOptions The field options value object.
	 */
	public function get_option(): FieldOptions {
		return $this->option;
	}

	/**
	 * Show the description if not empty.
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
	 * Get the description.
	 *
	 * @return string Description of the field.
	 */
	public function get_description(): string {
		return $this->option->get_description();
	}
}
