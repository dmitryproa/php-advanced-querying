<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying;

class Column
{
    /** @var Expression */
    public $expr;

    /** @var string */
    public $alias;

    public function __construct(Expression $expr, string $alias = '')
    {
        $this->expr = $expr;
        $this->alias = $alias;
    }
}
