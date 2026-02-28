<?php

declare(strict_types=1);

namespace Nip\Database\Adapters;

/**
 * Provides lazy-initialised adapter access to any class that uses it.
 *
 * @package Nip\Database\Adapters
 */
trait HasAdapterTrait
{
    protected ?AbstractAdapter $_adapter = null;

    public function getAdapter(): AbstractAdapter
    {
        if ($this->_adapter === null) {
            $this->initAdapter();
        }

        return $this->_adapter;
    }

    public function setAdapter(AbstractAdapter $adapter): void
    {
        $this->_adapter = $adapter;
    }

    public function initAdapter(): void
    {
        $this->setAdapterName('MySQLi');
    }

    public function setAdapterName(string $name): void
    {
        $this->setAdapter($this->newAdapter($name));
    }

    public function newAdapter(string $name): AbstractAdapter
    {
        $class = static::getAdapterClass($name);

        return new $class();
    }

    public static function getAdapterClass(string $name): string
    {
        return '\\Nip\\Database\\Adapters\\' . $name;
    }
}

