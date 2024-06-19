<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use DmitryProA\PhpAdvancedQuerying\Condition;
use DmitryProA\PhpAdvancedQuerying\Expression;

class ConditionExpression extends Expression
{
    /** @var Condition */
    public $condition;

    public function __construct(Condition $condition)
    {
        $this->condition = $condition;
    }
}
