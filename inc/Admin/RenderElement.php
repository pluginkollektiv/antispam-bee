<?php

namespace AntispamBee\Admin;

/**
 * Render Element.
 */
interface RenderElement {
    /**
     * Render function.
     * 
     * @return string Rendered Element.
     */
    public function render();
}