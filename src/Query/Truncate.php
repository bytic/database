<?php

declare(strict_types=1);

namespace Nip\Database\Query;

/**
 * TRUNCATE TABLE query builder.
 *
 * @package Nip\Database\Query
 */
class Truncate extends AbstractQuery
{
    public function assemble(): string
    {
        return 'TRUNCATE TABLE ' . $this->protect($this->getTable());
    }
}
