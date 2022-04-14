<?php
/**
 * The admin UI section.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Admin\Fields\Checkbox;
use AntispamBee\Admin\Fields\CheckboxGroup;
use AntispamBee\Admin\Fields\Select;
use AntispamBee\Admin\Fields\Text;
use AntispamBee\Admin\Fields\Textarea;
use AntispamBee\Admin\Fields\Field;

/**
 * Sections for admin.
 */
class Section {
	/**
	 * Name.
	 *
	 * @var string
	 */
	private $name;

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
	 * @param string      $name Name of the tab.
	 * @param string      $title Title for tab.
	 * @param string      $description Description of the tab.
	 * @param string|null $type Item type (e.g. comment, trackback).
	 */
	public function __construct( $name, $title, $description = '', $type = null ) {
		$this->name        = $name;
		$this->title       = $title;
		$this->description = $description;
		$this->type = $type;
	}

	public function add_rows( $rows ) {
		foreach ( $rows as $row ) {
			$this->rows[] = $row;
		}
	}

	public function add_controllables( $controllables ) {
		$this->generate_fields( $controllables );
	}

	private function generate_fields( $controllables ) {
		// Todo: DRY - Don’t run for other types than displayed
		foreach ( $controllables as $controllable ) {
			$controllable = $controllable;
			$slug = $controllable::get_slug();
			$label = $controllable::get_label();
			$description = $controllable::get_description();
			$fields = [];
			$fields[] = $this->generate_field( [
				'type' => 'checkbox',
				'option_name' => $slug . '_active',
				'label' => $label,
				'description' => $description
			] );

			$options = $controllable::get_options();
			if ( ! empty( $options ) ) {
				foreach ( $options as $option ) {
					$valid_for = isset( $option['valid_for'] ) ? $option['valid_for'] : null;
					if ( $valid_for !== null && $this->type !== $valid_for ) {
						continue;
					}
					$fields[] = $this->generate_field( $option );
				}
			}

			$this->rows[] = [
				'label' => $controllable::get_name(),
				'fields' => $fields
			];
		}
	}

	private function generate_field( $option ) {
		switch ( $option['type'] ) {
			case 'input':
				return new Text( $this->type, $option );
			case 'select':
				return new Select( $this->type, $option );
			case 'textarea':
				return new Textarea( $this->type, $option );
			case 'checkbox':
				return new Checkbox( $this->type, $option );
			case 'checkbox-group':
				return new CheckboxGroup( $this->type, $option );
		}
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
	 * @return Field[]
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
	 * Section
	 *   Field Lists/Rows array
	 *     Left-side Label string
	 *     Fields array
	 *       Field
	 */
}
