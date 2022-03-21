<?php

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;

/**
 * Text field.
 */
class Text extends Field implements RenderElement {
    /**
     * Get HTML.
     * 
     * @return string Elment HTML.
     */
    public function render() {
        echo '<input type="checkbox" name="' . $this->get_name() . '" value="' . $this->get_value() . '" />';
        $this->maybe_show_description();
    }
}
