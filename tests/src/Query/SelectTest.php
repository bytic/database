<?php

/** @noinspection SqlResolve */

/** @noinspection SqlNoDataSourceInspection */

namespace Nip\Database\Tests\Query;

use Mockery as m;
use Nip\Database\Adapters\MySQLi;
use Nip\Database\Connections\Connection;
use Nip\Database\Connections\ConnectionFactory;
use Nip\Database\Query\Insert;
use Nip\Database\Query\Select;
use Nip\Database\Tests\AbstractTest;
use Nip\Database\Tests\TestUtil;

/**
 * Class SelectTest
 * @package Nip\Database\Tests\Query
 *
 * @property Select $query
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
    protected $query;

    public function testSelectSimple()
    {
        $array = ['id, name as new_name', 'table2.test', 'MAX(pos) as pos'];
        call_user_func_array([$this->query, 'cols'], $array);
        $this->query->from('table x')->where('id = 5');

        static::assertEquals(
            'SELECT id, name as new_name, table2.test, MAX(pos) as pos FROM table x WHERE id = 5',
            $this->query->assemble()
        );
    }

    public function testSimpleSelectDistinct()
    {
        $this->query
            ->cols('id, name')
            ->options('distinct')
            ->from('table x')
            ->where('id = 5');
        static::assertEquals(
            "SELECT DISTINCT id, name FROM table x WHERE id = 5",
            $this->query->assemble()
        );
    }

    public function testWhereAndWhere()
    {
        $this->query->cols('id, name')->from('table x');
        $this->query->where('id = 5')->where("active = 'yes'");
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 AND active = 'yes'",
            $this->query->assemble()
        );

        $this->query->cols('id, name')->from('table x');
        $this->query->where('id = 5')->where("active = 'yes'");
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 AND active = 'yes'",
            $this->query->assemble()
        );
    }

    public function testWhereOrWhere()
    {
        $this->query->cols('id, name')->from('table x');
        $this->query->where('id = 5')->orWhere('id = 7');
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 OR id = 7",
            $this->query->assemble()
        );
        $this->query->cols('id, name')->from('table x');
        $this->query->where('id = 5')->orWhere('id = 7');
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 OR id = 7",
            $this->query->assemble()
        );
    }

    public function testInitializeCondition()
    {
        $condition = $this->query->getCondition("lorem ipsum");
        static::assertThat($condition, $this->isInstanceOf("Nip\Database\Query\Condition\Condition"));
    }

    public function testNested()
    {
        $this->query->from("table1");
        $this->query->from("table1");

        $query = $this->connection->newQuery();
        $query->from("table2");
        $query->where("id != 5");

        $this->query->where("id NOT IN ?", $query);

        static::assertEquals(
            "SELECT * FROM `table1` WHERE id NOT IN (SELECT * FROM `table2` WHERE id != 5)",
            $this->query->assemble()
        );
    }

    public function testUnion()
    {
        $this->query->from("table1");

        $query = $this->connection->newQuery();
        $query->from("table2");

        $union = $this->query->union($query);

        static::assertEquals("SELECT * FROM `table1` UNION SELECT * FROM `table2`", $union->assemble());
    }

    public function testJoinTableName()
    {
        $this->query->from("table1");
        $this->query->join("table2", ['id', 'id_table1']);

        static::assertEquals(
            "SELECT * FROM `table1` JOIN `table2` ON `table1`.`id` = `table2`.`id_table1`",
            $this->query->assemble()
        );
    }

    public function testJoinTableNameWithAlias()
    {
        $this->query->from("table1");
        $this->query->join(["table2", "alias"], ['id', 'id_table1']);

        static::assertEquals(
            "SELECT * FROM `table1` JOIN `table2` AS `alias` ON `table1`.`id` = `table2`.`id_table1`",
            $this->query->assemble()
        );
    }

    public function testJoinSubQuery()
    {
        $this->query->from("table1");

        $query = $this->connection->newQuery();
        $query->from("table2");

        $this->query->join([$query, "alias"], ['id', 'id_table1']);

        static::assertEquals(
            'SELECT * FROM `table1` JOIN (SELECT * FROM `table2`) AS `alias` ON `table1`.`id` = `alias`.`id_table1`',
            $this->query->assemble()
        );
    }

    public function testHasPart()
    {
        $this->query->cols('id, name');
        self::assertTrue($this->query->hasPart('cols'));

        $this->query->setCols('id, name');
        self::assertTrue($this->query->hasPart('cols'));

        $this->query->limit('');
        self::assertFalse($this->query->hasPart('limit'));

        $this->query->limit('6');
        self::assertTrue($this->query->hasPart('limit'));

        self::assertFalse($this->query->hasPart('where'));
    }

    public function testLimit()
    {
        $this->query->cols('id, name')->from('table x');
        $this->query->where('id = 5')->where("active = 'yes'");
        $this->query->limit(5);

        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 AND active = 'yes' LIMIT 5",
            $this->query->assemble()
        );

        $this->query->limit(5, 10);
        static::assertEquals(
            "SELECT id, name FROM table x WHERE id = 5 AND active = 'yes' LIMIT 5,10",
            $this->query->assemble()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = TestUtil::getConnection();
        $this->query = new Select($this->connection);
    }
}
