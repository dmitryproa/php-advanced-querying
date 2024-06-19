<?php

declare(strict_types=1);

use DmitryProA\PhpAdvancedQuerying\Condition;
use DmitryProA\PhpAdvancedQuerying\Conditions\AndCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\CompareCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\ExprCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\InCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\LikeCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\NotCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\NullCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\OrCondition;
use DmitryProA\PhpAdvancedQuerying\Expressions\ArithmeticExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\ConditionExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\CountExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\FunctionExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\GroupConcatExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\LiteralExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\SelectExpression;
use DmitryProA\PhpAdvancedQuerying\FieldValue;
use DmitryProA\PhpAdvancedQuerying\InvalidTypeException;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;
use DmitryProA\PhpAdvancedQuerying\Table;
use PHPUnit\Framework\TestCase;

use function DmitryProA\PhpAdvancedQuerying\and_;
use function DmitryProA\PhpAdvancedQuerying\checkArrayType;
use function DmitryProA\PhpAdvancedQuerying\checkType;
use function DmitryProA\PhpAdvancedQuerying\column;
use function DmitryProA\PhpAdvancedQuerying\columns;
use function DmitryProA\PhpAdvancedQuerying\condition;
use function DmitryProA\PhpAdvancedQuerying\conditions;
use function DmitryProA\PhpAdvancedQuerying\count_;
use function DmitryProA\PhpAdvancedQuerying\divide;
use function DmitryProA\PhpAdvancedQuerying\eq;
use function DmitryProA\PhpAdvancedQuerying\expr;
use function DmitryProA\PhpAdvancedQuerying\exprs;
use function DmitryProA\PhpAdvancedQuerying\false;
use function DmitryProA\PhpAdvancedQuerying\greater;
use function DmitryProA\PhpAdvancedQuerying\greaterEquals;
use function DmitryProA\PhpAdvancedQuerying\groupconcat;
use function DmitryProA\PhpAdvancedQuerying\in;
use function DmitryProA\PhpAdvancedQuerying\isNotNull;
use function DmitryProA\PhpAdvancedQuerying\isNull;
use function DmitryProA\PhpAdvancedQuerying\less;
use function DmitryProA\PhpAdvancedQuerying\lessEquals;
use function DmitryProA\PhpAdvancedQuerying\like;
use function DmitryProA\PhpAdvancedQuerying\literal;
use function DmitryProA\PhpAdvancedQuerying\literals;
use function DmitryProA\PhpAdvancedQuerying\makeUpdateValue;
use function DmitryProA\PhpAdvancedQuerying\makeUpdateValues;
use function DmitryProA\PhpAdvancedQuerying\minus;
use function DmitryProA\PhpAdvancedQuerying\multiply;
use function DmitryProA\PhpAdvancedQuerying\not;
use function DmitryProA\PhpAdvancedQuerying\notEq;
use function DmitryProA\PhpAdvancedQuerying\notIn;
use function DmitryProA\PhpAdvancedQuerying\notLike;
use function DmitryProA\PhpAdvancedQuerying\or_;
use function DmitryProA\PhpAdvancedQuerying\plus;
use function DmitryProA\PhpAdvancedQuerying\select;
use function DmitryProA\PhpAdvancedQuerying\table;
use function DmitryProA\PhpAdvancedQuerying\true;

/**
 * @internal
 *
 * @coversNothing
 */
class HelpersTest extends TestCase
{
    public function testTable()
    {
        $table = table('test', 't');
        $expected = new Table('test', 't');
        $this->assertEquals($expected, $table);

        $table = table('test as t');
        $expected = new Table('test', 't');
        $this->assertEquals($expected, $table);

        $table2 = table($table);
        $this->assertEquals($table, $table2);

        $table = table(null);
        $this->assertNull($table);

        $this->expectException(InvalidTypeException::class);
        table(123);
    }

    public function testSelect()
    {
        $table = table('test');
        $columns = ['alias' => 'column', 'alias2' => 123, new CountExpression(false, new ColumnExpression('col'))];

        $select = select($table, $columns);
        $expected = new Select($table, $columns);
        $this->assertEquals($expected, $select);
    }

