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
use DmitryProA\PhpAdvancedQuerying\Expression;
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
use DmitryProA\PhpAdvancedQuerying\Formatters\MysqlFormatter;
use DmitryProA\PhpAdvancedQuerying\Join;
use DmitryProA\PhpAdvancedQuerying\OrderBy;
use DmitryProA\PhpAdvancedQuerying\QueryFormattingException;
use DmitryProA\PhpAdvancedQuerying\Statements\Delete;
use DmitryProA\PhpAdvancedQuerying\Statements\Insert;
use DmitryProA\PhpAdvancedQuerying\Statements\InsertSelect;
use DmitryProA\PhpAdvancedQuerying\Statements\Replace;
use DmitryProA\PhpAdvancedQuerying\Statements\ReplaceSelect;
use DmitryProA\PhpAdvancedQuerying\Statements\Select;
use DmitryProA\PhpAdvancedQuerying\Statements\Update;
use DmitryProA\PhpAdvancedQuerying\Table;
use PHPUnit\Framework\TestCase;

use function DmitryProA\PhpAdvancedQuerying\column;
use function DmitryProA\PhpAdvancedQuerying\count_;
use function DmitryProA\PhpAdvancedQuerying\func;
use function DmitryProA\PhpAdvancedQuerying\greater;
use function DmitryProA\PhpAdvancedQuerying\isNull;
use function DmitryProA\PhpAdvancedQuerying\plus;
use function DmitryProA\PhpAdvancedQuerying\select;

/**
 * @internal
 *
 * @covers \DmitryProA\PhpAdvancedQuerying\Formatters\MysqlFormatter
 */
class MysqlFormatterTest extends TestCase
{
    /** @var MysqlFormatter */
    private $formatter;

    public function setUp(): void
    {
        $this->formatter = new MysqlFormatter();
    }

    public function testSelectExpression()
    {
        $expr = new SelectExpression(new Select());
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('(SELECT 1)', $sql);
    }

    public function testLiteralExpression()
    {
        $expr = new LiteralExpression('test');
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql(':v1', $sql);

        $expr = new LiteralExpression(2);
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql(':v2', $sql);

        $expr = new LiteralExpression(null);
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('NULL', $sql);
    }

    public function testColumnExpression()
    {
        $expr = new ColumnExpression('test');
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('`test`', $sql);

        $expr = new ColumnExpression('test', 't');
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('`t`.`test`', $sql);

        $expr = new ColumnExpression('*');
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('*', $sql);

        $expr = new ColumnExpression('*', 't');
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('`t`.*', $sql);
    }

    public function testFunctionExpression()
    {
        $expr = new FunctionExpression('func', new ColumnExpression('column'), new LiteralExpression(123));
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('FUNC(`column`, :v1)', $sql);
    }

    public function testGroupConcatExpression()
    {
        $expr = new GroupConcatExpression(new ColumnExpression('test'));
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('GROUP_CONCAT(`test`)', $sql);

        $expr = new GroupConcatExpression(new ColumnExpression('test'), true, ';');
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('GROUP_CONCAT(DISTINCT `test` SEPARATOR :v1)', $sql);
    }

    public function testCountExpression()
    {
        $expr = new CountExpression();
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('COUNT(*)', $sql);

        $expr = new CountExpression(true, new ColumnExpression('col'), new ColumnExpression('col2'));
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('COUNT(DISTINCT `col`, `col2`)', $sql);
    }

    public function testCastExpression()
    {
        $expr = new CastExpression(new ColumnExpression('test'), CastExpression::BINARY);
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('CAST(`test` AS BINARY)', $sql);
    }

    public function testOverExpression()
    {
        $expr = new WindowFunctionExpression(
            new FunctionExpression('first_value', new ColumnExpression('column')),
            new ColumnExpression('column2')
        );
        $expr->OrderBy(new ColumnExpression('column3'))->OrderBy(new ColumnExpression('column4'), OrderBy::DESC);
        $sql = $this->formatter->formatExpression($expr);

        $this->assertSql('FIRST_VALUE(`column`) OVER (PARTITION BY `column2` ORDER BY `column3` ASC, `column4` DESC)', $sql);

        $expr = new WindowFunctionExpression(new FunctionExpression('row_number'), new ColumnExpression('column'));
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('ROW_NUMBER() OVER (PARTITION BY `column`)', $sql);
    }

