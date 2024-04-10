<?php

namespace Nip\Database\Tests\Adapters;

use Nip\Database\Adapters\MySQLi;
use PHPUnit\Framework\TestCase;

class MySQLiTest extends TestCase
{
    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider data_qoute
     */
    public function test_qoute($input, $expected)
    {
        $adapter = \Mockery::mock(MySQLi::class)->makePartial();
        $adapter->shouldReceive('cleanData')->andReturnArg(0);

        $this->assertEquals($expected, $adapter->quote($input));
    }

    public function data_qoute()
    {
        return [
            ['test', '\'test\''],
            ['65e10864', '\'65e10864\''],
            ['24a','\'24a\''],
            ['a24','\'a24\''],
            [0, '0'],
            [0.5, '0.5'],
            [1, 1],
            ['1', '1'],
            ['0', '0'],
            ['0.5','0.5'],
        ];
    }
}
