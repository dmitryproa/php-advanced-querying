<?php

declare(strict_types=1);

use DmitryProA\PhpAdvancedQuerying\Conditions\AndCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\CompareCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\ExprCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\InCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\LikeCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\NotCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\NullCondition;
use DmitryProA\PhpAdvancedQuerying\Conditions\OrCondition;
use DmitryProA\PhpAdvancedQuerying\Expressions\ColumnExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\FunctionExpression;
use DmitryProA\PhpAdvancedQuerying\Expressions\LiteralExpression;
use DmitryProA\PhpAdvancedQuerying\InvalidTypeException;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;
use PHPUnit\Framework\TestCase;

use function DmitryProA\PhpAdvancedQuerying\true;

/**
 * @internal
 *
 * @covers \DmitryProA\PhpAdvancedQuerying\Conditions\AndCondition
 * @covers \DmitryProA\PhpAdvancedQuerying\Conditions\EqualCondition
 * @covers \DmitryProA\PhpAdvancedQuerying\Conditions\ExprCondition
 * @covers \DmitryProA\PhpAdvancedQuerying\Conditions\InCondition
 * @covers \DmitryProA\PhpAdvancedQuerying\Conditions\LikeCondition
 * @covers \DmitryProA\PhpAdvancedQuerying\Conditions\NotCondition
 * @covers \DmitryProA\PhpAdvancedQuerying\Conditions\NullCondition
 * @covers \DmitryProA\PhpAdvancedQuerying\Conditions\OrCondition
 */
class ConditionTest extends TestCase
{
    public function testExprCondition()
    {
        $expr = new LiteralExpression('test');
        $cond = new ExprCondition($expr);

        $this->assertEquals($expr, $cond->expr);
    }

    public function testCompareCondition()
    {
        $left = new LiteralExpression('left');
        $right = new LiteralExpression('right');
        $cond = new CompareCondition($left, $right, CompareCondition::EQUALS);

        $this->assertEquals($left, $cond->left);
        $this->assertEquals($right, $cond->right);
        $this->assertEquals(CompareCondition::EQUALS, $cond->type);

        $this->expectException(InvalidArgumentException::class);
        new CompareCondition($left, $right, 'test');
    }

    public function testInCondition()
    {
        $expr = new ColumnExpression('column');
        $values = [new LiteralExpression(1), new LiteralExpression('string')];
        $cond = new InCondition($expr, ...$values);

        $this->assertEquals($expr, $cond->expr);
        $this->assertEquals($values, $cond->values);

        $this->expectException(InvalidTypeException::class);
        new InCondition($expr, 123, $cond);
    }

    public function testLikeCondition()
    {
        $left = new ColumnExpression('column');
        $right = new LiteralExpression('test');
        $cond = new LikeCondition($left, $right);

        $this->assertEquals($left, $cond->left);
        $this->assertEquals($right, $cond->right);
    }

    public function testNullCondition()
    {
        $expr = new ColumnExpression('column');
        $cond = new NullCondition($expr);

        $this->assertEquals($expr, $cond->expr);
    }

    public function testNotCondition()
    {
        $inCond = new ExprCondition(new LiteralExpression(123));
        $cond = new NotCondition($inCond);

        $this->assertEquals($inCond, $cond->condition);
    }

    public function testAndCondition()
    {
        $expr1 = new ExprCondition(new LiteralExpression(1));
        $expr2 = new ExprCondition(new LiteralExpression(2));
        $expr3 = new ExprCondition(new LiteralExpression(3));
        $cond = new AndCondition($expr1, $expr2);
        $this->assertEquals([$expr1, $expr2], $cond->conditions);

        $cond = new AndCondition($cond, $expr3);
        $this->assertEquals([$expr1, $expr2, $expr3], $cond->conditions);

        $this->expectException(InvalidTypeException::class);
        new AndCondition(1, 'test');
    }

    public function testAndConditionException()
    {
        $cond = new AndCondition();
        $this->expectException(Exception::class);
        $cond->end();
    }

