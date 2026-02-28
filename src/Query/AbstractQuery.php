<?php

declare(strict_types=1);

namespace Nip\Database\Query;

use Nip\Database\Connections\Connection;
use Nip\Database\Query\Condition\Condition;
use Nip\Database\Result;

/**
 * Base query builder.
 *
 * Provides a fluent, magic-method-driven interface for building SQL queries.
 * Sub-classes implement {@see assemble()} to produce the final SQL string.
 *
 * @method $this setCols(array|string $cols = null)
 * @method $this setWhere(array|string $cols = null)
 * @method $this cols(array|string $cols)
 * @method $this count(string $col, string $alias = null)
 * @method $this sum(array|string $cols)
 * @method $this from(array|string $from)
 * @method $this data(array $data)
 * @method $this table(array|string $table)
 * @method $this order(array|string $order)
 * @method $this group(array|string $group, bool $rollup = false)
 *
 * @package Nip\Database\Query
 */
abstract class AbstractQuery
{
    protected Connection $db;

    /** @var array<string, mixed> */
    protected array $parts = [
        'where' => null,
    ];

    protected ?string $string = null;

    public function setManager(Connection $manager): static
    {
        $this->db = $manager;

        return $this;
    }

    /**
     * Magic method: routes set*() calls to initPart() and any other call to addPart().
     *
     * @param string $name
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): static
    {
        if (str_starts_with($name, 'set')) {
            $name = lcfirst(substr($name, 3));
            $this->initPart($name);
        }

        foreach ($arguments as $argument) {
            $this->addPart($name, $argument);
        }

        return $this;
    }

    protected function initPart(string $name): static
    {
        $this->isGenerated(false);
        $this->parts[$name] = [];

        return $this;
    }

    public function isGenerated(bool|null $generated = null): bool
    {
        if ($generated === false) {
            $this->string = null;
        }

        return $this->string !== null;
    }

    protected function addPart(string $name, mixed $value): static
    {
        if (!isset($this->parts[$name])) {
            $this->initPart($name);
        }

        $this->isGenerated(false);
        $this->parts[$name][] = $value;

        return $this;
    }

    /** @param array<string, mixed> $params */
    public function addParams(array $params): void
    {
        $this->checkParamSelect($params);
        $this->checkParamFrom($params);
        $this->checkParamWhere($params);
        $this->checkParamOrder($params);
        $this->checkParamGroup($params);
        $this->checkParamHaving($params);
        $this->checkParamLimit($params);
    }

    /** @param array<string, mixed> $params */
    protected function checkParamSelect(array $params): void
    {
        if (isset($params['select']) && is_array($params['select'])) {
            call_user_func_array([$this, 'cols'], $params['select']);
        }
    }

    /** @param array<string, mixed> $params */
    protected function checkParamFrom(array $params): void
    {
        if (isset($params['from']) && !empty($params['from'])) {
            $this->from($params['from']);
        }
    }

    /** @param array<string, mixed> $params */
    protected function checkParamWhere(array $params): void
    {
        if (isset($params['where']) && is_array($params['where'])) {
            foreach ($params['where'] as $condition) {
                if ($condition instanceof Condition) {
                    $condition->setQuery($this);
                    $this->where($condition);
                    continue;
                }
                $condition = (array) $condition;
                $this->where(
                    $condition[0],
                    $condition[1] ?? null
                );
            }
        }
    }

    /**
     * Add a WHERE clause (AND-chained).
     *
     * @param Condition|string $string
     * @param mixed            $values
     */
    public function where(mixed $string, mixed $values = []): static
    {
        if ($string) {
            if ($this->parts['where'] instanceof Condition) {
                $this->parts['where'] = $this->parts['where']->and_($this->getCondition($string, $values));
            } else {
                $this->parts['where'] = $this->getCondition($string, $values);
            }
        }

        return $this;
    }

    /**
     * Build or return a Condition instance.
     *
     * @param Condition|string $string
     * @param mixed            $values
     */
    public function getCondition(mixed $string, mixed $values = []): Condition
    {
        if (!is_object($string)) {
            $condition = new Condition((string) $string, $values);
            $condition->setQuery($this);
        } else {
            $condition = $string;
        }

        return $condition;
    }

    /** @param array<string, mixed> $params */
    protected function checkParamOrder(array $params): void
    {
        if (isset($params['order']) && !empty($params['order'])) {
            call_user_func_array([$this, 'order'], $params['order']);
        }
    }

    /** @param array<string, mixed> $params */
    protected function checkParamGroup(array $params): void
    {
        if (isset($params['group']) && !empty($params['group'])) {
            call_user_func_array([$this, 'group'], [$params['group']]);
        }
    }

    /** @param array<string, mixed> $params */
    protected function checkParamHaving(array $params): void
    {
        if (isset($params['having']) && !empty($params['having'])) {
            call_user_func_array([$this, 'having'], [$params['having']]);
        }
    }

