<?php

declare(strict_types=1);

namespace Nip\Database\Query;

/**
 * INSERT query builder.
 *
 * @package Nip\Database\Query
 */
class Insert extends AbstractQuery
{
    protected ?array $_cols = null;

    protected mixed $_values = null;

    public function assemble(): string
    {
        $return  = 'INSERT INTO ' . $this->protect($this->getTable());
        $return .= $this->parseCols();
        $return .= $this->parseValues();
        $return .= $this->parseOnDuplicate();

        return $return;
    }

    public function parseCols(): string
    {
        if (isset($this->parts['data'][0]) && is_array($this->parts['data'][0])) {
            $this->setCols(array_keys($this->parts['data'][0]));
        }

        return $this->_cols
            ? ' (' . implode(',', array_map([$this, 'protect'], $this->_cols)) . ')'
            : '';
    }

    public function setCols(?array $cols = null): static
    {
        $this->_cols = $cols;
        return $this;
    }

    public function parseValues(): string
    {
        if ($this->_values instanceof AbstractQuery) {
            return ' ' . (string) $this->_values;
        }

        if (is_array($this->parts['data'] ?? null)) {
            return $this->parseData();
        }

        return '';
    }

    protected function parseData(): string
    {
        $values = [];

        foreach ($this->parts['data'] as $key => $data) {
            foreach ($data as $value) {
                if (!is_array($value)) {
                    $value = [$value];
                }

                foreach ($value as $insertValue) {
                    if ($insertValue === null) {
                        $insertValue = 'NULL';
                    } else {
                        $insertValue = $this->getManager()->getAdapter()->quote($insertValue);
                    }
                    $values[$key][] = $insertValue;
                }
            }
        }

        foreach ($values as &$value) {
            $value = '(' . implode(', ', $value) . ')';
        }
        unset($value);

        return ' VALUES ' . implode(', ', $values);
    }

    public function parseOnDuplicate(): string
    {
        if ($this->hasPart('onDuplicate')) {
            $update = $this->getManager()->newUpdate();

            $onDuplicates = $this->getPart('onDuplicate');
            $data         = [];
            foreach ($onDuplicates as $onDuplicate) {
                foreach ($onDuplicate as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $update->data($data);

            return " ON DUPLICATE KEY UPDATE {$update->parseUpdate()}";
        }

        return '';
    }

    public function setValues(mixed $values): static
    {
        $this->_values = $values;

        return $this;
    }

    public function onDuplicate(array $value): void
    {
        $this->addPart('onDuplicate', $value);
    }
}
