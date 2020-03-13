<?php

namespace Nip\Database\Adapters;

/**
 * Trait HasAdapterTrait
 * @package Nip\Database\Adapters
 */
trait HasAdapterTrait
{
    protected $_adapter = null;

    /**
     * @return AbstractAdapter
     */
    public function getAdapter()
    {
        if ($this->_adapter == null) {
            $this->initAdapter();
        }

        return $this->_adapter;
    }

    /**
     * @param $adapter
     */
    public function setAdapter($adapter)
    {
        $this->_adapter = $adapter;
    }

    public function initAdapter()
    {
        $this->setAdapterName('MySQLi');
    }

    /**
     * @param $name
     */
    public function setAdapterName($name)
    {
        $this->setAdapter($this->newAdapter($name));
    }

    /**
     * @param $name
     *
     * @return AbstractAdapter
     */
    public function newAdapter($name)
    {
        $class = static::getAdapterClass($name);

        return new $class();
    }

    /**
     * @param $name
     * @return string
     */
    public static function getAdapterClass($name)
    {
        return '\Nip\Database\Adapters\\'.$name;
    }

}
