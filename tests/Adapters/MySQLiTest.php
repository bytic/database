<?php
declare(strict_types=1);

namespace Nip\Database\Tests\Adapters;

use Nip\Database\Adapters\MySQLi;
use PHPUnit\Framework\TestCase;

class MySQLiTest extends TestCase
{
    /**
     * @param $value
     * @param $expected
     * @return void
     * @dataProvider data_quote
     */
    public function test_quote($value, $expected)
    {
        $adapter = \Mockery::mock(MySQLi::class)->makePartial();
        $adapter->shouldReceive('cleanData')->andReturnArg(0);
        self::assertSame($expected, $adapter->quote($value));
    }

    public function data_quote()
    {
        return [
            [1, 1],
            ['1', 1],
            [1.1, 1.1],
            ['1.0', 1.0],
            ['242e8116', '\'242e8116\''],
            ['test', '\'test\''],
        ];
    }
}
