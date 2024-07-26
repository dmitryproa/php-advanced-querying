<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Formatters\Mysql;

use DmitryProA\PhpAdvancedQuerying\Column;
use DmitryProA\PhpAdvancedQuerying\Expressions\ConditionExpression;
use DmitryProA\PhpAdvancedQuerying\FieldValue;
use DmitryProA\PhpAdvancedQuerying\QueryFormattingException;
use DmitryProA\PhpAdvancedQuerying\SelectTable;
use DmitryProA\PhpAdvancedQuerying\Statement;
use DmitryProA\PhpAdvancedQuerying\Statements\Delete;
use DmitryProA\PhpAdvancedQuerying\Statements\Insert;
use DmitryProA\PhpAdvancedQuerying\Statements\InsertSelect;
use DmitryProA\PhpAdvancedQuerying\Statements\Replace;
use DmitryProA\PhpAdvancedQuerying\Statements\ReplaceSelect;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;
use DmitryProA\PhpAdvancedQuerying\Statements\Update;

trait Statements
{
    /** @var array<callable|string> */
    protected $statements = [
        Select::class => 'formatSelect',
        Update::class => 'formatUpdate',
        Insert::class => 'formatInsertOrReplace',
        Replace::class => 'formatInsertOrReplace',
        InsertSelect::class => 'formatInsertSelect',
        ReplaceSelect::class => 'formatReplaceSelect',
        Delete::class => 'formatDelete',
    ];

    protected function formatSelect(Select $select, bool $isUnion = false): string
    {
        $unionOrigin = $select->getUnionOrigin();
        if (!$isUnion && $unionOrigin) {
            return $this->formatSelect($unionOrigin);
        }

        $query = 'SELECT';
        if ($select->getDistinct()) {
            $query .= ' DISTINCT';
        }

        $table = $select->getTable();
        $columns = $select->getColumns();

        if (!empty($columns)) {
            $query .= $this->indentUp();

            $formattedColumns = array_map(
                function (Column $column) {
                    $result = $this->formatExpression($column->expr);

                    if ($column->expr instanceof ConditionExpression) {
                        $result = "({$result})";
                    }

                    if ($column->alias) {
                        $result .= " AS `{$column->alias}`";
                    }

                    return $result;
                },
                $columns
            );

            $query .= implode(','.$this->newline(), $formattedColumns).$this->indentDown();
        } else {
            $query .= ($table ? ' *' : ' 1').$this->newline();
        }

        if ($table) {
            $query .= 'FROM';
            if ($table instanceof SelectTable) {
                $query .= $this->indentUp().$this->formatTable($table).$this->indentDown();
            } else {
                $query .= ' '.$this->formatTable($table);
            }
            $query .= $this->newline();
        }
        $query .= $this->formatJoins($select);
        $query .= $this->formatWhere($select);

        $groupColumns = $select->getGroupBy();
        if ($groupColumns) {
            $query .= 'GROUP BY '.implode(', ', array_map([$this, 'formatExpression'], $groupColumns));

            $having = $select->getHaving();
            if ($having) {
                $query .= ' HAVING ('.$this->formatCondition($having).')'.$this->newline();
            }
        }
        $query .= $this->formatOrderBy($select->getOrderBy());
        $query .= $this->formatLimit($select);

        $unionSelect = $select->getUnionSelect();
        if ($unionSelect) {
            $query .= $this->newline().($select->getUnionAll() ? 'UNION ALL ' : 'UNION ').$this->formatSelect($unionSelect, true);
        }

        return trim($query);
    }

