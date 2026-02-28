<?php

declare(strict_types=1);

namespace Nip\Database;

use Nip\Database\Adapters\AbstractAdapter;
use Nip\Database\Adapters\MySQLi;
use Nip\Database\Query\AbstractQuery;

/**
 * Wraps the raw driver result resource and provides row-fetching helpers.
 *
 * @package Nip\Database
 */
class Result
{
    protected mixed $resultSQL;

    protected AbstractAdapter $adapter;

    protected ?AbstractQuery $query = null;

    /** @var list<array<string, mixed>> */
    protected array $results = [];

    /**
     * @param mixed           $resultSQL  Raw driver result (e.g. \mysqli_result or bool)
     * @param AbstractAdapter $adapter
     */
    public function __construct(mixed $resultSQL, AbstractAdapter $adapter)
    {
        $this->resultSQL = $resultSQL;
        $this->adapter   = $adapter;
    }

    public function __destruct()
    {
        if ($this->resultSQL && !is_bool($this->resultSQL)) {
            $this->getAdapter()->freeResults($this->resultSQL);
        }
    }

    public function getAdapter(): AbstractAdapter
    {
        return $this->adapter;
    }

    public function setAdapter(AbstractAdapter $adapter): void
    {
        $this->adapter = $adapter;
    }

    /**
     * Fetch and cache all rows from the current result set.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchResults(): array
    {
        if (count($this->results) === 0) {
            while ($result = $this->fetchResult()) {
                $this->results[] = $result;
            }
        }

        return $this->results;
    }

    /**
     * Fetch the next row as an associative array.
     *
     * @return array<string, mixed>|false
     */
    public function fetchResult(): array|false
    {
        if ($this->checkValid()) {
            try {
                return $this->getAdapter()->fetchAssoc($this->resultSQL) ?? false;
            } catch (Exception $e) {
                $e->log();
            }
        }

        return false;
    }

    public function checkValid(): bool
    {
        if (!$this->isValid()) {
            trigger_error(
                'Invalid result for query [' . ($this->query?->getString() ?? '') . ']',
                E_USER_WARNING
            );
            return false;
        }

        return true;
    }

    public function isValid(): bool
    {
        return $this->resultSQL !== false && $this->resultSQL !== null;
    }

    public function getQuery(): ?AbstractQuery
    {
        return $this->query;
    }

    public function setQuery(AbstractQuery $query): void
    {
        $this->query = $query;
    }

    public function numRows(): int|false
    {
        if ($this->checkValid()) {
            return $this->getAdapter()->numRows($this->resultSQL);
        }

        return false;
    }
}

