<?php

declare(strict_types=1);

namespace Nip\Database\Adapters;

use PDO;
use PDOException;
use PDOStatement;

/**
 * PDO-based database adapter.
 *
 * Implements both {@see AdapterInterface} (drop-in replacement for MySQLi) and
 * {@see PreparedStatementAdapterInterface} so that
 * {@see \Nip\Database\Connections\Connection::executeQuery()} and
 * {@see \Nip\Database\Connections\Connection::executeStatement()} can use
 * native prepared statements instead of string interpolation.
 *
 * ## Backward compatibility
 * - All methods required by `AdapterInterface` are implemented and behave
 *   identically to the MySQLi adapter from the caller's perspective.
 * - The existing `execute(string $sql)` path (inherited from AbstractAdapter)
 *   still works without parameters; the new `executeWithParams()` is opt-in.
 *
 * ## Usage via ConnectionFactory
 * Set `driver => pdo_mysql` (or `use_pdo => true`) in your connection config
 * and `ConnectionFactory` will wire up this adapter automatically.
 *
 * ## Manual usage
 * ```php
 * $pdo    = new \PDO('mysql:host=localhost;dbname=app', 'user', 'pass');
 * $adapter = new \Nip\Database\Adapters\PdoAdapter();
 * $adapter->setPdo($pdo);
 *
 * $conn = new \Nip\Database\Connections\Connection(false);
 * $conn->setAdapter($adapter);
 *
 * // Symfony DBAL-style parameterised query:
 * $result = $conn->executeQuery('SELECT * FROM users WHERE id = ?', [42]);
 * $result = $conn->executeStatement('UPDATE users SET active=? WHERE id=?', [1, 42]);
 * ```
 *
 * @package Nip\Database\Adapters
 */
class PdoAdapter extends AbstractAdapter implements AdapterInterface, PreparedStatementAdapterInterface
{
    protected ?PDO $connection = null;

