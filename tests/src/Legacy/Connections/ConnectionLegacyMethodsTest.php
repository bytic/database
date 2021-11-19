<?php

namespace Nip\Database\Tests\Legacy\Connections;

use Nip\Database\Connections\ConnectionFactory;
use Nip\Database\Result;
use Nip\Database\Tests\AbstractTest;

/**
 * Class ConnectionLegacyMethodsTest
 * @package Nip\Database\Tests\Legacy\Connections
 */
class ConnectionLegacyMethodsTest extends AbstractTest
{
    public function test_executeQuery()
    {
        $connection = (new ConnectionFactory())->make([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $result = $connection->executeQuery('CREATE TABLE groups (
   group_id INTEGER PRIMARY KEY,
   name TEXT NOT NULL
)');
        self::assertInstanceOf(Result::class, $result);
        self::assertSame(0, $result->rowCount());
    }
}

