<?php

namespace Nip\Database\Connections;

use Nip\Database\Exception;

/**
 * Trait ConnectionLegacyMethods
 * @package Nip\Database\Connections
 */
trait ConnectionLegacyMethods
{
    protected $_query;


    /**
     * @param string $database
     * @deprecated Databases should be selected in config
     */
    public function setDatabase($database)
    {
    }

    /**
     * Disconnects from server
     * @deprecated use $connection->close()
     */
    public function disconnect()
    {
        $this->close();
    }
}