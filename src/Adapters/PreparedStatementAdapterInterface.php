<?php

declare(strict_types=1);

namespace Nip\Database\Adapters;

/**
 * Optional extension for adapters that support native prepared statements.
 *
 * Adapters that implement this interface (e.g. the PDO adapter) will be
 * detected by {@see \Nip\Database\Connections\Connection::executeQuery()} and
 * {@see \Nip\Database\Connections\Connection::executeStatement()} so that
 * bound parameters are passed to the driver rather than interpolated into the
 * SQL string.
 *
 * The MySQLi adapter does NOT implement this interface; it continues to use
 * the legacy quoting path so that all existing code remains unchanged.
 *
 * @package Nip\Database\Adapters
 */
interface PreparedStatementAdapterInterface
{
    /**
     * Prepare the SQL string and execute it with the given bound parameters.
     *
     * @param  string                  $sql    SQL with `?` positional placeholders.
     * @param  array<int|string, mixed> $params Values to bind in order.
     * @return mixed                    Driver statement handle (e.g. \PDOStatement).
     */
    public function executeWithParams(string $sql, array $params): mixed;
}
