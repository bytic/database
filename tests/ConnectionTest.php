<?php

namespace Nip\Database\Tests;

use Nip\Database\Adapters\MySQLi;
use Nip\Database\Connections\Connection;
use Nip\Database\Connections\ConnectionFactory;

/**
 * Class ConnectionTest
 * @package Nip\Database\Tests
 */
class ConnectionTest extends AbstractTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var Connection
     */
    protected $connection;

    public function testNewAdapter()
    {
        static::assertInstanceOf(
            \Nip\Database\Adapters\MySQLi::class,
            $this->connection->newAdapter('MySQLi')
        );
    }

    public function testGetAdapterClass()
    {
        static::assertEquals(
            '\Nip\Database\Adapters\MySQLi',
            $this->connection->getAdapterClass('MySQLi')
        );
    }

    /**
     * @return array
     */
    public function newQueryProvider()
    {
        $types = ['select', 'insert', 'delete'];
        $return = [];
        foreach ($types as $type) {
            $return[] = [$type, 'Nip\Database\Query\\' . ucfirst($type)];
        }
        return $return;
    }

    /**
     * @dataProvider newQueryProvider
     *
     * @param $type
     * @param $class
     */
    public function testNewQuery($type, $class)
    {
        $query = $this->connection->newQuery($type);
        static::assertInstanceOf($class, $query);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $params = [
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'user' => 'root',
            'password' => 'password',
            'port' => '1234',
        ];
        $factory = new ConnectionFactory();
        $this->connection = $factory->make($params);
    }
}
