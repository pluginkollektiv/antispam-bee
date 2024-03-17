<?php
/**
 * The Checkbox Field group for the admin UI.
 *
 * @package AntispamBee\Admin\Fields
 */

namespace AntispamBee\Admin\Fields;

use AntispamBee\Admin\RenderElement;
use AntispamBee\Helpers\Settings;

/**
 * Checkbox group.
 */
class CheckboxGroup extends Field implements RenderElement {
	/**
	 * Render HTML.
	 */
	public function render() {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

		$options = isset( $this->option['options'] ) ? $this->option['options'] : [];
		if ( ! is_array( $options ) ) {
			return;
		}

		printf(
			'<p class="asb-checkbox-group-label"><strong>%s</strong></p>',
			$this->get_label()
		);
		foreach ( $options as $key => $value ) {
			printf(
				'<label for="%1$s">
						<input type="checkbox" id="%1$s" name="%1$s" %2$s />%3$s
					</label><br>',
				esc_attr( $this->get_name() . '[' . $key . ']' ),
				checked( 'on', $this->get_custom_value( $key ), false ),
				esc_html( $value )
			);
		}
		$this->maybe_show_description();
	}

	/**
	 * Get Value.
	 *
	 * @param string $key Option key.
	 *
	 * @return mixed Value stored in database.
	 */
	protected function get_custom_value( $key ) {
		$options = Settings::get_option( "{$this->controllable_option_name}", $this->type );

		return isset( $options[ $key ] ) ? $options[ $key ] : null;
	}
}
