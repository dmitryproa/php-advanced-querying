<?php

declare(strict_types=1);

use DmitryProA\PhpAdvancedQuerying\Column;
use DmitryProA\PhpAdvancedQuerying\Expressions\LiteralExpression;
use DmitryProA\PhpAdvancedQuerying\FieldValue;
use DmitryProA\PhpAdvancedQuerying\QueryBuilder;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;
use PHPUnit\Framework\TestCase;

use function DmitryProA\PhpAdvancedQuerying\column;
use function DmitryProA\PhpAdvancedQuerying\expr;
use function DmitryProA\PhpAdvancedQuerying\table;

/**
 * @internal
 *
 * @covers \DmitryProA\PhpAdvancedQuerying\QueryBuilder
 */
class QueryBuilderTest extends TestCase
{
    /** @var string */
    private $table;

    /** @var QueryBuilder */
    private $builder;

    /** @var array<string,LiteralExpression> */
    private $updateValues;

    /** @var FieldValue[] */
    private $updateValuesExpected;

    /** @var string[] */
    private $insertFields;

    /** @var ColumnExpression[] */
    private $insertFieldsExpected;

    /** @var array */
    private $insertValues;

    /** @var LiteralExpression[] */
    private $insertValuesExpected;

    public function setUp(): void
    {
        $this->table = 't.test';

        $this->updateValues = ['t.test1' => new LiteralExpression(123), 'test2' => new LiteralExpression('value')];
        $this->updateValuesExpected = array_map(function ($field, $value) {
            return new FieldValue(column($field), expr($value));
        }, array_keys($this->updateValues), $this->updateValues);

        $this->insertFields = ['field1', 'field2'];
        $this->insertFieldsExpected = array_map('DmitryProA\\PhpAdvancedQuerying\\column', $this->insertFields);

        $this->insertValues = [123, 'test'];
        $this->insertValuesExpected = array_map('DmitryProA\\PhpAdvancedQuerying\\literal', $this->insertValues);

        $this->builder = new QueryBuilder();
    }

    public function testSelect()
    {
        $selectColumns = ['alias' => 't.col', 'alias2' => 123, new Select()];
        $expectedColumns = array_map(function ($alias, $column) {
            return new Column(expr($column), is_string($alias) ? $alias : '');
        }, array_keys($selectColumns), $selectColumns);

        $select = $this->builder->select($this->table, $selectColumns);
        $this->assertEquals(table($this->table), $select->getTable());
        $this->assertEquals($expectedColumns, $select->getColumns());
    }

    public function testUpdate()
    {
        $update = $this->builder->update($this->table, $this->updateValues);
        $this->assertEquals(table($this->table), $update->getTable());
        $this->assertEquals($this->updateValuesExpected, $update->getValues());
    }

    public function testInsert()
    {
        $insert = $this->builder->insert($this->table, $this->insertFields, $this->insertValues);
        $this->assertEquals(table($this->table), $insert->getTable());
        $this->assertEquals($this->insertFieldsExpected, $insert->getFields());
        $this->assertEquals([$this->insertValuesExpected], $insert->getValues());
    }

    public function testReplace()
    {
        $replace = $this->builder->replace($this->table, $this->insertFields, $this->insertValues);
        $this->assertEquals(table($this->table), $replace->getTable());
        $this->assertEquals($this->insertFieldsExpected, $replace->getFields());
        $this->assertEquals([$this->insertValuesExpected], $replace->getValues());
    }

    public function testInsertSelect()
    {
        $select = $this->builder->select();
        $insert = $this->builder->insertSelect($this->table, $this->insertFields, $select);
        $this->assertEquals(table($this->table), $insert->getTable());
        $this->assertEquals($this->insertFieldsExpected, $insert->getFields());
        $this->assertEquals($select, $insert->getSelect());
    }

    public function testReplaceSelect()
    {
        $select = $this->builder->select();
        $replace = $this->builder->replaceSelect($this->table, $this->insertFields, $select);
        $this->assertEquals(table($this->table), $replace->getTable());
        $this->assertEquals($this->insertFieldsExpected, $replace->getFields());
        $this->assertEquals($select, $replace->getSelect());
    }

    public function testDelete()
    {
        $delete = $this->builder->delete($this->table);
        $this->assertEquals(table($this->table), $delete->getTable());
    }
}
