<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Conditions;

use DmitryProA\PhpAdvancedQuerying\Condition;
use DmitryProA\PhpAdvancedQuerying\Statement;

use function DmitryProA\PhpAdvancedQuerying\checkArrayType;

abstract class MultipleCondition extends Condition
{
    /** @var Condition[] */
    public $conditions = [];

    /** @param Condition $conditions */
    public function __construct(...$conditions)
    {
        checkArrayType($conditions, Condition::class);

        foreach ($conditions as $condition) {
            if ($condition instanceof MultipleCondition) {
                if (is_a($condition, static::class)) {
                    $this->conditions = array_merge($this->conditions, $condition->conditions);

                    continue;
                }
            }
            $this->conditions[] = $condition;
        }
    }

    public function end(): Statement
    {
        if (empty($this->conditions)) {
            throw new \Exception('Condition cannot be empty');
        }

        $this->statement->setCondition($this);

        return $this->statement;
    }
}
