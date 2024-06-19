<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use DmitryProA\PhpAdvancedQuerying\Expression;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;

class SelectExpression extends Expression
{
    /** @var Select */
    public $select;

    public function __construct(Select $select)
    {
        $this->select = $select;
    }
}
