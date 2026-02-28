<?php

declare(strict_types=1);

namespace Nip\Database\Manager;

/**
 * Provides an optional Application reference to the owning class.
 *
 * @package Nip\Database\Manager
 */
trait HasApplication
{
    protected mixed $application = null;

    public function setApplication(mixed $application): void
    {
        $this->application = $application;
    }

    public function getBootstrap(): mixed
    {
        return $this->application;
    }

    public function setBootstrap(mixed $bootstrap): void
    {
        $this->application = $bootstrap;
    }
}
