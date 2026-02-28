<?php

declare(strict_types=1);

namespace Nip\Database\Query\Select;

use Nip\Database\Query\Select;

/**
 * Represents a UNION of two SELECT queries.
 *
 * @package Nip\Database\Query\Select
 */
class Union extends Select
{
    protected Select $_query1;
    protected Select $_query2;

    public function __construct(Select $query1, Select $query2)
    {
        $this->_query1 = $query1;
        $this->_query2 = $query2;
    }

    public function assemble(): string
    {
        $query  = ($this->_query1 instanceof Union) ? '(' . $this->_query1 . ')' : (string) $this->_query1;
        $query .= ' UNION ';
        $query .= ($this->_query2 instanceof Union) ? '(' . $this->_query2 . ')' : (string) $this->_query2;

        $order = $this->parseOrder();
        if (!empty($order)) {
            $query .= " ORDER BY {$order}";
        }

        if (!empty($this->parts['limit'])) {
            $query .= " LIMIT {$this->parts['limit']}";
        }

        return $query;
    }
}
