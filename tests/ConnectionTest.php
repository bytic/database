<?php

namespace Nip\Database\Tests;

use Nip\Database\Adapters\MySQLi;
use Nip\Database\Connection;

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
    protected $_object;

    public function testNewAdapter()
    {
        static::assertInstanceOf(MySQLi::class, $this->_object->newAdapter('MySQLi'));
    }

    public function testGetAdapterClass()
    {
        static::assertEquals('\Nip\Database\Adapters\MySQL', $this->_object->getAdapterClass('MySQL'));
        static::assertEquals('\Nip\Database\Adapters\MySQLi', $this->_object->getAdapterClass('MySQLi'));
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
        $query = $this->_object->newQuery($type);
        static::assertInstanceOf($class, $query);
    }

    protected function setUp()
    {
        $this->_object = new Connection();
    }
}
