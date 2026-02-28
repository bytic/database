<?php

declare(strict_types=1);

namespace Nip\Database\Query;

/**
 * REPLACE INTO query builder.
 *
 * @package Nip\Database\Query
 */
class Replace extends Insert
{
    public function assemble(): string
    {
        return 'REPLACE INTO ' . $this->protect($this->getTable()) . $this->parseCols() . $this->parseValues();
    }
}
