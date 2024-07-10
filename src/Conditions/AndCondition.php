<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Conditions;

use DmitryProA\PhpAdvancedQuerying\Condition;

class AndCondition extends MultipleCondition
{
    protected function setCondition(Condition $condition): MultipleCondition
    {
        $condition->statement = $this->statement;
        $this->conditions[] = $condition;

        return $this;
    }
}
