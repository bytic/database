<?php

namespace Nip\Database\Tests\Manager;

use Nip\Database\Connections\Connection;
use Nip\Database\DatabaseManager;
use Nip\Database\Tests\AbstractTest;

/**
 * Class HasConnectionsTest
 * @package Nip\Database\Tests\Manager
 */
class HasConnectionsTest extends AbstractTest
{
    public function test_getDefaultConnection()
    {
        $manager = new DatabaseManager();

        $connection = new Connection(false);
        $manager->setConnection($connection, 'main');

        $connectionFromManager = $manager->connection();
        self::assertSame($connection, $connectionFromManager);
    }
}
