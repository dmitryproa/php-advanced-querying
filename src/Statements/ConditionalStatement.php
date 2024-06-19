<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Statements;

use DmitryProA\PhpAdvancedQuerying\Condition;

use function DmitryProA\PhpAdvancedQuerying\and_;

trait ConditionalStatement
{
    /** @var Condition */
    protected $condition;

    /** @var callable */
    protected $conditionCallback;

    public function where(): Condition
    {
        $this->condition = and_();
        $this->condition->setStatement($this);
        $this->conditionCallback = [$this, 'setWhere'];

        return $this->condition;
    }

    /** @return null|Condition */
    public function getCondition()
    {
        return $this->condition;
    }

    public function setCondition(Condition $cond): void
    {
        ($this->conditionCallback ?? [$this, 'setWhere'])($cond);
    }

    protected function setWhere(Condition $cond): void
    {
        $this->condition = $cond;
    }
}
