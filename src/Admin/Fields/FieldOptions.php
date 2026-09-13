<?php
/**
 * The options of a single admin field.
 *
 * @package AntispamBee\Admin\Fields
 */

namespace AntispamBee\Admin\Fields;

/**
 * Typed value object holding the configuration of one admin field.
 *
 * Replaces the loose associative arrays that were passed around for field
 * options, so the available keys are discoverable and typed instead of being
 * implicit array keys.
 */
class FieldOptions {
	/**
	 * Field type string.
	 *
	 * @see FieldType
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name;

	/**
	 * Label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * Allowed HTML tags for the label output.
	 *
	 * @var array<string, mixed>
	 */
	private $label_kses;

	/**
	 * Description.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Placeholder.
	 *
	 * @var string
	 */
	private $placeholder;

	/**
	 * Default value.
	 *
	 * @var mixed
	 */
	private $default;

	/**
	 * Select or checkbox group choices.
	 *
	 * @var array<array-key, mixed>
	 */
	private $choices;

	/**
	 * Whether a select allows multiple values.
	 *
	 * @var bool
	 */
	private $multiple;

	/**
	 * Reaction type this option is valid for.
	 *
	 * @var string
	 */
	private $valid_for;

	/**
	 * Sanitize callback.
	 *
	 * @var callable|null
	 */
	private $sanitize;

	/**
	 * Persist callback.
	 *
	 * @var callable|null
	 */
	private $persist;

	/**
	 * Load callback.
	 *
	 * @var callable|null
	 */
	private $load;

	/**
	 * Injectable field, used by the inline field type.
	 *
	 * @var InjectableField|null
	 */
	private $input;

	/**
	 * Input type attribute.
	 *
	 * @var string
	 */
	private $input_type;

	/**
	 * Input size name.
	 *
	 * @var string
	 */
	private $input_size;

	/**
	 * Initialize the field options.
	 *
	 * @param string $type Field type string.
	 */
	public function __construct( string $type ) {
		$this->type        = FieldType::from( $type );
		$this->option_name = '';
		$this->label       = '';
		$this->label_kses  = [];
		$this->description = '';
		$this->placeholder = '';
		$this->default     = null;
		$this->choices     = [];
		$this->multiple    = false;
		$this->valid_for   = '';
		$this->sanitize    = null;
		$this->persist     = null;
		$this->load        = null;
		$this->input       = null;
		$this->input_type  = '';
		$this->input_size  = '';
	}

	/**
	 * Get the field type string.
	 *
	 * @return string The field type.
	 */
	public function type(): string {
		return $this->type;
	}

	/**
	 * Set the option name.
	 *
	 * @param string $option_name Option name.
	 *
	 * @return self
	 */
	public function option_name( string $option_name ): self {
		$this->option_name = $option_name;

		return $this;
	}

	/**
	 * Get the option name.
	 *
	 * @return string The option name.
	 */
	public function get_option_name(): string {
		return $this->option_name;
	}

	/**
	 * Set the label.
	 *
	 * @param string $label Label.
	 *
	 * @return self
	 */
	public function label( string $label ): self {
		$this->label = $label;

		return $this;
	}

	/**
	 * Get the label.
	 *
	 * @return string The label.
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Set the allowed HTML tags for the label output.
	 *
	 * @param array<string, mixed> $label_kses Allowed tags and attributes.
	 *
	 * @return self
	 */
	public function label_kses( array $label_kses ): self {
		$this->label_kses = $label_kses;

		return $this;
	}

	/**
	 * Get the allowed HTML tags for the label output.
	 *
	 * @return array<string, mixed> Allowed tags and attributes.
	 */
	public function get_label_kses(): array {
		return $this->label_kses;
	}

	/**
	 * Set the description.
	 *
	 * @param string $description Description.
	 *
	 * @return self
	 */
	public function description( string $description ): self {
		$this->description = $description;

		return $this;
	}

	/**
	 * Get the description.
	 *
	 * @return string The description.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Set the placeholder.
	 *
	 * @param string $placeholder Placeholder.
	 *
	 * @return self
	 */
	public function placeholder( string $placeholder ): self {
		$this->placeholder = $placeholder;

		return $this;
	}

