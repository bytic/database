<?php

namespace Nip\Database\Manager;

use Nip\Application;

/**
 * Trait HasApplication
 * @package Nip\Database\Manager
 */
trait HasApplication
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @param Application $application
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @return Bootstrap
     */
    public function getBootstrap()
    {
        return $this->application;
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public function setBootstrap($bootstrap)
    {
        $this->application = $bootstrap;
    }
}
