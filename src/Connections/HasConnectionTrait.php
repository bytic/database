<?php

namespace Nip\Database\Connections;

/**
 * Trait HasConnectionTrait
 * @package Nip\Database\Connections
 */
trait HasConnectionTrait
{
    protected $connection;

    /**
     * @param $wrapper
     * @return $this
     */
    public function setConnection($wrapper)
    {
        $this->connection = $wrapper;
        return $this;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}