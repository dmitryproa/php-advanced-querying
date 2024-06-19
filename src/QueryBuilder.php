<?php

declare(strict_types=1);

namespace DmitryProA\PhpAdvancedQuerying;

use DmitryProA\PhpAdvancedQuerying\Statements\Delete;
use DmitryProA\PhpAdvancedQuerying\Statements\Insert;
use DmitryProA\PhpAdvancedQuerying\Statements\InsertSelect;
use DmitryProA\PhpAdvancedQuerying\Statements\Replace;
use DmitryProA\PhpAdvancedQuerying\Statements\ReplaceSelect;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;
use DmitryProA\PhpAdvancedQuerying\Statements\Update;

class QueryBuilder
{
    /** @param null|Select|string|Table $table */
    public function select($table = null, array $columns = []): Select
    {
        return new Select($table instanceof Select ? $table : table($table), $columns);
    }

    /** @param string|Table $table */
    public function update($table = null, array $values = []): Update
    {
        return new Update(table($table), $values);
    }

    /**
     * @param string|Table                   $table
     * @param array<ColumnExpression|string> $fields
     * @param array|array[]                  $values
     */
    public function insert($table = null, array $fields = [], array $values = []): Insert
    {
        return new Insert(table($table), $fields, $values);
    }

    /**
     * @param string|Table                   $table
     * @param array<ColumnExpression|string> $fields
     * @param array|array[]                  $values
     */
    public function replace($table = null, array $fields = [], array $values = []): Replace
    {
        return new Replace(table($table), $fields, $values);
    }

    /**
     * @param string|Table                   $table
     * @param array<ColumnExpression|string> $fields
     * @param null|Select                    $select
     */
    public function insertSelect($table = null, $fields = [], $select = null): InsertSelect
    {
        return new InsertSelect(table($table), $fields, $select);
    }

    /**
     * @param string|Table                   $table
     * @param array<ColumnExpression|string> $fields
     * @param null|Select                    $select
     */
    public function replaceSelect($table = null, $fields = [], $select = null): ReplaceSelect
    {
        return new ReplaceSelect(table($table), $fields, $select);
    }

    /**
     * @param string|Table $table
     */
    public function delete($table = null): Delete
    {
        return new Delete($table);
    }
}

require_once 'Helpers.php';
