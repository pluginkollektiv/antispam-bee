<?php
declare(strict_types=1);

namespace Pluginkollektiv\AntispamBee\Tests\Filter;

use PHPUnit\Framework\TestCase;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Filter\Selfping;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;

use Brain\Monkey\Functions;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

class SelfpingTest extends TestCase {


    public function test_url_is_not_home() {

        $option_factory = \Mockery::mock(OptionInterface::class);
        $testee = new Selfping($option_factory);

        Functions\expect('home_url')->andReturn('https://other-domain.com');

        $data = \Mockery::mock(DataInterface::class);
        $data->expects('website')->andReturn('https://example.com/?p=1');

        self::assertEquals(0, $testee->filter($data));
    }
    public function test_url_is_no_post() {

        $website = 'https://example.com/?p=1';
        $option_factory = \Mockery::mock(OptionInterface::class);
        $testee = new Selfping($option_factory);

        Functions\expect('home_url')
            ->andReturn('https://example.com/');

        Functions\expect('url_to_postid')
            ->andReturnUsing( function($url) use ($website){
                if ( $website !== $url ) {
                    return 1;
                }
                return false;
            });

        $data = \Mockery::mock(DataInterface::class);
        $data->expects('website')->andReturn($website);

        self::assertEquals(0, $testee->filter($data));
    }
}
