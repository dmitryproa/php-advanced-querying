<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Statements;

use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\Statement;

/**
 * @method ReplaceSelect setTable(null|string|Table $table)
 * @method ReplaceSelect setFields(array<ColumnExpression|string> $fields)
 */
class ReplaceSelect extends Statement
{
    use FieldStatement;

    /** @var Select */
    protected $select_;

    /**
     * @param null|Table                     $table
     * @param array<ColumnExpression|string> $fields
     * @param null|Select                    $select
     */
    public function __construct($table = null, $fields = [], $select = null)
    {
        $this->setTable($table);
        $this->setFields($fields);

        if ($select) {
            $this->select($select);
        }
    }

    public function select(Select $select): self
    {
        $this->select_ = $select;

        return $this;
    }

    /** @return null|Select */
    public function getSelect()
    {
        return $this->select_;
    }
}
