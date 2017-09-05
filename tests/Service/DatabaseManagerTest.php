<?php

declare(strict_types=1);

namespace Linio\Component\Database;

use Linio\Component\Database\Entity\Connection;
use Linio\Component\Database\Exception\DatabaseConnectionException;
use Linio\Component\Database\Exception\DatabaseException;
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
    public function testIsConstructingService(): void
    {
        $actual = new DatabaseManager();

        $this->assertInstanceOf(DatabaseManager::class, $actual);
    }

    public function testIsAddingNewConnection(): void
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

    public function testIsThrowingExceptionWhenAddingMasterConnectionTwice(): void
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

    public function testIsThrowingExceptionWhenAddingInvalidDatabaseDriver(): void
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

    public function testIsThrowingExceptionWhenAddingInvalidDatabaseRole(): void
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

    public function testIsGettingConnections(): void
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

    public function testIsCreatingAndCommitingTransaction(): void
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

    public function testIsCreatingAndCommitingTransactionUsingExecuteTransaction(): void
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

        $callable = function (DatabaseManager $databaseManager): string {
            return $databaseManager->fetchValue('SELECT 1');
        };

        $this->assertEquals('1', $db->executeTransaction($callable));
    }

    public function testIsCreatingAndRollingBackTransaction(): void
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

    public function testIsCreatingAndRollingBackTransactionUsingExecuteTransaction(): void
    {
        $this->expectException(DatabaseException::class);

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

        $db->execute('CREATE TABLE testing_rollback (id int(11))');

        $callable = function (DatabaseManager $databaseManager): void {
            $databaseManager->execute('INSERT INTO testing_rollback VALUES (1)');
            $databaseManager->execute('WRONG SQL');
        };

        $db->executeTransaction($callable);

        $this->assertEmpty($db->fetchValue('SELECT id FROM testing_rollback'));
    }

    public function testIsNotCreatingNestedTransactions(): void
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
