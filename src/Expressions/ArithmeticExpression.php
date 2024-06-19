<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use DmitryProA\PhpAdvancedQuerying\Expression;

class ArithmeticExpression extends Expression
{
    const PLUS = '+';
    const MINUS = '-';
    const MULTIPLY = '*';
    const DIVIDE = '/';
    const MOD = '%';

    /** @var string */
    public $op;

    /** @var Expression */
    public $left;

    /** @var Expression */
    public $right;

    public function __construct(Expression $left, Expression $right, string $op)
    {
        if (!in_array($op, (new \ReflectionClass(__CLASS__))->getConstants())) {
            throw new \InvalidArgumentException("Invalid operator '{$op}'");
        }

        $this->left = $left;
        $this->right = $right;
        $this->op = $op;
    }
}
