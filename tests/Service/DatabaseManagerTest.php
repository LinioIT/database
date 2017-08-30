<?php

declare(strict_types=1);

namespace Linio\Component\Database;

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

        $this->assertInstanceOf('Linio\Component\Database\DatabaseManager', $actual);
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

    /**
     * @expectedException \Linio\Component\Database\Exception\DatabaseConnectionException
     */
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

        $actual = $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_MASTER);
        $actual = $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, DatabaseManager::ROLE_MASTER);
    }

    /**
     * @expectedException \Linio\Component\Database\Exception\DatabaseConnectionException
     */
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

        $actual = $db->addConnection('nop', $connectionOptions, DatabaseManager::ROLE_SLAVE);
    }

    /**
     * @expectedException \Linio\Component\Database\Exception\DatabaseConnectionException
     */
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

        $actual = $db->addConnection(DatabaseManager::DRIVER_MYSQL, $connectionOptions, 'nop');
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
        $this->assertInstanceOf('\Linio\Component\Database\Entity\Connection', $actual[DatabaseManager::ROLE_MASTER]);
    }

    public function testIsCreatingandCommitingTransaction()
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

    public function testIsCreatingandRollingBackTransaction()
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
