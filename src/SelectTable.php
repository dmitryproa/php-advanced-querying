<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying;

use DmitryProA\PhpAdvancedQuerying\Statements\Select;

class SelectTable extends Table
{
    /** @var Select */
    public $select;

    public function __construct(Select $select, string $alias = '')
    {
        $this->select = $select;
        $this->alias = $alias;
    }
}
