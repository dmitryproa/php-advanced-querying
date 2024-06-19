<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying;

class Table
{
    /** @var string */
    public $name;

    /** @var string */
    public $alias;

    public function __construct(string $name, string $alias = '')
    {
        $this->name = $name;
        $this->alias = $alias;
    }
}