    public function testArithmeticExpression()
    {
        $column = new ColumnExpression('test');
        $literal = new LiteralExpression(123);

        $plus = new ArithmeticExpression($column, $literal, ArithmeticExpression::PLUS);
        $sql = $this->formatter->formatExpression($plus);
        $this->assertSql('`test` + :v1', $sql);

        $minus = new ArithmeticExpression($column, $literal, ArithmeticExpression::MINUS);
        $sql = $this->formatter->formatExpression($minus);
        $this->assertSql('`test` - :v2', $sql);

        $multiply = new ArithmeticExpression($column, $literal, ArithmeticExpression::MULTIPLY);
        $sql = $this->formatter->formatExpression($multiply);
        $this->assertSql('`test` * :v3', $sql);

        $divide = new ArithmeticExpression($column, $literal, ArithmeticExpression::DIVIDE);
        $sql = $this->formatter->formatExpression($divide);
        $this->assertSql('`test` / :v4', $sql);

        $mod = new ArithmeticExpression($column, $literal, ArithmeticExpression::MOD);
        $sql = $this->formatter->formatExpression($mod);
        $this->assertSql('`test` % :v5', $sql);

        $expr = new ArithmeticExpression(
            $column,
            new ArithmeticExpression($plus, $minus, ArithmeticExpression::MULTIPLY),
            ArithmeticExpression::MOD
        );
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('`test` % ((`test` + :v6) * (`test` - :v7))', $sql);

        $expr = new ArithmeticExpression($column, $multiply, ArithmeticExpression::MULTIPLY);
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql('`test` * `test` * :v8', $sql);
    }

    public function testConditionExpression()
    {
        $expr = new ConditionExpression(greater('col1', column('col2')));
        $expected = $this->formatter->formatCondition($expr->condition);
        $sql = $this->formatter->formatExpression($expr);
        $this->assertSql($expected, $sql);
    }

    public function testExprCondition()
    {
        $condition = new ExprCondition(new ColumnExpression('test'));
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test`', $sql);
    }

    public function testCompareCondition()
    {
        $condition = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(123), CompareCondition::EQUALS);
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` = :v1', $sql);

        $condition = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(123), CompareCondition::GREATER);
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` > :v2', $sql);

        $condition = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(123), CompareCondition::GREATER_EQUALS);
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` >= :v3', $sql);