    public function testColumn()
    {
        $column = column('test');
        $expected = new ColumnExpression('test');
        $this->assertEquals($expected, $column);

        $column = column('test', 't');
        $expected = new ColumnExpression('test', 't');
        $this->assertEquals($expected, $column);

        $column = column('t.test');
        $expected = new ColumnExpression('test', 't');
        $this->assertEquals($expected, $column);
    }

    public function testColumns()
    {
        $column = new ColumnExpression('test', 't');
        $columns = ['test', $column, 't.test'];
        $expectedColumns = [new ColumnExpression('test'), $column, $column];

        $this->assertEquals($expectedColumns, columns(...$columns));

        $this->expectException(InvalidTypeException::class);
        columns(123);
    }

    public function testLiteral()
    {
        $literal = literal('test');
        $expected = new LiteralExpression('test');
        $this->assertEquals($expected, $literal);

        $literal2 = literal($literal);
        $this->assertEquals($literal, $literal2);

        $this->expectException(InvalidTypeException::class);
        literal([]);
    }

    public function testLiterals()
    {
        $literals = [123, 'test'];
        $expectedLiterals = [new LiteralExpression(123), new LiteralExpression('test')];

        $this->assertEquals($expectedLiterals, literals(...$literals));

        $this->expectException(InvalidTypeException::class);
        literals([]);
    }

    public function testGroupconcat()
    {
        $groupConcat = groupconcat(123, true, ';');
        $expected = new GroupConcatExpression(new LiteralExpression(123), true, ';');
        $this->assertEquals($expected, $groupConcat);

        $this->expectException(InvalidTypeException::class);
        groupconcat([]);
    }

    public function testCount()
    {
        $count = count_(true, 'col1', 't.col2');
        $expected = new CountExpression(true, new ColumnExpression('col1'), new ColumnExpression('col2', 't'));
        $this->assertEquals($expected, $count);

        $this->expectException(InvalidTypeException::class);
        count_(false, []);
    }

    public function testPlus()
    {
        $plus = plus('col', 1);
        $expected = new ArithmeticExpression(new ColumnExpression('col'), new LiteralExpression(1), ArithmeticExpression::PLUS);
        $this->assertEquals($expected, $plus);

        $this->expectException(InvalidTypeException::class);
        plus([], []);
    }

    public function testMinus()
    {
        $minus = minus('col', 1);
        $expected = new ArithmeticExpression(new ColumnExpression('col'), new LiteralExpression(1), ArithmeticExpression::MINUS);
        $this->assertEquals($expected, $minus);

        $this->expectException(InvalidTypeException::class);
        minus([], []);
    }

    public function testMultiply()
    {
        $multiply = multiply('col', 1);
        $expected = new ArithmeticExpression(new ColumnExpression('col'), new LiteralExpression(1), ArithmeticExpression::MULTIPLY);
        $this->assertEquals($expected, $multiply);

        $this->expectException(InvalidTypeException::class);
        multiply([], []);
    }

    public function testDivide()
    {
        $divide = divide('col', 1);
        $expected = new ArithmeticExpression(new ColumnExpression('col'), new LiteralExpression(1), ArithmeticExpression::DIVIDE);
        $this->assertEquals($expected, $divide);

        $this->expectException(InvalidTypeException::class);
        divide([], []);
    }

    public function testExpr()
    {
        $expr = expr('t.column');
        $expected = new ColumnExpression('column', 't');
        $this->assertEquals($expected, $expr);

        $expr = expr(123);
        $expected = new LiteralExpression(123);
        $this->assertEquals($expected, $expr);

        $expr = expr(';');
        $expected = new LiteralExpression(';');
        $this->assertEquals($expected, $expr);

        $select = new Select();
        $expr = expr($select);
        $expected = new SelectExpression($select);
        $this->assertEquals($expected, $expr);

        $condition = new ExprCondition(new LiteralExpression(123));
        $expr = expr($condition);
        $expected = new ConditionExpression($condition);
        $this->assertEquals($expected, $expr);

        $this->expectException(InvalidTypeException::class);
        expr([]);
    }

    public function testExprs()
    {
        $select = new Select();
        $exprs = exprs('t.column', 123, ';', $select);
        $expected = [
            new ColumnExpression('column', 't'),
            new LiteralExpression(123),
            new LiteralExpression(';'),
            new SelectExpression($select),
        ];

        $this->assertEquals($expected, $exprs);

        $this->expectException(InvalidTypeException::class);
        exprs([]);
    }