    /** @param array<string, mixed> $params */
    protected function checkParamLimit(array $params): void
    {
        if (isset($params['limit']) && !empty($params['limit'])) {
            call_user_func_array([$this, 'limit'], [$params['limit']]);
        }
    }

    public function limit(int|string $start, int|string|false $offset = false): static
    {
        $this->parts['limit'] = (string) $start;
        if ($offset !== false) {
            $this->parts['limit'] .= ',' . $offset;
        }

        return $this;
    }

    /**
     * Add a WHERE clause (OR-chained).
     *
     * @param Condition|string $string
     * @param mixed            $values
     */
    public function orWhere(mixed $string, mixed $values = []): static
    {
        if ($string) {
            if ($this->parts['where'] instanceof Condition) {
                $this->parts['where'] = $this->parts['where']->or_($this->getCondition($string, $values));
            } else {
                $this->parts['where'] = $this->getCondition($string, $values);
            }
        }

        return $this;
    }

    /**
     * Add a HAVING clause (AND-chained).
     *
     * @param Condition|string $string
     * @param mixed            $values
     */
    public function having(mixed $string, mixed $values = []): static
    {
        if (empty($string)) {
            return $this;
        }

        $condition = $this->getCondition($string, $values);
        $having    = $this->getPart('having');

        if ($having instanceof Condition) {
            $having = $having->and_($condition);
        } else {
            $having = $condition;
        }
        $this->parts['having'] = $having;

        return $this;
    }

    /** Escape a value for safe use in a SQL literal. */
    public function cleanData(mixed $data): mixed
    {
        return $this->getManager()->getAdapter()->cleanData($data);
    }

    public function getManager(): Connection
    {
        return $this->db;
    }

    public function execute(): Result
    {
        return $this->getManager()->execute($this);
    }

    public function __toString(): string
    {
        return $this->getString();
    }

    public function getString(): string
    {
        if ($this->string === null) {
            $this->string = (string) $this->assemble();
        }

        return $this->string;
    }

    abstract public function assemble(): string;

    /** @return array<string, mixed> */
    public function getParts(): array
    {
        return $this->parts;
    }

    protected function assembleWhere(): string
    {
        $where = $this->parseWhere();

        return !empty($where) ? " WHERE {$where}" : '';
    }

    protected function parseWhere(): string
    {
        return ($this->parts['where'] instanceof Condition) ? (string) $this->parts['where'] : '';
    }

    protected function assembleLimit(): string
    {
        $limit = $this->getPart('limit');
        if (!empty($limit)) {
            return " LIMIT {$limit}";
        }

        return '';
    }

    public function getPart(string $name): mixed
    {
        return $this->hasPart($name) ? $this->parts[$name] : null;
    }

    public function hasPart(string $name): bool
    {
        if (!isset($this->parts[$name])) {
            return false;
        }
        if ($this->parts[$name] === null) {
            return false;
        }
        if (is_array($this->parts[$name]) && count($this->parts[$name]) < 1) {
            return false;
        }
        if (is_string($this->parts[$name]) && $this->parts[$name] === '') {
            return false;
        }

        return true;
    }

    protected function setPart(string $name, mixed $value): static
    {
        $this->initPart($name);
        $this->addPart($name, $value);

        return $this;
    }

    protected function getTable(): string
    {
        if (!is_array($this->parts['table']) || count($this->parts['table']) < 1) {
            trigger_error('No Table defined', E_USER_WARNING);
            return '';
        }

        return (string) reset($this->parts['table']);
    }

    protected function parseHaving(): string
    {
        if (isset($this->parts['having'])) {
            return (string) $this->parts['having'];
        }

        return '';
    }

    protected function parseOrder(): string
    {
        if (!isset($this->parts['order']) || !is_array($this->parts['order']) || count($this->parts['order']) < 1) {
            return '';
        }

        $orderParts = [];

        foreach ($this->parts['order'] as $itemOrder) {
            if ($itemOrder) {
                if (!is_array($itemOrder)) {
                    $itemOrder = [$itemOrder];
                }

                $column    = $itemOrder[0] ?? false;
                $type      = $itemOrder[1] ?? '';
                $protected = $itemOrder[2] ?? true;

                $column = ($protected ? $this->protect((string) $column) : (string) $column) . ' ' . strtoupper((string) $type);

                $orderParts[] = trim($column);
            }
        }

        return implode(', ', $orderParts);
    }

    /**
     * Wrap an identifier in back-ticks.
     *
     * Function calls (containing '(') are left as-is.
     */
    protected function protect(string $input): string
    {
        return str_contains($input, '(')
            ? $input
            : str_replace('`*`', '*', '`' . str_replace('.', '`.`', $input) . '`');
    }

    protected function tableName(string $table = ''): string
    {
        return $this->getManager()->tableName($table);
    }

    protected function cleanProtected(string $input): string
    {
        return str_replace('`', '', $input);
    }
}
