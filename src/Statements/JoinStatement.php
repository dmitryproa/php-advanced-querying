<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Statements;

use DmitryProA\PhpAdvancedQuerying\Condition;
use DmitryProA\PhpAdvancedQuerying\Join;
use DmitryProA\PhpAdvancedQuerying\Table;

use function DmitryProA\PhpAdvancedQuerying\and_;
use function DmitryProA\PhpAdvancedQuerying\table;

trait JoinStatement
{
    /** @var Join[] */
    protected $joins = [];

    /** @var Table */
    protected $joinTable;

    /** @var string */
    protected $joinType;

    /** @param string|Table $table */
    public function join($table, string $type = Join::OUTER): Condition
    {
        $this->joinTable = table($table);
        $this->joinType = $type;
        $this->conditionCallback = [$this, 'setJoinCondition'];

        $condition = and_();
        $condition->setStatement($this);

        return $condition;
    }

    /** @return Join[] */
    public function getJoins(): array
    {
        return $this->joins;
    }

    protected function setJoinCondition(Condition $cond): void
    {
        $this->joins[] = new Join($this->joinTable, $cond, $this->joinType);
    }
}
