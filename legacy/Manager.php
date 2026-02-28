<?php

declare(strict_types=1);

namespace Nip\Database;

use Nip\Database\Connections\Connection;

/**
 * @deprecated Use \Nip\Database\DatabaseManager instead.
 */
class Manager extends DatabaseManager
{
    public function newConnectionFromConfig(mixed $config): Connection
    {
        return $this->createNewConnection(
            $config->adapter,
            $config->host,
            $config->user,
            $config->password,
            $config->name
        );
    }

    public function createNewConnection(
        string $adapter,
        string $host,
        string $user,
        string $password,
        string $database
    ): Connection {
        try {
            $connection = $this->newConnection();

            $adapterInstance = $connection->newAdapter($adapter);
            $connection->setAdapter($adapterInstance);

            $connection->connect($host, $user, $password, $database);
            $this->initNewConnection($connection);
        } catch (Exception $e) {
            echo '<h1>Error connecting to database</h1>';
            if (app()->get('staging')->getStage()->inTesting()) {
                echo '<h4>' . $e->getMessage() . '</h4>';
                $e->log();
            }
            die();
        }

        return $connection;
    }

    public function initNewConnection(Connection $connection): void
    {
        if ($this->getBootstrap()->getDebugBar()->isEnabled()) {
            $this->getBootstrap()->getDebugBar()->initDatabaseAdapter($connection->getAdapter());
        }
    }

    public function newConnection(): Connection
    {
        return new Connection(false);
    }
}