    /**
     * Number of rows affected by the most recent DML statement.
     * Stored here because PDO exposes this per-statement, not per-connection.
     */
    protected int $lastAffectedRows = 0;

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    /**
     * Open a PDO connection and return it (or null on failure).
     *
     * This method follows the same signature as the MySQLi adapter so that
     * `Connection::connect()` can call it transparently.
     */
    public function connect(
        string $host = '',
        string $user = '',
        string $password = '',
        string $database = '',
        bool $newLink = false
    ): ?PDO {
        $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

        try {
            $this->connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            return $this->connection;
        } catch (PDOException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);

            return null;
        }
    }

    /**
     * Inject an already-open PDO instance (e.g. created by MySqlConnector).
     */
    public function setPdo(PDO $pdo): void
    {
        $this->connection = $pdo;
    }

    public function getPdo(): ?PDO
    {
        return $this->connection;
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }

    // -------------------------------------------------------------------------
    // Query execution (AdapterInterface)
    // -------------------------------------------------------------------------

    /**
     * Execute a raw SQL string without parameters.
     *
     * Used by the legacy query-builder path (values already interpolated).
     */
    public function query(string $sql): PDOStatement|false
    {
        if (!$this->connection instanceof PDO) {
            trigger_error('PDO adapter has no active connection', E_USER_WARNING);

            return false;
        }

        try {
            $stmt = $this->connection->query($sql);
            if ($stmt instanceof PDOStatement) {
                $this->lastAffectedRows = $stmt->rowCount();
                return $stmt;
            }

            return false;
        } catch (PDOException $e) {
            throw new \RuntimeException($e->getMessage() . ' for query ' . $sql, (int) $e->getCode(), $e);
        }
    }

    // -------------------------------------------------------------------------
    // PreparedStatementAdapterInterface
    // -------------------------------------------------------------------------

    /**
     * Prepare and execute a SQL statement with bound parameters.
     *
     * This is the preferred path for all parameterised queries. The `?`
     * positional placeholders in `$sql` correspond to the values in `$params`.
     *
     * @param  array<int|string, mixed> $params
     */
    public function executeWithParams(string $sql, array $params): PDOStatement|false
    {
        if (!$this->connection instanceof PDO) {
            trigger_error('PDO adapter has no active connection', E_USER_WARNING);

            return false;
        }

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $this->lastAffectedRows = $stmt->rowCount();

            return $stmt;
        } catch (PDOException $e) {
            throw new \RuntimeException($e->getMessage() . ' for query ' . $sql, (int) $e->getCode(), $e);
        }
    }

    // -------------------------------------------------------------------------
    // Result inspection (AdapterInterface)
    // -------------------------------------------------------------------------

    public function lastInsertID(): int|string
    {
        return $this->connection instanceof PDO ? $this->connection->lastInsertId() : 0;
    }

    public function affectedRows(): int
    {
        return $this->lastAffectedRows;
    }

    public function numRows(mixed $result): int
    {
        return ($result instanceof PDOStatement) ? $result->rowCount() : 0;
    }

    public function fetchArray(mixed $result): ?array
    {
        return ($result instanceof PDOStatement)
            ? ($result->fetch(PDO::FETCH_NUM) ?: null)
            : null;
    }

    public function fetchAssoc(mixed $result): ?array
    {
        return ($result instanceof PDOStatement)
            ? ($result->fetch(PDO::FETCH_ASSOC) ?: null)
            : null;
    }

    public function fetchObject(mixed $result): ?object
    {
        return ($result instanceof PDOStatement)
            ? ($result->fetchObject() ?: null)
            : null;
    }

    /**
     * Retrieve a single field from a specific row.
     *
     * Note: PDO cursors are forward-only; this method buffers the entire result
     * set to allow random-access retrieval. Prefer `fetchAssoc()` in hot paths.
     */
    public function result(mixed $result, int $row, string $field): mixed
    {
        if (!$result instanceof PDOStatement) {
            return null;
        }

        $rows = $result->fetchAll(PDO::FETCH_ASSOC);

        return $rows[$row][$field] ?? null;
    }

    public function freeResults(mixed $result): void
    {
        if ($result instanceof PDOStatement) {
            $result->closeCursor();
        }
    }

    // -------------------------------------------------------------------------
    // Schema inspection (AdapterInterface)
    // -------------------------------------------------------------------------

    public function describeTable(string $table): array|false
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $return = ['fields' => [], 'indexes' => []];

        $result = $this->execute('DESCRIBE ' . $table);
        if ($result === false) {
            return false;
        }

        while ($row = $this->fetchAssoc($result)) {
            $return['fields'][$row['Field']] = [
                'field'          => $row['Field'],
                'type'           => $row['Type'],
                'nullable'       => strtoupper($row['Null']) === 'YES',
                'primary'        => false,
                'default'        => $row['Default'],
                'auto_increment' => ($row['Extra'] === 'auto_increment'),
            ];
        }

        $result = $this->execute('SHOW INDEX IN ' . $table);
        if ($result === false) {
            return false;
        }

        while ($row = $this->fetchAssoc($result)) {
            $keyName = $row['Key_name'];
            if (!isset($return['indexes'][$keyName])) {
                $return['indexes'][$keyName] = [];
            }
            $return['indexes'][$keyName]['fields'][]  = $row['Column_name'];
            $return['indexes'][$keyName]['unique']    = $row['Non_unique'] === '0';
            $return['indexes'][$keyName]['fulltext']  = $row['Index_type'] === 'FULLTEXT';
            $return['indexes'][$keyName]['type']      = $row['Index_type'];
        }

        // Stamp the primary-key flag on the matching field
        $primaryField = $return['indexes']['PRIMARY']['fields'][0] ?? null;
        if ($primaryField !== null && isset($return['fields'][$primaryField])) {
            $return['fields'][$primaryField]['primary'] = true;
        }

        return $return;
    }

    // -------------------------------------------------------------------------
    // Value quoting / escaping (AdapterInterface)
    // -------------------------------------------------------------------------

    /**
     * Quote a value for safe embedding in a SQL string.
     *
     * Prefer parameterised queries (`executeWithParams`) over this method
     * wherever possible.
     */
    public function quote(mixed $value): int|float|string
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return $value;
        }

        if ($this->connection instanceof PDO) {
            return $this->connection->quote((string) $value);
        }

        return "'" . addslashes((string) $value) . "'";
    }

    /**
     * Escape a raw string value for use inside a quoted SQL literal.
     *
     * Uses `PDO::quote()` (strips the surrounding quotes) when a connection is
     * available; falls back to `addslashes()` in unit-test contexts.
     */
    public function cleanData(mixed $data): mixed
    {
        if ($data === null || $data === '') {
            return $data;
        }

        if (!is_string($data) && !is_numeric($data)) {
            return $data;
        }

        if ($this->connection instanceof PDO) {
            $quoted = $this->connection->quote((string) $data);
            // PDO::quote() wraps in single quotes; strip them to match MySQLi behaviour.
            return substr($quoted, 1, -1);
        }

        return addslashes((string) $data);
    }

    // -------------------------------------------------------------------------
    // Error reporting (AdapterInterface)
    // -------------------------------------------------------------------------

    public function error(): string
    {
        if (!$this->connection instanceof PDO) {
            return '';
        }

        $info = $this->connection->errorInfo();

        return $info[2] ?? '';
    }
}
