<?php

declare(strict_types=1);

use DmitryProA\PhpAdvancedQuerying\Conditions\ExprCondition;
use DmitryProA\PhpAdvancedQuerying\Expressions\ArithmeticExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\CastExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\ConditionExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\CountExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\FunctionExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\GroupConcatExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\LiteralExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\SelectExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\WindowFunctionExpression;
use DmitryProA\PhpAdvancedQuerying\InvalidTypeException;
use DmitryProA\PhpAdvancedQuerying\OrderBy;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \DmitryProA\PhpAdvancedQuerying\Expressions\ArithmeticExpression
 * @covers \DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression
 * @covers \DmitryProA\PhpAdvancedQuerying\Expressions\CountExpression
 * @covers \DmitryProA\PhpAdvancedQuerying\Expressions\FunctionExpression
 * @covers \DmitryProA\PhpAdvancedQuerying\Expressions\GroupConcatExpression
 * @covers \DmitryProA\PhpAdvancedQuerying\Expressions\LiteralExpression
 * @covers \DmitryProA\PhpAdvancedQuerying\Expressions\SelectExpression
 */
class ExpressionTest extends TestCase
{
    public function testLiteralExpression()
    {
        $expr = new LiteralExpression('abc');
        $this->assertEquals('abc', $expr->value);

        return $expr;
    }

    public function testLiteralExpressionException()
    {
        $this->expectException(InvalidTypeException::class);
        new LiteralExpression(new LiteralExpression(123));
    }

    public function testLiteralExpressionException2()
    {
        $this->expectException(InvalidTypeException::class);
        new LiteralExpression(['array']);
    }

    public function testColumnExpression(): array
    {
        $column1 = new ColumnExpression('columnName', 'tableName');
        $this->assertEquals('columnName', $column1->name);
        $this->assertEquals('tableName', $column1->table);

        $column2 = new ColumnExpression('column2Name');
        $this->assertEquals('', $column2->table);

        return [$column1, $column2];
    }

    /** @depends testColumnExpression */
    public function testCountExpression(array $columns)
    {
        $expr = new CountExpression(true, ...$columns);
        $this->assertTrue($expr->distinct);
        $this->assertEquals($columns, $expr->arguments);

        $expr2 = new CountExpression();
        $this->assertFalse($expr2->distinct);
        $this->assertEmpty($expr2->arguments);

        $this->expectException(InvalidTypeException::class);
        new CountExpression(false, [new stdClass()]);
    }

    public function testCountExpressionException()
    {
        $this->expectException(InvalidArgumentException::class);
        new CountExpression(true);
    }

    public function testGroupConcatExpression()
    {
        $column = new ColumnExpression('test');
        $expr = new GroupConcatExpression($column, true, ';');
        $this->assertEquals([$column], $expr->arguments);
        $this->assertTrue($expr->distinct);
        $this->assertEquals(';', $expr->separator);

        $expr2 = new GroupConcatExpression($column);
        $this->assertFalse($expr2->distinct);
        $this->assertEquals(',', $expr2->separator);
    }

    public function testCastExpression()
    {
        $column = new ColumnExpression('test');
        $expr = new CastExpression($column, CastExpression::SIGNED);
        $this->assertEquals($column, $expr->expr);
        $this->assertEquals(CastExpression::SIGNED, $expr->type);

        $this->expectException(InvalidArgumentException::class);
        new CastExpression($column, 'invalid');
    }

    public function testFunctionExpression()
    {
        $literals = [new LiteralExpression(123), new LiteralExpression('test')];
        $expr = new FunctionExpression('test', ...$literals);

        $this->assertEquals('test', $expr->function);
        $this->assertEquals($literals, $expr->arguments);

        $this->expectException(InvalidTypeException::class);
        new FunctionExpression('test', 'test');
    }

    public function testWindowFunctionExpression()
    {
        $function = new FunctionExpression('first_value', new ColumnExpression('test'));
        $column = new ColumnExpression('column');
        $orderColumn1 = new ColumnExpression('column2');
        $orderColumn2 = new ColumnExpression('column3');

        $expr = new WindowFunctionExpression($function, $column);
        $expr->OrderBy($orderColumn1)->OrderBy($orderColumn2, OrderBy::DESC);

        $this->assertEquals($function, $expr->function);
        $this->assertEquals($column, $expr->partitionExpr);
        $this->assertEquals([new OrderBy($orderColumn1), new OrderBy($orderColumn2, OrderBy::DESC)], $expr->orderBy_);

        $this->expectException(InvalidArgumentException::class);
        new WindowFunctionExpression(new FunctionExpression('invalid'), new ColumnExpression('test'));
    }

    public function testArithmeticExpression()
    {
        $left = new ColumnExpression('column', 'table');
        $right = new LiteralExpression(123);
        $expr = new ArithmeticExpression(
            $left,
            $right,
            ArithmeticExpression::PLUS
        );
        $this->assertEquals($left, $expr->left);
        $this->assertEquals($right, $expr->right);
        $this->assertEquals(ArithmeticExpression::PLUS, $expr->op);

        $this->expectException(InvalidArgumentException::class);
        new ArithmeticExpression($left, $right, 'test');
    }

    public function testSelectExpression()
    {
        $select = new Select();

        $expr = new SelectExpression($select);
        $this->assertEquals($select, $expr->select);
    }

    public function testConditionExpression()
    {
        $condition = new ExprCondition(new LiteralExpression(123));

        $expr = new ConditionExpression($condition);
        $this->assertEquals($condition, $expr->condition);
    }
}
