<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use DmitryProA\PhpAdvancedQuerying\Expression;

class GroupConcatExpression extends FunctionExpression
{
    /** @var bool */
    public $distinct;

    /** @var string */
    public $separator;

    public function __construct(Expression $expr, bool $distinct = false, string $separator = ',')
    {
        $this->function = 'GROUP_CONCAT';
        $this->distinct = $distinct;
        $this->separator = $separator;
        $this->arguments[] = $expr;
    }
}
