<?php

namespace Nip\Database\Tests;

use Nip\Database\Adapters\MySQLi;
use Nip\Database\Connections\Connection;

/**
 * Class ConnectionTest
 * @package Nip\Database\Tests
 *
 * @property Connection $object
 */
class ConnectionTest extends AbstractTest
{

    public function testNewAdapter()
    {
        static::assertInstanceOf(MySQLi::class, $this->object->newAdapter('MySQLi'));
    }

    public function testGetAdapterClass()
    {
        static::assertEquals('\Nip\Database\Adapters\MySQL', $this->object->getAdapterClass('MySQL'));
        static::assertEquals('\Nip\Database\Adapters\MySQLi', $this->object->getAdapterClass('MySQLi'));
    }

    /**
     * @return array
     */
    public function newQueryProvider()
    {
        $types = ['select', 'insert', 'delete'];
        $return = [];
        foreach ($types as $type) {
            $return[] = [$type, 'Nip\Database\Query\\'.ucfirst($type)];
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
        $query = $this->object->newQuery($type);
        static::assertInstanceOf($class, $query);
    }

    protected function setUp()
    {
        $this->object = new Connection();
    }
}
