<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Tests\Repository;

use PHPUnit\Framework\TestCase;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

class ReasonsRepositoryTest extends TestCase {

	public function test_add_reason() {

		$testee = new ReasonsRepository();
		$result = $testee->add_reason('reason', .1);

		self::assertTrue($result);
		self::assertTrue(in_array('reason', $testee->get_reasons(), true));
		self::assertEquals(.1, $testee->probability_by_reason('reason'));
	}

	public function test_cant_add_reason_twice() {

		$testee = new ReasonsRepository();
		$testee->add_reason('reason', .2);
		$result = $testee->add_reason('reason', .1);

		self::assertFalse($result);
		self::assertTrue(in_array('reason', $testee->get_reasons(), true));
		self::assertEquals(.2, $testee->probability_by_reason('reason'));
	}

	public function test_calc_total_probability() {

		$testee = new ReasonsRepository();
		$testee->add_reason('reason-0', .8);
		$testee->add_reason('reason-1', .2);
		$testee->add_reason('reason-2', .1);

		self::assertEquals(1.1, $testee->total_probability());
	}

	public function test_get_all_reasons() {

		$testee = new ReasonsRepository();
		$testee->add_reason('reason-1', .2);
		$testee->add_reason('reason-2', .1);

		$result = $testee->all();
		self::assertTrue(isset($result['reason-1']));
		self::assertTrue(isset($result['reason-2']));
		self::assertEquals(.2, $result['reason-1']);
		self::assertEquals(.1, $result['reason-2']);
	}
}