    public function testCondition()
    {
        $condition = condition('column');
        $expected = new ExprCondition(new ColumnExpression('column'));
        $this->assertEquals($expected, $condition);

        $condition2 = condition($condition);
        $this->assertEquals($condition, $condition2);

        $this->expectException(InvalidTypeException::class);
        condition([]);
    }

    public function testConditions()
    {
        $eq = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(1), CompareCondition::EQUALS);
        $conditions = conditions('column', $eq);

        $this->assertEquals([new ExprCondition(new ColumnExpression('column')), $eq], $conditions);

        $this->expectException(InvalidTypeException::class);
        conditions([]);
    }

    public function testAnd()
    {
        $eq = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(1), CompareCondition::EQUALS);
        $and = and_('column', $eq);

        $expected = new AndCondition(new ExprCondition(new ColumnExpression('column')), $eq);
        $this->assertEquals($expected, $and);

        $this->expectException(InvalidTypeException::class);
        and_([]);
    }

    public function testOr()
    {
        $eq = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(1), CompareCondition::EQUALS);
        $or = or_('column', $eq);

        $expected = new OrCondition(new ExprCondition(new ColumnExpression('column')), $eq);
        $this->assertEquals($expected, $or);

        $this->expectException(InvalidTypeException::class);
        or_([]);
    }

    public function testEq()
    {
        $eq = eq('column', 1);
        $expected = new CompareCondition(new ColumnExpression('column'), new LiteralExpression(1), CompareCondition::EQUALS);
        $this->assertEquals($expected, $eq);

        $this->expectException(InvalidTypeException::class);
        eq([], new stdClass());
    }

    public function testGreater()
    {
        $eq = greater('column', 1);
        $expected = new CompareCondition(new ColumnExpression('column'), new LiteralExpression(1), CompareCondition::GREATER);
        $this->assertEquals($expected, $eq);

        $this->expectException(InvalidTypeException::class);
        greater([], new stdClass());
    }

    public function testGreaterEquals()
    {
        $eq = greaterEquals('column', 1);
        $expected = new CompareCondition(new ColumnExpression('column'), new LiteralExpression(1), CompareCondition::GREATER_EQUALS);
        $this->assertEquals($expected, $eq);

        $this->expectException(InvalidTypeException::class);
        greaterEquals([], new stdClass());
    }

    public function testLess()
    {
        $eq = less('column', 1);
        $expected = new CompareCondition(new ColumnExpression('column'), new LiteralExpression(1), CompareCondition::LESS);
        $this->assertEquals($expected, $eq);

        $this->expectException(InvalidTypeException::class);
        less([], new stdClass());
    }

    public function testLessEquals()
    {
        $eq = lessEquals('column', 1);
        $expected = new CompareCondition(new ColumnExpression('column'), new LiteralExpression(1), CompareCondition::LESS_EQUALS);
        $this->assertEquals($expected, $eq);

        $this->expectException(InvalidTypeException::class);
        lessEquals([], new stdClass());
    }

    public function testLike()
    {
        $like = like('column', 1);
        $expected = new LikeCondition(new ColumnExpression('column'), new LiteralExpression(1));
        $this->assertEquals($expected, $like);

        $this->expectException(InvalidTypeException::class);
        like([], new stdClass());
    }

    public function testIn()
    {
        $in = in('column', 123, 'test');
        $expected = new InCondition(
            new ColumnExpression('column'),
            new LiteralExpression(123),
            new LiteralExpression('test')
        );
        $this->assertEquals($expected, $in);

        $this->expectException(InvalidTypeException::class);
        in([], new stdClass());
    }

    public function testNot()
    {
        $not = not('column');
        $expected = new NotCondition(new ExprCondition(new ColumnExpression('column')));
        $this->assertEquals($expected, $not);

        $this->expectException(InvalidTypeException::class);
        not([]);
    }

    public function testIsNull()
    {
        $isNull = isNull('column');
        $expected = new NullCondition(new ColumnExpression('column'));
        $this->assertEquals($expected, $isNull);

        $this->expectException(InvalidTypeException::class);
        isNull([]);
    }

    public function testIsNotNull()
    {
        $notNull = isNotNull('column');
        $expected = new NotCondition(new NullCondition(new ColumnExpression('column')));
        $this->assertEquals($expected, $notNull);

        $this->expectException(InvalidTypeException::class);
        isNotNull([]);
    }

    public function testNotEq()
    {
        $notEq = notEq('column', 1);
        $expected = new NotCondition(new CompareCondition(new ColumnExpression('column'), new LiteralExpression(1), CompareCondition::EQUALS));
        $this->assertEquals($expected, $notEq);

        $this->expectException(InvalidTypeException::class);
        notEq([], new stdClass());
    }

    public function testNotLike()
    {
        $notLike = notLike('column', 1);
        $expected = new NotCondition(new LikeCondition(new ColumnExpression('column'), new LiteralExpression(1)));
        $this->assertEquals($expected, $notLike);

        $this->expectException(InvalidTypeException::class);
        notLike([], new stdClass());
    }

    public function testNotIn()
    {
        $notIn = notIn('column', 123, 'test');
        $expected = new NotCondition(new InCondition(
            new ColumnExpression('column'),
            new LiteralExpression(123),
            new LiteralExpression('test')
        ));
        $this->assertEquals($expected, $notIn);

        $this->expectException(InvalidTypeException::class);
        notIn([], new stdClass());
    }

    public function testTrue()
    {
        $true = true('column');
        $expected = new ExprCondition(new ColumnExpression('column'));
        $this->assertEquals($expected, $true);

        $this->expectException(InvalidTypeException::class);
        true([]);
    }

    public function testFalse()
    {
        $false = false('column');
        $expected = new NotCondition(new ExprCondition(new ColumnExpression('column')));
        $this->assertEquals($expected, $false);

        $this->expectException(InvalidTypeException::class);
        false([]);
    }

    public function testCheckType()
    {
        $this->assertNull(checkType(new LiteralExpression(123), LiteralExpression::class));
        $this->assertNull(checkType(new LiteralExpression(123), [Condition::class, LiteralExpression::class]));
        $this->assertNull(checkType(null, LiteralExpression::class, true));

        $this->expectException(InvalidTypeException::class);
        checkType('test', LiteralExpression::class);
    }

    public function testCheckTypeException()
    {
        $this->expectException(InvalidTypeException::class);
        checkType(null, LiteralExpression::class);
    }

    public function testArrayType()
    {
        $this->assertNull(checkArrayType(
            [
                new LiteralExpression(123),
                new LiteralExpression('test')],
            LiteralExpression::class
        ));

        $this->assertNull(checkArrayType(
            [
                new LiteralExpression(123),
                null],
            LiteralExpression::class,
            true
        ));

        $this->expectException(InvalidTypeException::class);
        checkArrayType(['test', 123], LiteralExpression::class);
    }

    public function testCheckArrayTypeException()
    {
        $this->expectException(InvalidTypeException::class);
        checkArrayType([new LiteralExpression(123), null], LiteralExpression::class);
    }

    public function testMakeUpdateValue()
    {
        $value = makeUpdateValue('column', 'column2');
        $expected = new FieldValue(new ColumnExpression('column'), new ColumnExpression('column2'));
        $this->assertEquals($expected, $value);

        $value = makeUpdateValue('t.column', 123);
        $expected = new FieldValue(new ColumnExpression('column', 't'), new LiteralExpression(123));
        $this->assertEquals($expected, $value);

        $func = new FunctionExpression('func', new ColumnExpression('arg'));
        $value = makeUpdateValue('column', $func);
        $expected = new FieldValue(new ColumnExpression('column'), $func);
        $this->assertEquals($expected, $value);

        $this->expectException(InvalidTypeException::class);
        makeUpdateValue([], new stdClass());
    }

    public function testMakeUpdateValues()
    {
        $values = makeUpdateValues(['test' => 123, 't.column' => 'column2']);
        $expected = [
            new FieldValue(new ColumnExpression('test'), new LiteralExpression(123)),
            new FieldValue(new ColumnExpression('column', 't'), new ColumnExpression('column2')),
        ];
        $this->assertEquals($expected, $values);

        $this->expectException(InvalidTypeException::class);
        makeUpdateValues([new stdClass()]);
    }
}
