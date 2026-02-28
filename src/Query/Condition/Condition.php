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

    // -------------------------------------------------------------------------
    // Prepared-statement support
    // -------------------------------------------------------------------------

    /**
     * Return the SQL template with `?` positional placeholders intact.
     *
     * For array values the single `?` is expanded to `(?,?,…)` so the
     * returned string is ready to be passed to a PDO prepared statement.
     * Sub-query values are rendered inline (they cannot be parameterised).
     *
     * @return string SQL fragment, e.g. `"id = ?"` or `"status IN (?,?)"`
     */
    public function getParameterizedString(): string
    {
        $string = $this->_string;
        $values = $this->_values;

        $count = substr_count($string, '?');

        if ($count === 0) {
            return $string;
        }

        // Normalise single-placeholder case to an array for uniform iteration.
        if ($count === 1) {
            $values = [$values];
        }

        if (!is_array($values)) {
            return $string;
        }

        foreach ($values as $value) {
            if ($value instanceof Query) {
                // Sub-query: render parameterised SQL inline.
                $inline = '(' . $value->getParameterizedSql() . ')';
                $pos    = strpos($string, '?');
                if ($pos !== false) {
                    $string = substr_replace($string, $inline, $pos, 1);
                }
            } elseif (is_array($value)) {
                // IN / NOT IN list: expand one `?` to `(?,?,…)`.
                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $pos          = strpos($string, '?');
                if ($pos !== false) {
                    $string = substr_replace($string, '(' . $placeholders . ')', $pos, 1);
                }
            }
            // Scalar: leave the single `?` in place.
        }

        return $string;
    }

    /**
     * Return a flat list of binding values corresponding to the `?` placeholders
     * produced by {@see getParameterizedString()}.
     *
     * Sub-query bindings are recursively merged in left-to-right order.
     *
     * @return list<mixed>
     */
    public function getBindings(): array
    {
        $values = $this->_values;

        if ($values === null || $values === []) {
            return [];
        }

        $count = substr_count($this->_string, '?');

        if ($count === 0) {
            return [];
        }

        if ($count === 1) {
            $values = [$values];
        }

        if (!is_array($values)) {
            return [];
        }

        $bindings = [];

        foreach ($values as $value) {
            if ($value instanceof Query) {
                // Recursively include the sub-query's bindings.
                foreach ($value->getBindings() as $b) {
                    $bindings[] = $b;
                }
            } elseif (is_array($value)) {
                foreach ($value as $v) {
                    $bindings[] = $v;
                }
            } else {
                $bindings[] = $value;
            }
        }

        return $bindings;
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
