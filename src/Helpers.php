<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying;

use DmitryProA\PhpAdvancedQuerying\Conditions\AndCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\CompareCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\ExprCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\InCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\LikeCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\NotCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\NullCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\OrCondition;
use DmitryProA\PhpAdvancedQuerying\Expressions\ArithmeticExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\CastExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\ConditionExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\CountExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\FunctionExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\GroupConcatExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\LiteralExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\SelectExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\WindowFunctionExpression;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;

const REGEX_COLUMN = '[a-z_][a-z0-9_]*(?:\\.[a-z_][a-z0-9_]*)?';
const REGEX_NAME = '[a-z_][a-z0-9_]*';

/**
 * @param null|Select|string|Table $table
 *
 * @return null|Table
 */
function table($table, string $alias = '')
{
    if ($table) {
        if (is_string($table)) {
            $table = new Table($table, $alias);

            $tableRegex = REGEX_NAME;
            if (preg_match("/^({$tableRegex})\\s+as\\s+({$tableRegex})$/i", str_replace('`', '', $table->name), $matches)) {
                $table->name = $matches[1];
                $table->alias = $matches[2];
            }

            return $table;
        }

        if ($table instanceof Select) {
            return new SelectTable($table, $alias);
        }

        checkType($table, Table::class);

        if ($alias) {
            $table->alias = $alias;
        }

        return $table;
    }

    return null;
}

/**
 * @param null|Select|string $table
 */
function select($table = null, array $columns = []): Select
{
    return new Select($table, $columns);
}

// Expressions

function column(string $name, string $table = ''): ColumnExpression
{
    if (strpos($name, '.')) {
        list($table, $name) = explode('.', str_replace('`', '', $name));
    }

    return new ColumnExpression($name, $table);
}

/**
 * @param ColumnExpression|string $columns
 *
 * @return ColumnExpression[]
 */
function columns(...$columns): array
{
    if ($columns && is_array($columns[0])) {
        $columns = $columns[0];
    }

    return array_map(function ($column) {
        if (is_string($column)) {
            $column = column($column);
        }
        checkType($column, ColumnExpression::class);

        return $column;
    }, $columns);
}

function literal($value): LiteralExpression
{
    return ($value instanceof LiteralExpression) ? $value : new LiteralExpression($value);
}

function literalOrExpr($value): Expression
{
    return ($value instanceof Expression) ? $value : new LiteralExpression($value);
}

/** @return LiteralExpression[] */
function literals(...$values): array
{
    if ($values && is_array($values[0])) {
        $values = $values[0];
    }

    return array_map('DmitryProA\\PhpAdvancedQuerying\\literal', $values);
}

function func(string $function, ...$args): FunctionExpression
{
    if ($args && is_array($args[0])) {
        $args = $args[0];
    }

    $function = strtoupper($function);

    if ('GROUP_CONCAT' == $function) {
        return groupconcat($args);
    }
    if ('COUNT' == $function) {
        return count_(false, ...$args);
    }

    return new FunctionExpression($function, ...exprs(...$args));
}

/** @param array|mixed $expr */
function groupconcat($expr, bool $distinct = false, string $separator = ',')
{
    return new GroupConcatExpression(expr($expr), $distinct, $separator);
}

/** @param ColumnExpression|string $columns */
function count_(bool $distinct = false, ...$columns)
{
    if ($columns && is_array($columns[0])) {
        $columns = $columns[0];
    }

    return new CountExpression($distinct, ...columns(...$columns));
}

function cast($expr, string $type): CastExpression
{
    return new CastExpression(expr($expr), $type);
}

/** @param FunctionExpression|string $function */
function over($function, $partitionExpr): WindowFunctionExpression
{
    if (is_string($function)) {
        $function = func($function);
    }

    checkType($function, FunctionExpression::class);

    return new WindowFunctionExpression($function, expr($partitionExpr));
}

function plus($left, $right): ArithmeticExpression
{
    return new ArithmeticExpression(expr($left), expr($right), ArithmeticExpression::PLUS);
}

function minus($left, $right): ArithmeticExpression
{
    return new ArithmeticExpression(expr($left), expr($right), ArithmeticExpression::MINUS);
}

function multiply($left, $right): ArithmeticExpression
{
    return new ArithmeticExpression(expr($left), expr($right), ArithmeticExpression::MULTIPLY);
}

function divide($left, $right): ArithmeticExpression
{
    return new ArithmeticExpression(expr($left), expr($right), ArithmeticExpression::DIVIDE);
}

function mod($left, $right): ArithmeticExpression
{
    return new ArithmeticExpression(expr($left), expr($right), ArithmeticExpression::MOD);
}

