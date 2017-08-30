<?php

declare(strict_types=1);

namespace Linio\Component\Database;

use Linio\Component\Database\Entity\Connection;
use Linio\Component\Database\Exception\DatabaseConnectionException;
use PHPUnit\Framework\TestCase;

/**
 * @constant TEST_DATABASE_HOST
 * @constant TEST_DATABASE_PORT
 * @constant TEST_DATABASE_DBNAME
 * @constant TEST_DATABASE_USERNAME
 * @constant TEST_DATABASE_PASSWORD
 */
class DatabaseManagerTest extends TestCase
{
    public function testIsConstructingService()
    {
        $actual = new DatabaseManager();

        $this->assertInstanceOf(DatabaseManager::class, $actual);
    }

    public function testIsAddingNewConnection()
    {
        $db = new DatabaseManager();
        $connectionOptions = [
            'host' => TEST_DATABASE_HOST,
            'port' => TEST_DATABASE_PORT,
            'dbname' => TEST_DATABASE_DBNAME,
            'username' => TEST_DATABASE_USERNAME,
            'password' => TEST_DATABASE_PASSWORD,
        ];
        $actual = $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_MASTER);

        $this->assertTrue($actual);
    }

    public function testIsThrowingExceptionWhenAddingMasterConnectionTwice()
    {
        $db = new DatabaseManager();
        $connectionOptions = [
            'host' => TEST_DATABASE_HOST,
            'port' => TEST_DATABASE_PORT,
            'dbname' => TEST_DATABASE_DBNAME,
            'username' => TEST_DATABASE_USERNAME,
            'password' => TEST_DATABASE_PASSWORD,
        ];

        $this->expectException(DatabaseConnectionException::class);

        $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_MASTER);
        $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_MASTER);
    }

    public function testIsThrowingExceptionWhenAddingInvalidDatabaseDriver()
    {
        $db = new DatabaseManager();
        $connectionOptions = [
            'host' => TEST_DATABASE_HOST,
            'port' => TEST_DATABASE_PORT,
            'dbname' => TEST_DATABASE_DBNAME,
            'username' => 'nop',
            'password' => 'nop',
        ];

        $this->expectException(DatabaseConnectionException::class);

        $db->addConnection('nop', $connectionOptions, DatabaseManager::ROLE_SLAVE);
    }

    public function testIsThrowingExceptionWhenAddingInvalidDatabaseRole()
    {
        $db = new DatabaseManager();
        $connectionOptions = [
            'host' => TEST_DATABASE_HOST,
            'port' => TEST_DATABASE_PORT,
            'dbname' => TEST_DATABASE_DBNAME,
            'username' => 'nop',
            'password' => 'nop',
        ];

        $this->expectException(DatabaseConnectionException::class);

        $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, 'nop');
    }

    public function testIsGettingConnections()
    {
        $db = new DatabaseManager();
        $connectionOptions = [
            'host' => TEST_DATABASE_HOST,
            'port' => TEST_DATABASE_PORT,
            'dbname' => TEST_DATABASE_DBNAME,
            'username' => TEST_DATABASE_USERNAME,
            'password' => TEST_DATABASE_PASSWORD,
        ];
        $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_MASTER);

        $actual = $db->getConnections(DatabaseManager::ROLE_MASTER);

        $this->assertInternalType('array', $actual);
        $this->assertInstanceOf(Connection::class, $actual[DatabaseManager::ROLE_MASTER]);
    }

    public function testIsCreatingAndCommitingTransaction()
    {
        $db = new DatabaseManager();
        $connectionOptions = [
            'host' => TEST_DATABASE_HOST,
            'port' => TEST_DATABASE_PORT,
            'dbname' => TEST_DATABASE_DBNAME,
            'username' => TEST_DATABASE_USERNAME,
            'password' => TEST_DATABASE_PASSWORD,
        ];
        $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_MASTER);
        $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_SLAVE);

        $this->assertTrue($db->beginTransaction());
        $this->assertTrue($db->commit());
    }

    public function testIsCreatingAndRollingBackTransaction()
    {
        $db = new DatabaseManager();
        $connectionOptions = [
            'host' => TEST_DATABASE_HOST,
            'port' => TEST_DATABASE_PORT,
            'dbname' => TEST_DATABASE_DBNAME,
            'username' => TEST_DATABASE_USERNAME,
            'password' => TEST_DATABASE_PASSWORD,
        ];
        $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_MASTER);
        $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_SLAVE);

        $this->assertTrue($db->beginTransaction());
        $this->assertTrue($db->rollBack());
    }

    public function testIsNotCreatingNestedTransactions()
    {
        $db = new DatabaseManager();
        $connectionOptions = [
            'host' => TEST_DATABASE_HOST,
            'port' => TEST_DATABASE_PORT,
            'dbname' => TEST_DATABASE_DBNAME,
            'username' => TEST_DATABASE_USERNAME,
            'password' => TEST_DATABASE_PASSWORD,
        ];
        $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_MASTER);
        $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_SLAVE);

        $this->assertTrue($db->beginTransaction());
        $this->assertFalse($db->beginTransaction());
        $this->assertTrue($db->commit());
    }
}
