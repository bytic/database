<?php

declare(strict_types=1);

namespace Nip\Database\Metadata;

use Nip\Database\Connections\HasConnectionTrait;

/**
 * Manages database table metadata, delegating persistence to the Cache.
 *
 * @package Nip\Database\Metadata
 */
class Manager
{
    use HasConnectionTrait;

    protected ?Cache $_cache = null;

    /**
     * Return column/index metadata for the given table.
     *
     * @return array{fields: array<string, mixed>, indexes: array<string, mixed>}
     */
    public function describeTable(string $table): array
    {
        $data = $this->getCache()->describeTable($table);

        if (!is_array($data)) {
            trigger_error("Cannot load metadata for table [{$table}]", E_USER_ERROR);
            return [];
        }

        return $data;
    }

    public function getCache(): Cache
    {
        if (!$this->_cache) {
            $this->_cache = new Cache();
            $this->_cache->setMetadata($this);
        }

        return $this->_cache;
    }
}
