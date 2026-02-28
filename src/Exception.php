<?php

declare(strict_types=1);

namespace Nip\Database;

/**
 * Base exception class for the database package.
 *
 * The `log()` method is retained for backward compatibility with existing
 * catch blocks that call it; it is a no-op by default.
 *
 * @package Nip\Database
 */
class Exception extends \RuntimeException
{
    /**
     * Log this exception.
     *
     * Kept for backward compatibility.  Override in a sub-class or replace
     * with a proper PSR-3 logger integration as needed.
     */
    public function log(): void
    {
        // no-op – override or replace with a PSR-3 logger call
    }
}

