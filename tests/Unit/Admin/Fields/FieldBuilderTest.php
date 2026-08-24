<?php

namespace AntispamBee\Tests\Unit\Admin\Fields;

use AntispamBee\Admin\Fields\FieldBuilder;
use AntispamBee\Admin\Fields\FieldOptions;
use AntispamBee\Admin\Fields\FieldType;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Unit tests for {@see FieldBuilder}.
 */
class FieldBuilderTest extends TestCase {

	/**
	 * Each factory method returns an option pre-populated with its field type,
	 * so the renderer can switch on it without the caller setting it by hand.
	 */
	public function test_factories_preselect_the_field_type(): void {
		$cases = [
			'checkbox'       => FieldType::CHECKBOX,
			'checkbox_group' => FieldType::CHECKBOX_GROUP,
			'input'          => FieldType::INPUT,
			'inline'         => FieldType::INLINE,
			'select'         => FieldType::SELECT,
			'textarea'       => FieldType::TEXTAREA,
		];

		foreach ( $cases as $method => $type ) {
			$options = FieldBuilder::$method();

			self::assertInstanceOf( FieldOptions::class, $options );
			self::assertSame( $type, $options->type() );
		}
	}
}
