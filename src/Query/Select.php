<?php

declare(strict_types=1);

namespace Nip\Database\Query;

use Nip\Database\Query\Select\Union;

/**
 * SELECT query builder.
 *
 * @method $this options(string $option = null)
 * @method $this setFrom(string $table = null)
 * @method $this setOrder(array|string $cols = null)
 *
 * @package Nip\Database\Query
 */
class Select extends AbstractQuery
{
    public function __call(string $name, array $arguments): static
    {
        if (in_array($name, ['min', 'max', 'count', 'avg', 'sum'], true)) {
            $input = reset($arguments);

            if (is_array($input)) {
                $input[] = false;
            } else {
                $alias     = $arguments[1] ?? null;
                $protected = $arguments[2] ?? null;
                $input     = [$input, $alias, $protected];
            }

            $input[0] = strtoupper($name) . '(' . $this->protect((string) $input[0]) . ')';

            return $this->cols($input);
        }

        return parent::__call($name, $arguments);
    }

    /**
     * Add a MATCH … AGAINST full-text condition and column.
     *
     * @param array<int, string|array{0: string, 1?: bool}> $fields
     */
    public function match(array $fields, string $against, string $alias, bool $boolean_mode = true): static
    {
        $match = [];
        foreach ($fields as $itemField) {
            if (!is_array($itemField)) {
                $itemField = [$itemField];
            }

            $field     = $itemField[0] ?? false;
            $protected = $itemField[1] ?? true;

            $match[] = $protected ? $this->protect((string) $field) : (string) $field;
        }
        $match = 'MATCH(' . implode(',', $match) . ") AGAINST ('" . $against . "'" . ($boolean_mode ? ' IN BOOLEAN MODE' : '') . ')';

        return $this->cols([$match, $alias, false])->where([$match]);
    }

    /**
     * Add a JOIN for the most recently added FROM table.
     *
     * @param string|array{0: string|AbstractQuery, 1?: string} $table  Table name or [table, alias]
     * @param string|array{0: string, 1: string}|false          $on     ON condition
     * @param string                                             $type   JOIN type (LEFT, RIGHT, INNER, …)
     */
    public function join(mixed $table, mixed $on = false, string $type = ''): static
    {
        $lastTable = end($this->parts['from']);

        if (!$lastTable) {
            trigger_error('No previous table to JOIN', E_USER_ERROR);
        }

        if (is_array($lastTable)) {
            $lastTable = $lastTable[1];
        }

        $this->parts['join'][$lastTable][] = [$table, $on, $type];

        return $this;
    }

    /**
     * Set the GROUP BY clause.
     *
     * @param array<int, string|array{0: string, 1?: string}>|string $fields
     */
    public function group(mixed $fields, bool $rollup = false): static
    {
        $this->parts['group']['fields'] = $fields;
        $this->parts['group']['rollup'] = $rollup;

        return $this;
    }

    public function assemble(): string
    {
        $select  = $this->parseCols();
        $options = $this->parseOptions();
        $from    = $this->parseFrom();
        $group   = $this->parseGroup();
        $having  = $this->parseHaving();
        $order   = $this->parseOrder();

        $query = 'SELECT';

        if (!empty($options)) {
            $query .= " {$options}";
        }

        if (!empty($select)) {
            $query .= " {$select}";
        }

        if (!empty($from)) {
            $query .= " FROM {$from}";
        }

        $query .= $this->assembleWhere();

        if (!empty($group)) {
            $query .= " GROUP BY {$group}";
        }

        if (!empty($having)) {
            $query .= " HAVING {$having}";
        }

        if (!empty($order)) {
            $query .= " ORDER BY {$order}";
        }

        $query .= $this->assembleLimit();

        return $query;
    }

    public function parseOptions(): ?string
    {
        if (!empty($this->parts['options'])) {
            return implode(' ', array_map('strtoupper', $this->parts['options']));
        }

        return null;
    }

    public function union(AbstractQuery $query): Union
    {
        return new Union($this, $query);
    }

