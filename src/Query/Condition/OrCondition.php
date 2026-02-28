<?php

declare(strict_types=1);

namespace Nip\Database\Query\Condition;

/**
 * Combines two conditions with OR.
 *
 * @package Nip\Database\Query\Condition
 */
class OrCondition extends Condition
{
    protected Condition $_condition;
    protected Condition $_orCondition;

    public function __construct(Condition $condition, Condition $orCondition)
    {
        $this->_condition  = $condition;
        $this->_orCondition = $orCondition;
    }

    public function getString(): string
    {
        return $this->protectCondition($this->_condition->getString())
            . ' OR '
            . $this->protectCondition($this->_orCondition->getString());
    }
}
