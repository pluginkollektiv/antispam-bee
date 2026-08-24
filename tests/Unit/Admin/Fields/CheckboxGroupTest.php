<?php

namespace AntispamBee\Tests\Unit\Admin\Fields;

use AntispamBee\Admin\Fields\CheckboxGroup;
use AntispamBee\Helpers\Settings;
use AntispamBee\PostProcessors\DeleteForReasons;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Functions\when;

if ( ! defined( 'AntispamBee\MAIN_PLUGIN_FILE' ) ) {
	define( 'AntispamBee\MAIN_PLUGIN_FILE', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR . 'antispam_bee.php' );
}

/**
 * Unit tests for the {@see CheckboxGroup} renderer.
 */
class CheckboxGroupTest extends TestCase {

	/**
	 * Stored options for the `comment` section, as they would come from the database.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private $stored_options = [ 'comment' => [] ];

	protected function set_up(): void {
		parent::set_up();

		$this->stored_options = [ 'comment' => [] ];

		// Report the database as current so reading the settings never triggers the v2 migration.
		when( 'get_file_data' )->justReturn( [ 'Version' => '3.0.0-beta.1' ] );

		when( 'get_option' )->alias(
			function ( $name, $default = false ) {
				if ( 'antispambee_db_version' === $name ) {
					return '3.0.0-beta.1';
				}

				if ( Settings::OPTION_NAME === $name ) {
					return $this->stored_options;
				}

				return $default;
			}
		);

		// Keep the escaping and checked() calls out of the captured output's way.
		when( 'esc_attr' )->alias(
			static function ( $str ) {
				return is_scalar( $str ) ? htmlspecialchars( (string) $str, ENT_QUOTES ) : '';
			}
		);
		when( 'esc_html' )->returnArg();
		when( 'checked' )->alias(
			static function ( $checked, $current = true, $echo = true ) {
				$result = ( $checked === $current ) ? 'checked="checked"' : '';

				if ( $echo ) {
					echo $result;
				}

				return $result;
			}
		);
	}

	/**
	 * Render a checkbox group and return the captured HTML.
	 *
	 * @param array<string, mixed> $option Field options.
	 *
	 * @return string The rendered HTML.
	 */
	private function render( array $option ): string {
		$field = new CheckboxGroup( 'comment', $option, DeleteForReasons::class );

		ob_start();
		$field->render();
		return (string) ob_get_clean();
	}

	public function test_output_is_unchanged_without_disabled_keys(): void {
		$option = [
			'label'       => 'Reasons',
			'type'        => 'checkbox-group',
			'options'     => [ 'bbcode' => 'BBCode', 'nohtml' => 'No HTML' ],
			'option_name' => 'reasons',
		];

		$html = $this->render( $option );

		self::assertStringContainsString( 'type="checkbox"', $html, 'each row should render a checkbox input' );
		self::assertStringNotContainsString( 'disabled', $html, 'no row should be disabled when disabled_keys is absent' );
		self::assertStringNotContainsString( 'asb-checkbox-group-disabled', $html, 'no label should carry the disabled class' );
	}

	public function test_disabled_rule_renders_disabled_input_and_label_class(): void {
		$option = [
			'label'         => 'Reasons',
			'type'          => 'checkbox-group',
			'options'       => [ 'bbcode' => 'BBCode', 'nohtml' => 'No HTML' ],
			'disabled_keys' => [ 'bbcode' => true ],
			'option_name'   => 'reasons',
		];

		$html = $this->render( $option );

		self::assertStringContainsString(
			'<input type="checkbox" id="antispam_bee_options[comment][post_processor_asb_delete_for_reasons_reasons][bbcode]" name="antispam_bee_options[comment][post_processor_asb_delete_for_reasons_reasons][bbcode]"  disabled />',
			$html,
			'the inactive rule row should render a disabled checkbox'
		);
		self::assertStringContainsString( 'asb-checkbox-group-disabled', $html, 'the inactive rule label should carry the disabled class' );

		self::assertStringNotContainsString(
			'name="antispam_bee_options[comment][post_processor_asb_delete_for_reasons_reasons][nohtml]"  disabled />',
			$html,
			'the active rule row should not be disabled'
		);
	}

	public function test_stored_checked_choice_survives_when_rule_becomes_inactive(): void {
		// The user previously stored `on` for `bbcode` while the rule was active.
		$this->stored_options['comment']['post_processor_asb_delete_for_reasons_reasons'] = [ 'bbcode' => 'on' ];

		$option = [
			'label'         => 'Reasons',
			'type'          => 'checkbox-group',
			'options'       => [ 'bbcode' => 'BBCode', 'nohtml' => 'No HTML' ],
			'disabled_keys' => [ 'bbcode' => true ],
			'option_name'   => 'reasons',
		];

		$html = $this->render( $option );

		// The row is both checked (preserved user intent) and disabled (rule no longer fires).
		self::assertStringContainsString( 'checked="checked"', $html, 'a stored choice should still render as checked' );
		self::assertStringContainsString( 'disabled', $html, 'the row should be disabled while the rule is inactive' );
	}

	public function test_stored_off_value_renders_unchecked_but_still_disabled(): void {
		$this->stored_options['comment']['post_processor_asb_delete_for_reasons_reasons'] = [ 'bbcode' => 'off' ];

		$option = [
			'label'         => 'Reasons',
			'type'          => 'checkbox-group',
			'options'       => [ 'bbcode' => 'BBCode', 'nohtml' => 'No HTML' ],
			'disabled_keys' => [ 'bbcode' => true ],
			'option_name'   => 'reasons',
		];

		$html = $this->render( $option );

		self::assertStringNotContainsString( 'checked="checked"', $html, 'a stored off value should not render as checked' );
		self::assertStringContainsString( 'disabled', $html, 'the row should still be disabled while its rule is inactive' );
	}

	public function test_row_uses_escaped_name_and_id(): void {
		$option = [
			'label'       => 'Reasons',
			'type'        => 'checkbox-group',
			'options'     => [ 'bbcode' => 'BBCode' ],
			'option_name' => 'reasons',
		];

		$html = $this->render( $option );

		self::assertStringContainsString(
			'name="antispam_bee_options[comment][post_processor_asb_delete_for_reasons_reasons][bbcode]"',
			$html,
			'the input name should be the full controllable path with the slug suffix'
		);
		self::assertStringContainsString(
			'id="antispam_bee_options[comment][post_processor_asb_delete_for_reasons_reasons][bbcode]"',
			$html,
			'the input id should match the name'
		);
	}
}
