<?php

declare(strict_types=1);

namespace Nip\Database;

use Nip\Container\ServiceProviders\Providers\AbstractServiceProvider;
use Nip\Database\Connections\ConnectionFactory;

/**
 * Registers database services with the IoC container.
 *
 * @package Nip\Database
 */
class DatabaseServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerConnectionServices();
    }

    /**
     * Register the primary database bindings.
     */
    protected function registerConnectionServices(): void
    {
        $this->getContainer()->share('db.factory', ConnectionFactory::class);

        $this->getContainer()->share('db', DatabaseManager::class);

        $this->getContainer()->share('db.connection', function () {
            return app('db')->connection();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provides(): array
    {
        return ['db', 'db.factory', 'db.connection'];
    }
}
