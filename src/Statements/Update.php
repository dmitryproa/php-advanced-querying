<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Statements;

use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\FieldValue;
use DmitryProA\PhpAdvancedQuerying\Statement;
use DmitryProA\PhpAdvancedQuerying\Table;

use function DmitryProA\PhpAdvancedQuerying\makeUpdateValue;
use function DmitryProA\PhpAdvancedQuerying\makeUpdateValues;

/**
 * @method Update setTable(null|string|Table $table)
 * @method Update setValues(array $values)
 * @method Update setValue(string|ColumnExpression $field, $value)
 */
class Update extends Statement
{
    use ConditionalStatement;
    use JoinStatement;

    /** @var FieldValue[] */
    protected $values;

    /** @param null|Table $table */
    public function __construct($table = null, array $values = [])
    {
        $this->setTable($table);
        $this->setValues($values);
    }

    public function setValues(array $values): Statement
    {
        $this->values = makeUpdateValues($values);

        return $this;
    }

    /** @param ColumnExpression|string $field */
    public function setValue($field, $value): Statement
    {
        $this->values[] = makeUpdateValue($field, $value);

        return $this;
    }

    /** @return FieldValue[] */
    public function getValues(): array
    {
        return $this->values;
    }
}
