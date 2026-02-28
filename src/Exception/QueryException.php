<?php

declare(strict_types=1);

namespace Nip\Database\Exception;

use Nip\Database\Exception;

/**
 * Thrown when a database query fails to execute.
 *
 * @package Nip\Database\Exception
 */
class QueryException extends Exception
{
    public function __construct(
        private readonly string $sql,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message ?: "Query failed: [{$sql}]", $code, $previous);
    }

    public function getSql(): string
    {
        return $this->sql;
    }
}
