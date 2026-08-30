<?php
/**
 * The admin UI section.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Admin\Fields\Checkbox;
use AntispamBee\Admin\Fields\CheckboxGroup;
use AntispamBee\Admin\Fields\Field;
use AntispamBee\Admin\Fields\FieldBuilder;
use AntispamBee\Admin\Fields\FieldOptions;
use AntispamBee\Admin\Fields\FieldType;
use AntispamBee\Admin\Fields\Inline;
use AntispamBee\Admin\Fields\Select;
use AntispamBee\Admin\Fields\Text;
use AntispamBee\Admin\Fields\Textarea;
use AntispamBee\Interfaces\Controllable;

/**
 * Sections for admin.
 */
class Section {
	/**
	 * Name.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Title.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Description.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Fields.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $rows = [];

	/**
	 * Reaction type.
	 *
	 * @var string
	 */
	private $reaction_type;


	/**
	 * Initialize the tab.
	 *
	 * @param string $slug          The slug of the tab.
	 * @param string $title         Title for the tab.
	 * @param string $description   Description of the tab.
	 * @param string $reaction_type Reaction type (e.g. comment, trackback).
	 */
	public function __construct( string $slug, string $title, string $description = '', string $reaction_type = '' ) {
		$this->slug          = $slug;
		$this->title         = $title;
		$this->description   = $description;
		$this->reaction_type = $reaction_type;
	}

	/**
	 * Add controllable items to section.
	 *
	 * @param class-string<Controllable>[]|null $controllables A list of controllable items to add.
	 *
	 * @return void
	 */
	public function add_controllables( ?array $controllables ): void {
		if ( ! empty( $controllables ) ) {
			$this->generate_fields( $controllables );
		}
	}

	/**
	 * Generate settings fields for a list of controllable items.
	 *
	 * @param class-string<Controllable>[] $controllables A list of controllable items to add.
	 *
	 * @return void
	 */
	private function generate_fields( array $controllables ): void {
		foreach ( $controllables as $controllable ) {
			$fields = [];
			if ( ! $controllable::only_print_custom_options() ) {
				$options  = FieldBuilder::checkbox()
					->option_name( 'active' )
					->label( (string) $controllable::get_label() )
					->description( (string) $controllable::get_description() );
				$fields[] = $this->generate_field( $options, $controllable );
			}

			foreach ( (array) $controllable::get_options() as $options ) {
				$valid_for = $options->get_valid_for();
				if ( '' !== $valid_for && $this->reaction_type !== $valid_for ) {
					continue;
				}
				$fields[] = $this->generate_field( $options, $controllable );
			}

			$this->rows[] = [
				'label'  => $controllable::get_name(),
				'fields' => array_filter( $fields ),
			];
		}
	}

	/**
	 * Get the description.
	 *
	 * @return string The section description.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Generate field for a controllable item's option.
	 *
	 * @param FieldOptions $options      Field options.
	 * @param string       $controllable Controllable item (class name).
	 *
	 * @phpstan-param class-string<Controllable> $controllable
	 *
	 * @return Checkbox|CheckboxGroup|Inline|Select|Text|Textarea|null The generated field, or null if the type is missing or invalid.
	 */
	private function generate_field( FieldOptions $options, string $controllable ): ?Field {
		switch ( $options->type() ) {
			case FieldType::INPUT:
				return new Text( $this->reaction_type, $options, $controllable );
			case FieldType::SELECT:
				return new Select( $this->reaction_type, $options, $controllable );
			case FieldType::TEXTAREA:
				return new Textarea( $this->reaction_type, $options, $controllable );
			case FieldType::CHECKBOX:
				return new Checkbox( $this->reaction_type, $options, $controllable );
			case FieldType::CHECKBOX_GROUP:
				return new CheckboxGroup( $this->reaction_type, $options, $controllable );
			case FieldType::INLINE:
				return new Inline( $this->reaction_type, $options, $controllable );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'Missing or invalid `type` for field' );

		return null;
	}

	/**
	 * Print the UI element.
	 *
	 * @return void
	 */
	public function get_callback(): void {
		if ( ! empty( $this->description ) ) {
			printf(
				'<p>%s</p>',
				wp_kses_post( $this->get_description() )
			);
		}
	}

	/**
	 * Render the settings section.
	 */
	public function render(): void {
		$page = SettingsPage::SETTINGS_PAGE_SLUG . '_' . $this->reaction_type;

		add_settings_section(
			$this->get_slug(),
			$this->get_title(),
			[
				$this,
				'get_callback',
			],
			$page
		);

		foreach ( $this->get_rows() as $row ) {
			add_settings_field(
				'asb-row-' . wp_generate_uuid4(),
				$row['label'],
				function () use ( $row ) {
					$this->render_row_fields( $row );
				},
				$page,
				$this->get_slug()
			);
		}
	}

	/**
	 * Get the slug.
	 *
	 * @return string The section slug.
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Get the title.
	 *
	 * @return string The section title.
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get the rows.
	 *
	 * @return array<int, array<string, mixed>> The rows of the section.
	 */
	public function get_rows(): array {
		return $this->rows;
	}

	/**
	 * Render the fields for a row.
	 *
	 * @param array<string, mixed> $row Row of fields.
	 */
	protected function render_row_fields( array $row ): void {
		foreach ( $row['fields'] as $key => $field ) {
			$field->render();

			// Add linebreak after field if not (last and not checkbox without label).
			if ( ( count( $row['fields'] ) - 1 ) !== $key ) {
				if ( $field instanceof Checkbox && empty( $field->get_label() ) ) {
					continue;
				}
				echo '<br>';
			}
		}
	}
}
