<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Formatters\Mysql;

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

trait Expressions
{
    /** @var array<callable|string> */
    protected $expressions = [
        SelectExpression::class => 'formatSelectExpression',
        LiteralExpression::class => 'formatLiteralExpression',
        ColumnExpression::class => 'formatColumnExpression',
        FunctionExpression::class => 'formatFunctionExpression',
        GroupConcatExpression::class => 'formatGroupConcatExpression',
        CountExpression::class => 'formatCountExpression',
        CastExpression::class => 'formatCastExpression',
        WindowFunctionExpression::class => 'formatWindowFunctionExpression',
        ArithmeticExpression::class => 'formatArithmeticExpression',
        ConditionExpression::class => 'formatConditionExpression',
    ];

    protected function formatSelectExpression(SelectExpression $expr): string
    {
        return '('.$this->formatSelect($expr->select).')';
    }

    protected function formatLiteralExpression(LiteralExpression $expr): string
    {
        if (is_null($expr->value)) {
            return 'NULL';
        }

        $paramName = 'v'.(count($this->parameters) + 1);
        $this->parameters[$paramName] = $expr->value;

        return ":{$paramName}";
    }

    protected function formatColumnExpression(ColumnExpression $expr): string
    {
        $query = $expr->table ? ("`{$expr->table}`.") : '';
        if ('*' == $expr->name) {
            return $query.'*';
        }

        return $query."`{$expr->name}`";
    }

    protected function formatFunctionExpression(FunctionExpression $expr): string
    {
        $args = implode(', ', array_map([$this, 'formatExpression'], $expr->arguments));

        return strtoupper($expr->function)."({$args})";
    }

    protected function formatGroupConcatExpression(GroupConcatExpression $expr): string
    {
        $args = implode(', ', array_map([$this, 'formatExpression'], $expr->arguments));

        $query = 'GROUP_CONCAT(';
        if ($expr->distinct) {
            $query .= 'DISTINCT ';
        }

        $query .= $args;

        if (',' != $expr->separator) {
            $query .= ' SEPARATOR '.$this->formatExpression(new LiteralExpression($expr->separator));
        }

        $query .= ')';

        return $query;
    }

    protected function formatCountExpression(CountExpression $expr): string
    {
        $columns = implode(', ', array_map([$this, 'formatColumnExpression'], $expr->arguments)) ?: '*';

        $query = 'COUNT(';
        if ($expr->distinct) {
            $query .= 'DISTINCT ';
        }

        $query .= $columns.')';

        return $query;
    }

    protected function formatCastExpression(CastExpression $expr): string
    {
        return 'CAST('.$this->formatExpression($expr->expr)." AS {$expr->type})";
    }

    protected function formatWindowFunctionExpression(WindowFunctionExpression $expr): string
    {
        $partitionSql = $expr->partitionExpr
            ? 'PARTITION BY '.$this->formatExpression($expr->partitionExpr)
            : '';

        $orderBy = $expr->getOrderBy();
        $orderBySql = $orderBy
            ? ' '.$this->formatOrderBy($orderBy)
            : '';

        return $this->formatFunctionExpression($expr->function)." OVER ({$partitionSql}{$orderBySql})";
    }

    protected function formatArithmeticExpression(ArithmeticExpression $expr): string
    {
        $leftParens =
            ArithmeticExpression::MOD == $expr->op && $expr->left instanceof ArithmeticExpression
            || (ArithmeticExpression::MULTIPLY == $expr->op || ArithmeticExpression::DIVIDE == $expr->op)
                && $expr->left instanceof ArithmeticExpression
                && (ArithmeticExpression::PLUS == $expr->left->op || ArithmeticExpression::MINUS == $expr->left->op);

        $rightParens =
            ArithmeticExpression::MOD == $expr->op && $expr->right instanceof ArithmeticExpression
            || (ArithmeticExpression::MULTIPLY == $expr->op || ArithmeticExpression::DIVIDE == $expr->op)
                && $expr->right instanceof ArithmeticExpression
                && (ArithmeticExpression::PLUS == $expr->right->op || ArithmeticExpression::MINUS == $expr->right->op);

        return ($leftParens ? '(' : '').$this->formatExpression($expr->left).($leftParens ? ')' : '')
            ." {$expr->op} "
            .($rightParens ? '(' : '').$this->formatExpression($expr->right).($rightParens ? ')' : '');
    }

    protected function formatConditionExpression(ConditionExpression $expr): string
    {
        return $this->formatCondition($expr->condition);
    }
}
