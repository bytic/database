<?php

declare(strict_types=1);

namespace Nip\Database\Query\Condition;

use Nip\Database\Query\AbstractQuery as Query;

/**
 * Represents a single SQL condition (fragment of a WHERE or HAVING clause).
 *
 * Placeholders (`?`) inside the string are replaced with properly quoted
 * values at render time via {@see getString()}.
 *
 * @package Nip\Database\Query\Condition
 */
class Condition
{
    protected string $_string;
    protected mixed $_values;
    protected ?Query $_query = null;

    public function __construct(string $string, mixed $values = [])
    {
        $this->_string = $string;
        $this->_values = $values;
    }

    public function __toString(): string
    {
        return $this->getString();
    }

    public function getString(): string
    {
        return $this->parseString($this->_string, $this->_values);
    }

    /**
     * Replace every `?` placeholder with the corresponding quoted value.
     */
    public function parseString(string $string, mixed $values): string
    {
        $positions = [];
        $offset    = 0;

        while (($pos = strpos($string, '?', $offset)) !== false) {
            $positions[] = $pos;
            $offset      = $pos + 1;
        }

        $count = count($positions);

        if ($count === 1) {
            $values = [$values];
        }

        for ($i = 0; $i < $count; $i++) {
            $value = $values[$i];

            if ($value instanceof Query) {
                $value = $this->parseValueQuery($value);
            } elseif (is_array($value)) {
                foreach ($value as $key => $subvalue) {
                    if (trim((string) $subvalue) !== '') {
                        $value[$key] = is_numeric($subvalue)
                            ? $subvalue
                            : $this->getQuery()->getManager()->getAdapter()->quote($subvalue);
                    } else {
                        unset($value[$key]);
                    }
                }
                $value = '(' . implode(', ', $value) . ')';
            } elseif (is_int($value) || is_float($value)) {
                // numeric – use as-is
            } else {
                $value = $this->getQuery()->getManager()->getAdapter()->quote($values[$i]);
            }

            $string = substr_replace($string, (string) $value, strpos($string, '?'), 1);
        }

        return $string;
    }

    protected function parseValueQuery(Query $value): string
    {
        return '(' . $value->assemble() . ')';
    }

    public function getQuery(): ?Query
    {
        return $this->_query;
    }

    public function setQuery(Query $query): static
    {
        $this->_query = $query;

        return $this;
    }

    public function and_(Condition $condition): AndCondition
    {
        return new AndCondition($this, $condition);
    }

    public function or_(Condition $condition): OrCondition
    {
        return new OrCondition($this, $condition);
    }

    public function protectCondition(string $condition): string
    {
        return (str_contains($condition, ' AND ') || str_contains($condition, ' OR '))
            ? '(' . $condition . ')'
            : $condition;
    }
}
