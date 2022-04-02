<?php
/**
 * The admin UI section.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

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
	private $fields;

	/**
	 * Initializung Tab.
	 *
	 * @param string $name Name of the tab.
	 * @param string $title Title for tab.
	 * @param string $description Description of the tab.
	 * @param array  $fields Fields in the tab.
	 */
	public function __construct( $name, $title, $description = '', $fields = [] ) {
		$this->name        = $name;
		$this->title       = $title;
		$this->fields      = $fields;
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
