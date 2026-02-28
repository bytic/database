<?php

declare(strict_types=1);

namespace Nip\Database\Adapters\Profiler;

use Nip\Profiler\Profile;

/**
 * Stores timing and metadata for a single executed SQL statement.
 *
 * @package Nip\Database\Adapters\Profiler
 */
class QueryProfile extends Profile
{
    public ?string $query        = null;
    public ?string $type         = null;
    public mixed   $adapter      = null;
    public mixed   $info         = null;
    public int     $affectedRows = 0;

    /** @var list<string> */
    public array $columns = ['time', 'type', 'memory', 'query', 'affectedRows', 'info'];

    public function setName(mixed $name): void
    {
        $this->query = (string) $name;
        $this->type  = $this->detectQueryType();

        parent::setName($name);
    }

    public function detectQueryType(): string
    {
        return match (strtolower(substr((string) $this->query, 0, 6))) {
            'insert' => 'INSERT',
            'update' => 'UPDATE',
            'delete' => 'DELETE',
            'select' => 'SELECT',
            default  => 'QUERY',
        };
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function calculateResources(): void
    {
        parent::calculateResources();
        $this->getInfo();
    }

    public function getInfo(): void
    {
        $this->info         = $this->getAdapter()->info();
        $this->affectedRows = $this->getAdapter()->affectedRows();
    }

    public function getAdapter(): mixed
    {
        return $this->adapter;
    }

    public function setAdapter(mixed $adapter): void
    {
        $this->adapter = $adapter;
    }
}
