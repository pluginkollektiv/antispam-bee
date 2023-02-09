<?php
/**
 * The admin UI section.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Admin\Fields\Checkbox;
use AntispamBee\Admin\Fields\CheckboxGroup;
use AntispamBee\Admin\Fields\Inline;
use AntispamBee\Admin\Fields\Select;
use AntispamBee\Admin\Fields\Text;
use AntispamBee\Admin\Fields\Textarea;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\PostProcessors\Base as BasePostProcessor;

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
	 *
	 * @var Controllable[]
	 */
	private $controllables;

	/**
	 * Initializing Tab.
	 *
	 * @param string      $slug Slug of the tab.
	 * @param string      $title Title for tab.
	 * @param string      $description Description of the tab.
	 * @param string|null $type Item type (e.g. comment, trackback).
	 */
	public function __construct( $slug, $title, $description = '', $type = null ) {
		$this->slug        = $slug;
		$this->title       = $title;
		$this->description = $description;
		$this->type        = $type;
	}

	public function add_controllables( $controllables ) {
		if ( ! empty( $controllables ) ) {
			$this->generate_fields( $controllables );
		}
	}

	private function generate_fields( $controllables ) {
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
					if ( $valid_for !== null && $this->type !== $valid_for ) {
						continue;
					}
					$fields[] = $this->generate_field( $option, $controllable );
				}
			}

			$this->rows[] = [
				'label'  => $controllable::get_name(),
				'fields' => $fields,
			];
		}
	}

	private function generate_field( $option, $controllable ) {
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
	}

	/**
	 * Get Name.
	 *
	 * @return string Name of the field.
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Get title.
	 *
	 * @return string Title of the field.
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Get description.
	 *
	 * @return string Title of the field.
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Get fields.
	 *
	 * @return array
	 */
	public function get_rows() {
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
	public function render() {
		add_settings_section( $this->get_slug(), $this->get_title(), [ $this, 'get_callback' ], SettingsPage::SETTINGS_PAGE_SLUG );

		foreach ( $this->get_rows() as $row ) {
			add_settings_field(
				'asb-row-' . wp_generate_uuid4(),
				$row['label'],
				function() use ( $row ) {
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
	 * @param array $row
	 */
	protected function render_row_fields( $row ) {
		foreach ( $row['fields'] as $key => $field ) {
			$field->render();

			// Add linebreak after field if not (last and not checkbox without label).
			if ( $key !== count( $row['fields'] ) - 1 ) {
				if ( $field instanceof Checkbox && empty( $field->get_label() ) ) {
					continue;
				}
				echo '<br>';
			}
		}
	}
}
