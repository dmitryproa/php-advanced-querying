<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Conditions;

use DmitryProA\PhpAdvancedQuerying\Condition;

class NotCondition extends Condition
{
    /** @var Condition */
    public $condition;

    public function __construct(Condition $condition)
    {
        $this->condition = $condition;
    }
}
