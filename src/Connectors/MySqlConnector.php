<?php

declare(strict_types=1);

namespace Nip\Database\Connectors;

use PDO;

/**
 * MySQL PDO connector.
 *
 * Handles MySQL-specific DSN creation and post-connect configuration
 * (charset, collation, timezone, strict mode).
 *
 * @package Nip\Database\Connectors
 */
class MySqlConnector extends Connector
{
    /**
     * @param array<string, mixed> $config
     */
    public function connect(array $config): PDO
    {
        $dsn     = $this->getDsn($config);
        $options = $this->getOptions($config);

        $connection = $this->createConnection($dsn, $config, $options);

        if (!empty($config['database'])) {
            $connection->exec("use `{$config['database']}`;");
        }

        $this->configureIsolationLevel($connection, $config);
        $this->configureEncoding($connection, $config);
        $this->configureTimezone($connection, $config);
        $this->setModes($connection, $config);

        return $connection;
    }

    /** @param array<string, mixed> $config */
    protected function configureIsolationLevel(PDO $connection, array $config): void
    {
        if (!isset($config['isolation_level'])) {
            return;
        }

        $connection->prepare(
            "SET SESSION TRANSACTION ISOLATION LEVEL {$config['isolation_level']}"
        )->execute();
    }

    /** @param array<string, mixed> $config */
    protected function configureEncoding(PDO $connection, array $config): void
    {
        if (!isset($config['charset'])) {
            return;
        }

        $connection->prepare(
            "set names '{$config['charset']}'" . $this->getCollation($config)
        )->execute();
    }

    /** @param array<string, mixed> $config */
    protected function getCollation(array $config): string
    {
        return isset($config['collation']) ? " collate '{$config['collation']}'" : '';
    }

    /** @param array<string, mixed> $config */
    protected function configureTimezone(PDO $connection, array $config): void
    {
        if (isset($config['timezone'])) {
            $connection->prepare('set time_zone="' . $config['timezone'] . '"')->execute();
        }
    }

    /** @param array<string, mixed> $config */
    protected function getDsn(array $config): string
    {
        return $this->hasSocket($config)
            ? $this->getSocketDsn($config)
            : $this->getHostDsn($config);
    }

    /** @param array<string, mixed> $config */
    protected function hasSocket(array $config): bool
    {
        return isset($config['unix_socket']) && !empty($config['unix_socket']);
    }

    /** @param array<string, mixed> $config */
    protected function getSocketDsn(array $config): string
    {
        return "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
    }

    /** @param array<string, mixed> $config */
    protected function getHostDsn(array $config): string
    {
        $host     = $config['host']     ?? '';
        $database = $config['database'] ?? '';
        $port     = $config['port']     ?? null;

        return $port !== null
            ? "mysql:host={$host};port={$port};dbname={$database}"
            : "mysql:host={$host};dbname={$database}";
    }

    /** @param array<string, mixed> $config */
    protected function setModes(PDO $connection, array $config): void
    {
        if (isset($config['modes'])) {
            $this->setCustomModes($connection, $config);
        } elseif (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->prepare($this->strictMode($connection, $config))->execute();
            } else {
                $connection->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
            }
        }
    }

    /** @param array<string, mixed> $config */
    protected function setCustomModes(PDO $connection, array $config): void
    {
        $modes = implode(',', (array) $config['modes']);
        $connection->prepare("set session sql_mode='{$modes}'")->execute();
    }

    /** @param array<string, mixed> $config */
    protected function strictMode(PDO $connection, array $config): string
    {
        $version = $config['version'] ?? $connection->getAttribute(PDO::ATTR_SERVER_VERSION);

        if (version_compare((string) $version, '8.0.11') >= 0) {
            return "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
        }

        return "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'";
    }
}
