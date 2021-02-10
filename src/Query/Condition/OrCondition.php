<?php

namespace Nip\Database\Query\Condition;

/**
 * Class OrCondition
 * @package Nip\Database\Query\Condition
 */
class OrCondition extends Condition
{
    protected $_condition;
    protected $_orCondition;

    /**
     * OrCondition constructor.
     * @param $condition
     * @param $orCondition
     */
    public function __construct($condition, $orCondition)
    {
        $this->_condition = $condition;
        $this->_orCondition = $orCondition;
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->protectCondition($this->_condition->getString()) . ' OR ' . $this->protectCondition($this->_orCondition->getString()) . '';
    }
}
