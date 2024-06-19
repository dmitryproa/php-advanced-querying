<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use DmitryProA\PhpAdvancedQuerying\Expression;

class ColumnExpression extends Expression
{
    /** @var string */
    public $name;

    /** @var string */
    public $table;

    /** @param null|string $table */
    public function __construct(string $name, string $table = '')
    {
        $this->name = $name;
        $this->table = $table;
    }
}
