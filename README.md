# Linio Database
[![Latest Stable Version](https://poser.pugx.org/linio/database/v/stable.svg)](https://packagist.org/packages/linio/database) [![License](https://poser.pugx.org/linio/database/license.svg)](https://packagist.org/packages/linio/database) [![Build Status](https://secure.travis-ci.org/LinioIT/database.png)](http://travis-ci.org/LinioIT/database) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/LinioIT/database/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/LinioIT/database/?branch=master)

Linio Database is a component of the Linio Framework. It aims to
abstract database access by wrapping PDO and providing helper methods to speed up development.

## Install


The recommended way to install Linio Database is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "linio/database": "dev-master"
    }
}
```

## Tests

To run the test suite, you need install the dependencies via composer, then
run PHPUnit.

    $ composer install
    $ phpunit

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
		];
		$db->addConnection(DatabaseManager::DRIVER_MYSQL, $driverOptions);

		return $db;
	}

	$rows = $container['db']->fetchAll("SELECT * FROM `table` WHERE `field` = :value", ['value' => 'test']);

```

## Roles

For performance reasons, you might want to use slave databases for read queries while sending write queries to the master database. When creating a connection, you can specify the connection role: `ROLE_MASTER` or `ROLE_SLAVE`. Only one master connection is allowed.

You can have multiple slave connections. The `weight` parameter is used to balance the queries among the slaves. Database servers capable of handling more load should have higher `weight` paramaters.

## Methods

### `addConnection`

```php
<?php

    /**
     * @param string $driver
     * @param array $options
     * @param string $role
     * @param int $weight
     *
     * @return bool
     */
    public function addConnection($driver, array $options, $role = self::ROLE_MASTER, $weight = 1);

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
    public function getConnections();

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

    /**
     * @param string $query
     * @param array $params
     *
     * @return array
     */
    public function fetchAll($query, array $params = []);

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

    /**
     * @param string $query
     * @param array $params
     *
     * @return array
     */
    public function fetchOne($query, array $params = []);

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

    /**
     * @param string $query
     * @param array $params
     *
     * @return string
     */
    public function fetchValue($query, array $params = []);

    $name = $db->fetchValue("SELECT `name` FROM `table` WHERE `id` = :id", ['id' => 1]);

	var_dump($row);

	/*
	string(6) "name 1"
	*/

```

### `fetchKeyPairs`

```php
<?php

     /**
     * @param string $query
     * @param array $params
     *
     * @return array
     */
    public function fetchKeyPairs($query, array $params = []);

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

    /**
     * @param string $query
     * @param array $params
     * @param int $columnIndex
     *
     * @return array
     */
    public function fetchColumn($query, array $params = [], $columnIndex = 0);

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

    /**
     * @param string $query
     * @param array $params
     *
     * @return LazyFetch
     */
    public function fetchLazy($query, array $params = []);

    $lazyFetch = $db->fetchLazy("SELECT `id`,`name` FROM `table` WHERE `id` > ?", [1]);

	while ($row = $lazyFetch->fetch()) {
		$name = $row['name'];
	}

```

In this example, when this `while` loop reached the end of the result set, the `fetch()` method will return an empty array.

### `execute`

```php
<?php

    /**
     * @param string $query
     * @param array $params
     *
     * @return int
     */
    public function execute($query, array $params = []);

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

### `Linio\Component\Database\Exception\DatabaseException`

Reasons:

- Lost connection to the database after creating the statement

## Drivers

### `DatabaseManager::MYSQL`

Adapter options:

- `host`
- `port`
- `dbname`
- `username`
- `password`

----------

### `DatabaseManager::PGSQL`

Adapter options:

- `host`
- `port`
- `dbname`
- `username`
- `password`


----------

### `DatabaseManager::SQLITE`

Adapter options:

- `filepath`

----------

### `DatabaseManager::SQLSRV`

Adapter options:

- `host`
- `port`
- `dbname`
- `username`
- `password`

----------
