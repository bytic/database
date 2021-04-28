<?php

namespace Nip\Database\Connections;

use Nip\Database\Adapters\HasAdapterTrait;
use Nip\Database\Exception;
use Nip\Database\Metadata\Manager as MetadataManager;
use Nip\Database\Query\AbstractQuery as AbstractQuery;
use Nip\Database\Query\Delete as DeleteQuery;
use Nip\Database\Query\Insert as InsertQuery;
use Nip\Database\Query\Select as SelectQuery;
use Nip\Database\Query\Update as UpdateQuery;
use Nip\Database\Result;
use PDO;

/**
 * Class Connection
 * @package Nip\Database\Connections
 */
class Connection extends \Doctrine\DBAL\Connection
{
    use ConnectionLegacyMethods;

    use HasAdapterTrait;

    /**
     * The active PDO connection.
     *
     * @var PDO
     */
    protected $pdo;

    protected $metadata;

    protected $_queries = [];


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
     * @param string $type optional
     *
     * @return AbstractQuery|SelectQuery
     */
    public function newSelect()
    {
        return $this->newQuery('select');
    }

    /**
     * @param string $type optional
     * @return AbstractQuery|SelectQuery|UpdateQuery|InsertQuery|DeleteQuery
     */
    public function newQuery($type = "select")
    {
        $className = '\Nip\Database\Query\\' . inflector()->camelize($type);
        $query = new $className();
        /** @var AbstractQuery $query */
        $query->setManager($this);

        return $query;
    }

    /**
     * @return InsertQuery
     */
    public function newInsert()
    {
        return $this->newQuery('insert');
    }

    /**
     * @return UpdateQuery
     */
    public function newUpdate()
    {
        return $this->newQuery('update');
    }

    /**
     * @return DeleteQuery
     */
    public function newDelete()
    {
        return $this->newQuery('delete');
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

    /**
     * Adds backticks to input
     *
     * @param string $input
     * @return string
     */
    public function protect($input)
    {
        return str_replace("`*`", "*", '`' . str_replace('.', '`.`', $input) . '`');
    }

    /**
     * @return array
     */
    public function getQueries()
    {
        return $this->_queries;
    }
}
