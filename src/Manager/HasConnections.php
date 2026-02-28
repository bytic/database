<?php

declare(strict_types=1);

namespace Nip\Database\Manager;

use InvalidArgumentException;
use Nip\Container\Container;
use Nip\Database\Connections\Connection;

/**
 * Provides lazy-loading connection pool management.
 *
 * @package Nip\Database\Manager
 */
trait HasConnections
{
    /** @var array<string, Connection> */
    protected array $connections = [];

    /**
     * Retrieve (and lazily create) a named connection.
     */
    public function connection(?string $name = null): Connection
    {
        $connectionName = $this->parseConnectionName($name);
        $name           = $name ?? $connectionName;

        if (!isset($this->connections[$name])) {
            $connection = $this->configure($this->makeConnection($name), null);
            $this->setConnection($connection, $name);
        }

        return $this->connections[$name];
    }

    public function setConnection(Connection $connection, string $name): void
    {
        $this->connections[$name] = $connection;
    }

    /** @return array<string, Connection> */
    public function getConnections(): array
    {
        return $this->connections;
    }

    public function getDefaultConnection(): string
    {
        if (!function_exists('config')) {
            return 'main';
        }

        if (!Container::getInstance() || !Container::getInstance()->has('config')) {
            return 'main';
        }

        if (Container::getInstance() && config()->has('database.default')) {
            return (string) config()->get('database.default');
        }

        return 'main';
    }

    protected function parseConnectionName(?string $name): string
    {
        return $name ?? $this->getDefaultConnection();
    }

    /**
     * Apply post-creation configuration to the connection.
     *
     * The $type parameter (e.g. 'read'/'write' split) is reserved for a
     * future read/write splitting feature and is unused at this time.
     */
    protected function configure(Connection $connection, ?string $type): Connection
    {
        return $connection;
    }

    protected function makeConnection(string $name): Connection
    {
        $config = $this->configuration($name);

        return $this->factory->make($config, $name);
    }

    /**
     * Retrieve configuration for a named connection.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    protected function configuration(string $name): array
    {
        $name = $name ?: $this->getDefaultConnection();

        $connections = config('database.connections');
        $config      = is_array($connections) ? ($connections[$name] ?? null) : null;

        if ($config === null) {
            throw new InvalidArgumentException("Database [{$name}] not configured.");
        }

        if (!empty($config['user'])) {
            $config['username'] = $config['user'];
        }

        if (!empty($config['name'])) {
            $config['database'] = $config['name'];
        }

        return $config;
    }
}
