<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying;

use DmitryProA\PhpAdvancedQuerying\Conditions\AndCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\MultipleCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\OrCondition;
use DmitryProA\PhpAdvancedQuerying\Statements\ConditionalStatement;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;
use DmitryProA\PhpAdvancedQuerying\Statements\Update;

abstract class Condition
{
    /** @var ConditionalStatement|Statement */
    protected $statement;

    /** @return AndCondition|OrCondition */
    public function or(...$conditions): Condition
    {
        return $this->setCondition(or_(...$conditions));
    }

    public function and(...$conditions): AndCondition
    {
        $condition = and_($this, ...$conditions);
        $condition->statement = $this->statement;

        return $condition;
    }

    public function eq($left, $right): AndCondition
    {
        return $this->setCondition(eq($left, $right));
    }

    public function greater($left, $right): AndCondition
    {
        return $this->setCondition(greater($left, $right));
    }

    public function greaterEquals($left, $right): AndCondition
    {
        return $this->setCondition(greaterEquals($left, $right));
    }

    public function less($left, $right): AndCondition
    {
        return $this->setCondition(less($left, $right));
    }

    public function lessEquals($left, $right): AndCondition
    {
        return $this->setCondition(lessEquals($left, $right));
    }

    public function like($left, $right): AndCondition
    {
        return $this->setCondition(like($left, $right));
    }

    public function in($expr, ...$values): AndCondition
    {
        return $this->setCondition(in($expr, ...$values));
    }

    public function isNull($expr): AndCondition
    {
        return $this->setCondition(isNull($expr));
    }

    public function notEq($left, $right): AndCondition
    {
        return $this->setCondition(notEq($left, $right));
    }

    public function notLike($left, $right): AndCondition
    {
        return $this->setCondition(notLike($left, $right));
    }

    public function notIn($expr, ...$values): AndCondition
    {
        return $this->setCondition(not(in($expr, ...$values)));
    }

    public function isNotNull($expr): AndCondition
    {
        return $this->setCondition(isNotNull($expr));
    }

    public function true($expr): AndCondition
    {
        return $this->setCondition(true($expr));
    }

    public function false($expr): AndCondition
    {
        return $this->setCondition(not(true($expr)));
    }

    public function setStatement(Statement $statement): self
    {
        if (!method_exists($statement, 'setCondition')) {
            throw new \Exception('Statement must be ConditionalStatement');
        }
        $this->statement = $statement;

        return $this;
    }

    /** @return Select|Update */
    public function end(): Statement
    {
        $this->statement->setCondition($this);

        return $this->statement;
    }

    protected function setCondition(Condition $condition): MultipleCondition
    {
        $condition = and_($this, $condition);
        $condition->statement = $this->statement;

        return $condition;
    }
}
