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
}
