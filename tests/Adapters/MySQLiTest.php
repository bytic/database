<?php
declare(strict_types=1);

namespace Nip\Database\Tests\Adapters;

use Nip\Database\Adapters\MySQLi;
use PHPUnit\Framework\TestCase;

class MySQLiTest extends TestCase
{
    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider data_quote
     */
    public function test_qoute($input, $expected)
    {
        $adapter = \Mockery::mock(MySQLi::class)->makePartial();
        $adapter->shouldReceive('cleanData')->andReturnArg(0);

        self::assertSame($expected, $adapter->quote($value));
    }

    public function data_quote()
    {
        return [
            ['test', '\'test\''],
            ['65e10864', '\'65e10864\''],
            ['242e8116', '\'242e8116\''],
            ['24a','\'24a\''],
            ['a24','\'a24\''],
            [0, '0'],
            ['0', '0'],
            [0.5, '0.5'],
            ['0.5','0.5'],
            [1, 1],
            ['1', 1],
            [1.1, 1.1],
            ['1.0', 1.0],
        ];
    }
}