    protected function parseCols(): string
    {
        if (!isset($this->parts['cols']) || !is_array($this->parts['cols']) || count($this->parts['cols']) < 1) {
            return '*';
        }

        $selectParts = [];

        foreach ($this->parts['cols'] as $itemSelect) {
            if (is_array($itemSelect)) {
                $field     = $itemSelect[0] ?? false;
                $alias     = $itemSelect[1] ?? false;
                $protected = $itemSelect[2] ?? true;

                $selectParts[] = ($protected ? $this->protect((string) $field) : (string) $field)
                    . (!empty($alias) ? ' AS ' . $this->protect((string) $alias) : '');
            } else {
                $selectParts[] = $itemSelect;
            }
        }

        return implode(', ', $selectParts);
    }

    private function parseFrom(): string
    {
        if (empty($this->parts['from'])) {
            return '';
        }

        $parts = [];

        foreach ($this->parts['from'] as $key => $item) {
            if (is_array($item)) {
                $table = $item[0] ?? false;
                $alias = $item[1] ?? false;

                if (is_object($table)) {
                    if (!$alias) {
                        trigger_error('Select statements in FROM need aliases defined', E_USER_ERROR);
                    }
                    $parts[$key] = '(' . $table . ') AS ' . $this->protect((string) $alias) . $this->parseJoin((string) $alias);
                } else {
                    $parts[$key] = $this->protect((string) $table)
                        . ' AS ' . $this->protect((string) (!empty($alias) ? $alias : $table))
                        . $this->parseJoin((string) (!empty($alias) ? $alias : $table));
                }
            } elseif (!str_contains((string) $item, ' ')) {
                $parts[] = $this->protect((string) $item) . $this->parseJoin((string) $item);
            } else {
                $parts[] = (string) $item;
            }
        }

        return implode(', ', array_unique($parts));
    }

    private function parseJoin(string $table): string
    {
        $result = '';

        if (!isset($this->parts['join'][$table])) {
            return $result;
        }

        foreach ($this->parts['join'][$table] as $join) {
            if (!is_array($join[0])) {
                $join[0] = [$join[0]];
            }

            $joinTable = $join[0][0] ?? false;
            $joinAlias = $join[0][1] ?? false;
            $joinOn    = $join[1] ?? false;
            $joinType  = $join[2] ?? '';

            $result .= ($joinType ? ' ' . strtoupper((string) $joinType) : '') . ' JOIN ';

            if ($joinTable instanceof AbstractQuery) {
                $result .= '(' . $joinTable . ')';
                if (empty($joinAlias)) {
                    $joinAlias = 'join1';
                }
                $joinTable = $joinAlias;
            } elseif (str_contains((string) $joinTable, '(')) {
                $result .= (string) $joinTable;
            } else {
                $result .= $this->protect((string) $joinTable);
            }

            $result .= (!empty($joinAlias) ? ' AS ' . $this->protect((string) $joinAlias) : '');

            if ($joinOn) {
                $result .= ' ON ';
                if (is_array($joinOn)) {
                    $result .= $this->protect($table . '.' . $joinOn[0])
                        . ' = '
                        . $this->protect($joinTable . '.' . $joinOn[1]);
                } else {
                    $result .= '(' . $joinOn . ')';
                }
            }
        }

        return $result;
    }

    private function parseGroup(): string
    {
        $group = '';

        if (isset($this->parts['group']['fields'])) {
            if (is_array($this->parts['group']['fields'])) {
                $groupFields = [];
                foreach ($this->parts['group']['fields'] as $field) {
                    $field  = is_array($field) ? $field : [$field];
                    $column = $field[0] ?? false;
                    $type   = $field[1] ?? '';

                    $groupFields[] = $this->protect((string) $column) . ($type ? ' ' . strtoupper((string) $type) : '');
                }

                $group .= implode(', ', $groupFields);
            } else {
                $group .= (string) $this->parts['group']['fields'];
            }
        }

        if (isset($this->parts['group']['rollup']) && $this->parts['group']['rollup'] !== false) {
            $group .= ' WITH ROLLUP';
        }

        return $group;
    }
}
