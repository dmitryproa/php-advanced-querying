<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Conditions;

use DmitryProA\PhpAdvancedQuerying\Condition;
use DmitryProA\PhpAdvancedQuerying\Expression;

class UnaryCondition extends Condition
{
    /** @var Expression */
    public $expr;

    public function __construct(Expression $expr)
    {
        $this->expr = $expr;
    }
}