    protected function formatUpdate(Update $update): string
    {
        $table = $update->getTable();
        if (!$table) {
            throw new QueryFormattingException('Table must be specified for Update statement');
        }

        $values = $update->getValues();
        if (empty($values)) {
            throw new QueryFormattingException('Values must be specified for Update statement');
        }

        $formattedValues = array_map(function (FieldValue $value) {
            return $this->formatExpression($value->field)
                .' = '
                .$this->formatExpression($value->value);
        }, $values);

        $query = 'UPDATE '.$this->formatTable($table).$this->newline();
        $query .= $this->formatJoins($update);
        $query .= 'SET'.$this->indentUp();
        $query .= implode(','.$this->newline(), $formattedValues).$this->indentDown();

        $query .= $this->formatWhere($update);

        return $query;
    }

    /** @param Insert|Replace $st */
    protected function formatInsertOrReplace(Statement $st)
    {
        $op = ($st instanceof Insert) ? 'INSERT' : 'REPLACE';

        $table = $st->getTable();
        if (!$table) {
            throw new QueryFormattingException("Table must be specified for {$op} statement");
        }

        $values = $st->getValues();
        if (empty($values)) {
            throw new QueryFormattingException('Values must be specified for $op statement');
        }

        $fields = $st->getFields();
        $fieldCount = count($fields);

        if ($fieldCount) {
            if (array_filter($values, function ($row) use ($fieldCount) { return count($row) != $fieldCount; })) {
                throw new QueryFormattingException('Value count mismatch');
            }
        }

        $query = "{$op} ";
        if ($st instanceof Insert && $st->getIgnore()) {
            $query .= 'IGNORE ';
        }

        $query .= "INTO `{$table->name}`";
        if ($fields) {
            $query .= $this->indentUp().'('.implode(', ', array_map([$this, 'formatExpression'], $fields)).')'.$this->indentDown();
        } else {
            $query .= ' ';
        }
        $query .= 'VALUES'.$this->indentUp().$this->formatInsertValues($values).$this->indentDown();

        if ($st instanceof Insert) {
            $updateValues = $st->getOnDuplicateUpdateValues();

            if (!empty($updateValues)) {
                $query .= 'ON DUPLICATE KEY UPDATE'.$this->indentUp().$this->formatUpdateValues($updateValues).$this->indentDown();
            }
        }

        return $query;
    }

    protected function formatInsertSelect(InsertSelect $insert): string
    {
        return $this->formatInsertOrReplaceSelect($insert);
    }

    protected function formatReplaceSelect(ReplaceSelect $replace): string
    {
        return $this->formatInsertOrReplaceSelect($replace);
    }

    /** @param InsertSelect|ReplaceSelect $st */
    protected function formatInsertOrReplaceSelect(Statement $st): string
    {
        $op = ($st instanceof InsertSelect) ? 'INSERT' : 'REPLACE';

        $table = $st->getTable();
        if (!$table) {
            throw new QueryFormattingException("Table must be specified for {$op} statement");
        }

        $fields = $st->getFields();

        $select = $st->getSelect();
        if (!$select) {
            throw new QueryFormattingException("Select must be specified for {$op} statement");
        }

        $query = "{$op} ";
        if ($st instanceof InsertSelect && $st->getIgnore()) {
            $query .= 'IGNORE ';
        }

        $query .= "INTO `{$table->name}`".$this->newline();

        if ($fields) {
            $query .= $this->indentUp(false).'('.implode(', ', array_map([$this, 'formatExpression'], $fields)).')'.$this->indentDown();
        }

        $query .= $this->formatSelect($select).$this->newline();

        if ($st instanceof InsertSelect) {
            $updateValues = $st->getOnDuplicateUpdateValues();

            if (!empty($updateValues)) {
                $query .= 'ON DUPLICATE KEY UPDATE'.$this->indentUp().$this->formatUpdateValues($updateValues).$this->indentDown();
            }
        }

        return $query;
    }

    protected function formatDelete(Delete $delete): string
    {
        $table = $delete->getTable();
        if (!$table) {
            throw new QueryFormattingException('Table must be specified for DELETE statement');
        }

        $query = 'DELETE FROM '.$this->formatTable($table).$this->newline();
        $query .= $this->formatWhere($delete);

        return $query;
    }
}
