<?php

declare(strict_types=1);

namespace Nip\Database;

use Nip\Application\ApplicationInterface;
use Nip\Database\Connections\ConnectionFactory;
use Nip\Database\Manager\HasApplication;
use Nip\Database\Manager\HasConnections;

/**
 * Manages multiple named database connections.
 *
 * Acts as the central entry-point for obtaining Connection instances.
 * Follows the Symfony DBAL-inspired approach of a single manager that
 * lazily creates and caches connections from configuration.
 *
 * @package Nip\Database
 */
class DatabaseManager
{
    use HasApplication;
    use HasConnections;

    protected ConnectionFactory $factory;

    public function __construct(
        ?ApplicationInterface $application = null,
        ?ConnectionFactory $factory = null
    ) {
        $this->application = $application;
        $this->factory     = $factory ?? new ConnectionFactory();
    }
}
