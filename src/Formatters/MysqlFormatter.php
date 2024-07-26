<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Formatters;

use DmitryProA\PhpAdvancedQuerying\Column;
use DmitryProA\PhpAdvancedQuerying\Condition;
use DmitryProA\PhpAdvancedQuerying\Expression;
use DmitryProA\PhpAdvancedQuerying\FieldValue;
use DmitryProA\PhpAdvancedQuerying\Formatters\Mysql\Conditions;
use DmitryProA\PhpAdvancedQuerying\Formatters\Mysql\Expressions;
use DmitryProA\PhpAdvancedQuerying\Formatters\Mysql\Statements;
use DmitryProA\PhpAdvancedQuerying\Join;
use DmitryProA\PhpAdvancedQuerying\OrderBy;
use DmitryProA\PhpAdvancedQuerying\SelectTable;
use DmitryProA\PhpAdvancedQuerying\Statement;
use DmitryProA\PhpAdvancedQuerying\Statements\ConditionalStatement;
use DmitryProA\PhpAdvancedQuerying\Statements\JoinStatement;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;
use DmitryProA\PhpAdvancedQuerying\Table;

class MysqlFormatter
{
    use Statements;
    use Expressions;
    use Conditions;

    private $parameters = [];
    private $indentLevel = 0;
    private $tableSelects = 0;

    /**
     * @param array &$parameters
     */
    public function format(Statement $statement, &$parameters = null): string
    {
        $this->parameters = [];
        $this->indentLevel = $this->tableSelects = 0;

        $result = $this->getFormatter(get_class($statement))($statement);
        $parameters = $this->parameters;

        return trim($result).';';
    }

    public function formatExpression(Expression $expr): string
    {
        return $this->getFormatter(get_class($expr))($expr);
    }

    public function formatCondition(Condition $condition): string
    {
        return $this->getFormatter(get_class($condition))($condition);
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    protected function getFormatter(string $class): callable
    {
        if (is_subclass_of($class, Statement::class)) {
            $array = $this->statements;
        } elseif (is_subclass_of($class, Condition::class)) {
            $array = $this->conditions;
        } elseif (is_subclass_of($class, Expression::class)) {
            $array = $this->expressions;
        } else {
            throw new \Exception("Invalid class '{$class}'");
        }

        if (!isset($array[$class])) {
            throw new \Exception("No formatter available for class '{$class}'");
        }

        $formatter = $array[$class];

        return [$this, $formatter];
    }

    /** @param ConditionalStatement|Statement $statement */
    protected function formatWhere(Statement $statement)
    {
        $condition = $statement->getCondition();
        if ($condition) {
            return 'WHERE'.
                $this->indentUp().$this->formatCondition($condition).$this->indentDown();
        }

        return '';
    }

    /** @param OrderBy[] $orderBy */
    protected function formatOrderBy(array $orderBy)
    {
        if ($orderBy) {
            return 'ORDER BY '
                .implode(', ', array_map(function (OrderBy $entry) {
                    return $this->formatExpression($entry->expr)." {$entry->direction}";
                }, $orderBy))
                .$this->newline();
        }

        return '';
    }

    protected function formatLimit(Select $select)
    {
        $limit = $limit = $select->getLimit();
        $offset = $select->getOffset();

        if ($limit || $offset) {
            $limitParts = [];
            if ($limit) {
                $limitParts[] = "LIMIT {$limit}";
            }
            if ($offset) {
                $limitParts[] = "OFFSET {$offset}";
            }

            return implode(' ', $limitParts).$this->newline();
        }

        return '';
    }

    protected function formatTable(Table $table): string
    {
        if ($table instanceof SelectTable) {
            $alias = $table->alias ?: 's'.(++$this->tableSelects);

            return '('.$this->formatSelect($table->select).$this->newline().") as `{$alias}`";
        }
        $query = "`{$table->name}`";

        if ($table->alias) {
            $query .= " AS `{$table->alias}`";
        }

        return $query;
    }

    /** @param ColumnExpression[] $columns */
    protected function formatColumns(array $columns): string
    {
        $columns = array_map(
            function (Column $column) {
                $result = $this->formatExpression($column->expr);

                if ($column->alias) {
                    $result .= " AS `{$column->alias}`";
                }

                return $result;
            },
            $columns
        );

        return implode(','.$this->newline(), $columns);
    }

    /** @var FieldValue[] */
    protected function formatUpdateValues(array $values): string
    {
        $updateValues = array_map(function (FieldValue $value) {
            return $this->formatExpression($value->field)
                .' = '
                .$this->formatExpression($value->value);
        }, $values);

        return implode(','.$this->newline(), $updateValues);
    }

    protected function formatInsertValues(array $values): string
    {
        $rows = array_map(function (array $row) {
            return '('.implode(', ', array_map([$this, 'formatExpression'], $row)).')';
        }, $values);

        return implode(','.$this->newline(), $rows);
    }

    /** @param JoinStatement $st */
    protected function formatJoins(Statement $st): string
    {
        $joins = $st->getJoins();
        if (!$joins) {
            return '';
        }

        return implode($this->newline(), array_map(function (Join $join) {
            return (Join::OUTER != $join->type ? "{$join->type} " : '')
                .'JOIN '.$this->formatTable($join->table)
                .' ON ('.$this->formatCondition($join->condition).')';
        }, $joins)).$this->newline();
    }

    protected function indent(): string
    {
        return str_repeat("\t", $this->indentLevel) ?: '';
    }

    protected function newline(): string
    {
        return "\n".$this->indent();
    }

    protected function indentUp(bool $newline = true): string
    {
        ++$this->indentLevel;

        return $newline ? $this->newline() : "\t";
    }

    protected function indentDown(bool $newline = true): string
    {
        --$this->indentLevel;

        return $newline ? $this->newline() : '';
    }
}
