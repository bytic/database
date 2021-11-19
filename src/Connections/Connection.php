<?php

namespace Nip\Database\Connections;

use Nip\Database\Adapters\HasAdapterTrait;
use Nip\Database\Metadata\Manager as MetadataManager;
use Nip\Database\Query\AbstractQuery as AbstractQuery;
use Nip\Database\Query\Delete as DeleteQuery;
use Nip\Database\Query\Insert as InsertQuery;
use Nip\Database\Query\Select as SelectQuery;
use Nip\Database\Query\Update as UpdateQuery;
use Nip\Database\Result;

/**
 * Class Connection
 * @package Nip\Database\Connections
 */
class Connection extends \Doctrine\DBAL\Connection
{
    use ConnectionLegacyMethods;

    use HasAdapterTrait;

    protected $metadata;


    /**
     * @return MetadataManager
     */
    public function getMetadata()
    {
        if (!$this->metadata) {
            $this->metadata = new MetadataManager();
            $this->metadata->setConnection($this);
        }

        return $this->metadata;
    }

    /**
     * Prefixes table names
     *
     * @param string $table
     * @return string
     */
    public function tableName($table)
    {
        return $table;
    }



    /**
     * Executes SQL query
     *
     * @param mixed|AbstractQuery $query
     * @return Result
     */
    public function execute($query)
    {
        $this->_queries[] = $query;

        $sql = is_string($query) ? $query : $query->getString();

        $resultSQL = $this->getAdapter()->execute($sql);
        $result = new Result($resultSQL, $this->getAdapter());
        $result->setQuery($query);

        return $result;
    }

    /**
     * Gets the number of rows affected by the last operation
     * @return int
     */
    public function affectedRows()
    {
        return $this->getAdapter()->affectedRows();
    }

    /**
     * @param null|string $table
     * @return mixed
     */
    public function describeTable($table)
    {
        return $this->getAdapter()->describeTable($this->protect($table));
    }



}
