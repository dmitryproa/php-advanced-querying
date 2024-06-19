<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying;

abstract class Statement
{
    /** @var null|Table */
    protected $table;

    /** @param null|string|Table $table */
    public function setTable($table): Statement
    {
        $this->table = table($table);

        return $this;
    }

    /** @return null|Table */
    public function getTable()
    {
        return $this->table;
    }
}
