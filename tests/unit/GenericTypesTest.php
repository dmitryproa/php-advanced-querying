<?php

declare(strict_types=1);

use DmitryProA\PhpAdvancedQuerying\Column;
use DmitryProA\PhpAdvancedQuerying\Conditions\CompareCondition;
use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\LiteralExpression;
use DmitryProA\PhpAdvancedQuerying\FieldValue;
use DmitryProA\PhpAdvancedQuerying\Join;
use DmitryProA\PhpAdvancedQuerying\OrderBy;
use DmitryProA\PhpAdvancedQuerying\Table;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \DmitryProA\PhpAdvancedQuerying\Column
 * @covers \DmitryProA\PhpAdvancedQuerying\FieldValue
 * @covers \DmitryProA\PhpAdvancedQuerying\Join
 * @covers \DmitryProA\PhpAdvancedQuerying\OrderBy
 * @covers \DmitryProA\PhpAdvancedQuerying\Table
 */
class GenericTypesTest extends TestCase
{
    public function testColumn()
    {
        $expr = new LiteralExpression(123);
        $column = new Column($expr, 'test');

        $this->assertEquals($expr, $column->expr);
        $this->assertEquals('test', $column->alias);
    }

    public function testTable()
    {
        $name = 'name_';
        $alias = 'alias_';
        $table = new Table($name, $alias);
        $this->assertEquals($name, $table->name);
        $this->assertEquals($alias, $table->alias);
    }

    public function testFieldValue()
    {
        $column = new ColumnExpression('test');
        $expr = new LiteralExpression(123);
        $fieldValue = new FieldValue($column, $expr);
        $this->assertEquals($column, $fieldValue->field);
        $this->assertEquals($expr, $fieldValue->value);
    }

    public function testJoin()
    {
        $table = new Table('test');
        $conditon = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(123), CompareCondition::EQUALS);
        $join = new Join($table, $conditon, Join::LEFT);
        $this->assertEquals($table, $join->table);
        $this->assertEquals($conditon, $join->condition);
        $this->assertEquals(Join::LEFT, $join->type);

        $this->expectException(InvalidArgumentException::class);
        new Join($table, $conditon, 'test');
    }

    public function testOrderBy()
    {
        $expression = new ColumnExpression('test');
        $orderBy = new OrderBy($expression, OrderBy::DESC);
        $this->assertEquals($expression, $orderBy->expr);
        $this->assertEquals(OrderBy::DESC, $orderBy->direction);

        $this->expectException(InvalidArgumentException::class);
        new OrderBy($expression, 'test');
    }
}
