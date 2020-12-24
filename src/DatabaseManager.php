<?php

namespace Nip\Database;

use InvalidArgumentException;
use Nip\Application\ApplicationInterface;
use Nip\Database\Connections\ConnectionFactory;
use Nip\Database\Manager\HasApplication;
use Nip\Database\Manager\HasConnections;

/**
 * Class DatabaseManager
 * @package Nip\Database
 */
class DatabaseManager
{
    use HasApplication;
    use HasConnections;

    /**
     * The database connection factory instance.
     *
     * @var ConnectionFactory
     */
    protected $factory;

    /**
     * DatabaseManager constructor.
     * @param ApplicationInterface $application
     * @param ConnectionFactory $factory
     */
    public function __construct(ApplicationInterface $application = null, ConnectionFactory $factory = null)
    {
        $this->application = $application;
        $this->factory = $factory ? $factory : new ConnectionFactory();
    }
}
