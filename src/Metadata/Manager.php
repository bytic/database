<?php

namespace Nip\Database\Metadata;

use Nip\Database\Connections\HasConnectionTrait;

/**
 * Class Manager
 * @package Nip\Database\Metadata
 */
class Manager
{
    use HasConnectionTrait;

    protected $_cache;

    /**
     * @param $table
     * @return bool|mixed
     */
    public function describeTable($table)
    {
        $data = $this->getCache()->describeTable($table);
        if (!is_array($data)) {
            return trigger_error("Cannot load metadata for table [$table]", E_USER_ERROR);
        }
        return $data;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        if (!$this->_cache) {
            $this->_cache = new Cache();
            $this->_cache->setMetadata($this);
        }

        return $this->_cache;
    }
}
