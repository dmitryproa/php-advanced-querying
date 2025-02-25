<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Statements;

use DmitryProA\PhpAdvancedQuerying\Column;
use DmitryProA\PhpAdvancedQuerying\Condition;
use DmitryProA\PhpAdvancedQuerying\Expression;
use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\ConditionExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\SelectExpression;
use DmitryProA\PhpAdvancedQuerying\OrderBy;
use DmitryProA\PhpAdvancedQuerying\SelectTable;
use DmitryProA\PhpAdvancedQuerying\Statement;
use DmitryProA\PhpAdvancedQuerying\Table;

use function DmitryProA\PhpAdvancedQuerying\and_;
use function DmitryProA\PhpAdvancedQuerying\column;
use function DmitryProA\PhpAdvancedQuerying\columns;
use function DmitryProA\PhpAdvancedQuerying\expr;
use function DmitryProA\PhpAdvancedQuerying\literal;
use function DmitryProA\PhpAdvancedQuerying\table;

/**
 * @method null|Select|Table getTable()
 * @method Select            setCondition(Condition $cond)
 */
class Select extends Statement
{
    use ConditionalStatement;
    use JoinStatement;

    /** @var null|Select|Table */
    protected $table;

    /** @var Column[] */
    protected $columns = [];

    /** @var OrderBy[] */
    protected $orderBy = [];

    /** @var ColumnExpression[] */
    protected $groupBy_ = [];

    /** @var Condition */
    protected $having_;

    /** @var Select */
    protected $unionSelect_;

    /** @var Select */
    protected $unionOrigin;

    protected $unionAll = false;

    protected $limitNumber = 0;
    protected $offsetNumber = 0;

    protected $distinct_ = false;

    /** @param null|Select|string|Table $table */
    public function __construct($table = null, array $columns = [])
    {
        $this->setTable($table);
        $this->setColumns($columns);
    }

    /**
     * @param null|Select|string|Table $table
     *
     * @return Select
     */
    public function setTable($table): Statement
    {
        $this->table = table($table);

        return $this;
    }

    public function setColumns(array $columns): self
    {
        foreach ($columns as $key => $column) {
            $this->setColumn($column, is_string($key) ? $key : '');
        }

        return $this;
    }

    public function setColumn($column, string $alias = ''): self
    {
        if (is_string($column)) {
            $column = column($column);
        } elseif ($column instanceof Select) {
            $column = new SelectExpression($column);
        } elseif ($column instanceof SelectTable) {
            $column = new SelectExpression($column->select);
        } elseif ($column instanceof Condition) {
            $column = new ConditionExpression($column);
        } elseif (!$column instanceof Expression) {
            $column = literal($column);
        }

        $this->columns[] = new Column($column, $alias);

        return $this;
    }

    /** @return Column[] */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function distinct(): self
    {
        $this->distinct_ = true;

        return $this;
    }

    public function orderBy($expr, string $direction = OrderBy::ASC): self
    {
        $this->orderBy[] = new OrderBy(expr($expr), $direction);

        return $this;
    }

    public function limit(int $count): self
    {
        $this->limitNumber = $count;

        return $this;
    }

    public function offset(int $amount): self
    {
        $this->offsetNumber = $amount;

        return $this;
    }

    /** @param array<ColumnExpression|string> $columns */
    public function groupBy(...$columns): self
    {
        if (empty($columns)) {
            throw new \Exception('Columns array cannot be empty');
        }
        $this->groupBy_ = columns(...$columns);

        return $this;
    }

    public function having(): Condition
    {
        if (!$this->groupBy_) {
            throw new \Exception('groupBy method must be called beforehand');
        }

        $this->having_ = and_();
        $this->having_->setStatement($this);
        $this->conditionCallback = [$this, 'setHaving'];

        return $this->having_;
    }

    public function unionSelect($table = null, $fields = [], $unionAll = false): Select
    {
        if ($this->unionSelect_) {
            return $this->unionSelect_->unionSelect($table, $fields, $unionAll);
        }
        $select = new Select($table, $fields);
        $select->unionOrigin = $this->unionOrigin ?? $this;

        $this->unionSelect_ = $select;
        $this->unionAll = $unionAll;

        return $select;
    }

    /** @return OrderBy[] */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getLimit(): int
    {
        return $this->limitNumber;
    }

    public function getOffset(): int
    {
        return $this->offsetNumber;
    }

    public function getDistinct(): bool
    {
        return $this->distinct_;
    }

    /** @return ColumnExpression[] */
    public function getGroupBy(): array
    {
        return $this->groupBy_;
    }

    /** @return null|Condition */
    public function getHaving()
    {
        return $this->having_;
    }

    /** @return null|Select */
    public function getUnionSelect()
    {
        return $this->unionSelect_;
    }

    public function getUnionAll(): bool
    {
        return $this->unionAll;
    }

    /** @return null|Select */
    public function getUnionOrigin()
    {
        return $this->unionOrigin;
    }

    protected function setHaving(Condition $condition)
    {
        $this->having_ = $condition;
    }
}
