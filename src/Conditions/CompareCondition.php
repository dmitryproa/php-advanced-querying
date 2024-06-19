<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Conditions;

use DmitryProA\PhpAdvancedQuerying\Expression;

class CompareCondition extends BinaryCondition
{
    const EQUALS = '=';
    const GREATER = '>';
    const GREATER_EQUALS = '>=';
    const LESS = '<';
    const LESS_EQUALS = '<=';

    /** @var string */
    public $type;

    public function __construct(Expression $left, Expression $right, string $type)
    {
        if (!in_array($type, (new \ReflectionClass(__CLASS__))->getConstants())) {
            throw new \InvalidArgumentException("Invalid type '{$type}'");
        }

        $this->left = $left;
        $this->right = $right;
        $this->type = $type;
    }
}
