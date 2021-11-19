<?php

namespace Nip\Database\Tests\Query;

use Mockery as m;
use Nip\Database\Connections\Connection;
use Nip\Database\Connections\ConnectionFactory;
use Nip\Database\Query\Insert;
use Nip\Database\Tests\AbstractTest;
use Nip\Database\Tests\TestUtil;

/**
 * Class InsertTest
 * @package Nip\Database\Tests\Query
 */
class InsertTest extends AbstractTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var Insert
     */
    protected $object;

    public function test_null()
    {
        $this->object->table("table");
        $this->object->data(["id" => 3, "name" => null]);

        static::assertEquals(
            "INSERT INTO `table` (`id`,`name`) VALUES (3, NULL)",
            $this->object->assemble()
        );
    }

    public function testOnDuplicate()
    {
        $this->object->table("table");
        $this->object->data(["id" => 3, "name" => "Lorem Ipsum"]);
        $this->object->onDuplicate([
            "id" => ["VALUES(`id`)", false],
            "name" => ["VALUES(`name`)", false]
        ]);

        static::assertEquals(
            "INSERT INTO `table` (`id`,`name`) VALUES (3, 'Lorem Ipsum') ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `name` = VALUES(`name`)",
            $this->object->assemble()
        );
    }

    public function testMultiple()
    {
        $this->object->table("table");

        $items = [
            ["name" => "Lorem Ipsum", "telephone" => 1234],
            ["name" => "Dolor sit amet", "telephone" => 5678]
        ];

        foreach ($items as $item) {
            $this->object->data($item);
        }

        static::assertEquals(
            "INSERT INTO `table` (`name`,`telephone`) VALUES ('Lorem Ipsum', 1234), ('Dolor sit amet', 5678)",
            $this->object->assemble()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $manager = TestUtil::getConnection();
        $this->object = new Insert($manager);
    }
}
