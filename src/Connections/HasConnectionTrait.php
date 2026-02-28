<?php

declare(strict_types=1);

namespace Nip\Database\Connections;

/**
 * Provides a typed $connection property and accessor pair.
 *
 * @package Nip\Database\Connections
 */
trait HasConnectionTrait
{
    protected ?Connection $connection = null;

    public function setConnection(Connection $connection): static
    {
        $this->connection = $connection;
        return $this;
    }

    public function getConnection(): ?Connection
    {
        return $this->connection;
    }
}

