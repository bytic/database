<?php

declare(strict_types=1);

namespace Nip\Database\Query\Condition;

/**
 * Combines two conditions with AND.
 *
 * @package Nip\Database\Query\Condition
 */
class AndCondition extends Condition
{
    protected Condition $_condition;
    protected Condition $_andCondition;

    public function __construct(Condition $condition, Condition $andCondition)
    {
        $this->_condition    = $condition;
        $this->_andCondition = $andCondition;
    }

    public function getString(): string
    {
        return $this->protectCondition($this->_condition->getString())
            . ' AND '
            . $this->protectCondition($this->_andCondition->getString());
    }

    /**
     * Return the parameterised SQL template (both sides combined with AND).
     */
    public function getParameterizedString(): string
    {
        return $this->protectCondition($this->_condition->getParameterizedString())
            . ' AND '
            . $this->protectCondition($this->_andCondition->getParameterizedString());
    }

    /**
     * Return bindings from both sides in left-to-right order.
     *
     * @return list<mixed>
     */
    public function getBindings(): array
    {
        return array_merge(
            $this->_condition->getBindings(),
            $this->_andCondition->getBindings()
        );
    }
}
