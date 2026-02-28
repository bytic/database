<?php

declare(strict_types=1);

namespace Nip\Database\Connections;

/**
 * MySQL-specific database connection.
 *
 * Extends {@see Connection} to allow MySQL-only configuration to be added
 * here in the future without polluting the generic Connection class.
 *
 * @package Nip\Database\Connections
 */
class MySqlConnection extends Connection
{
}

