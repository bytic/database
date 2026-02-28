<?php

declare(strict_types=1);

namespace Nip\Database\Metadata;

use Nip\Cache\Manager as CacheManager;
use Nip\Database\Connections\Connection;

/**
 * Caches DESCRIBE / SHOW INDEX results so the database is not queried on
 * every page load.
 *
 * @package Nip\Database\Metadata
 */
class Cache extends CacheManager
{
    protected ?Manager $metadata = null;

    public function __construct()
    {
        $this->setTtl(10 * 24 * 60 * 60);
        $this->setActive(true);
    }

    /**
     * Return the cached (or freshly generated) metadata for a table.
     *
     * @return array<string, mixed>|null
     */
    public function describeTable(string $table): ?array
    {
        $cacheId = $this->getCacheId($table);

        return $this->get($cacheId);
    }

    public function getCacheId(string $table): string
    {
        return $this->getConnection()->getDatabase() . '.' . $table;
    }

    public function getConnection(): Connection
    {
        return $this->getMetadata()->getConnection();
    }

    public function getMetadata(): Manager
    {
        return $this->metadata;
    }

    public function setMetadata(Manager $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function get(mixed $cacheId): mixed
    {
        if (!$this->valid($cacheId)) {
            $this->reload($cacheId);
        }

        return $this->getData($cacheId);
    }

    public function reload(mixed $cacheId): mixed
    {
        $data = $this->generate($cacheId);

        if (is_array($data) && isset($data['fields'])) {
            return $this->saveData($cacheId, $data);
        }

        return false;
    }

    public function generate(string $cacheId): mixed
    {
        $data                  = $this->getConnection()->describeTable($cacheId);
        $this->data[$cacheId]  = $data;

        return $data;
    }

    public function cachePath(): string
    {
        return parent::cachePath() . '/db-metadata/';
    }
}
