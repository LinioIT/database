<?php

namespace Linio\Component\Database\Adapter;

use Linio\Component\Database\DatabaseManager;

/**
 * @constant TEST_DATABASE_HOST
 * @constant TEST_DATABASE_PORT
 * @constant TEST_DATABASE_DBNAME
 * @constant TEST_DATABASE_USERNAME
 * @constant TEST_DATABASE_PASSWORD
 */
class PdoAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PdoAdapter
     */
    protected $adapter;

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var array
     */
    protected $driverOptions;

    function __construct()
    {
        $this->driverOptions = [
            'host' => TEST_DATABASE_HOST,
            'port' => TEST_DATABASE_PORT,
            'dbname' => TEST_DATABASE_DBNAME,
            'username' => TEST_DATABASE_USERNAME,
            'password' => TEST_DATABASE_PASSWORD,
        ];
        $this->pdo = $this->getPdo();
        $this->createDatabase();
    }

    protected function setUp()
    {
        $this->createDatabaseFixture();
        $this->adapter = new PdoAdapter(DatabaseManager::DRIVER_MYSQL, $this->driverOptions, DatabaseManager::ROLE_MASTER);
    }

    public function testIsConstructing()
    {
        $this->assertInstanceOf('Linio\Component\Database\Adapter\PdoAdapter', $this->adapter);
    }

    public function testIsFetchingAllWithoutParams()
    {
        $actual = $this->adapter->fetchAll("SELECT * FROM `departments` ORDER BY `dept_no`");

        $this->assertInternalType('array', $actual);
        $firstRow = reset($actual);
        $this->assertInternalType('array', $firstRow);
        $this->assertArrayHasKey('dept_id', $firstRow);
        $this->assertArrayHasKey('dept_no', $firstRow);
        $this->assertEquals('d001', $firstRow['dept_no']);
        $this->assertArrayHasKey('dept_name', $firstRow);
        $this->assertEquals('Marketing', $firstRow['dept_name']);
    }

    public function testIsFetchingAllWithNamelessParam()
    {
        $actual = $this->adapter->fetchAll("SELECT * FROM `departments` WHERE `dept_no` = ?", ['d001']);

        $this->assertInternalType('array', $actual);
        $firstRow = reset($actual);
        $this->assertInternalType('array', $firstRow);
        $this->assertArrayHasKey('dept_id', $firstRow);
        $this->assertArrayHasKey('dept_no', $firstRow);
        $this->assertEquals('d001', $firstRow['dept_no']);
        $this->assertArrayHasKey('dept_name', $firstRow);
        $this->assertEquals('Marketing', $firstRow['dept_name']);
    }

    public function testIsFetchingAllWithNamedParam()
    {
        $actual = $this->adapter->fetchAll("SELECT * FROM `departments` WHERE `dept_no` = :dept_no", ['dept_no' => 'd001']);

        $this->assertInternalType('array', $actual);
        $firstRow = reset($actual);
        $this->assertInternalType('array', $firstRow);
        $this->assertArrayHasKey('dept_id', $firstRow);
        $this->assertArrayHasKey('dept_no', $firstRow);
        $this->assertEquals('d001', $firstRow['dept_no']);
        $this->assertArrayHasKey('dept_name', $firstRow);
        $this->assertEquals('Marketing', $firstRow['dept_name']);
    }

    public function testIsFetchingAllWithEmptyResult()
    {
        $actual = $this->adapter->fetchAll("SELECT * FROM `departments` WHERE `dept_no` = :dept_no", ['dept_no' => 'd099']);

        $this->assertInternalType('array', $actual);
        $this->assertEmpty($actual);
    }

    public function testIsFetchingOneWithNamedParam()
    {
        $actual = $this->adapter->fetchOne("SELECT * FROM `departments` WHERE `dept_no` = :dept_no", ['dept_no' => 'd001']);

        $this->assertInternalType('array', $actual);
        $this->assertArrayHasKey('dept_id', $actual);
        $this->assertArrayHasKey('dept_no', $actual);
        $this->assertEquals('d001', $actual['dept_no']);
        $this->assertArrayHasKey('dept_name', $actual);
        $this->assertEquals('Marketing', $actual['dept_name']);
    }

    public function testIsFetchingOneWithEmptyResult()
    {
        $actual = $this->adapter->fetchOne("SELECT * FROM `departments` WHERE `dept_no` = :dept_no", ['dept_no' => 'd099']);

        $this->assertInternalType('array', $actual);
        $this->assertEmpty($actual);
    }

    public function testIsFetchingValueWithNamedParam()
    {
        $actual = $this->adapter->fetchValue("SELECT `dept_name` FROM `departments` WHERE `dept_no` = :dept_no", ['dept_no' => 'd001']);

        $this->assertEquals('Marketing', $actual);
    }

    public function testIsFetchingValueWithEmptyResult()
    {
        $actual = $this->adapter->fetchValue("SELECT `dept_name` FROM `departments` WHERE `dept_no` = :dept_no", ['dept_no' => 'd099']);

        $this->assertNull($actual);
    }

    public function testIsFetchingKeyPairWithNamedParam()
    {
        $actual = $this->adapter->fetchKeyPairs("SELECT `dept_no`,`dept_name` FROM `departments` WHERE `dept_no` = :dept_no", ['dept_no' => 'd001']);

        $this->assertInternalType('array', $actual);
        $this->assertArrayHasKey('d001', $actual);
        $this->assertEquals('Marketing', $actual['d001']);
    }

    public function testIsFetchingKeyPairWithEmptyResult()
    {
        $actual = $this->adapter->fetchKeyPairs("SELECT `dept_no`,`dept_name` FROM `departments` WHERE `dept_no` = :dept_no", ['dept_no' => 'd099']);

        $this->assertInternalType('array', $actual);
        $this->assertEmpty($actual);
    }

    public function testIsFetchingColumnWithIndexZero()
    {
        $actual = $this->adapter->fetchColumn("SELECT `dept_no`,`dept_name` FROM `departments` ORDER BY `dept_no`", [], 0);

        $this->assertInternalType('array', $actual);
        $this->assertCount(9, $actual);
        $firstValue = reset($actual);
        $this->assertEquals('d001', $firstValue);
    }

    public function testIsFetchingColumnWithIndexOne()
    {
        $actual = $this->adapter->fetchColumn("SELECT `dept_no`,`dept_name` FROM `departments` ORDER BY `dept_no`", [], 1);

        $this->assertInternalType('array', $actual);
        $this->assertCount(9, $actual);
        $firstValue = reset($actual);
        $this->assertEquals('Marketing', $firstValue);
    }

    public function testIsFetchingLazy()
    {
        $lazyFetch = $this->adapter->fetchLazy("SELECT * FROM `departments` ORDER BY `dept_no`");

        $this->assertInstanceOf('\Linio\Component\Database\Entity\LazyFetch', $lazyFetch);

        $firstRow = $lazyFetch->fetch();
        $this->assertInternalType('array', $firstRow);
        $this->assertArrayHasKey('dept_id', $firstRow);
        $this->assertArrayHasKey('dept_no', $firstRow);
        $this->assertEquals('d001', $firstRow['dept_no']);
        $this->assertArrayHasKey('dept_name', $firstRow);
        $this->assertEquals('Marketing', $firstRow['dept_name']);

        $secondRow = $lazyFetch->fetch();
        $this->assertInternalType('array', $secondRow);
        $this->assertArrayHasKey('dept_id', $secondRow);
        $this->assertArrayHasKey('dept_no', $secondRow);
        $this->assertEquals('d002', $secondRow['dept_no']);
        $this->assertArrayHasKey('dept_name', $secondRow);
        $this->assertEquals('Finance', $secondRow['dept_name']);

        for ($i = 0; $i < 7; $i++) {
            $lazyFetch->fetch();
        }

        $endOfRows = $lazyFetch->fetch();
        $this->assertInternalType('array', $endOfRows);
        $this->assertEmpty($endOfRows);
    }

    public function testIsExecutingInserts()
    {
        $actual = $this->adapter->execute(
            "INSERT INTO `departments` (`dept_no`, `dept_name`) VALUES (?, ?), (?, ?)",
            ['d010', 'Test Dept 1', 'd011', 'Test Dept 2']
        );

        $this->assertEquals(2, $actual);

        $count = $this->adapter->fetchValue("SELECT COUNT(*) FROM `departments` WHERE `dept_no` IN (?, ?)", ['d010', 'd011']);
        $this->assertEquals(2, $count);
    }

    public function testIsExecutingUpdates()
    {
        $actual = $this->adapter->execute("UPDATE `departments` SET `dept_name` = ?", ['Test Dept']);

        $this->assertEquals(9, $actual);

        $count = $this->adapter->fetchValue("SELECT COUNT(*) FROM `departments` WHERE `dept_name` = ?", ['Test Dept']);
        $this->assertEquals(9, $count);
    }

    public function testIsExecutingUpdatesWithNoMatched()
    {
        $actual = $this->adapter->execute("UPDATE `departments` SET `dept_name` = ? WHERE `dept_no` = ?", ['Test Dept', 'd010']);

        $this->assertEquals(0, $actual);
    }

    /**
     * @expectedException \Linio\Component\Database\Exception\InvalidQueryException
     */
    public function testIsThrowingExceptionWithInvalidQuery()
    {
        $this->adapter->execute("UPDATE `nop` SET `dept_name` = ? WHERE `dept_no` = ?", ['Test Dept', 'd010']);
    }

    public function testIsCreatingandCommitingTransaction()
    {
        $this->assertTrue($this->pdo->beginTransaction());
        $this->assertTrue($this->pdo->commit());
    }

    public function testIsCreatingandRollingBackTransaction()
    {
        $this->assertTrue($this->pdo->beginTransaction());
        $this->assertTrue($this->pdo->rollBack());
    }

    /**
     * @expectedException \PDOException
     */
    public function testIsNotCommitingTransactionWithoutCreating()
    {
        $this->pdo->commit();
    }

    /**
     * @expectedException \PDOException
     */
    public function testIsNotRollingBackTransactionWithoutCreating()
    {
        $this->pdo->rollBack();
    }

    public function testIsGettingLastInsertId()
    {
        $this->adapter->execute("INSERT INTO `departments` (`dept_no`, `dept_name`) VALUES (?, ?)", ['d010', 'Test Dept 110']);

        $actual = $this->adapter->getLastInsertId();

        $this->assertEquals(10, $actual);
    }

    protected function createDatabase()
    {
        $this->pdo->exec(sprintf("CREATE DATABASE IF NOT EXISTS `%s`", $this->driverOptions['dbname']));
        $this->pdo->exec(sprintf("DROP TABLE IF EXISTS `%s`.`departments`", $this->driverOptions['dbname']));
        $this->pdo->exec(
            sprintf(
                "CREATE TABLE IF NOT EXISTS `%s`.`departments` (
          `dept_id` int(10) NOT NULL AUTO_INCREMENT,
          `dept_no` char(4) NOT NULL,
          `dept_name` varchar(40) NOT NULL,
          PRIMARY KEY (`dept_id`))",
                $this->driverOptions['dbname']
            )
        );
    }

    protected function createDatabaseFixture()
    {
        $this->pdo->exec(sprintf("TRUNCATE TABLE `%s`.`departments`", $this->driverOptions['dbname']));
        $this->pdo->exec(
            sprintf(
                "INSERT INTO `%s`.`departments` (`dept_no`, `dept_name`) VALUES ('d009','Customer Service'),('d005','Development'),('d002','Finance'),
          ('d003','Human Resources'),('d001','Marketing'),('d004','Production'),('d006','Quality Management'),('d008','Research'),('d007','Sales');",
                $this->driverOptions['dbname']
            )
        );
    }

    /**
     * @return \PDO
     */
    protected function getPdo()
    {
        $pdo = new \PDO(
            sprintf('mysql:host=%s;port=%s', $this->driverOptions['host'], $this->driverOptions['port']),
            $this->driverOptions['username'],
            $this->driverOptions['password'],
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]
        );

        return $pdo;
    }
}
