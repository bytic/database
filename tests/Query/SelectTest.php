<?php

/** @noinspection SqlResolve */

/** @noinspection SqlNoDataSourceInspection */

namespace Nip\Database\Tests\Query;

use Mockery as m;
use Nip\Database\Adapters\MySQLi;
use Nip\Database\Connections\Connection;
use Nip\Database\Query\Select;
use Nip\Database\Tests\AbstractTest;

/**
 * Class SelectTest
 * @package Nip\Database\Tests\Query
 *
 * @property Select $object
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
        $this->object->cols('id, name')->from('table x');
        $this->object->where('id = 5')->where("active = 'yes'");
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 AND active = 'yes'",
            $this->object->assemble());
    }

    public function testWhereOrWhere()
    {
        $this->selectQuery->cols('id, name')->from('table x');
        $this->selectQuery->where('id = 5')->orWhere('id = 7');
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 OR id = 7",
            $this->selectQuery->assemble()
        $this->object->cols('id, name')->from('table x');
        $this->object->where('id = 5')->orWhere('id = 7');
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 OR id = 7",
            $this->object->assemble()
        );
    }

    public function testInitializeCondition()
    {
        $condition = $this->object->getCondition("lorem ipsum");
        static::assertThat($condition, $this->isInstanceOf("Nip\Database\Query\Condition\Condition"));
    }

    public function testNested()
    {
        $this->selectQuery->from("table1");
        $this->object->from("table1");

        $query = $this->connection->newQuery();
        $query->from("table2");
        $query->where("id != 5");

        $this->object->where("id NOT IN ?", $query);

        static::assertEquals(
            "SELECT * FROM `table1` WHERE id NOT IN (SELECT * FROM `table2` WHERE id != 5)",
            $this->object->assemble()
        );
    }

    public function testUnion()
    {
        $this->object->from("table1");

        $query = $this->connection->newQuery();
        $query->from("table2");

        $union = $this->object->union($query);

        static::assertEquals("SELECT * FROM `table1` UNION SELECT * FROM `table2`", $union->assemble());
    }

    public function testJoinTableName()
    {
        $this->object->from("table1");
        $this->object->join("table2", ['id', 'id_table1']);

        static::assertEquals(
            "SELECT * FROM `table1` JOIN `table2` ON `table1`.`id` = `table2`.`id_table1`",
            $this->object->assemble()
        );
    }

    public function testJoinTableNameWithAlias()
    {
        $this->object->from("table1");
        $this->object->join(["table2", "alias"], ['id', 'id_table1']);

        static::assertEquals(
            "SELECT * FROM `table1` JOIN `table2` AS `alias` ON `table1`.`id` = `table2`.`id_table1`",
            $this->object->assemble()
        );
    }

    public function testJoinSubQuery()
    {
        $this->object->from("table1");

        $query = $this->connection->newQuery();
        $query->from("table2");

        $this->object->join([$query, "alias"], ['id', 'id_table1']);

        static::assertEquals(
            'SELECT * FROM `table1` JOIN (SELECT * FROM `table2`) AS `alias` ON `table1`.`id` = `alias`.`id_table1`',
            $this->object->assemble()
        );
    }

    public function testHasPart()
    {
        $this->object->cols('id, name');
        self::assertTrue($this->object->hasPart('cols'));

        $this->object->setCols('id, name');
        self::assertTrue($this->object->hasPart('cols'));

        $this->object->limit('');
        self::assertFalse($this->object->hasPart('limit'));

        $this->object->limit('6');
        self::assertTrue($this->object->hasPart('limit'));

        self::assertFalse($this->object->hasPart('where'));
    }

    public function testLimit()
    {
        $this->object->cols('id, name')->from('table x');
        $this->object->where('id = 5')->where("active = 'yes'");
        $this->object->limit(5);

        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 AND active = 'yes' LIMIT 5",
            $this->object->assemble()
        );

        $this->object->limit(5, 10);
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 AND active = 'yes' LIMIT 5,10",
            $this->object->assemble()
        );
    }

    protected function setUp()
    {
        parent::setUp();
        $this->object = new Select();

        $adapterMock = m::mock(MySQLi::class)->shouldDeferMissing();
        $adapterMock->shouldReceive('cleanData')->andReturnUsing(function ($data) {
            return $data;
        });
        $this->connection = new Connection(false);
        $this->connection->setAdapter($adapterMock);
        $this->object->setManager($this->connection);
    }
}
