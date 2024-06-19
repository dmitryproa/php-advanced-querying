<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying;

use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;

class FieldValue
{
    /** @var ColumnExpression */
    public $field;

    /** @var Expression */
    public $value;

    public function __construct(ColumnExpression $field, Expression $value)
    {
        $this->field = $field;
        $this->value = $value;
    }
}
