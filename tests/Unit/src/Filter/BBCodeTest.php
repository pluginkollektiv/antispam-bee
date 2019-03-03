<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Tests\Filter;

use PHPUnit\Framework\TestCase;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Filter\BBCodeSpam;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;

class BBCodeTest extends TestCase {

	public function test_is_bbcode() {

		$option_factory = \Mockery::mock(OptionFactory::class);
		$testee = new BBCodeSpam($option_factory);
		$data = \Mockery::mock(DataInterface::class);
		$data->expects('text')->andReturn('This is a [url="http://example.com/"]BBCode[/url] example.');

		$result = $testee->filter($data);
		self::assertEquals(1, $result);
	}

	public function test_is_not_bbcode() {

		$option_factory = \Mockery::mock(OptionFactory::class);
		$testee = new BBCodeSpam($option_factory);
		$data = \Mockery::mock(DataInterface::class);
		$data->expects('text')->andReturn('This is not a BBCode example.');

		$result = $testee->filter($data);
		self::assertEquals(0, $result);
	}
}