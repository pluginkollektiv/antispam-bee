<?php

namespace AntispamBee\Admin;

/**
 * Tab for admin.
 */
class Tab {
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
     * Sections.
     * 
     * @var Section[]
     */
    private $sections;

    /**
     * Initializung Tab.
     * 
     * @param string $label Title for tab
     * @param Section[] $sections Sections object array.
     */
    public function __construct( $name, $title, $sections ) {
        $this->name     = $name;
        $this->title    = $title;
        $this->sections = $sections;
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
     * Get sections.
     * 
     * @return Section[]
     */
    public function get_sections() {
        return $this->sections;
    }
}