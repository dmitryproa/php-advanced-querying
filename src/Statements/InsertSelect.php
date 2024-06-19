<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Statements;

/**
 * @method InsertSelect setTable(null|string|Table $table)
 * @method InsertSelect setFields(array<ColumnExpression|string> $fields)
 * @method InsertSelect select(Select $select)
 */
class InsertSelect extends ReplaceSelect
{
    use InsertStatement;
}
