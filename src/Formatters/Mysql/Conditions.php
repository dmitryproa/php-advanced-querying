<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Formatters\Mysql;

use DmitryProA\PhpAdvancedQuerying\Conditions\AndCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\CompareCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\ExprCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\InCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\LikeCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\NotCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\NullCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\OrCondition;
use DmitryProA\PhpAdvancedQuerying\Formatters\MysqlFormatter;

/**
 * @property MysqlFormatter $this
 */
trait Conditions
{
    /** @var array<callable|string> */
    protected $conditions = [
        ExprCondition::class => 'formatExprCondition',
        CompareCondition::class => 'formatCompareCondition',
        LikeCondition::class => 'formatLikeCondition',
        InCondition::class => 'formatInCondition',
        NullCondition::class => 'formatNullCondition',
        NotCondition::class => 'formatNotCondition',
        AndCondition::class => 'formatAndCondition',
        OrCondition::class => 'formatOrCondition',
    ];

    protected function formatExprCondition(ExprCondition $condition): string
    {
        return $this->formatExpression($condition->expr);
    }

    protected function formatCompareCondition(CompareCondition $condition): string
    {
        return $this->formatExpression($condition->left)
            ." {$condition->type} "
            .$this->formatExpression($condition->right);
    }

    protected function formatLikeCondition(LikeCondition $condition): string
    {
        return $this->formatExpression($condition->left)
            .' LIKE '
            .$this->formatExpression($condition->right);
    }

    protected function formatInCondition(InCondition $condition): string
    {
        return
            $this->formatExpression($condition->expr)
            .' IN ('
            .implode(', ', array_map([$this, 'formatExpression'], $condition->values))
            .')';
    }

    protected function formatNullCondition(NullCondition $condition): string
    {
        return $this->formatExpression($condition->expr).' IS NULL';
    }

    protected function formatNotCondition(NotCondition $condition): string
    {
        $condition = $condition->condition;

        if ($condition instanceof CompareCondition && CompareCondition::EQUALS == $condition->type) {
            return $this->formatExpression($condition->left).' != '.$this->formatExpression($condition->right);
        }

        if ($condition instanceof LikeCondition) {
            return $this->formatExpression($condition->left).' NOT LIKE '.$this->formatExpression($condition->right);
        }

        if ($condition instanceof NullCondition) {
            return $this->formatExpression($condition->expr).' IS NOT NULL';
        }

        if ($condition instanceof InCondition) {
            return $this->formatExpression($condition->expr)
            .' NOT IN ('.implode(', ', array_map([$this, 'formatExpression'], $condition->values))
            .')';
        }

        return 'NOT '.$this->formatCondition($condition);
    }

    protected function formatAndCondition(AndCondition $condition): string
    {
        $formattedConditions = array_map([$this, 'formatCondition'], $condition->conditions);

        return implode(' AND ', $formattedConditions);
    }

    protected function formatOrCondition(OrCondition $condition): string
    {
        $formattedConditions = array_map([$this, 'formatCondition'], $condition->conditions);

        return '('.implode(' OR ', $formattedConditions).')';
    }
}
