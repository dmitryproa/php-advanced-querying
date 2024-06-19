<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use DmitryProA\PhpAdvancedQuerying\Expression;
use DmitryProA\PhpAdvancedQuerying\InvalidTypeException;

class LiteralExpression extends Expression
{
    public $value;

    public function __construct($value)
    {
        if (is_object($value) || is_array($value)) {
            throw new InvalidTypeException('Objects and arrays are not allowed in LiteralExpression');
        }

        $this->value = $value;
    }
}
