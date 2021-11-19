<?php

namespace Nip\Database\Query;

use Nip\Database\Connections\Connection;

/**
 * Class Update
 * @package Nip\Database\Query
 */
class Update extends AbstractQuery
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->builder->update();
    }

    public function data(array $data): AbstractQuery
    {
        foreach ($data as $key => $value) {
            $this->builder->set($key, $this->builder->getConnection()->quote($value));
//            $this->builder->setParameter('data_'.$key, $value);
        }

        return $this;
    }

    /**
     * @return bool|string
     */
    public function parseUpdate()
    {
        if (!$this->parts['data']) {
            return false;
        }
        $fields = [];
        foreach ($this->parts['data'] as $data) {
            foreach ($data as $key => $values) {
                if (!is_array($values)) {
                    $values = [$values];
                }
                $value = $values[0];
                $quote = isset($values[1]) ? $values[1] : null;

                if ($value === null) {
                    $value = 'NULL';
                } elseif (!is_numeric($value)) {
                    if (is_null($quote)) {
                        $quote = true;
                    }
                    if ($quote) {
                        $value = $this->getManager()->getAdapter()->quote($value);
                    }
                }

                $fields[] = "{$this->protect($key)} = $value";
            }
        }

        return implode(", ", $fields);
    }
}
