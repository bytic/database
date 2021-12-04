<?php

namespace Nip\Database\Connections;

use InvalidArgumentException;
use Nip\Config\Config;
use Nip\Container\Container;
use Nip\Database\Connectors\MySqlConnector;
use Nip\Utility\Arr;
use PDOException;

/**
 * Class ConnectionFactory
 * @package Nip\Database\Connectors
 */
class ConnectionFactory
{
    /**
     * The IoC container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Create a new connection factory instance.
     *
     * @param  Container $container
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container ? $container : Container::getInstance();
    }

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

//        if (isset($config['read'])) {
//            return $this->createReadWriteConnection($config);
//        }
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
     * @param  array $config
     * @return Connection
     */
    protected function createSingleConnection($config)
    {
        $pdo = $this->createPdoResolver($config);
        if (!isset($config['driver'])) {
            $config['driver'] = 'mysql';
        }
        $connection = $this->createConnection($config['driver'], $pdo, $config['database'], $config['prefix'], $config);
        $connection->connect($config['host'], $config['username'], $config['password'], $config['database']);

        return $connection;
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
     *
     * @throws \InvalidArgumentException
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', $config = [])
    {
//        if ($resolver = Connection::getResolver($driver)) {
//            return $resolver($connection, $database, $prefix, $config);
//        }
        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $prefix, $config);
        }

        throw new InvalidArgumentException("Unsupported driver [$driver]");
    }

    /**
     * Create a new Closure that resolves to a PDO instance.
     *
     * @param  array|Config  $config
     * @return \Closure
     */
    protected function createPdoResolver($config)
    {
        return false;
        $config = $config instanceof Config ? $config->toArray() : $config;
        return array_key_exists('host', $config)
            ? $this->createPdoResolverWithHosts($config)
            : $this->createPdoResolverWithoutHosts($config);
    }

    /**
     * Create a new Closure that resolves to a PDO instance with a specific host or an array of hosts.
     *
     * @param  array  $config
     * @return \Closure
     *
     * @throws \PDOException
     */
    protected function createPdoResolverWithHosts(array $config)
    {
        return function () use ($config) {
            foreach (Arr::shuffle($hosts = $this->parseHosts($config)) as $key => $host) {
                $config['host'] = $host;

                try {
                    return $this->createConnector($config)->connect($config);
                } catch (PDOException $e) {
                    continue;
                }
            }

            throw $e;
        };
    }

    /**
     * Parse the hosts configuration item into an array.
     *
     * @param  array  $config
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseHosts(array $config)
    {
        $hosts = Arr::wrap($config['host']);

        if (empty($hosts)) {
            throw new InvalidArgumentException('Database hosts array is empty.');
        }

        return $hosts;
    }

    /**
     * Create a new Closure that resolves to a PDO instance where there is no configured host.
     *
     * @param  array  $config
     * @return \Closure
     */
    protected function createPdoResolverWithoutHosts(array $config)
    {
        return function () use ($config) {
            return $this->createConnector($config)->connect($config);
        };
    }

    /**
     * Create a connector instance based on the configuration.
     *
     * @param  array  $config
     * @return \Illuminate\Database\Connectors\ConnectorInterface|MySqlConnector
     *
     * @throws \InvalidArgumentException
     */
    public function createConnector(array $config)
    {
        if (! isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

//        if ($this->container->bound($key = "db.connector.{$config['driver']}")) {
//            return $this->container->make($key);
//        }

        switch ($config['driver']) {
            case 'mysql':
                return new MySqlConnector();
        }

        throw new InvalidArgumentException("Unsupported driver [{$config['driver']}].");
    }
}
