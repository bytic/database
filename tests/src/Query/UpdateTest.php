<?php

namespace Nip\Database\Tests\Query;

use Mockery as m;
use Nip\Database\Connections\Connection;
use Nip\Database\Connections\ConnectionFactory;
use Nip\Database\Query\Insert;
use Nip\Database\Query\Update;
use Nip\Database\Tests\AbstractTest;

/**
 * Class UpdateTest
 * @package Nip\Database\Tests\Query
 */
class UpdateTest extends AbstractTest
{
    /**
     * @var Update
     */
    protected $object;

    public function test_null()
    {
        $this->object->table("table");
        $this->object->data(["id" => 3, "name" => null]);

        static::assertEquals(
            "UPDATE `table` SET `id` = 3, `name` = NULL",
            $this->object->assemble()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $adapterMock = m::mock('Nip\Database\Adapters\MySQLi')->makePartial();
        $adapterMock->shouldReceive('cleanData')->andReturnUsing(function ($data) {
            return $data;
        });
        $manager = (new ConnectionFactory())->make(['driver' => 'pdo_mysql']);
        $manager->setAdapter($adapterMock);
        $this->object = new Update($manager);
    }
}