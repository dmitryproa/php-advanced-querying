<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying;

class OrderBy
{
    const ASC = 'ASC';
    const DESC = 'DESC';

    /** @var Expression */
    public $expr;

    /** @var string */
    public $direction;

    public function __construct(Expression $expr, string $direction = self::ASC)
    {
        $direction = strtoupper($direction);

        if (self::ASC != $direction && self::DESC != $direction) {
            throw new \InvalidArgumentException('Invalid direction');
        }

        $this->expr = $expr;
        $this->direction = $direction;
    }
}
