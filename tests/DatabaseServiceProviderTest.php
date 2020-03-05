<?php

namespace Nip\Database\Tests;

use Nip\Container\Container;
use Nip\Database\DatabaseManager;
use Nip\Database\DatabaseServiceProvider;

/**
 * Class DatabaseServiceProviderTest
 * @package Nip\Database\Tests
 */
class DatabaseServiceProviderTest extends AbstractTest
{
    public function test_register()
    {
        $container = new Container();

        $provider = new DatabaseServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $manager = $container->get('db');
        self::assertInstanceOf(DatabaseManager::class, $manager);
    }
}