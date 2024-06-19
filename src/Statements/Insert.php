<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Statements;

use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;

/**
 * @method Insert setTable(null|string|Table $table)
 * @method Insert setFields(array<ColumnExpression|string> $fields)
 * @method Insert setValues(array $values)
 */
class Insert extends Replace
{
    use InsertStatement;
}
