<?php

declare(strict_types=1);

namespace Nip\Database\Connections;

use InvalidArgumentException;
use Nip\Config\Config;
use Nip\Container\Container;
use Nip\Database\Adapters\PdoAdapter;
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
     * When the config contains `driver => pdo_mysql` or `use_pdo => true` the
     * connection is wired with the PDO adapter (via MySqlConnector) instead of
     * the legacy MySQLi adapter.  All other config keys are unchanged.
     *
     * @param  array<string, mixed> $config
     */
    protected function createSingleConnection(array $config): Connection
    {
        $driver = $config['driver'] ?? 'mysql';
        $usePdo = ($driver === 'pdo_mysql') || !empty($config['use_pdo']);

        if ($usePdo) {
            return $this->createPdoConnection($config);
        }

        // $pdoResolver is currently always false (deprecated legacy shim); kept
        // here so that createConnection() signature remains intact.
        $pdoResolver = $this->createPdoResolver($config);
        $connection  = $this->createConnection(
            $driver,
            $pdoResolver,
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
     * Create a Connection backed by the PDO adapter.
     *
     * Uses {@see MySqlConnector} to build the PDO instance so that all the
     * Symfony-style charset / timezone / strict-mode configuration is applied.
     *
     * @param  array<string, mixed> $config
     */
    protected function createPdoConnection(array $config): Connection
    {
        $pdoInstance = (new MySqlConnector())->connect($config);

        $adapter = new PdoAdapter();
        $adapter->setPdo($pdoInstance);

        // Normalise the driver name so `createConnection()` receives 'mysql'.
        $config['driver'] = 'mysql';

        $connection = $this->createConnection(
            'mysql',
            false,
            $config['database'] ?? '',
            $config['prefix']   ?? '',
            $config
        );

        $connection->setAdapter($adapter);

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
     * Return a PDO resolver (currently a no-op; kept for future PDO migration).
     *
     * @deprecated Will be implemented properly when PDO support is added.
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

