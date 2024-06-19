<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying;

class Join
{
    const OUTER = 'OUTER';
    const INNER = 'INNER';
    const LEFT = 'LEFT';
    const RIGHT = 'RIGHT';

    /** @var Table */
    public $table;

    /** @var Condition */
    public $condition;

    /** @var string */
    public $type;

    public function __construct(Table $table, Condition $condition, string $type = self::OUTER)
    {
        $type = strtoupper($type);

        if (!in_array($type, (new \ReflectionClass(__CLASS__))->getConstants())) {
            throw new \InvalidArgumentException('Invalid join type');
        }

        $this->table = $table;
        $this->condition = $condition;
        $this->type = $type;

        return $this;
    }
}
