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
	 * Render the HTML.
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

		$options = $this->option['options'] ?? [];
		if ( ! is_array( $options ) ) {
			return;
		}

		printf(
			'<p class="asb-checkbox-group-label"><strong>%s</strong></p>',
			$this->get_label()
		);

		$disabled_keys = isset( $this->option['disabled_keys'] ) && is_array( $this->option['disabled_keys'] )
			? $this->option['disabled_keys']
			: [];

		foreach ( $options as $key => $value ) {
			$is_disabled = isset( $disabled_keys[ $key ] );
			$label_class = $is_disabled ? ' class="asb-checkbox-group-disabled"' : '';
			$disabled    = $is_disabled ? ' disabled' : '';
			$field_name  = esc_attr( $this->get_name() . '[' . $key . ']' );
			$stored      = $this->get_custom_value( $key );

			printf(
				'<label for="%1$s"%4$s>
						<input type="checkbox" id="%1$s" name="%1$s" %2$s%5$s />%3$s
					</label><br>',
				$field_name,
				checked( 'on', $stored, false ),
				esc_html( $value ),
				$label_class,
				$disabled
			);

			// A disabled checkbox is not submitted and the save replaces the stored reasons, so keep a checked one.
			if ( $is_disabled && 'on' === $stored ) {
				printf( '<input type="hidden" name="%s" value="on" />', $field_name );
			}
		}
		$this->maybe_show_description();
	}

	/**
	 * Get the value.
	 *
	 * @param string $key Option key.
	 *
	 * @return mixed The value stored in the database.
	 */
	protected function get_custom_value( string $key ) {
		$options = Settings::get_option( "{$this->controllable_option_name}", $this->reaction_type );

		return $options[ $key ] ?? null;
	}
}
