<?php

declare(strict_types=1);

namespace Nip\Database\Adapters;

/**
 * Contract for a low-level database adapter.
 *
 * All methods that interact with the underlying driver are declared here so
 * that higher-level components (Connection, Query, Result) can program against
 * this interface rather than a concrete driver class.
 *
 * @package Nip\Database\Adapters
 */
interface AdapterInterface
{
    /** Execute a raw SQL string and return the driver result resource or false. */
    public function execute(string $sql): mixed;

    /** Return the auto-increment ID produced by the most recent INSERT. */
    public function lastInsertID(): int|string;

    /** Return the number of rows affected by the most recent DML statement. */
    public function affectedRows(): int;

    /** Return the number of rows in a result resource. */
    public function numRows(mixed $result): int;

    /** Fetch the next row as a numeric array, or null when exhausted. */
    public function fetchArray(mixed $result): ?array;

    /** Fetch the next row as an associative array, or null when exhausted. */
    public function fetchAssoc(mixed $result): ?array;

    /** Fetch the next row as a \stdClass object, or null when exhausted. */
    public function fetchObject(mixed $result): ?object;

    /** Return a single field value from a result resource. */
    public function result(mixed $result, int $row, string $field): mixed;

    /** Release the memory associated with a result resource. */
    public function freeResults(mixed $result): void;

    /**
     * Return column/index metadata for the given table.
     *
     * @return array{fields: array<string, array<string, mixed>>, indexes: array<string, array<string, mixed>>}|false
     */
    public function describeTable(string $table): array|false;

    /**
     * Quote a scalar value for safe embedding in a SQL string.
     *
     * Prefer using parameterised queries where possible; this method exists
     * as a fallback for the legacy query-builder.
     */
    public function quote(mixed $value): int|float|string;

    /**
     * Escape a raw string value so it is safe to use inside a quoted SQL literal.
     *
     * @param mixed $data
     * @return mixed
     */
    public function cleanData(mixed $data): mixed;

    /** Return the error message produced by the most recent failed statement. */
    public function error(): string;

    /** Close the underlying driver connection. */
    public function disconnect(): void;
}

