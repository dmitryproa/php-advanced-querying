<?php

declare(strict_types=1);

use DmitryProA\PhpAdvancedQuerying\Column;
use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\LiteralExpression;
use DmitryProA\PhpAdvancedQuerying\FieldValue;
use DmitryProA\PhpAdvancedQuerying\InvalidTypeException;
use DmitryProA\PhpAdvancedQuerying\Join;
use DmitryProA\PhpAdvancedQuerying\SelectTable;
use DmitryProA\PhpAdvancedQuerying\Statement;
use DmitryProA\PhpAdvancedQuerying\Statements\ConditionalStatement;
use DmitryProA\PhpAdvancedQuerying\Statements\Delete;
use DmitryProA\PhpAdvancedQuerying\Statements\Insert;
use DmitryProA\PhpAdvancedQuerying\Statements\InsertSelect;
use DmitryProA\PhpAdvancedQuerying\Statements\JoinStatement;
use DmitryProA\PhpAdvancedQuerying\Statements\Replace;
use DmitryProA\PhpAdvancedQuerying\Statements\ReplaceSelect;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;
use DmitryProA\PhpAdvancedQuerying\Statements\Update;
use DmitryProA\PhpAdvancedQuerying\Table;
use PHPUnit\Framework\TestCase;

use function DmitryProA\PhpAdvancedQuerying\column;
use function DmitryProA\PhpAdvancedQuerying\eq;
use function DmitryProA\PhpAdvancedQuerying\expr;
use function DmitryProA\PhpAdvancedQuerying\plus;
use function DmitryProA\PhpAdvancedQuerying\table;

/**
 * @internal
 *
 * @covers \DmitryProA\PhpAdvancedQuerying\Statements\Delete
 * @covers \DmitryProA\PhpAdvancedQuerying\Statements\Insert
 * @covers \DmitryProA\PhpAdvancedQuerying\Statements\InsertSelect
 * @covers \DmitryProA\PhpAdvancedQuerying\Statements\Replace
 * @covers \DmitryProA\PhpAdvancedQuerying\Statements\ReplaceSelect
 * @covers \DmitryProA\PhpAdvancedQuerying\Statements\Select
 * @covers \DmitryProA\PhpAdvancedQuerying\Statements\Update
 */
class StatementTest extends TestCase
{
    /** @var string */
    private $table;

    /** @var array */
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
        $this->table = 'test as t';

        $this->updateValues = ['t.test1' => 123, 'test2' => 'value', 'test3' => plus('col1', 'col2')];
        $this->updateValuesExpected = array_map(function ($field, $value) {
            return new FieldValue(column($field), expr($value));
        }, array_keys($this->updateValues), $this->updateValues);

        $this->insertFields = ['field1', 'field2'];
        $this->insertFieldsExpected = array_map('DmitryProA\\PhpAdvancedQuerying\\column', $this->insertFields);

