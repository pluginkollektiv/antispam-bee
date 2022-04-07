<?php
/**
 * The admin UI section.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Admin\Fields\Select;
use AntispamBee\Admin\Fields\Text;
use AntispamBee\Admin\Fields\Textarea;
use AntispamBee\Helpers\InterfaceHelper;
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
	 * @var Field[]
	 */
	private $fields = [];

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

	public function add_fields( $fields ) {
		$this->fields = array_merge( $this->fields, $fields );
	}

	public function add_controllables( $controllables ) {
		$this->generate_fields( $controllables );
	}

	private function generate_fields( $controllables ) {
		$fields = [];
		foreach ( $controllables as $controllable ) {
			// Todo: Generate checkbox to activate/deactivate rule

			$options = InterfaceHelper::call( $controllable, 'controllable', 'get_options' );
			if ( empty( $options ) ) {
				continue;
			}

			foreach ( $options as $option ) {
				$fields[] = $this->generate_field( $option );
			}
		}

		$this->fields = array_merge( $this->fields, $fields );
	}

	private function generate_field( $option ) {
		switch ( $option['type'] ) {
			case 'input':
				return new Text( $this->type . '_' . $option['option_name'], $option['label'], '' );
			case 'select':
				return new Select( $this->type . '_' . $option['option_name'], $option['label'],
					'Hold CTRL to select multiple entries', $option['options'], false );
			case 'textarea':
				return new Textarea( $this->type . '_' . $option['option_name'], $option['label'],
					$option['label'] );
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
	public function get_fields() {
		return $this->fields;
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
}
