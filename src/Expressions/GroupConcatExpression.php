<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use DmitryProA\PhpAdvancedQuerying\Expression;
use DmitryProA\PhpAdvancedQuerying\OrderBy;

use function DmitryProA\PhpAdvancedQuerying\expr;

class GroupConcatExpression extends FunctionExpression
{
    /** @var bool */
    public $distinct;

    /** @var string */
    public $separator;

    /** @var OrderBy[] */
    protected $orderBy_ = [];

    public function __construct(Expression $expr, bool $distinct = false, string $separator = ',')
    {
        $this->function = 'GROUP_CONCAT';
        $this->distinct = $distinct;
        $this->separator = $separator;
        $this->arguments[] = $expr;
    }

    public function orderBy($expr, string $direction = OrderBy::ASC): self
    {
        $this->orderBy_[] = new OrderBy(expr($expr), $direction);

        return $this;
    }

    /** @return OrderBy[] */
    public function getOrderBy(): array
    {
        return $this->orderBy_;
    }
}
