<?php

namespace Nip\Database\Tests\Connections;

use Nip\Database\Connections\Connection;
use Nip\Database\Connections\ConnectionFactory;
use Nip\Database\Tests\AbstractTest;

/**
 * Class ConnectionFactoryTest
 * @package Nip\Database\Tests\Connections
 */
class ConnectionFactoryTest extends AbstractTest
{
    public function test_make()
    {
        $connection = (new ConnectionFactory())->make([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        self::assertInstanceOf(Connection::class, $connection);
    }

}