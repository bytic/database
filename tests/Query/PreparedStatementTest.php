<?php

declare(strict_types=1);

namespace Nip\Database\Tests\Query;

use Mockery as m;
use Nip\Database\Adapters\MySQLi;
use Nip\Database\Connections\Connection;
use Nip\Database\Query\Select;
use Nip\Database\Tests\AbstractTest;

/**
 * Tests for the prepared-statement helpers added to AbstractQuery / Condition.
 *
 * These tests do NOT require a live database; they only verify the SQL
 * generation logic (toSql, getParameterizedSql, getBindings).
 *
 * @package Nip\Database\Tests\Query
 */
class PreparedStatementTest extends AbstractTest
{
    private Connection $connection;
    private Select $query;

    // -------------------------------------------------------------------------
    // getParameterizedSql / getBindings
    // -------------------------------------------------------------------------

    public function test_scalar_placeholder(): void
    {
        $this->query->from('users')->where('id = ?', 42);

        self::assertSame('SELECT * FROM `users` WHERE id = ?', $this->query->getParameterizedSql());
        self::assertSame([42], $this->query->getBindings());
    }

    public function test_multiple_scalar_placeholders_via_and(): void
    {
        $this->query->from('users')
            ->where('id = ?', 42)
            ->where('active = ?', 1);

        self::assertSame('SELECT * FROM `users` WHERE id = ? AND active = ?', $this->query->getParameterizedSql());
        self::assertSame([42, 1], $this->query->getBindings());
    }

    public function test_or_where_bindings(): void
    {
        $this->query->from('users')
            ->where('id = ?', 1)
            ->orWhere('id = ?', 2);

        self::assertSame('SELECT * FROM `users` WHERE id = ? OR id = ?', $this->query->getParameterizedSql());
        self::assertSame([1, 2], $this->query->getBindings());
    }

    public function test_array_in_clause_expands_placeholders(): void
    {
        $this->query->from('users')->where('id IN ?', [10, 20, 30]);

        self::assertSame('SELECT * FROM `users` WHERE id IN (?, ?, ?)', $this->query->getParameterizedSql());
        self::assertSame([10, 20, 30], $this->query->getBindings());
    }

    public function test_no_placeholders_returns_empty_bindings(): void
    {
        $this->query->from('users')->where('deleted_at IS NULL');

        self::assertSame('SELECT * FROM `users` WHERE deleted_at IS NULL', $this->query->getParameterizedSql());
        self::assertSame([], $this->query->getBindings());
    }

    public function test_no_where_clause(): void
    {
        $this->query->from('users');

        self::assertSame('SELECT * FROM `users`', $this->query->getParameterizedSql());
        self::assertSame([], $this->query->getBindings());
    }

    // -------------------------------------------------------------------------
    // toSql
    // -------------------------------------------------------------------------

    public function test_to_sql_returns_tuple(): void
    {
        $this->query->from('users')->where('email = ?', 'alice@example.com');

        [$sql, $bindings] = $this->query->toSql();

        self::assertSame('SELECT * FROM `users` WHERE email = ?', $sql);
        self::assertSame(['alice@example.com'], $bindings);
    }

    // -------------------------------------------------------------------------
    // Verify that getString() (legacy path) is NOT affected
    // -------------------------------------------------------------------------

    public function test_getString_still_interpolates(): void
    {
        $this->query->from('users')->where('id = ?', 5);

        // The legacy path must still inline the value.
        self::assertSame('SELECT * FROM `users` WHERE id = 5', $this->query->getString());

        // And toSql must NOT be changed by the getString() call.
        [$sql, $bindings] = $this->query->toSql();
        self::assertSame('SELECT * FROM `users` WHERE id = ?', $sql);
        self::assertSame([5], $bindings);
    }

    public function test_getString_cache_is_preserved_after_toSql(): void
    {
        $this->query->from('users')->where('id = ?', 7);

        // Prime the getString() cache.
        $first = $this->query->getString();

        // toSql() must not corrupt the cache.
        $this->query->toSql();

        self::assertSame($first, $this->query->getString());
    }

    // -------------------------------------------------------------------------
    // Nested sub-query bindings
    // -------------------------------------------------------------------------

    public function test_nested_subquery_bindings_are_merged(): void
    {
        $inner = new Select();
        $inner->setManager($this->connection);
        $inner->from('orders')->where('user_id = ?', 99);

        $this->query->from('users')->where('id NOT IN ?', $inner);

        [$sql, $bindings] = $this->query->toSql();

        self::assertSame(
            'SELECT * FROM `users` WHERE id NOT IN (SELECT * FROM `orders` WHERE user_id = ?)',
            $sql
        );
        self::assertSame([99], $bindings);
    }

    // -------------------------------------------------------------------------
    // setUp
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();

        $adapterMock = m::mock(MySQLi::class)->makePartial();
        $adapterMock->shouldReceive('cleanData')->andReturnUsing(fn($d) => $d);

        $this->connection = new Connection(false);
        $this->connection->setAdapter($adapterMock);

        $this->query = new Select();
        $this->query->setManager($this->connection);
    }
}
