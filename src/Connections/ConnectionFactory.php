<?php

namespace Nip\Database\Connections;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\AbstractMySQLDriver;

/**
 * Class ConnectionFactory
 * @package Nip\Database\Connectors
 */
class ConnectionFactory
{
    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  array $config
     * @param  string $name
     * @return Connection
     */
    public function make($config, $name = null)
    {
        $config = $this->parseConfig($config, $name);

        return $this->createSingleConnection($config);
    }

    /**
     * Parse and prepare the database configuration.
     *
     * @param  array $config
     * @param  string $name
     * @return array
     */
    protected function parseConfig($config, $name)
    {
        return $config;
//        return Arr::add(Arr::add($config, 'prefix', ''), 'name', $name);
    }

    /**
     * Create a single database connection instance.
     *
     * @param array $config
     * @return Connection
     * @inspiration https://github.com/doctrine/DoctrineBundle/blob/2.3.x/ConnectionFactory.php
     */
    protected function createSingleConnection(
        $params,
        ?Configuration $config = null,
        ?EventManager $eventManager = null
    ) {
        $params = (array)$params;
        $connection = \Doctrine\DBAL\DriverManager::getConnection($params);

        $params = array_merge($connection->getParams());
        $driver = $connection->getDriver();

        if ($driver instanceof AbstractMySQLDriver) {
            $params['charset'] = 'utf8mb4';

            if (!isset($params['defaultTableOptions']['collate'])) {
                $params['defaultTableOptions']['collate'] = 'utf8mb4_unicode_ci';
            }
        } else {
            $params['charset'] = 'utf8';
        }
        $wrapperClass = Connection::class;

        $connection = new $wrapperClass($params, $driver, $config, $eventManager);

        return $connection;
    }
}
