<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use DmitryProA\PhpAdvancedQuerying\Expression;

use function DmitryProA\PhpAdvancedQuerying\checkArrayType;

/**
 * @property string       $function
 * @property Expression[] $arguments
 */
class FunctionExpression extends Expression
{
    public $function;
    public $arguments;

    /** @param Expression $args */
    public function __construct(string $function, ...$args)
    {
        $this->function = $function;

        checkArrayType($args, Expression::class);
        $this->arguments = $args;
    }
}
