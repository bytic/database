<?php

namespace Nip\Database\Connections;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;

/**
 * Class ConnectionFactory
 * @package Nip\Database\Connectors
 * @inspiration https://github.com/doctrine/DoctrineBundle/blob/1.3.x/ConnectionFactory.php
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
        $params = $this->parseConfig($config, $name);

        return $this->createSingleConnection($params);
    }

    /**
     * Parse and prepare the database configuration.
     *
     * @param  array $params
     * @param  string $name
     * @return array
     */
    protected function parseConfig($params, $name)
    {
        $params['wrapperClass'] = Connection::class;
        $params['charset'] = 'utf8mb4';

        if (!isset($params['defaultTableOptions']['collate'])) {
            $params['defaultTableOptions']['collate'] = 'utf8mb4_unicode_ci';
        }

        return $params;
//        return Arr::add(Arr::add($config, 'prefix', ''), 'name', $name);
    }

    /**
     * Create a single database connection instance.
     *
     * @param array $config
     * @return Connection
     */
    protected function createSingleConnection(
        $params,
        ?Configuration $config = null,
        ?EventManager $eventManager = null
    ) {
        $params = (array)$params;

        return \Doctrine\DBAL\DriverManager::getConnection($params);
    }

    /**
     * Create a new connection instance.
     *
     * @param  string $driver
     * @param  boolean $connection
     * @param  string $database
     * @param  string $prefix
     * @param  array $config
     * @return Connection
     */
    protected function createSingleConnection(
        $params,
        ?Configuration $config = null,
        ?EventManager $eventManager = null
    ) {
        $params = (array)$params;

        return \Doctrine\DBAL\DriverManager::getConnection($params);
    }
}