	/**
	 * Get the placeholder.
	 *
	 * @return string The placeholder.
	 */
	public function get_placeholder(): string {
		return $this->placeholder;
	}

	/**
	 * Set the default value.
	 *
	 * @param mixed $value Default value.
	 *
	 * @return self
	 */
	public function default_value( $value ): self {
		$this->default = $value;

		return $this;
	}

	/**
	 * Get the default value.
	 *
	 * @return mixed The default value.
	 */
	public function get_default() {
		return $this->default;
	}

	/**
	 * Set the choices.
	 *
	 * @param array<array-key, mixed> $choices Select or checkbox group choices.
	 *
	 * @return self
	 */
	public function choices( array $choices ): self {
		$this->choices = $choices;

		return $this;
	}

	/**
	 * Get the choices.
	 *
	 * @return array<array-key, mixed> Select or checkbox group choices.
	 */
	public function get_choices(): array {
		return $this->choices;
	}

	/**
	 * Set whether a select allows multiple values.
	 *
	 * @param bool $multiple Whether the select allows multiple values.
	 *
	 * @return self
	 */
	public function multiple( bool $multiple ): self {
		$this->multiple = $multiple;

		return $this;
	}

	/**
	 * Get whether a select allows multiple values.
	 *
	 * @return bool Whether the select allows multiple values.
	 */
	public function is_multiple(): bool {
		return $this->multiple;
	}

	/**
	 * Set the reaction type this option is valid for.
	 *
	 * @param string $valid_for Reaction type slug.
	 *
	 * @return self
	 */
	public function valid_for( string $valid_for ): self {
		$this->valid_for = $valid_for;

		return $this;
	}

	/**
	 * Get the reaction type this option is valid for.
	 *
	 * @return string The reaction type slug, or an empty string if valid everywhere.
	 */
	public function get_valid_for(): string {
		return $this->valid_for;
	}

	/**
	 * Set the sanitize callback.
	 *
	 * @param callable $sanitize Sanitize callback.
	 *
	 * @return self
	 */
	public function sanitize( callable $sanitize ): self {
		$this->sanitize = $sanitize;

		return $this;
	}

	/**
	 * Get the sanitize callback.
	 *
	 * @return callable|null The sanitize callback, or null.
	 */
	public function get_sanitize(): ?callable {
		return $this->sanitize;
	}

	/**
	 * Set the persist callback.
	 *
	 * @param callable $persist Persist callback.
	 *
	 * @return self
	 */
	public function persist( callable $persist ): self {
		$this->persist = $persist;

		return $this;
	}

	/**
	 * Get the persist callback.
	 *
	 * @return callable|null The persist callback, or null.
	 */
	public function get_persist(): ?callable {
		return $this->persist;
	}

	/**
	 * Set the load callback.
	 *
	 * @param callable $load Load callback.
	 *
	 * @return self
	 */
	public function load( callable $load ): self {
		$this->load = $load;

		return $this;
	}

	/**
	 * Get the load callback.
	 *
	 * @return callable|null The load callback, or null.
	 */
	public function get_load(): ?callable {
		return $this->load;
	}

	/**
	 * Set the injectable field.
	 *
	 * @param InjectableField $input Injectable field.
	 *
	 * @return self
	 */
	public function input( InjectableField $input ): self {
		$this->input = $input;

		return $this;
	}

	/**
	 * Get the injectable field.
	 *
	 * @return InjectableField|null The injectable field, or null.
	 */
	public function get_input(): ?InjectableField {
		return $this->input;
	}

	/**
	 * Set the input type attribute.
	 *
	 * @param string $input_type Input type.
	 *
	 * @return self
	 */
	public function input_type( string $input_type ): self {
		$this->input_type = $input_type;

		return $this;
	}

	/**
	 * Get the input type attribute.
	 *
	 * @return string The input type, or 'text' if unset.
	 */
	public function get_input_type(): string {
		return '' !== $this->input_type ? $this->input_type : 'text';
	}

	/**
	 * Set the input size name.
	 *
	 * @param string $input_size Input size.
	 *
	 * @return self
	 */
	public function input_size( string $input_size ): self {
		$this->input_size = $input_size;

		return $this;
	}

	/**
	 * Get the input size name.
	 *
	 * @return string The input size.
	 */
	public function get_input_size(): string {
		return $this->input_size;
	}
}
