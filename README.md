# Linio Database
[![Latest Stable Version](https://poser.pugx.org/linio/database/v/stable.svg)](https://packagist.org/packages/linio/database) [![License](https://poser.pugx.org/linio/database/license.svg)](https://packagist.org/packages/linio/database) [![Build Status](https://secure.travis-ci.org/LinioIT/database.png)](http://travis-ci.org/LinioIT/database)

Linio Database is a component of the Linio Framework. It aims to
abstract database access by wrapping PDO and providing helper methods to speed up development.

## Install

The recommended way to install Linio Database is [through composer](http://getcomposer.org).

```bash
$ composer require linio/database
```

## Tests

To run the test suite, you need install the dependencies via composer, then
run PHPUnit.

    $ composer install
    $ vendor/bin/phpunit

## Usage

```php
<?php

use Linio\Component\Database\DatabaseManager;

$container['db'] = function() {
    $db = new DatabaseManager();
    $driverOptions = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'test_db',
        'username' => 'root',
        'password' => '',
        'pdo_attributes' => [
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ],
    ];
    $db->addConnection(DatabaseManager::DRIVER_MYSQL, $driverOptions);

    return $db;
};

$rows = $container['db']->fetchAll("SELECT * FROM `table` WHERE `field` = :value", ['value' => 'test']);

```

## Roles

For performance reasons, you might want to use slave databases for read queries while sending write queries to the master database. When creating a connection, you can specify the connection role: `ROLE_MASTER` or `ROLE_SLAVE`. Only one master connection is allowed.

You can have multiple slave connections. The `weight` parameter is used to balance the queries among the slaves. Database servers capable of handling more load should have higher `weight` paramaters.

In order to force a read query to use the master connection, use the parameter `forceMasterConnection` when using the `fetch` methods. 


## Safe Mode

When you use read replicas to improve the performance in your database, in a master-slave setup, the replication lag between the instances may cause some issues when you try to read data that you have recently modified.

The safe mode option guarantees that, once you have used the master connection to issue a query, every query from this moment on will use the same connection for reads.

To prevent replication lag issues, this library uses the safe mode by default. To override this behavior, set the `$safeMode` parameter to false when instantiating the `DatabaseManager` object.

```php
<?php

use Linio\Component\Database\DatabaseManager;

$db = new DatabaseManager(false);
```

## Methods

### `addConnection`

```php
<?php

public function addConnection(string $driver, array $options, string $role = DatabaseManager::ROLE_MASTER, int $weight = 1): bool;

$masterDbOptions = ['host' => '127.0.0.1', 'port' => 3306, 'dbname' => 'master_db', 'username' => 'root','password' => ''];
$db->addConnection(DatabaseManager::DRIVER_MYSQL, $masterDbOptions, DatabaseManager::ROLE_MASTER);

$bigSlaveDbOptions = ['host' => '127.0.0.1', 'port' => 3306, 'dbname' => 'big_slave_db', 'username' => 'root','password' => ''];
$db->addConnection(DatabaseManager::DRIVER_MYSQL, $bigSlaveDbOptions, DatabaseManager::ROLE_SLAVE, 5);

$smallSlaveDbOptions = ['host' => '127.0.0.1', 'port' => 3306, 'dbname' => 'small_slave_db', 'username' => 'root','password' => ''];
$db->addConnection(DatabaseManager::DRIVER_MYSQL, $smallSlaveDbOptions, DatabaseManager::ROLE_SLAVE, 2);
```

### `getConnections`

```php
<?php

use Linio\Component\Database\Entity\Connection;

/**
 * @return Connection[]
 */
public function getConnections() : array;

$connections = $db->getConnections();

var_dump($connections);

/*
array(2) {
  'master' =>
  class Linio\Component\Database\Entity\Connection
  'slave' =>
  array(2) {
    [0] =>
    class Linio\Component\Database\Entity\Connection
    [1] =>
    class Linio\Component\Database\Entity\Connection
  }
}
*/

```

### `fetchAll`

```php
<?php

use Linio\Component\Database\Exception\FetchException;
use Linio\Component\Database\Exception\InvalidQueryException;

/**
 * @throws InvalidQueryException
 * @throws FetchException
 */
public function fetchAll(string $query, array $params = [], bool $forceMasterConnection = false): array;

$rows = $db->fetchAll("SELECT `id`,`name` FROM `table` WHERE `id` > ?", [1]);

var_dump($rows);

/*
array(2) {
  [0] =>
  array(2) {
    'id' =>
    string(1) "2"
    'name' =>
    string(6) "name 2"
  }
  [1] =>
  array(2) {
    'id' =>
    string(1) "3"
    'name' =>
    string(6) "name 3"
  }
}
*/

```

### `fetchOne`

```php
<?php

use Linio\Component\Database\Exception\FetchException;
use Linio\Component\Database\Exception\InvalidQueryException;

/**
 * @throws InvalidQueryException
 * @throws FetchException
 */
public function fetchOne(string $query, array $params = [], bool $forceMasterConnection = false): array;

$row = $db->fetchOne("SELECT `id`,`name` FROM `table` WHERE `id` = :id", ['id' => 1]);

var_dump($row);

/*
array(2) {
    'id' =>
    string(1) "1"
    'name' =>
    string(6) "name 1"
}
*/


```

### `fetchValue`

```php
<?php

use Linio\Component\Database\Exception\FetchException;
use Linio\Component\Database\Exception\InvalidQueryException;

/**
 * @throws InvalidQueryException
 * @throws FetchException
 */
public function fetchValue(string $query, array $params = [], bool $forceMasterConnection = false)

$name = $db->fetchValue("SELECT `name` FROM `table` WHERE `id` = :id", ['id' => 1]);

var_dump($row);

/*
string(6) "name 1"
*/

```

### `fetchKeyPairs`

```php
<?php

use Linio\Component\Database\Exception\FetchException;
use Linio\Component\Database\Exception\InvalidQueryException;

/**
 * @throws InvalidQueryException
 * @throws FetchException
 */
public function fetchKeyPairs(string $query, array $params = [], bool $forceMasterConnection = false): array;

$keyPairs = $db->fetchKeyPairs("SELECT `id`,`name` FROM `table` WHERE `id` > :id", ['id' => 1]);

var_dump($keyPairs);

/*
array(2) {
    '2' =>
    string(6) "name 2"
    '3' =>
    string(6) "name 3"
}
*/

```

### `fetchColumn`

```php
<?php

use Linio\Component\Database\Exception\FetchException;
use Linio\Component\Database\Exception\InvalidQueryException;

/**
 * @throws InvalidQueryException
 * @throws FetchException
 */
public function fetchColumn(string $query, array $params = [], int $columnIndex = 0, bool $forceMasterConnection = false): array;

$names = $db->fetchColumn("SELECT `id`,`name` FROM `table` WHERE `id` > :id", ['id' => 1], 1);

var_dump($names);

/*
array(2) {
    [0] =>
    string(6) "name 2"
    [1] =>
    string(6) "name 3"
}
*/

```

### `fetchLazy`

```php
<?php

use Linio\Component\Database\Entity\LazyFetch;
use Linio\Component\Database\Exception\InvalidQueryException;

/**
 * @throws InvalidQueryException
 */
public function fetchLazy(string $query, array $params = [], bool $forceMasterConnection = false): LazyFetch;

$lazyFetch = $db->fetchLazy("SELECT `id`,`name` FROM `table` WHERE `id` > ?", [1]);

while ($row = $lazyFetch->fetch()) {
    $name = $row['name'];
}

```

In this example, when this `while` loop reached the end of the result set, the `fetch()` method will return an empty array.

### `execute`

```php
<?php

use Linio\Component\Database\Exception\InvalidQueryException;

/**
 * @throws InvalidQueryException
 */
public function execute(string $query, array $params = []): int;

$affectedRowsInsert = $db->execute("INSERT INTO `table` VALUES(:id, :name)", ['id' => 10, 'name' => 'test_name']);

var_dump($affectedRowsInsert);

/*
int(1)
*/

$affectedRowsUpdate = $db->execute("UPDATE `table` SET `name` = :name", ['name' => 'test_name']);

var_dump($affectedRowsUpdate);

/*
int(3)
*/


```

### `escapeValue`

```php
<?php

use Linio\Component\Database\Exception\DatabaseException;

/**
 * @throws DatabaseException
 */
public function escapeValue(string $value): string;

$escapedValue = $db->escapeValue('Linio\'s Library');

var_dump($escapedValue);

/*
string(17) "Linio\\'s Library"
*/


```

### `escapeValues`

```php
<?php

use Linio\Component\Database\Exception\DatabaseException;

/**
 * @throws DatabaseException
 */
public function escapeValues(array $values): array;

$escapedValues = $db->escapeValues(['Linio\'s Library', 'Linio\'s Library']);

var_dump($escapedValues);

/*
 * 
array(2) {                    
  [0]=>                       
  string(17) "Linio\\'s Library"
  [1]=>                       
  string(17) "Linio\\'s Library"
}                             
*/


```

## Exceptions

### `Linio\Component\Database\Exception\DatabaseConnectionException`

Reasons:

- Invalid driver name
- Invalid connections parameters
- Error when trying to establish a connection to the database

### `Linio\Component\Database\Exception\InvalidQueryException`

Reasons:

- Lost connection to the database before creating the statement
- Malformed SQL query
- Wrong table or field names

### `Linio\Component\Database\Exception\FetchException`

Reasons:

- Lost connection to the database after creating the statement

### `Linio\Component\Database\Exception\TransactionException`

Reasons:

- Failure to begin, commit or rollback a transaction

### `Linio\Component\Database\Exception\DatabaseException`

Reasons:

- All exceptions extend from this
- Non-specific errors

## Drivers

### `DatabaseManager::MYSQL`

Adapter options:

- `host` string
- `port` int
- `dbname` string
- `username` string
- `password` string
- `pdo_attributes` array *optional*

----------

### `DatabaseManager::PGSQL`

Adapter options:

- `host` string
- `port` int
- `dbname` string
- `username` string
- `password` string
- `pdo_attributes` array *optional*


----------

### `DatabaseManager::SQLITE`

Adapter options:

- `filepath`

----------

### `DatabaseManager::SQLSRV`

Adapter options:

- `host` string
- `port` int
- `dbname` string
- `username` string
- `password` string
- `pdo_attributes` array *optional*

----------
