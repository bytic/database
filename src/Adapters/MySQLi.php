<?php

declare(strict_types=1);

namespace Nip\Database\Adapters;

/**
 * MySQLi-based database adapter.
 *
 * Uses the procedural mysqli_* API to stay compatible with the legacy code
 * while exposing a clean, typed surface via AdapterInterface.
 *
 * @package Nip\Database\Adapters
 */
class MySQLi extends AbstractAdapter implements AdapterInterface
{
    protected ?\mysqli $connection = null;

    /**
     * Connect to a MySQL server.
     *
     * Returns the raw mysqli connection on success or null on failure
     * (a PHP warning is triggered so the caller can react accordingly).
     */
    public function connect(
        string $host = '',
        string $user = '',
        string $password = '',
        string $database = '',
        bool $newLink = false
    ): ?\mysqli {
        $this->connection = mysqli_connect($host, $user, $password);

        if ($this->connection instanceof \mysqli) {
            if ($this->selectDatabase($database)) {
                return $this->connection;
            }
            $message = 'Cannot select database ' . $database;
        } else {
            $message = mysqli_connect_error() ?? 'Unknown connection error';
        }

        trigger_error($message, E_USER_WARNING);

        return null;
    }

    public function selectDatabase(string $database): bool
    {
        return $this->connection instanceof \mysqli
            && mysqli_select_db($this->connection, $database);
    }

    public function query(string $sql): \mysqli_result|bool
    {
        if (!$this->connection instanceof \mysqli) {
            trigger_error('MySQLi adapter has no active connection', E_USER_WARNING);
            return false;
        }

        try {
            $result = mysqli_query($this->connection, $sql);
            return $result === false ? false : $result;
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage() . ' for query ' . $sql, $e->getCode(), $e);
        }
    }

    public function lastInsertID(): int|string
    {
        return $this->connection instanceof \mysqli ? mysqli_insert_id($this->connection) : 0;
    }

    public function affectedRows(): int
    {
        return $this->connection instanceof \mysqli ? mysqli_affected_rows($this->connection) : 0;
    }

    public function info(): string
    {
        return $this->connection instanceof \mysqli ? (mysqli_info($this->connection) ?? '') : '';
    }

    public function fetchObject(mixed $result): ?object
    {
        return ($result instanceof \mysqli_result) ? (mysqli_fetch_object($result) ?: null) : null;
    }

    public function result(mixed $result, int $row, string $field): mixed
    {
        if (!$result instanceof \mysqli_result) {
            return null;
        }
        mysqli_data_seek($result, $row);
        $row = mysqli_fetch_assoc($result);
        return $row[$field] ?? null;
    }

    public function freeResults(mixed $result): void
    {
        if ($result instanceof \mysqli_result) {
            mysqli_free_result($result);
        }
    }

    public function describeTable(string $table): array|false
    {
        if (!$this->connection instanceof \mysqli) {
            return false;
        }

        $return = ['fields' => [], 'indexes' => []];

        $result = $this->execute('DESCRIBE ' . $table);
        if ($result === false || is_bool($result)) {
            return false;
        }
        if (mysqli_num_rows($result)) {
            while ($row = $this->fetchAssoc($result)) {
                $return['fields'][$row['Field']] = [
                    'field'          => $row['Field'],
                    'type'           => $row['Type'],
                    'nullable'       => strtoupper($row['Null']) === 'YES',
                    'primary'        => (
                        isset($return['indexes']['PRIMARY']['fields'][0])
                        && $return['indexes']['PRIMARY']['fields'][0] === $row['Field']
                    ),
                    'default'        => $row['Default'],
                    'auto_increment' => ($row['Extra'] === 'auto_increment'),
                ];
            }
        }

        $result = $this->execute('SHOW INDEX IN ' . $table);
        if ($result === false || is_bool($result)) {
            return false;
        }
        if (mysqli_num_rows($result)) {
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
        }

        return $return;
    }

    public function fetchAssoc(mixed $result): ?array
    {
        return ($result instanceof \mysqli_result) ? (mysqli_fetch_assoc($result) ?: null) : null;
    }

    /**
     * @return array<string, array{type: string}>
     */
    public function getTables(): array
    {
        $return = [];

        $result = $this->execute('SHOW FULL TABLES');
        if ($result instanceof \mysqli_result && $this->numRows($result)) {
            while ($row = $this->fetchArray($result)) {
                $return[$row[0]] = [
                    'type' => $row[1] === 'BASE TABLE' ? 'table' : 'view',
                ];
            }
        }

        return $return;
    }

    public function numRows(mixed $result): int
    {
        return ($result instanceof \mysqli_result) ? (int) mysqli_num_rows($result) : 0;
    }

    public function fetchArray(mixed $result): ?array
    {
        return ($result instanceof \mysqli_result) ? (mysqli_fetch_array($result) ?: null) : null;
    }

    /**
     * Quote a scalar value for safe embedding in a SQL string.
     *
     * Numeric values are returned as-is; strings are escaped and wrapped in
     * single quotes using the connection's current character set.
     */
    public function quote(mixed $value): int|float|string
    {
        $cleaned = $this->cleanData($value);

        $intVal = filter_var($cleaned, FILTER_VALIDATE_INT);
        if ($intVal !== false) {
            return (int) $intVal;
        }

        $floatVal = filter_var($cleaned, FILTER_VALIDATE_FLOAT);
        if ($floatVal !== false) {
            return (float) $floatVal;
        }

        return "'{$cleaned}'";
    }

    /**
     * Escape a string using the connection's current character set.
     *
     * Falls back to addslashes() when there is no active connection so that
     * unit tests without a real database still produce a safe result.
     */
    public function cleanData(mixed $data): mixed
    {
        if ($data === null || $data === '') {
            return $data;
        }

        if (!is_string($data) && !is_numeric($data)) {
            return $data;
        }

        if ($this->connection instanceof \mysqli) {
            return mysqli_real_escape_string($this->connection, (string) $data);
        }

        // Fallback: addslashes is not a cryptographic guarantee, but it
        // prevents the most obvious injections when there is no live connection
        // (e.g. during unit tests that mock the connection).
        return addslashes((string) $data);
    }

    public function error(): string
    {
        return $this->connection instanceof \mysqli ? (mysqli_error($this->connection) ?: '') : '';
    }

    public function disconnect(): void
    {
        if ($this->connection instanceof \mysqli) {
            mysqli_close($this->connection);
            $this->connection = null;
        }
    }
}

