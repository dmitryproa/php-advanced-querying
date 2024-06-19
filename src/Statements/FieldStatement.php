<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Statements;

use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\Statement;

use function DmitryProA\PhpAdvancedQuerying\columns;

trait FieldStatement
{
    /** @var ColumnExpression[] */
    protected $fields;

    /** @param array<ColumnExpression|string> $fields */
    public function setFields(array $fields): Statement
    {
        $this->fields = columns(...$fields);

        return $this;
    }

    /** @return ColumnExpression[] */
    public function getFields(): array
    {
        return $this->fields;
    }
}
