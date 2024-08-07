<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use DmitryProA\PhpAdvancedQuerying\Expression;
use DmitryProA\PhpAdvancedQuerying\OrderBy;

use function DmitryProA\PhpAdvancedQuerying\checkType;
use function DmitryProA\PhpAdvancedQuerying\expr;

class WindowFunctionExpression extends Expression
{
    const ALLOWED_FUNCTIONS = [
        'CUME_DIST',
        'DENSE_RANK',
        'FIRST_VALUE',
        'LAG',
        'LAST_VALUE',
        'LEAD',
        'NTH_VALUE',
        'NTILE',
        'PERCENT_RANK',
        'RANK',
        'ROW_NUMBER',
    ];

    /** @var FunctionExpression */
    public $function;

    /** @var null|Expression */
    public $partitionExpr;

    /** @var OrderBy[] */
    protected $orderBy_ = [];

    /** @param null|Expression $partitionExpr */
    public function __construct(FunctionExpression $function, $partitionExpr = null)
    {
        checkType($partitionExpr, Expression::class, true);

        $function->function = strtoupper($function->function);

        if (!in_array($function->function, static::ALLOWED_FUNCTIONS)) {
            throw new \InvalidArgumentException("Function {$function->function} is not a window function.");
        }

        $this->function = $function;
        $this->partitionExpr = $partitionExpr;
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
