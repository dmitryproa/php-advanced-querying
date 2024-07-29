PHP Advanced Querying
=====================

A PHP library for building and formatting complex SQL queries.
Tested on PHP 7.1 and PHP 7.4.

## Example

```php
$builder = new QueryBuilder();
$select = $builder->select(table("users", "u"))->distinct()->setColumns([
        "id", 
        "registered" => "registrationDate",
        "fullName" => func("CONCAT", "name", " ", "surname"),
        "avatar" => "up.image",
        "status" => func("IF", greater("premiumExpireDate", func("NOW")), literal("VIP"), literal("peasant")),
        "isBanned" => isNotNull("ub.id"),
        "totalPosts" => select("posts", [
                count_()
            ])->where()->eq("u.id", "posts.userId")->end()
    ])->join("user_pictures as up", Join::INNER)->eq("up.id", "u.id")->end()
    ->join("user_banlist as ub", Join::LEFT)->eq("ub.id", "u.id")->end()
    ->where()->true("enabled")->end()
    ->orderBy("totalPosts", OrderBy::DESC)->orderBy("fullName")->limit(10)->offset(1);

$formatter = new MysqlFormatter();
echo $formatter->format($select, $parameters);
//$parameters -> ["v1" => " ", "v2" => "VIP", "v3" => "peasant"]
```

Result:

```sql
SELECT DISTINCT
    `id`,
    `registrationDate` AS `registered`,
    CONCAT(`name`, :v1, `surname`) AS `fullName`,
    `up`.`image` AS `avatar`,
    IF(`premiumExpireDate` > NOW(), :v2, :v3) AS `status`,
    (`ub`.`id` IS NOT NULL) AS `isBanned`,
    (SELECT
        COUNT(*)
    FROM `posts`
    WHERE
        `u`.`id` = `posts`.`userId`) AS `totalPosts`
FROM `users` AS `u`
INNER JOIN `user_pictures` AS `up` ON (`up`.`id` = `u`.`id`)
LEFT JOIN `user_banlist` AS `ub` ON (`ub`.`id` = `u`.`id`)
WHERE
    `enabled`
ORDER BY `totalPosts` DESC, `fullName` ASC
LIMIT 10 OFFSET 1;
```

# Table of contents

<a name="index_block"></a>