        $condition = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(123), CompareCondition::LESS);
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` < :v4', $sql);

        $condition = new CompareCondition(new ColumnExpression('test'), new LiteralExpression(123), CompareCondition::LESS_EQUALS);
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` <= :v5', $sql);
    }

    public function testLikeCondition()
    {
        $condition = new LikeCondition(new ColumnExpression('test'), new LiteralExpression(123));
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` LIKE :v1', $sql);
    }

    public function testInCondition()
    {
        $condition = new InCondition(new ColumnExpression('test'), new LiteralExpression(123), new LiteralExpression('test'));
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` IN (:v1, :v2)', $sql);
    }

    public function testNullCondition()
    {
        $condition = new NullCondition(new ColumnExpression('column'));
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`column` IS NULL', $sql);
    }

    public function testNotCondition()
    {
        $condition = new NotCondition(new ExprCondition(new ColumnExpression('test')));
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('NOT `test`', $sql);

        $condition = new NotCondition(new CompareCondition(new ColumnExpression('test'), new LiteralExpression(123), CompareCondition::EQUALS));
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` != :v1', $sql);

        $condition = new NotCondition(new LikeCondition(new ColumnExpression('test'), new LiteralExpression(123)));
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` NOT LIKE :v2', $sql);

        $condition = new NotCondition(new InCondition(new ColumnExpression('test'), new LiteralExpression(123), new LiteralExpression('test')));
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` NOT IN (:v3, :v4)', $sql);

        $condition = new NotCondition(new NullCondition(new ColumnExpression('test')));
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` IS NOT NULL', $sql);
    }

    public function testAndCondition()
    {
        $condition = new AndCondition(
            new ExprCondition(new ColumnExpression('test')),
            new CompareCondition(new ColumnExpression('column'), new LiteralExpression(123), CompareCondition::EQUALS)
        );
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('`test` AND `column` = :v1', $sql);
    }

    public function testOrCondition()
    {
        $condition = new OrCondition(
            new ExprCondition(new ColumnExpression('test')),
            new CompareCondition(new ColumnExpression('column'), new LiteralExpression(123), CompareCondition::EQUALS)
        );
        $sql = $this->formatter->formatCondition($condition);
        $this->assertSql('(`test` OR `column` = :v1)', $sql);
    }

    public function testSelect()
    {
        $st = new Select();
        $sql = $this->formatter->format($st, $params);
        $this->assertEmpty($params);
        $this->assertSql('SELECT 1;', $sql);

        $st->distinct()->setTable('test as t');
        $expectedTable = 'FROM `test` as `t`';
        $sql = $this->formatter->format($st, $params);
        $this->assertEmpty($params);
        $this->assertSql("SELECT DISTINCT * {$expectedTable};", $sql);

        $st->setColumns(['t.column', 'literal' => 123, 'func' => func('FUN', 'col2'), 'condition' => isNull('col3')]);
        $expectedColumns = '`t`.`column`, :v1 as `literal`, FUN(`col2`) as `func`, (`col3` IS NULL) as `condition`';
        $expectedParams = ['v1' => 123];
        $sql = $this->formatter->format($st, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql("SELECT DISTINCT {$expectedColumns} {$expectedTable};", $sql);

        $st->setColumn(select('test2', ['test2.col']), 'alias');
        $expected = "SELECT DISTINCT {$expectedColumns}, (SELECT `test2`.`col` FROM `test2`) AS `alias` {$expectedTable}";
        $sql = $this->formatter->format($st, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql($expected.';', $sql);

        $st->join('test3 as t3', Join::INNER)->eq('t.col', column('t3.col'))->end();
        $st->join('test4 as t4')->eq('t4.sort', 1)->end();
        $expected .= ' INNER JOIN `test3` AS `t3` ON (`t`.`col` = `t3`.`col`) JOIN `test4` AS `t4` ON (`t4`.`sort` = :v2)';
        $expectedParams['v2'] = 1;
        $sql = $this->formatter->format($st, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql($expected.';', $sql);

        $st->where()->true('enabled')->greaterEquals('expire', func('NOW'))->end();
        $expected .= ' WHERE `enabled` AND `expire` >= NOW()';
        $sql = $this->formatter->format($st, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql($expected.';', $sql);

        $st->groupBy('groupCol', 'groupCol2')->having()->eq('cat', 2)->end();
        $expected .= ' GROUP BY `groupCol`, `groupCol2` HAVING (`cat` = :v3)';
        $expectedParams['v3'] = 2;
        $sql = $this->formatter->format($st, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql($expected.';', $sql);

        $st->orderBy('orderCol', OrderBy::DESC)->orderBy('orderCol2');
        $expected .= ' ORDER BY `orderCol` DESC, `orderCol2` ASC';
        $sql = $this->formatter->format($st, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql($expected.';', $sql);

        $st->limit(5)->offset(3);
        $expected .= ' LIMIT 5 OFFSET 3';
        $sql = $this->formatter->format($st, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql($expected.';', $sql);

        $select = new Select($st);
        $sql = $this->formatter->format($select, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql("SELECT * FROM ({$expected}) as `s1`;", $sql);
    }

    public function testUpdate()
    {
        $st = new Update(new Table('test', 't'), ['t.col' => 't2.col', 'col2' => select('test3', ['col3']), 'col4' => 123]);
        $expectedTable = '`test` AS `t`';
        $expectedColumns = '`t`.`col` = `t2`.`col`, `col2` = (SELECT `col3` FROM `test3`), `col4` = :v1';
        $expectedParams = ['v1' => 123];
        $sql = $this->formatter->format($st, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql("UPDATE {$expectedTable} SET {$expectedColumns};", $sql);

        $st->join('test2 as t2', Join::LEFT)->eq('t.id', column('t2.id'))->end();
        $expected = "UPDATE {$expectedTable} LEFT JOIN `test2` as `t2` ON (`t`.`id` = `t2`.`id`) SET {$expectedColumns}";
        $sql = $this->formatter->format($st, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql($expected.';', $sql);

        $st->where()->eq('t.id', 456)->true('enabled')->end();
        $expected .= ' WHERE `t`.`id` = :v2 AND `enabled`';
        $expectedParams['v2'] = 456;
        $sql = $this->formatter->format($st, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql($expected.';', $sql);
    }

    public function testUpdateException()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new Update());
    }

    public function testUpdateException2()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new Update('test'));
    }

    public function testInsertAndReplace()
    {
        $values = [[123, 'test'], [456, 'str']];
        $expectedValues = '(:v1, :v2), (:v3, :v4)';
        $expectedParams = ['v1' => 123, 'v2' => 'test', 'v3' => 456, 'v4' => 'str'];

        $insert = new Insert('table', [], $values);
        $sql = $this->formatter->format($insert, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql("INSERT INTO `table` VALUES {$expectedValues};", $sql);

        $replace = new Replace('table', [], $values);
        $sql = $this->formatter->format($replace);
        $this->assertSql("REPLACE INTO `table` VALUES {$expectedValues};", $sql);

        $fields = ['col1', 'col2'];
        $expectedFields = '`col1`, `col2`';

        $insert->setFields($fields);
        $sql = $this->formatter->format($insert);
        $this->assertSql("INSERT INTO `table` ({$expectedFields}) VALUES {$expectedValues};", $sql);

        $replace->setFields($fields);
        $sql = $this->formatter->format($replace);
        $this->assertSql("REPLACE INTO `table` ({$expectedFields}) VALUES {$expectedValues};", $sql);

        $insert->ignore();
        $sql = $this->formatter->format($insert);
        $this->assertSql("INSERT IGNORE INTO `table` ({$expectedFields}) VALUES {$expectedValues};", $sql);

        $insert->onDuplicateKeyUpdate(['col1' => plus('col1', 1), 'col2' => func('now')]);
        $expectedParams['v5'] = 1;
        $sql = $this->formatter->format($insert, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql("INSERT INTO `table` ({$expectedFields}) VALUES {$expectedValues} ON DUPLICATE KEY UPDATE `col1` = `col1` + :v5, `col2` = NOW();", $sql);
    }

    public function testInsertException()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new Insert());
    }

    public function testInsertException2()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new Insert('table'));
    }

    public function testInsertException3()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new Insert('table', ['col'], [1, 2]));
    }

    public function testReplaceException()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new Replace());
    }

    public function testReplaceException2()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new Replace('table'));
    }

    public function testReplaceException3()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new Replace('table', ['col'], [1, 2]));
    }

    public function testInsertSelectAndReplaceSelect()
    {
        $select = select('table2', ['type', count_(false, 'id')])->groupBy('type');
        $expectedSelect = trim($this->formatter->format($select), ';');

        $insert = new InsertSelect('table', [], $select);
        $sql = $this->formatter->format($insert);
        $this->assertSql("INSERT INTO `table` {$expectedSelect};", $sql);

        $replace = new ReplaceSelect('table', [], $select);
        $sql = $this->formatter->format($replace);
        $this->assertSql("REPLACE INTO `table` {$expectedSelect};", $sql);

        $fields = ['col1', 'col2'];
        $expectedFields = '`col1`, `col2`';

        $insert->setFields($fields);
        $sql = $this->formatter->format($insert);
        $this->assertSql("INSERT INTO `table` ({$expectedFields}) {$expectedSelect};", $sql);

        $replace->setFields($fields);
        $sql = $this->formatter->format($replace);
        $this->assertSql("REPLACE INTO `table` ({$expectedFields}) {$expectedSelect};", $sql);

        $insert->onDuplicateKeyUpdate(['col1' => plus('col1', 1), 'col2' => func('now')]);
        $expectedParams = ['v1' => 1];
        $sql = $this->formatter->format($insert, $params);
        $this->assertEquals($expectedParams, $params);
        $this->assertSql("INSERT INTO `table` ({$expectedFields}) {$expectedSelect} ON DUPLICATE KEY UPDATE `col1` = `col1` + :v1, `col2` = NOW();", $sql);
    }

    public function testInsertSelectException()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new InsertSelect());
    }

    public function testInsertSelectException2()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new InsertSelect('table'));
    }

    public function testReplaceSelectException()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new ReplaceSelect());
    }

    public function testReplaceSelectException2()
    {
        $this->expectException(QueryFormattingException::class);
        $this->formatter->format(new ReplaceSelect('table'));
    }

    public function testDelete()
    {
        $st = new Delete('table');
        $sql = $this->formatter->format($st);
        $this->assertSql('DELETE FROM `table`;', $sql);

        $st->where()->eq('id', 1)->end();
        $sql = $this->formatter->format($st, $params);
        $this->assertEquals(['v1' => 1], $params);
        $this->assertSql('DELETE FROM `table` WHERE `id` = :v1;', $sql);
    }

    public function testIndents()
    {
        $st = new Select();
        $st->setTable('test')
            ->setColumns([
                'col1',
                'col2' => select('test2', ['col3'])->where()->true('enabled')->end(),
            ])
            ->where()->eq('id', 1)->end()
            ->groupBy('type')->having()->true('active')->end()
        ;

        $sql = $this->formatter->format($st);
        $expected =
<<<'SQL'
SELECT
    `col1`,
    (SELECT
        `col3`
    FROM `test2`
    WHERE
        `enabled`) AS `col2`
FROM `test`
WHERE
    `id` = :v1
GROUP BY `type` HAVING (`active`);
SQL;

        $this->assertEquals(str_replace('    ', "\t", $expected), $sql);
    }

    private function assertSql(string $expected, string $sql)
    {
        list($expected, $sql) = array_map(function ($str) {
            return preg_replace(
                '/(?<![\\w\\s`])\\s+|\\s+(?![\\w\\s`:])/',
                '',
                preg_replace(
                    '/\\s+/',
                    ' ',
                    strtolower($str)
                )
            );
        }, [$expected, $sql]);
        $this->assertEquals($expected, $sql);
    }
}

class TestExpression extends Expression
{
    public $str = '';

    public function __construct(string $str)
    {
        $this->str = $str;
    }
}
