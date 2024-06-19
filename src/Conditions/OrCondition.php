<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Conditions;

use DmitryProA\PhpAdvancedQuerying\Condition;

use function DmitryProA\PhpAdvancedQuerying\or_;

class OrCondition extends MultipleCondition
{
    public function or(...$conditions): Condition
    {
        return or_($this, ...$conditions);
    }
}
