<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use function DmitryProA\PhpAdvancedQuerying\checkArrayType;

class CountExpression extends FunctionExpression
{
    /** @var bool */
    public $distinct;

    /** @param ColumnExpression $columns */
    public function __construct(bool $distinct = false, ...$columns)
    {
        if ($distinct && !$columns) {
            throw new \InvalidArgumentException('Columns must be specified for COUNT(DISTINCT...) expression');
        }
        checkArrayType($columns, ColumnExpression::class);

        $this->arguments = $columns;
        $this->distinct = $distinct;
    }
}
