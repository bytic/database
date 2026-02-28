<?php

declare(strict_types=1);

namespace Nip\Database\Connectors;

use PDO;

/**
 * Base PDO connector.
 *
 * Handles the low-level PDO instantiation and default option management.
 * Concrete connectors (e.g. MySqlConnector) extend this class to provide
 * driver-specific DSN generation and post-connect configuration.
 *
 * @package Nip\Database\Connectors
 */
class Connector
{
    /**
     * Default PDO connection options – these can be overridden via the
     * 'options' key in the database configuration array.
     *
     * @var array<int, mixed>
     */
    protected array $options = [
        PDO::ATTR_CASE               => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS       => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    /**
     * Create a new PDO connection.
     *
     * @param array<string, mixed> $config
     * @param array<int, mixed>    $options
     */
    public function createConnection(string $dsn, array $config, array $options): PDO
    {
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        return $this->createPdoConnection($dsn, $username, $password, $options);
    }

    /**
     * @param array<int, mixed> $options
     */
    protected function createPdoConnection(string $dsn, ?string $username, ?string $password, array $options): PDO
    {
        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * @param array<int, mixed> $options
     */
    protected function isPersistentConnection(array $options): bool
    {
        return isset($options[PDO::ATTR_PERSISTENT]) && (bool) $options[PDO::ATTR_PERSISTENT];
    }

    /**
     * Merge driver-level options with any user-supplied overrides.
     *
     * @param array<string, mixed> $config
     * @return array<int, mixed>
     */
    public function getOptions(array $config): array
    {
        $options = $config['options'] ?? [];

        return array_diff_key($this->options, $options) + $options;
    }

    /** @return array<int, mixed> */
    public function getDefaultOptions(): array
    {
        return $this->options;
    }

    /** @param array<int, mixed> $options */
    public function setDefaultOptions(array $options): void
    {
        $this->options = $options;
    }
}