* [1. Installation](#block1)
* [2. Syntax](#block2)
    * [2.1 Builder](#block2.1)
    * [2.2 Table](#block2.2)
    * [2.3 Select columns](#block2.3)
    * [2.4 Update values](#block2.4)
    * [2.5 Insert fields](#block2.5)
    * [2.6 Insert values](#block2.6)
    * [2.7 Expressions](#block2.7)
        * [2.7.1 Column expression](#block2.7.1)
        * [2.7.2 Literal expression](#block2.7.2)
        * [2.7.3 Select expression](#block2.7.3)
        * [2.7.4 Function expression](#block2.7.4)
    * [2.8 Statements](#block2.8)
        * [2.8.1 Conditional (WHERE) statements](#block2.8.1)
        * [2.8.2 JOIN statements](#block2.8.2)
        * [2.8.3 SELECT statement](#block2.8.3)
        * [2.8.4 UPDATE statement](#block2.8.4)
        * [2.8.5 INSERT and REPLACE statements](#block2.8.5)
        * [2.8.6 INSERT... SELECT and REPLACE... SELECT statements](#block2.8.6)


<a name="block1"></a>

# 1. Installation [↑](#index_block)

The recommended way to install this library is through Composer. Run the following command to install it:
`composer require dmitryproa/php-advanced-querying`

<a name="block2"></a>

# 2. Syntax [↑](#index_block)

<a name="block2.1"></a>

## 2.1 Builder [↑](#index_block)

Class `QueryBuilder` provides the following methods for building a statement:
```php
->select($table = null, $columns = []) //SELECT statement
->update($table = null, $values = []) //UPDATE statement
->insert($table = null, $fields = [], $values = []) //INSERT INTO... VALUES statement
->replace($table = null, $fields = [], $values = []) //REPLACE INTO... VALUES statement
->insertSelect($table = null, $select = null) //INSERT INTO... SELECT statement
->replaceSelect($table = null, $select = null) //REPLACE INTO... SELECT statement
```

<a name="block2.2"></a>

## 2.2 Table [↑](#index_block)

`Table` specified as follows:
```php
table($name) //-> `$name`
table($name, $alias) //-> `$name` as `$alias`
"name" => //-> same as table("name")
"name as alias" => //-> same as table("name", "alias"), case-insensitive
```

<a name="block2.3"></a>

## 2.3 Select columns [↑](#index_block)

Select columns are pairs of alias (optional) and expression, and defined as associative array:
```php
["alias" => $expr, $expr2, ...]
```

<a name="block2.4"></a>

## 2.4 Update values [↑](#index_block)

Update values are pairs of a column and an expression, and defined the same way as columns:
```php
["column" => $expr, "table.column" => $expr2, ...]
```

<a name="block2.5"></a>

## 2.5 Insert fields [↑](#index_block)

Insert fields are plain strings:
```php
["field1", "field2"]
```

<a name="block2.6"></a>

## 2.6 Insert values [↑](#index_block)

One- or two-dimensional array of literals:
```php
[1, "string", null]
[
    [1, "a"],
    [2, "b"]
]
```

<a name="block2.7"></a>

## 2.7 Expressions [↑](#index_block)

<a name="block2.7.1"></a>

### 2.7.1 Column expression [↑](#index_block)

```php
column($name) //-> `$column`
column($name, $table) => //-> `$table`.`$column`
"name" //-> same as column("name")
"table.name" //-> same as column("name", "table")
```

<a name="block2.7.2"></a>

### 2.7.2 Literal expression [↑](#index_block)

```php
literal($value) //will be translated to the PDO parameter (:v1, :v2 etc.)
123 //same as literal(123)
null //same as literal(null)
"," //same as literal(","), if not matches the column format
```

<a name="block2.7.3"></a>

### 2.7.3 Select expression [↑](#index_block)

```php
select($table = null, $columns = []) //-> (SELECT ...)
select()->setTable(...)->setColumns(...)->where(...)
```

<a name="block2.7.4"></a>

### 2.7.4 Function expression [↑](#index_block)
```php
func($name, ...$args)//-> $name($arg1, $arg2, ...)
func("CONCAT", "column1", ":", "table.column2") //-> CONCAT(`column1`, :v1, `table`.`column2`)
```
There are several pre-defined functions:
```php
count_($distinct = false, ...$columns) //COUNT() function
count_() //-> COUNT(*)
count_(false, "column1", "column2") //-> COUNT(`column1`, `column2`)
count_(true, "column1", "column2") //-> COUNT(DISTINCT `column1`, `column2`)

groupconcat($expression, $distinct = false, $separator = ",") //GROUP_CONCAT() function
groupconcat("column") //-> GROUP_CONCAT(`column`)
groupconcat("column", true, ";") //-> GROUP_CONCAT(DISTINCT `column` SEPARATOR :v1)

cast($expression, $type) //CAST($expression AS $type)
cast("column", CastExpression::SIGNED) //-> CAST(`column` AS SIGNED)

over($function, $partitionExpr = null) //$function OVER ([PARTITION BY $partitionExpr])
over(...)->orderBy($expr, $direction = OrderBy::ASC)->orderBy(...) //$function OVER (... ORDER BY $expr, ...)
over("row_number") //-> ROW_NUMBER() OVER()
over("row_number", "column")->orderBy("orderColumn", OrderBy::DESC) //-> ROW_NUMBER() OVER (PARTITION BY `column` ORDER BY `orderColumn` DESC)
over(func("first_value", "valueColumn"), "column") //-> FIRST_VALUE(`valueColumn`) OVER (PARTITION BY `column`)
```

<a name="block2.8"></a>

## 2.8 Statements [↑](#index_block)

Every statement have a `setTable($table)` method.

<a name="block2.8.1"></a>

### 2.8.1 Conditional (WHERE) statements [↑](#index_block)

Some statements (such as SELECT, UPDATE and DELETE) can specify WHERE conditions:
```php
$statement->where()->...conditions...->end()

->true($expr) //-> $expr
->false($expr) //-> NOT $expr

->eq($expr1, $expr2) //-> $expr1 = $expr2
->notEq($expr1, $expr2) //-> $expr1 != $expr2
->greater($expr1, $expr2) //-> $expr1 > $expr2
->greaterEquals($expr1, $expr2) //-> $expr1 >= $expr2
->less($expr1, $expr2) //-> $expr1 < $expr2
->lessEquals($expr1, $expr2) //-> $expr1 <= $expr2
->like($expr1, $expr2) //-> $expr1 LIKE $expr2
->notLike($expr1, $expr2) //-> $expr1 NOT LIKE $expr2

//!!! $expr2 is treated as literal, unless Expression is passed


->isNull($expr) //-> $expr IS NULL
->isNotNull($expr) //-> $expr IS NOT NULL
->in($expr, ...$literals) //-> $expr IN ($literal1, $literal2, ...)
->notIn($expr, ...$literals) //-> $expr NOT IN ($literal1, $literal2, ...)
->and(...$conditions) //-> $condition1 AND $condition2 AND...
->or(...$conditions) //-> ($condition1 OR $condition2 OR...)
```

Conditions can also be defined using helper functions: `true()`, `false()`, `eq()`, `notEq()`, `greater()`, `greaterEquals()`, `less()`, `lessEquals()`, `like()`, `notLike()`, `isNull()`, `isNotNull()`, `in()`, `notIn()`, `and_()` and `or_()`.
For example:
```php
$statement()->where()->or(
        eq("column1", "column2"), 
        isNull("column3"), 
        and_(true("column4"), in("column5", 1, 2)))
    ->end();
```
Conditions can be used as expressions:
```php
$select->setColumn(func("IF", greater("column1", "column2"), "column1", null)); // -> SELECT IF(`column1` > `column2`, `column`, NULL) ...
```

<a name="block2.8.2"></a>

### 2.8.2 JOIN statements [↑](#index_block)

`Select` and `Update` statements have a `join()` method:
```php
$statement
    ->join($table, $joinType = Join::OUTER)->...conditions...->end()
    ->join(select(...))
    ->join(table(select(...), $joinTableAlias))
    ->join...
```
Available join types: `Join::OUTER`, `Join::INNER`, `Join::LEFT`, `Join::RIGHT`.

<a name="block2.8.3"></a>

### 2.8.3 SELECT statement [↑](#index_block)

`Select` statement have following methods:
```php
->setColumn($expr, $alias = '')
->setColumns($columns)
->orderBy($expr, $direction = OrderBy::ASC) // avaliable directions: OrderBy::ASC, OrderBy::DESC
->limit($count)
->offset($amount)
```

`Select` statement can use another `Select` as table, for example:
```php
$inner = $builder->select("table", ["type", "count" => count_()])->groupBy("type");
$select = $builder->select($select)->orderBy("type");
// -> SELECT * FROM (SELECT `type`, COUNT(*) as `count` FROM `table` GROUP BY `type`) ORDER BY `type`;
$select = $builder->select(table($select, "selectAlias")); // -> SELECT * FROM (SELECT ...) as `selectAlias`
```

`UNION SELECT` statement can be made by calling an `unionSelect()` functions, which returns a new `SELECT`.
For example:
```php
$select->unionSelect("anotherTable", ["column"], true); //-> SELECT ... UNION ALL SELECT `column` FROM `anotherTable`;
$builder->select(null, ["id" => 123])
    ->unionSelect(null, [456])
    ->unionSelect(null, [589]);
//-> SELECT :v1 as `id` UNION SELECT :v2 UNION SELECT :v3;
```


<a name="block2.8.4"></a>

### 2.8.4 UPDATE statement [↑](#index_block)

`Update` statement have the following methods:
```php
->setValue($field, $value)
->setValues($values)
```

<a name="block2.8.5"></a>

### 2.8.5 INSERT and REPLACE statements [↑](#index_block)

These statements have the following methods:
```php
->setFields($fields) // -> INSERT INTO ($field1, $field2, ...)
->setValues($values) // -> INSERT INTO ... VALUES (...)
```

`INSERT` statement also have the following methods:
```php
->ignore() // -> INSERT IGNORE...
->onDuplicateKeyUpdate($updateValues) // -> INSERT INTO... ON DUPLICATE KEY UPDATE $field1 => $value1, $field2 => $value2...
```

<a name="block2.8.6"></a>

### 2.8.6 INSERT... SELECT and REPLACE... SELECT statements [↑](#index_block)
These statements have the following methods:
```php
->setFields($fields)
->setSelect($select)
```

`InsertSelect` statement also have `ignore()` and `onDuplicateKeyUpdate()` methods.