<?php

declare(strict_types=1);

namespace Nip\Database\Connections;

use InvalidArgumentException;
use Nip\Config\Config;
use Nip\Container\Container;
use Nip\Database\Connectors\MySqlConnector;
use Nip\Utility\Arr;
use PDOException;

/**
 * Factory that creates typed Connection instances from configuration arrays.
 *
 * @package Nip\Database\Connections
 */
class ConnectionFactory
{
    protected ?Container $container;

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();
    }

    /**
     * Establish a database connection based on the configuration.
     *
     * @param  array<string, mixed>|Config $config
     */
    public function make(array|Config $config, ?string $name = null): Connection
    {
        $config = $this->parseConfig($config, $name);

        return $this->createSingleConnection($config);
    }

    /**
     * Normalise the configuration array.
     *
     * @param  array<string, mixed>|Config $config
     * @param  string|null $name
     * @return array<string, mixed>
     */
    protected function parseConfig(array|Config $config, ?string $name): array
    {
        return $config instanceof Config ? $config->toArray() : $config;
    }

    /**
     * Create a single connection from a normalised configuration array.
     *
     * @param  array<string, mixed> $config
     */
    protected function createSingleConnection(array $config): Connection
    {
        $pdo    = $this->createPdoResolver($config);
        $driver = $config['driver'] ?? 'mysql';

        $connection = $this->createConnection(
            $driver,
            $pdo,
            $config['database'] ?? '',
            $config['prefix']   ?? '',
            $config
        );

        $connection->connect(
            $config['host']     ?? '',
            $config['username'] ?? '',
            $config['password'] ?? '',
            $config['database'] ?? ''
        );

        return $connection;
    }

    /**
     * Instantiate the correct Connection sub-class for the given driver.
     *
     * @param  array<string, mixed> $config
     *
     * @throws InvalidArgumentException
     */
    protected function createConnection(
        string $driver,
        mixed $connection,
        string $database,
        string $prefix = '',
        array $config = []
    ): Connection {
        return match ($driver) {
            'mysql' => new MySqlConnection($connection, $database, $prefix, $config),
            default => throw new InvalidArgumentException("Unsupported driver [{$driver}]"),
        };
    }

    /**
     * Return a PDO resolver closure (currently a no-op; kept for future migration).
     *
     * @param  array<string, mixed>|Config $config
     */
    protected function createPdoResolver(array|Config $config): mixed
    {
        return false;
    }

    /**
     * Create a connector instance for the given configuration.
     *
     * @param  array<string, mixed> $config
     *
     * @throws InvalidArgumentException
     */
    public function createConnector(array $config): MySqlConnector
    {
        $driver = $config['driver'] ?? null;
        if ($driver === null) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        return match ($driver) {
            'mysql' => new MySqlConnector(),
            default => throw new InvalidArgumentException("Unsupported driver [{$driver}]."),
        };
    }
}

