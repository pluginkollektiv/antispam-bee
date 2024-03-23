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
	 * @var array
	 */
	private $rows = [];

	/**
	 * Item type.
	 *
	 * @var string
	 */
	private $type;


	/**
	 * Initializing Tab.
	 *
	 * @param string      $slug Slug of the tab.
	 * @param string      $title Title for tab.
	 * @param string      $description Description of the tab.
	 * @param string|null $type Item type (e.g. comment, trackback).
	 */
	public function __construct( string $slug, string $title, string $description = '', string $type = null ) {
		$this->slug        = $slug;
		$this->title       = $title;
		$this->description = $description;
		$this->type        = $type;
	}

	/**
	 * Add controllable items to section.
	 *
	 * @param array|null $controllables List of controllable items to add.
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
	 * @param Controllable[] $controllables List of controllable items to add.
	 * @return void
	 */
	private function generate_fields( array $controllables ): void {
		foreach ( $controllables as $controllable ) {
			$label       = $controllable::get_label();
			$description = $controllable::get_description();
			$fields      = [];
			if ( ! $controllable::only_print_custom_options() ) {
				$fields[] = $this->generate_field(
					[
						'type'        => 'checkbox',
						'option_name' => 'active',
						'label'       => $label,
						'description' => $description,
					],
					$controllable
				);
			}

			$options = $controllable::get_options();
			if ( ! empty( $options ) ) {
				foreach ( $options as $option ) {
					$valid_for = isset( $option['valid_for'] ) ? $option['valid_for'] : null;
					if ( null !== $valid_for && $this->type !== $valid_for ) {
						continue;
					}
					$fields[] = $this->generate_field( $option, $controllable );
				}
			}

			$this->rows[] = [
				'label'  => $controllable::get_name(),
				'fields' => array_filter( $fields ),
			];
		}
	}

	/**
	 * Generate field for a controllable item's option.
	 *
	 * @param array  $option       Option name.
	 * @param string $controllable Controllable item (class name).
	 * @return Checkbox|CheckboxGroup|Inline|Select|Text|Textarea|null
	 */
	private function generate_field( array $option, string $controllable ): ?Field {
		switch ( $option['type'] ) {
			case 'input':
				return new Text( $this->type, $option, $controllable );
			case 'select':
				return new Select( $this->type, $option, $controllable );
			case 'textarea':
				return new Textarea( $this->type, $option, $controllable );
			case 'checkbox':
				return new Checkbox( $this->type, $option, $controllable );
			case 'checkbox-group':
				return new CheckboxGroup( $this->type, $option, $controllable );
			case 'inline':
				return new Inline( $this->type, $option, $controllable );
		}

		error_log( 'Missing or invalid `type` for field' );
		return null;
	}

	/**
	 * Get Name.
	 *
	 * @return string Name of the field.
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Get title.
	 *
	 * @return string Title of the field.
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get description.
	 *
	 * @return string Title of the field.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Get fields.
	 *
	 * @return array
	 */
	public function get_rows(): array {
		return $this->rows;
	}

	/**
	 * Print the UI element.
	 */
	public function get_callback() {
		if ( ! empty( $this->description ) ) {
			printf(
				'<p>%s</p>',
				wp_kses_post( $this->get_description() )
			);
		}
	}

	/**
	 * Renders the settings section.
	 */
	public function render(): void {
		add_settings_section(
			$this->get_slug(),
			$this->get_title(),
			[
				$this,
				'get_callback',
			],
			SettingsPage::SETTINGS_PAGE_SLUG
		);

		foreach ( $this->get_rows() as $row ) {
			add_settings_field(
				'asb-row-' . wp_generate_uuid4(),
				$row['label'],
				function () use ( $row ) {
					$this->render_row_fields( $row );
				},
				SettingsPage::SETTINGS_PAGE_SLUG,
				$this->get_slug()
			);
		}
	}

	/**
	 * Renders the fields for a row.
	 *
	 * @param array $row Row of fields.
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
