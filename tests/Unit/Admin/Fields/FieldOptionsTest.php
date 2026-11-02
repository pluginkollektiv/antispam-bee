<?php

namespace AntispamBee\Tests\Unit\Admin\Fields;

use AntispamBee\Admin\Fields\FieldBuilder;
use AntispamBee\Admin\Fields\FieldOptions;
use AntispamBee\Admin\Fields\FieldType;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Unit tests for {@see FieldOptions}.
 */
class FieldOptionsTest extends TestCase {

	/**
	 * The options are initialized to safe defaults so call sites never have to
	 * guard against missing keys.
	 */
	public function test_defaults(): void {
		$options = new FieldOptions( FieldType::INPUT );

		self::assertSame( FieldType::INPUT, $options->type() );
		self::assertSame( '', $options->get_option_name() );
		self::assertSame( '', $options->get_label() );
		self::assertSame( [], $options->get_label_kses() );
		self::assertSame( '', $options->get_description() );
		self::assertSame( '', $options->get_placeholder() );
		self::assertNull( $options->get_default() );
		self::assertSame( [], $options->get_choices() );
		self::assertFalse( $options->is_multiple() );
		self::assertSame( '', $options->get_valid_for() );
		self::assertNull( $options->get_sanitize() );
		self::assertNull( $options->get_persist() );
		self::assertNull( $options->get_load() );
		self::assertNull( $options->get_input() );
		self::assertSame( 'text', $options->get_input_type() );
		self::assertSame( '', $options->get_input_size() );
	}

	/**
	 * An invalid type is rejected by the enum factory, so the value object can
	 * never carry a type the renderer does not know.
	 */
	public function test_invalid_type_is_rejected(): void {
		$options = new FieldOptions( 'not-a-type' );

		self::assertSame( '', $options->type() );
	}

	/**
	 * The fluent setters return the same instance, so a chain keeps building on
	 * one object, and each setter is reflected by its getter.
	 */
	public function test_fluent_setters_chain_and_round_trip(): void {
		$options = FieldBuilder::checkbox_group();

		self::assertSame( $options, $options->option_name( 'reasons' ) );
		self::assertSame( $options, $options->label( 'Reasons' ) );
		self::assertSame( $options, $options->label_kses( [ 'a' => [ 'href' => true ] ] ) );
		self::assertSame( $options, $options->description( 'Pick the reasons.' ) );
		self::assertSame( $options, $options->placeholder( 'Type here' ) );
		self::assertSame( $options, $options->default_value( 'de' ) );
		self::assertSame( $options, $options->choices( [ 'de' => 'German' ] ) );
		self::assertSame( $options, $options->multiple( true ) );
		self::assertSame( $options, $options->valid_for( 'comment' ) );

		$sanitize = static function ( $value ) {
			return $value;
		};
		self::assertSame( $options, $options->sanitize( $sanitize ) );
		self::assertSame( $options, $options->persist( $sanitize ) );
		self::assertSame( $options, $options->load( $sanitize ) );
		self::assertSame( $options, $options->input_type( 'number' ) );
		self::assertSame( $options, $options->input_size( 'small' ) );

		self::assertSame( 'reasons', $options->get_option_name() );
		self::assertSame( 'Reasons', $options->get_label() );
		self::assertSame( [ 'a' => [ 'href' => true ] ], $options->get_label_kses() );
		self::assertSame( 'Pick the reasons.', $options->get_description() );
		self::assertSame( 'Type here', $options->get_placeholder() );
		self::assertSame( 'de', $options->get_default() );
		self::assertSame( [ 'de' => 'German' ], $options->get_choices() );
		self::assertTrue( $options->is_multiple() );
		self::assertSame( 'comment', $options->get_valid_for() );
		self::assertSame( $sanitize, $options->get_sanitize() );
		self::assertSame( $sanitize, $options->get_persist() );
		self::assertSame( $sanitize, $options->get_load() );
		self::assertSame( 'number', $options->get_input_type() );
		self::assertSame( 'small', $options->get_input_size() );
	}
}