        $this->insertValues = [123, 'test'];
        $this->insertValuesExpected = [array_map('DmitryProA\\PhpAdvancedQuerying\\literal', $this->insertValues)];
    }

    public function testSelect()
    {
        $selectColumns = ['alias' => 't.col', 'alias2' => 123, 'alias3' => eq('a', 'b'), new Select()];
        $expectedColumns = array_map(function ($alias, $column) {
            return new Column(expr($column), is_string($alias) ? $alias : '');
        }, array_keys($selectColumns), $selectColumns);

        $select = new Select($this->table, $selectColumns);
        $this->assertEquals(table($this->table), $select->getTable());
        $this->assertEquals($expectedColumns, $select->getColumns());

        $select = new Select();
        $this->assertEquals(null, $select->getTable());
        $this->assertEmpty($select->getColumns());

        $select->setTable($this->table);
        $this->assertEquals(table($this->table), $select->getTable());

        $select->setColumns(array_slice($selectColumns, 0, 3, true));
        $select->setColumn(array_values($selectColumns)[3]);
        $this->assertEquals($expectedColumns, $select->getColumns());

        $this->testConditionalStatement($select);
        $this->testJoinStatement($select);

        $select2 = new Select($select);
        $this->assertEquals(new SelectTable($select), $select2->getTable());

        $select2 = new Select(table($select, 'alias'));
        $this->assertEquals(new SelectTable($select, 'alias'), $select2->getTable());

        $select = new Select($this->table, $selectColumns);

        $table = new Table('testTable');
        $columns = ['alias' => new ColumnExpression('column')];
        $unionSelect = $select->unionSelect($table, $columns, true);

        $expectedSelect = new Select($table, $columns);
        $this->assertEquals($unionSelect, $select->getUnionSelect());
        $this->assertEquals($expectedSelect->getTable(), $unionSelect->getTable());
        $this->assertEquals($expectedSelect->getColumns(), $unionSelect->getColumns());
        $this->assertTrue($select->getUnionAll());

        $select = new Select('table', ['column']);
        $select->unionSelect('table2', ['column2']);
        $select->unionSelect('table3', ['column3']);

        $select2 = new Select('table', ['column']);
        $select2 = $select2->unionSelect('table2', ['column2']);
        $select2 = $select2->unionSelect('table3', ['column3']);

        $this->assertEquals($select2->getUnionOrigin(), $select);

        $this->expectException(InvalidTypeException::class);
        new Select(123);
    }

    public function testSelectException()
    {
        $this->expectException(InvalidTypeException::class);
        new Select(null, [new stdClass()]);
    }

    public function testUpdate()
    {
        $update = new Update($this->table, $this->updateValues);
        $this->assertEquals(table($this->table), $update->getTable());
        $this->assertEquals($this->updateValuesExpected, $update->getValues());

        $update = new Update();
        $this->assertNull($update->getTable());
        $this->assertEmpty($update->getValues());

        $update->setTable($this->table);
        $this->assertEquals(table($this->table), $update->getTable());

        $update->setValues(array_slice($this->updateValues, 0, 2, true));
        $update->setValue(array_keys($this->updateValues)[2], array_values($this->updateValues)[2]);
        $this->assertEquals($this->updateValuesExpected, $update->getValues());

        $this->testConditionalStatement($update);
        $this->testJoinStatement($update);

        $this->expectException(InvalidTypeException::class);
        new Update(123);
    }

    public function testUpdateException()
    {
        $this->expectException(InvalidTypeException::class);
        new Update(null, [123]);
    }

    public function testReplace()
    {
        $replace = new Insert($this->table, $this->insertFields, $this->insertValues);
        $this->assertEquals(table($this->table), $replace->getTable());
        $this->assertEquals($this->insertFieldsExpected, $replace->getFields());
        $this->assertEquals($this->insertValuesExpected, $replace->getValues());

        $replace = new Insert();
        $this->assertNull($replace->getTable());
        $this->assertEmpty($replace->getFields());
        $this->assertEmpty($replace->getValues());

        $replace->setTable($this->table);
        $this->assertEquals(table($this->table), $replace->getTable());

        $replace->setFields($this->insertFields);
        $this->assertEquals($this->insertFieldsExpected, $replace->getFields());

        $replace->setValues($this->insertValues);
        $this->assertEquals($this->insertValuesExpected, $replace->getValues());

        $this->expectException(InvalidTypeException::class);
        new Replace(123);
    }

    public function testReplaceException()
    {
        $this->expectException(InvalidTypeException::class);
        new Replace(null, [123]);
    }

    public function testReplaceException2()
    {
        $this->expectException(InvalidTypeException::class);
        new Replace(null, [], [new stdClass()]);
    }

    public function testInsert()
    {
        $insert = new Insert($this->table, $this->insertFields, $this->insertValues);
        $this->assertEquals(table($this->table), $insert->getTable());
        $this->assertEquals($this->insertFieldsExpected, $insert->getFields());
        $this->assertEquals($this->insertValuesExpected, $insert->getValues());

        $insert = new Insert();
        $this->assertNull($insert->getTable());
        $this->assertEmpty($insert->getFields());
        $this->assertEmpty($insert->getValues());

        $insert->setTable($this->table);
        $this->assertEquals(table($this->table), $insert->getTable());

        $insert->setFields($this->insertFields);
        $this->assertEquals($this->insertFieldsExpected, $insert->getFields());

        $insert->setValues($this->insertValues);
        $this->assertEquals($this->insertValuesExpected, $insert->getValues());

        $insert->onDuplicateKeyUpdate($this->updateValues);
        $this->assertEquals($this->updateValuesExpected, $insert->getOnDuplicateUpdateValues());

        $insert->ignore();
        $this->assertEquals(true, $insert->getIgnore());

        $this->expectException(InvalidTypeException::class);
        new Insert(123);
    }

    public function testInsertException()
    {
        $this->expectException(InvalidTypeException::class);
        new Insert(null, [123]);
    }

    public function testInsertException2()
    {
        $this->expectException(InvalidTypeException::class);
        new Insert(null, [], [new stdClass()]);
    }

    public function testInsertException3()
    {
        $this->expectException(InvalidTypeException::class);
        (new Insert())->onDuplicateKeyUpdate([123]);
    }

    public function testReplaceSelect()
    {
        $select = new Select();
        $replace = new ReplaceSelect($this->table, $this->insertFields, $select);
        $this->assertEquals(table($this->table), $replace->getTable());
        $this->assertEquals($this->insertFieldsExpected, $replace->getFields());
        $this->assertEquals($select, $replace->getSelect());

        $replace = new ReplaceSelect();
        $this->assertNull($replace->getTable());
        $this->assertEmpty($replace->getFields());
        $this->assertNull($replace->getSelect());

        $replace->setTable($this->table);
        $this->assertEquals(table($this->table), $replace->getTable());

        $replace->setFields($this->insertFields);
        $this->assertEquals($this->insertFieldsExpected, $replace->getFields());

        $replace->select($select);
        $this->assertEquals($select, $replace->getSelect());

        $this->expectException(InvalidTypeException::class);
        new Replace(123);
    }

    public function testReplaceSelectException()
    {
        $this->expectException(InvalidTypeException::class);
        new Replace(null, [123]);
    }

    public function testReplaceSelectException2()
    {
        $this->expectException(InvalidTypeException::class);
        new Replace(null, [], [new stdClass()]);
    }

    public function testUpdateSelect()
    {
        $select = new Select();
        $insert = new InsertSelect($this->table, $this->insertFields, $select);
        $this->assertEquals(table($this->table), $insert->getTable());
        $this->assertEquals($this->insertFieldsExpected, $insert->getFields());
        $this->assertEquals($select, $insert->getSelect());

        $insert = new InsertSelect();
        $this->assertNull($insert->getTable());
        $this->assertEmpty($insert->getFields());
        $this->assertNull($insert->getSelect());

        $insert->setTable($this->table);
        $this->assertEquals(table($this->table), $insert->getTable());

        $insert->setFields($this->insertFields);
        $this->assertEquals($this->insertFieldsExpected, $insert->getFields());

        $insert->select($select);
        $this->assertEquals($select, $insert->getSelect());

        $insert->onDuplicateKeyUpdate($this->updateValues);
        $this->assertEquals($this->updateValuesExpected, $insert->getOnDuplicateUpdateValues());

        $insert->ignore();
        $this->assertEquals(true, $insert->getIgnore());

        $this->expectException(InvalidTypeException::class);
        new Insert(123);
    }

    public function testInsertSelectException()
    {
        $this->expectException(InvalidTypeException::class);
        new Insert(null, [123]);
    }

    public function testInsertSelectException2()
    {
        $this->expectException(InvalidTypeException::class);
        new Insert(null, [], [new stdClass()]);
    }

    public function testInsertSelectException3()
    {
        $this->expectException(InvalidTypeException::class);
        (new InsertSelect())->onDuplicateKeyUpdate([123]);
    }

    public function testDelete()
    {
        $delete = new Delete($this->table);
        $this->assertEquals(table($this->table), $delete->getTable());

        $delete = new Delete();
        $delete->setTable($this->table);
        $this->assertEquals(table($this->table), $delete->getTable());

        $this->testConditionalStatement($delete);

        $this->expectException(InvalidTypeException::class);
        new Delete(123);
    }

    /** @param ConditionalStatement $st */
    private function testConditionalStatement(Statement $st)
    {
        $condition = $st->where()->eq(new LiteralExpression(1), new LiteralExpression(2));
        $end = $condition->end();

        $this->assertEquals($st, $end);
        $this->assertEquals($condition, $st->getCondition());
    }

    /** @param JoinStatement $st */
    private function testJoinStatement(Statement $st)
    {
        $left = new LiteralExpression(1);
        $right = new LiteralExpression(2);

        $condition1 = $st->join($this->table, Join::INNER)->eq($left, $right);
        $condition1->end();

        $condition2 = $st->join('joinTable', Join::LEFT)->eq($left, $right);
        $condition2->end();

        $joins = $st->getJoins();
        $this->assertCount(2, $joins);
        $this->assertEquals(new Join(table($this->table), $condition1, Join::INNER), $joins[0]);
        $this->assertEquals(new Join(table('joinTable'), $condition2, Join::LEFT), $joins[1]);
    }
}
