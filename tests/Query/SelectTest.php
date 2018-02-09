<?php

namespace Nip\Database\Tests\Query;

use Mockery as m;
use Nip\Database\Connections\Connection;
use Nip\Database\Query\Select;
use Nip\Database\Tests\AbstractTest;

/**
 * Class SelectTest
 * @package Nip\Database\Tests\Query
 */
class SelectTest extends AbstractTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Select
     */
    protected $selectQuery;

    public function testSelectSimple()
    {
        $array = ['id, name as new_name', 'table2.test', 'MAX(pos) as pos'];
        call_user_func_array([$this->selectQuery, 'cols'], $array);
        $this->selectQuery->from('table x')->where('id = 5');

        static::assertEquals(
            'SELECT id, name as new_name, table2.test, MAX(pos) as pos FROM table x WHERE id = 5',
            $this->selectQuery->assemble()
        );
    }

    public function testSimpleSelectDistinct()
    {
        $this->selectQuery->cols('id, name')->options('distinct')->from('table x')->where('id = 5');
        static::assertEquals(
            "SELECT DISTINCT id, name FROM table x WHERE id = 5",
            $this->selectQuery->assemble()
        );
    }

    public function testWhereAndWhere()
    {
        $this->selectQuery->cols('id, name')->from('table x');
        $this->selectQuery->where('id = 5')->where("active = 'yes'");
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 AND active = 'yes'",
            $this->selectQuery->assemble()
        );
    }

    public function testWhereOrWhere()
    {
        $this->selectQuery->cols('id, name')->from('table x');
        $this->selectQuery->where('id = 5')->orWhere('id = 7');
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 OR id = 7",
            $this->selectQuery->assemble()
        );
    }

    public function testInitializeCondition()
    {
        $condition = $this->selectQuery->getCondition("lorem ipsum");
        static::assertThat($condition, $this->isInstanceOf("Nip\Database\Query\Condition\Condition"));
    }

    public function testNested()
    {
        $this->selectQuery->from("table1");

        $query = $this->connection->newQuery();
        $query->from("table2");
        $query->where("id != 5");

        $this->selectQuery->where("id NOT IN ?", $query);

        static::assertEquals(
            "SELECT * FROM `table1` WHERE id NOT IN (SELECT * FROM `table2` WHERE id != 5)",
            $this->selectQuery->assemble()
        );
    }

    public function testUnion()
    {
        $this->selectQuery->from("table1");

        $query = $this->connection->newQuery();
        $query->from("table2");

        $union = $this->selectQuery->union($query);

        static::assertEquals("SELECT * FROM `table1` UNION SELECT * FROM `table2`", $union->assemble());
    }

    public function testHasPart()
    {
        $this->selectQuery->cols('id, name');
        self::assertTrue($this->selectQuery->hasPart('cols'));

        $this->selectQuery->setCols('id, name');
        self::assertTrue($this->selectQuery->hasPart('cols'));

        $this->selectQuery->limit('');
        self::assertFalse($this->selectQuery->hasPart('limit'));

        $this->selectQuery->limit('6');
        self::assertTrue($this->selectQuery->hasPart('limit'));

        self::assertFalse($this->selectQuery->hasPart('where'));
    }

    public function testLimit()
    {
        $this->selectQuery->cols('id, name')->from('table x');
        $this->selectQuery->where('id = 5')->where("active = 'yes'");
        $this->selectQuery->limit(5);

        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 AND active = 'yes' LIMIT 5",
            $this->selectQuery->assemble()
        );

        $this->selectQuery->limit(5, 10);
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 AND active = 'yes' LIMIT 5,10",
            $this->selectQuery->assemble()
        );
    }

    protected function setUp()
    {
        parent::setUp();
        $this->selectQuery = new Select();

        $adapterMock = m::mock('Nip\Database\Adapters\MySQLi')->shouldDeferMissing();
        $adapterMock->shouldReceive('cleanData')->andReturnUsing(function ($data) {
            return $data;
        });
        $this->connection = new Connection(false);
        $this->connection->setAdapter($adapterMock);
        $this->selectQuery->setManager($this->connection);
    }
}
