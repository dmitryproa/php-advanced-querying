<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Statements;

use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\Statement;

use function DmitryProA\PhpAdvancedQuerying\literals;

/**
 * @method Replace setTable(null|string|Table $table)
 * @method Replace setFields(array<ColumnExpression|string> $fields)
 * @method Replace setValues(array $values)
 */
class Replace extends Statement
{
    use FieldStatement;

    /** @var LiteralExpression[][] */
    protected $values;

    /**
     * @param null|Table                     $table
     * @param array<ColumnExpression|string> $fields
     * @param array|array[]                  $values
     */
    public function __construct($table = null, array $fields = [], array $values = [])
    {
        $this->setTable($table);
        $this->setFields($fields);
        $this->setValues($values);
    }

    /** @param array|array[] $values */
    public function setValues($values): self
    {
        if ($values) {
            $valuesArrayCount = count(array_filter($values, 'is_array'));
            if ($valuesArrayCount) {
                if ($valuesArrayCount != count($values)) {
                    throw new \InvalidArgumentException('Mixed values and arrays are not allowed');
                }
            } else {
                $values = [$values];
            }
        }

        $this->values = array_map(function (array $row) {
            return literals(...$row);
        }, $values);

        return $this;
    }

    /** @return LiteralExpression[][] */
    public function getValues(): array
    {
        return $this->values;
    }
}