function expr($value): Expression
{
    if ($value instanceof Expression) {
        return $value;
    }

    if ($value instanceof Condition) {
        return new ConditionExpression($value);
    }

    if ($value instanceof Select) {
        return new SelectExpression($value);
    }

    if (is_string($value)) {
        $column = REGEX_COLUMN;

        if (preg_match("/^{$column}$/i", $value)) {
            return column($value);
        }
    }

    return literal($value);
}

/** @return Expression[] */
function exprs(...$values): array
{
    if ($values && is_array($values[0])) {
        $values = $values[0];
    }

    return array_map(__NAMESPACE__.'\\expr', $values);
}

// Conditions

function condition($condition): Condition
{
    return ($condition instanceof Condition)
        ? $condition
        : true($condition);
}

/** @return Condition[] */
function conditions(...$conditions): array
{
    if ($conditions && is_array($conditions[0])) {
        $conditions = $conditions[0];
    }

    return array_map(function ($condition) { return condition($condition); }, $conditions);
}

function and_(...$conditions): AndCondition
{
    if ($conditions && is_array($conditions[0])) {
        $conditions = $conditions[0];
    }

    return new AndCondition(...conditions(...$conditions));
}

function or_(...$conditions): OrCondition
{
    if ($conditions && is_array($conditions[0])) {
        $conditions = $conditions[0];
    }

    return new OrCondition(...conditions(...$conditions));
}

function eq($left, $right): CompareCondition
{
    return new CompareCondition(expr($left), literalOrExpr($right), CompareCondition::EQUALS);
}

function greater($left, $right): CompareCondition
{
    return new CompareCondition(expr($left), literalOrExpr($right), CompareCondition::GREATER);
}

function greaterEquals($left, $right): CompareCondition
{
    return new CompareCondition(expr($left), literalOrExpr($right), CompareCondition::GREATER_EQUALS);
}

function less($left, $right): CompareCondition
{
    return new CompareCondition(expr($left), literalOrExpr($right), CompareCondition::LESS);
}

function lessEquals($left, $right): CompareCondition
{
    return new CompareCondition(expr($left), literalOrExpr($right), CompareCondition::LESS_EQUALS);
}

function like($left, $right): LikeCondition
{
    return new LikeCondition(expr($left), literalOrExpr($right));
}

function in($expr, ...$values): InCondition
{
    if ($values && is_array($values[0])) {
        $values = $values[0];
    }

    return new InCondition(expr($expr), ...literals(...$values));
}

function not($condition): NotCondition
{
    return new NotCondition(condition($condition));
}

function isNull($expr): NullCondition
{
    return new NullCondition(expr($expr));
}

function isNotNull($expr): NotCondition
{
    return not(isNull($expr));
}

function notEq($left, $right): NotCondition
{
    return not(eq($left, $right));
}

function notLike($left, $right): NotCondition
{
    return not(like($left, $right));
}

function notIn($expr, ...$values): NotCondition
{
    if ($values && is_array($values[0])) {
        $values = $values[0];
    }

    return not(in(expr($expr), ...literals(...$values)));
}

function true($expr): ExprCondition
{
    return new ExprCondition(expr($expr));
}

function false($expr): NotCondition
{
    return not(true(expr($expr)));
}

// Utils

/**
 * @param object          $object
 * @param string|string[] $class
 */
function checkType($object, $class, bool $canBeNull = false)
{
    $classes = is_string($class) ? [$class] : $class;

    if (is_null($object)) {
        if (!$canBeNull) {
            goto fail;
        }

        return;
    }

    if (!is_object($object)) {
        goto fail;
    }

    foreach ($classes as $class) {
        if (is_a($object, $class) || is_subclass_of($object, $class)) {
            return;
        }
    }

    fail:
        throw new InvalidTypeException("Invalid class '".(is_object($object) ? get_class($object) : gettype($object))."', expected '".implode("', '", $classes)."'");
}

/**
 * @param object[]        $objects
 * @param string|string[] $class
 */
function checkArrayType(array $objects, $class, bool $canBeNull = false)
{
    foreach ($objects as $object) {
        checkType($object, $class, $canBeNull);
    }
}

function makeUpdateValue($field, $value): FieldValue
{
    if (is_string($field)) {
        $field = column($field);
    }

    checkType($field, ColumnExpression::class);

    if (is_string($value)) {
        $value = column($value);
    }

    if ($value instanceof Select) {
        $value = new SelectExpression($value);
    }

    return new FieldValue($field, $value instanceof Expression ? $value : literal($value));
}

/** @return FieldValue[] */
function makeUpdateValues(array $values): array
{
    return array_map(__NAMESPACE__.'\\makeUpdateValue', array_keys($values), $values);
}
