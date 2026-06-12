<?php
/**
 * The interface for rendered elements.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

/**
 * Render Element.
 */
interface RenderElement {
	/**
	 * Render function.
	 *
	 * @return void
	 */
	public function render(): void;
}
