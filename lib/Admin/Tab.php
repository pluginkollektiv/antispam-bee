<?php

namespace AntispamBee\Admin;

/**
 * Sections for admin.
 */
class Tab {
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
	 * Fields.
	 *
	 * @var Section[]
	 */
	private $sections;

	/**
	 * Initialize the tab.
	 *
	 * @param string    $slug    Title for tab
	 * @param string    $title    Title for tab
	 * @param Section[] $sections Sections object array.
	 */
	public function __construct( $slug, $title, $sections ) {
		$this->slug  = $slug;
		$this->title = $title;
		$this->sections = $sections;
	}

	/**
	 * Get slug.
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
	 * Get sections.
	 *
	 * @return Section[]
	 */
	public function get_sections() {
		return $this->sections;
	}

	public function add_section( Section $section ) {
		$this->sections[] = $section;
	}
}
