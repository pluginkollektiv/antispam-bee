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
	 * @param string    $slug     Slug for the tab.
	 * @param string    $title    Title for the tab.
	 * @param Section[] $sections An array of Section objects.
	 */
	public function __construct( string $slug, string $title, array $sections = [] ) {
		$this->slug     = $slug;
		$this->title    = $title;
		$this->sections = $sections;
	}

	/**
	 * Get the slug.
	 *
	 * @return string The tab slug.
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Get the title.
	 *
	 * @return string The tab title.
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get the sections.
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
	 *
	 * @return void
	 */
	public function add_section( Section $section ): void {
		$this->sections[] = $section;
	}
}
