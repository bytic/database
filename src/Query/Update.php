<?php

declare(strict_types=1);

namespace Nip\Database\Query;

/**
 * UPDATE query builder.
 *
 * @package Nip\Database\Query
 */
class Update extends AbstractQuery
{
    public function assemble(): string
    {
        $query  = 'UPDATE ' . $this->protect($this->getTable()) . ' SET ' . $this->parseUpdate();
        $query .= $this->assembleWhere();
        $query .= $this->assembleLimit();

        return $query;
    }

    public function parseUpdate(): string
    {
        if (empty($this->parts['data'])) {
            return '';
        }

        $fields = [];

        foreach ($this->parts['data'] as $data) {
            foreach ($data as $key => $values) {
                if (!is_array($values)) {
                    $values = [$values];
                }
                $value = $values[0];
                $quote = $values[1] ?? null;

                if ($value === null) {
                    $value = 'NULL';
                } elseif (!is_numeric($value)) {
                    if ($quote === null) {
                        $quote = true;
                    }
                    if ($quote) {
                        $value = $this->getManager()->getAdapter()->quote($value);
                    }
                }

                $fields[] = "{$this->protect((string) $key)} = {$value}";
            }
        }

        return implode(', ', $fields);
    }
}
