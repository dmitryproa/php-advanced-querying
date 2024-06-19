<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Conditions;

use DmitryProA\PhpAdvancedQuerying\Condition;
use DmitryProA\PhpAdvancedQuerying\Expression;
use DmitryProA\PhpAdvancedQuerying\Expressions\LiteralExpression;

use function DmitryProA\PhpAdvancedQuerying\checkArrayType;

class InCondition extends Condition
{
    /** @var Expression */
    public $expr;

    /** @var LiteralExpression[] */
    public $values;

    /** @param LiteralExpression $values */
    public function __construct(Expression $expr, ...$values)
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('Values array must not be empty');
        }

        checkArrayType($values, LiteralExpression::class);

        $this->expr = $expr;
        $this->values = $values;
    }
}