    public function testOrCondition()
    {
        $conditions = [new ExprCondition(new LiteralExpression(123)), new ExprCondition(new LiteralExpression('test'))];
        $cond = new OrCondition(...$conditions);

        $this->assertEquals($conditions, $cond->conditions);

        $this->expectException(InvalidTypeException::class);
        new OrCondition($conditions);
    }

    public function testOrConditionException()
    {
        $cond = new OrCondition();
        $this->expectException(Exception::class);
        $cond->end();
    }

    public function testConditionChaining()
    {
        $expr1 = new LiteralExpression(123);
        $expr2 = new LiteralExpression('test');
        $expr3 = new ColumnExpression('column');
        $expr4 = new FunctionExpression('func', new ColumnExpression('arg'));

        $cond = new CompareCondition($expr1, $expr2, CompareCondition::EQUALS);

        $st = new Select();
        $cond->setStatement($st);

        // ->eq()
        $new = $cond->eq($expr3, $expr4);
        $expected = new AndCondition($cond, new CompareCondition($expr3, $expr4, CompareCondition::EQUALS));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->greater()
        $new = $cond->greater($expr3, $expr4);
        $expected = new AndCondition($cond, new CompareCondition($expr3, $expr4, CompareCondition::GREATER));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->greaterEquals()
        $new = $cond->greaterEquals($expr3, $expr4);
        $expected = new AndCondition($cond, new CompareCondition($expr3, $expr4, CompareCondition::GREATER_EQUALS));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->less()
        $new = $cond->less($expr3, $expr4);
        $expected = new AndCondition($cond, new CompareCondition($expr3, $expr4, CompareCondition::LESS));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->lessEquals()
        $new = $cond->lessEquals($expr3, $expr4);
        $expected = new AndCondition($cond, new CompareCondition($expr3, $expr4, CompareCondition::LESS_EQUALS));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->true()
        $new = $cond->true($expr3);
        $expected = new AndCondition($cond, new ExprCondition($expr3));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->like()
        $new = $cond->like($expr3, $expr4);
        $expected = new AndCondition($cond, new LikeCondition($expr3, $expr4));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->isNull()
        $new = $cond->isNull($expr3);
        $expected = new AndCondition($cond, new NullCondition($expr3));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->in()
        $new = $cond->in($expr3, 1, 2);
        $expected = new AndCondition($cond, new InCondition($expr3, new LiteralExpression(1), new LiteralExpression(2)));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->notEq()
        $new = $cond->notEq($expr3, $expr4);
        $expected = new AndCondition($cond, new NotCondition(new CompareCondition($expr3, $expr4, CompareCondition::EQUALS)));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->false()
        $new = $cond->false($expr3);
        $expected = new AndCondition($cond, new NotCondition(new ExprCondition($expr3)));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->notLike()
        $new = $cond->notLike($expr3, $expr4);
        $expected = new AndCondition($cond, new NotCondition(new LikeCondition($expr3, $expr4)));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->isNotNull()
        $new = $cond->isNotNull($expr3);
        $expected = new AndCondition($cond, new NotCondition(new NullCondition($expr3)));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->notIn()
        $new = $cond->notIn($expr3, 1, 2);
        $expected = new AndCondition($cond, new NotCondition(new InCondition($expr3, new LiteralExpression(1), new LiteralExpression(2))));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        $cond2 = new LikeCondition($expr1, $expr2);
        $cond3 = new NullCondition($expr3);

        // ->and()
        $new = $cond->and($cond2, $cond3);
        $expected = new AndCondition($cond, $cond2, $cond3);
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // ->or()
        $new = $cond->or($cond2, $cond3);
        $expected = new AndCondition($cond, new OrCondition($cond2, $cond3));
        $expected->setStatement($st);
        $this->assertEquals($expected, $new);

        // Asset that condition returns self
        $new = $cond->true($expr3);
        $new2 = $new->true($expr4);
        $this->assertEquals($new, $new2);
    }
}
