<?php
/**
 * Settings tab model.
 *
 * @package AntispamBee\Admin
 */

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
	 * @param string    $slug Title for tab.
	 * @param string    $title Title for tab.
	 * @param Section[] $sections Sections object array.
	 */
	public function __construct( string $slug, string $title, array $sections = [] ) {
		$this->slug     = $slug;
		$this->title    = $title;
		$this->sections = $sections;
	}

	/**
	 * Get slug.
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
	 * Get sections.
	 *
	 * @return Section[]
	 */
	public function get_sections(): array {
		return $this->sections;
	}

	/**
	 * Add a section to the settings tab.
	 *
	 * @param Section $section Section to add.
	 * @return void
	 */
	public function add_section( Section $section ): void {
		$this->sections[] = $section;
	}
}
