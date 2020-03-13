<?php

namespace Nip\Database;

use Nip\Database\Connections\Connection;

/**
 * Class Manager.
 * @deprecated use \Nip\Database\DatabaseManager
 */
class Manager extends DatabaseManager
{

    /**
     * @param $config
     *
     * @return Connection
     */
    public function newConnectionFromConfig($config)
    {
        $connection = $this->createNewConnection(
            $config->adapter,
            $config->host,
            $config->user,
            $config->password,
            $config->name);

        return $connection;
    }

    /**
     * @param $adapter
     * @param $host
     * @param $user
     * @param $password
     * @param $database
     *
     * @return Connection
     */
    public function createNewConnection($adapter, $host, $user, $password, $database)
    {
        try {
            $connection = $this->newConnection();

            $adapter = $connection->newAdapter($adapter);
            $connection->setAdapter($adapter);

            $connection->connect($host, $user, $password, $database);
            $this->initNewConnection($connection);
        } catch (Exception $e) {
            echo '<h1>Error connecting to database</h1>';
            if (app()->get('staging')->getStage()->inTesting()) {
                echo '<h4>'.$e->getMessage().'</h4>';
                $e->log();
            }
            die();
        }

        return $connection;
    }

    /**
     * @param $connection
     */
    public function initNewConnection($connection)
    {
        if ($this->getBootstrap()->getDebugBar()->isEnabled()) {
            $this->getBootstrap()->getDebugBar()->initDatabaseAdapter($connection->getAdapter());
        }
    }

    /**
     * @return Connection
     */
    public function newConnection()
    {
        return new Connection(false);
    }
}
