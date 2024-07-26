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
use DmitryProA\PhpAdvancedQuerying\Expressions\CastExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\ConditionExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\CountExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\FunctionExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\GroupConcatExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\LiteralExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\SelectExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\WindowFunctionExpression;
use DmitryProA\PhpAdvancedQuerying\FieldValue;
use DmitryProA\PhpAdvancedQuerying\InvalidTypeException;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;
use DmitryProA\PhpAdvancedQuerying\Table;
use PHPUnit\Framework\TestCase;

use function DmitryProA\PhpAdvancedQuerying\and_;
use function DmitryProA\PhpAdvancedQuerying\cast;
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
use function DmitryProA\PhpAdvancedQuerying\literalOrExpr;
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
use function DmitryProA\PhpAdvancedQuerying\over;
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

        $table = table('tasks'); // Name contains 'as'
        $expected = new Table('tasks');
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
        $this->assertEquals($expectedColumns, columns($columns));

        $this->expectException(InvalidTypeException::class);
        columns(new stdClass());
    }

    public function testLiteral()
    {
        $literal = literal('test');
        $expected = new LiteralExpression('test');
        $this->assertEquals($expected, $literal);

        $literal2 = literal($literal);
        $this->assertEquals($literal, $literal2);

        $this->expectException(InvalidTypeException::class);
        literal(new stdClass());
    }

    public function testLiterals()
    {
        $literals = [123, 'test'];
        $expectedLiterals = [new LiteralExpression(123), new LiteralExpression('test')];

        $this->assertEquals($expectedLiterals, literals(...$literals));
        $this->assertEquals($expectedLiterals, literals($literals));

        $this->expectException(InvalidTypeException::class);
        literals(new stdClass());
    }

    public function testLiteralOrExpr()
    {
        $expr = literalOrExpr('value');
        $this->assertEquals(new LiteralExpression('value'), $expr);

        $column = new ColumnExpression('col');
        $expr = literalOrExpr($column);
        $this->assertEquals($column, $expr);

        $this->expectException(InvalidTypeException::class);
        literalOrExpr(new stdClass());
    }

    public function testGroupconcat()
    {
        $groupConcat = groupconcat(123, true, ';');
        $expected = new GroupConcatExpression(new LiteralExpression(123), true, ';');
        $this->assertEquals($expected, $groupConcat);

        $this->expectException(InvalidTypeException::class);
        groupconcat(new stdClass());
    }

    public function testCount()
    {
        $columns = ['col1', 't.col2'];
        $expected = new CountExpression(true, new ColumnExpression('col1'), new ColumnExpression('col2', 't'));
        $this->assertEquals($expected, count_(true, ...$columns));
        $this->assertEquals($expected, count_(true, $columns));

        $this->expectException(InvalidTypeException::class);
        count_(false, new stdClass());
    }

    public function testCast()
    {
        $cast = cast('test', CastExpression::SIGNED);
        $expected = new CastExpression(new ColumnExpression('test'), CastExpression::SIGNED);
        $this->assertEquals($expected, $cast);

        $this->expectException(InvalidTypeException::class);
        cast(new stdClass(), CastExpression::SIGNED);
    }

    public function testOver()
    {
        $over = over('row_number', 'column');
        $expected = new WindowFunctionExpression(new FunctionExpression('row_number'), new ColumnExpression('column'));
        $this->assertEquals($expected, $over);

        $func = new FunctionExpression('first_value', new ColumnExpression('test'));
        $over = over($func, 'column');
        $expected = new WindowFunctionExpression($func, new ColumnExpression('column'));
        $this->assertEquals($expected, $over);

        $this->expectException(InvalidTypeException::class);
        over(123, 'column');
    }

    public function testOverException()
    {
        $this->expectException(InvalidTypeException::class);
        over('row_number', new stdClass());
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
        expr(new stdClass());
    }

    public function testExprs()
    {
        $select = new Select();
        $exprs = ['t.column', 123, ';', $select];
        $expected = [
            new ColumnExpression('column', 't'),
            new LiteralExpression(123),
            new LiteralExpression(';'),
            new SelectExpression($select),
        ];

        $this->assertEquals($expected, exprs(...$exprs));
        $this->assertEquals($expected, exprs($exprs));

        $this->expectException(InvalidTypeException::class);
        exprs(new stdClass());
    }

    public function testCondition()
    {
        $condition = condition('column');
        $expected = new ExprCondition(new ColumnExpression('column'));
        $this->assertEquals($expected, $condition);

        $condition2 = condition($condition);
        $this->assertEquals($condition, $condition2);

        $this->expectException(InvalidTypeException::class);
        condition(new stdClass());
    }

    public function testConditions()
    {
        $eq = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(1), CompareCondition::EQUALS);
        $conditions = conditions('column', $eq);

        $this->assertEquals([new ExprCondition(new ColumnExpression('column')), $eq], $conditions);

        $this->expectException(InvalidTypeException::class);
        conditions(new stdClass());
    }

    public function testAnd()
    {
        $eq = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(1), CompareCondition::EQUALS);
        $and = and_('column', $eq);

        $expected = new AndCondition(new ExprCondition(new ColumnExpression('column')), $eq);
        $this->assertEquals($expected, $and);

        $this->expectException(InvalidTypeException::class);
        and_(new stdClass());
    }

    public function testOr()
    {
        $eq = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(1), CompareCondition::EQUALS);
        $conditions = ['column', $eq];

        $expected = new OrCondition(new ExprCondition(new ColumnExpression('column')), $eq);
        $this->assertEquals($expected, or_(...$conditions));
        $this->assertEquals($expected, or_($conditions));

        $this->expectException(InvalidTypeException::class);
        or_(new stdClass());
    }

    public function testEq()
    {
        $expr = eq('column', 'string');
        $expected = new CompareCondition(new ColumnExpression('column'), new LiteralExpression('string'), CompareCondition::EQUALS);
        $this->assertEquals($expected, $expr);

        $expr = eq('column', column('column2'));
        $expected = new CompareCondition(new ColumnExpression('column'), new ColumnExpression('column2'), CompareCondition::EQUALS);
        $this->assertEquals($expected, $expr);

        $this->expectException(InvalidTypeException::class);
        eq([], new stdClass());
    }

    public function testGreater()
    {
        $expr = greater('column', 'string');
        $expected = new CompareCondition(new ColumnExpression('column'), new LiteralExpression('string'), CompareCondition::GREATER);
        $this->assertEquals($expected, $expr);

        $expr = greater('column', column('column2'));
        $expected = new CompareCondition(new ColumnExpression('column'), new ColumnExpression('column2'), CompareCondition::GREATER);
        $this->assertEquals($expected, $expr);

        $this->expectException(InvalidTypeException::class);
        greater([], new stdClass());
    }

    public function testGreaterEquals()
    {
        $expr = greaterEquals('column', 'string');
        $expected = new CompareCondition(new ColumnExpression('column'), new LiteralExpression('string'), CompareCondition::GREATER_EQUALS);
        $this->assertEquals($expected, $expr);

        $expr = greaterEquals('column', column('column2'));
        $expected = new CompareCondition(new ColumnExpression('column'), new ColumnExpression('column2'), CompareCondition::GREATER_EQUALS);
        $this->assertEquals($expected, $expr);

        $this->expectException(InvalidTypeException::class);
        greaterEquals([], new stdClass());
    }

    public function testLess()
    {
        $expr = less('column', 'string');
        $expected = new CompareCondition(new ColumnExpression('column'), new LiteralExpression('string'), CompareCondition::LESS);
        $this->assertEquals($expected, $expr);

        $expr = less('column', column('column2'));
        $expected = new CompareCondition(new ColumnExpression('column'), new ColumnExpression('column2'), CompareCondition::LESS);
        $this->assertEquals($expected, $expr);

        $this->expectException(InvalidTypeException::class);
        less([], new stdClass());
    }

    public function testLessEquals()
    {
        $expr = lessEquals('column', 'string');
        $expected = new CompareCondition(new ColumnExpression('column'), new LiteralExpression('string'), CompareCondition::LESS_EQUALS);
        $this->assertEquals($expected, $expr);

        $expr = lessEquals('column', column('column2'));
        $expected = new CompareCondition(new ColumnExpression('column'), new ColumnExpression('column2'), CompareCondition::LESS_EQUALS);
        $this->assertEquals($expected, $expr);

        $this->expectException(InvalidTypeException::class);
        lessEquals([], new stdClass());
    }

    public function testLike()
    {
        $like = like('column', 'string');
        $expected = new LikeCondition(new ColumnExpression('column'), new LiteralExpression('string'));
        $this->assertEquals($expected, $like);

        $like = like('column', column('column2'));
        $expected = new LikeCondition(new ColumnExpression('column'), new ColumnExpression('column2'));
        $this->assertEquals($expected, $like);

        $this->expectException(InvalidTypeException::class);
        like([], new stdClass());
    }

    public function testIn()
    {
        $exprs = [123, 'test'];
        $expected = new InCondition(
            new ColumnExpression('column'),
            new LiteralExpression(123),
            new LiteralExpression('test')
        );
        $this->assertEquals($expected, in('column', ...$exprs));
        $this->assertEquals($expected, in('column', $exprs));

        $this->expectException(InvalidTypeException::class);
        in([], new stdClass());
    }

    public function testNot()
    {
        $not = not('column');
        $expected = new NotCondition(new ExprCondition(new ColumnExpression('column')));
        $this->assertEquals($expected, $not);

        $this->expectException(InvalidTypeException::class);
        not(new stdClass());
    }

    public function testIsNull()
    {
        $isNull = isNull('column');
        $expected = new NullCondition(new ColumnExpression('column'));
        $this->assertEquals($expected, $isNull);

        $this->expectException(InvalidTypeException::class);
        isNull(new stdClass());
    }

    public function testIsNotNull()
    {
        $notNull = isNotNull('column');
        $expected = new NotCondition(new NullCondition(new ColumnExpression('column')));
        $this->assertEquals($expected, $notNull);

        $this->expectException(InvalidTypeException::class);
        isNotNull(new stdClass());
    }

    public function testNotEq()
    {
        $notEq = notEq('column', 'string');
        $expected = new NotCondition(eq('column', 'string'));
        $this->assertEquals($expected, $notEq);

        $this->expectException(InvalidTypeException::class);
        notEq([], new stdClass());
    }

    public function testNotLike()
    {
        $notLike = notLike('column', 'string');
        $expected = new NotCondition(like('column', 'string'));
        $this->assertEquals($expected, $notLike);

        $this->expectException(InvalidTypeException::class);
        notLike([], new stdClass());
    }

    public function testNotIn()
    {
        $exprs = [123, 'test'];
        $expected = new NotCondition(new InCondition(
            new ColumnExpression('column'),
            new LiteralExpression(123),
            new LiteralExpression('test')
        ));
        $this->assertEquals($expected, notIn('column', ...$exprs));
        $this->assertEquals($expected, notIn('column', $exprs));

        $this->expectException(InvalidTypeException::class);
        notIn([], new stdClass());
    }

    public function testTrue()
    {
        $true = true('column');
        $expected = new ExprCondition(new ColumnExpression('column'));
        $this->assertEquals($expected, $true);

        $this->expectException(InvalidTypeException::class);
        true(new stdClass());
    }

    public function testFalse()
    {
        $false = false('column');
        $expected = new NotCondition(new ExprCondition(new ColumnExpression('column')));
        $this->assertEquals($expected, $false);

        $this->expectException(InvalidTypeException::class);
        false(new stdClass());
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
