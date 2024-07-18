<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying\Expressions;

use DmitryProA\PhpAdvancedQuerying\Expression;

class CastExpression extends Expression
{
    const BINARY = 'BINARY';
    const CHAR = 'CHAR';
    const DATE = 'DATE';
    const DATETIME = 'DATETIME';
    const TIME = 'TIME';
    const DECIMAL = 'DECIMAL';
    const SIGNED = 'SIGNED';
    const UNSIGNED = 'UNSIGNED';

    /** @var Expression */
    public $expr;

    /** @var string */
    public $type;

    public function __construct(Expression $expr, string $type)
    {
        $this->expr = $expr;

        $type = strtoupper($type);
        if (!in_array($type, (new \ReflectionClass(__CLASS__))->getConstants())) {
            throw new \InvalidArgumentException('Invalid cast type');
        }

        $this->type = $type;
    }
}
