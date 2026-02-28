<?php

declare(strict_types=1);

namespace Nip\Database\Query;

/**
 * DELETE query builder.
 *
 * @package Nip\Database\Query
 */
class Delete extends AbstractQuery
{
    public function assemble(): string
    {
        $query  = 'DELETE FROM ' . $this->getManager()->protect($this->getTable());
        $query .= $this->assembleWhere();

        $order = $this->parseOrder();
        if (!empty($order)) {
            $query .= " ORDER BY {$order}";
        }

        $query .= $this->assembleLimit();

        return $query;
    }
}
