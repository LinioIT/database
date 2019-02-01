<?php

declare(strict_types=1);

namespace Linio\Component\Database\Adapter;

use Linio\Component\Database\DatabaseManager;
use Linio\Component\Database\Entity\LazyFetch;
use Linio\Component\Database\Exception\InvalidQueryException;
use Linio\Component\Database\Exception\TransactionException;
use PDO;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * @constant TEST_DATABASE_HOST
 * @constant TEST_DATABASE_PORT
 * @constant TEST_DATABASE_DBNAME
 * @constant TEST_DATABASE_USERNAME
 * @constant TEST_DATABASE_PASSWORD
 */
class PdoAdapterTest extends TestCase
{
    /**
     * @var PdoAdapter
     */
    protected $adapter;

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var array
     */
    protected $driverOptions;

    protected function setUp(): void
    {
        $this->driverOptions = [
            'host' => TEST_DATABASE_HOST,
            'port' => TEST_DATABASE_PORT,
            'dbname' => TEST_DATABASE_DBNAME,
            'username' => TEST_DATABASE_USERNAME,
            'password' => TEST_DATABASE_PASSWORD,
            PdoAdapter::ENABLE_NAMED_ARRAY_VALUES => true,
        ];

        $this->createDatabaseFixture();

        $this->adapter = new PdoAdapter(DatabaseManager::DRIVER_MYSQL, $this->driverOptions, DatabaseManager::ROLE_MASTER);
    }

    public function testIsConstructing(): void
    {
        $this->assertInstanceOf(PdoAdapter::class, $this->adapter);
    }

    public function testIsPdoObjectLazilyInstantiated(): void
    {
        $testAdapter = new PdoAdapter(DatabaseManager::DRIVER_MYSQL, $this->driverOptions, DatabaseManager::ROLE_MASTER);

        $adapterPdo = Assert::readAttribute($testAdapter, 'pdo');
        $this->assertNull($adapterPdo);

        $testAdapter->execute('SELECT 1');

        /** @var PDO $adapterPdo */
        $adapterPdo = Assert::readAttribute($testAdapter, 'pdo');
        $this->assertInstanceOf(PDO::class, $adapterPdo);
    }

    public function testIsSettingPdoDefaultErrorModeAttributeToException(): void
    {
        $testAdapter = new PdoAdapter(DatabaseManager::DRIVER_MYSQL, $this->driverOptions, DatabaseManager::ROLE_MASTER);
        $testAdapter->execute('SELECT 1');

        /** @var PDO $adapterPdo */
        $adapterPdo = Assert::readAttribute($testAdapter, 'pdo');
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $adapterPdo->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function testIsSettingPdoDefaultErrorModeAttributeToExceptionWithoutUsernameAndPassword(): void
    {
        $testAdapter = new PdoAdapter(DatabaseManager::DRIVER_SQLITE, ['filepath' => '/tmp/test-db.sqlite'], DatabaseManager::ROLE_MASTER);
        $testAdapter->execute('SELECT 1');

        /** @var PDO $adapterPdo */
        $adapterPdo = Assert::readAttribute($testAdapter, 'pdo');
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $adapterPdo->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function testIsSettingPdoAttributes(): void
    {
        $testOptions = [
            'pdo_attributes' => [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
            ],
        ];

        $driverOptions = $this->driverOptions + $testOptions;

        $testAdapter = new PdoAdapter(DatabaseManager::DRIVER_MYSQL, $driverOptions, DatabaseManager::ROLE_MASTER);
        $testAdapter->execute('SELECT 1');

        /** @var PDO $adapterPdo */
        $adapterPdo = Assert::readAttribute($testAdapter, 'pdo');
        $this->assertTrue($adapterPdo->getAttribute(PDO::ATTR_PERSISTENT));
        $this->assertEquals(PDO::ERRMODE_WARNING, $adapterPdo->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function testIsFetchingAllWithoutParams(): void
    {
        $actual = $this->adapter->fetchAll('SELECT * FROM `departments` ORDER BY `dept_no`');

        $this->assertInternalType('array', $actual);
        $firstRow = reset($actual);
        $this->assertInternalType('array', $firstRow);
        $this->assertArrayHasKey('dept_id', $firstRow);
        $this->assertArrayHasKey('dept_no', $firstRow);
        $this->assertEquals('d001', $firstRow['dept_no']);
        $this->assertArrayHasKey('dept_name', $firstRow);
        $this->assertEquals('Marketing', $firstRow['dept_name']);
    }

    public function testIsFetchingAllWithNamelessParam(): void
    {
        $actual = $this->adapter->fetchAll('SELECT * FROM `departments` WHERE `dept_no` = ?', ['d001']);

        $this->assertInternalType('array', $actual);
        $firstRow = reset($actual);
        $this->assertInternalType('array', $firstRow);
        $this->assertArrayHasKey('dept_id', $firstRow);
        $this->assertArrayHasKey('dept_no', $firstRow);
        $this->assertEquals('d001', $firstRow['dept_no']);
        $this->assertArrayHasKey('dept_name', $firstRow);
        $this->assertEquals('Marketing', $firstRow['dept_name']);
    }

    public function testIsFetchingAllWithNamedParam(): void
    {
        $actual = $this->adapter->fetchAll('SELECT * FROM `departments` WHERE `dept_no` = :dept_no', ['dept_no' => 'd001']);

        $this->assertInternalType('array', $actual);
        $firstRow = reset($actual);
        $this->assertInternalType('array', $firstRow);
        $this->assertArrayHasKey('dept_id', $firstRow);
        $this->assertArrayHasKey('dept_no', $firstRow);
        $this->assertEquals('d001', $firstRow['dept_no']);
        $this->assertArrayHasKey('dept_name', $firstRow);
        $this->assertEquals('Marketing', $firstRow['dept_name']);
    }

    public function testIsFetchingAllWithEmptyResult(): void
    {
        $actual = $this->adapter->fetchAll('SELECT * FROM `departments` WHERE `dept_no` = :dept_no', ['dept_no' => 'd099']);

        $this->assertInternalType('array', $actual);
        $this->assertEmpty($actual);
    }

    public function testIsFetchingOneWithNamedParam(): void
    {
        $actual = $this->adapter->fetchOne('SELECT * FROM `departments` WHERE `dept_no` = :dept_no', ['dept_no' => 'd001']);

        $this->assertInternalType('array', $actual);
        $this->assertArrayHasKey('dept_id', $actual);
        $this->assertArrayHasKey('dept_no', $actual);
        $this->assertEquals('d001', $actual['dept_no']);
        $this->assertArrayHasKey('dept_name', $actual);
        $this->assertEquals('Marketing', $actual['dept_name']);
    }

    public function testIsFetchingOneWithEmptyResult(): void
    {
        $actual = $this->adapter->fetchOne('SELECT * FROM `departments` WHERE `dept_no` = :dept_no', ['dept_no' => 'd099']);

        $this->assertInternalType('array', $actual);
        $this->assertEmpty($actual);
    }

    public function testIsFetchingValueWithNamedParam(): void
    {
        $actual = $this->adapter->fetchValue('SELECT `dept_name` FROM `departments` WHERE `dept_no` = :dept_no', ['dept_no' => 'd001']);

        $this->assertEquals('Marketing', $actual);
    }

    public function testIsFetchingValueWithNamedArrayParamUsingColonPrefix(): void
    {
        $actual = $this->adapter->fetchAll('SELECT `dept_name` FROM `departments` WHERE `dept_no` IN (:dept_no) ORDER BY dept_no', [
            ':dept_no' => [
                'd001', // Marketing
                'd004', // Production
            ],
        ]);

        $this->assertEquals('Marketing', $actual[0]['dept_name']);

        $this->assertEquals('Production', $actual[1]['dept_name']);
    }

    public function testIsFetchingValueWithEmptyResult(): void
    {
        $actual = $this->adapter->fetchValue('SELECT `dept_name` FROM `departments` WHERE `dept_no` = :dept_no', ['dept_no' => 'd099']);

        $this->assertNull($actual);
    }

    public function testIsFetchingKeyPairWithNamedParam(): void
    {
        $actual = $this->adapter->fetchKeyPairs('SELECT `dept_no`,`dept_name` FROM `departments` WHERE `dept_no` = :dept_no', ['dept_no' => 'd001']);

        $this->assertInternalType('array', $actual);
        $this->assertArrayHasKey('d001', $actual);
        $this->assertEquals('Marketing', $actual['d001']);
    }

    public function testIsFetchingKeyPairWithEmptyResult(): void
    {
        $actual = $this->adapter->fetchKeyPairs('SELECT `dept_no`,`dept_name` FROM `departments` WHERE `dept_no` = :dept_no', ['dept_no' => 'd099']);

        $this->assertInternalType('array', $actual);
        $this->assertEmpty($actual);
    }

    public function testIsFetchingColumnWithIndexZero(): void
    {
        $actual = $this->adapter->fetchColumn('SELECT `dept_no`,`dept_name` FROM `departments` ORDER BY `dept_no`', [], 0);

        $this->assertInternalType('array', $actual);
        $this->assertCount(9, $actual);
        $firstValue = reset($actual);
        $this->assertEquals('d001', $firstValue);
    }

    public function testIsFetchingColumnWithIndexOne(): void
    {
        $actual = $this->adapter->fetchColumn('SELECT `dept_no`,`dept_name` FROM `departments` ORDER BY `dept_no`', [], 1);

        $this->assertInternalType('array', $actual);
        $this->assertCount(9, $actual);
        $firstValue = reset($actual);
        $this->assertEquals('Marketing', $firstValue);
    }

    public function testIsFetchingLazy(): void
    {
        $lazyFetch = $this->adapter->fetchLazy('SELECT * FROM `departments` ORDER BY `dept_no`');

        $this->assertInstanceOf(LazyFetch::class, $lazyFetch);

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

    public function testIsExecutingInserts(): void
    {
        $actual = $this->adapter->execute(
            'INSERT INTO `departments` (`dept_no`, `dept_name`) VALUES (?, ?), (?, ?)',
            ['d010', 'Test Dept 1', 'd011', 'Test Dept 2']
        );

        $this->assertEquals(2, $actual);

        $count = $this->adapter->fetchValue('SELECT COUNT(*) FROM `departments` WHERE `dept_no` IN (?, ?)', ['d010', 'd011']);
        $this->assertEquals(2, $count);
    }

    public function testIsExecutingUpdates(): void
    {
        $actual = $this->adapter->execute('UPDATE `departments` SET `dept_name` = ?', ['Test Dept']);

        $this->assertEquals(9, $actual);

        $count = $this->adapter->fetchValue('SELECT COUNT(*) FROM `departments` WHERE `dept_name` = ?', ['Test Dept']);
        $this->assertEquals(9, $count);
    }

    public function testIsExecutingUpdatesWithNoMatched(): void
    {
        $actual = $this->adapter->execute('UPDATE `departments` SET `dept_name` = ? WHERE `dept_no` = ?', ['Test Dept', 'd010']);

        $this->assertEquals(0, $actual);
    }

    public function testIsExecutingMultipleStatementsWithoutEmulatePrepares(): void
    {
        $driverOptions = $this->driverOptions;
        $driverOptions['pdo_attributes'] = [
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->createDatabaseFixture();
        $this->adapter = new PdoAdapter(DatabaseManager::DRIVER_MYSQL, $driverOptions, DatabaseManager::ROLE_MASTER);

        $actual = $this->adapter->execute("
          DELETE FROM `departments`;
          INSERT INTO `departments` (`dept_no`, `dept_name`) VALUES ('d010', 'Test Dept 1'), ('d011', 'Test Dept 2');
        ");

        $this->assertEquals(9, $actual);
    }

    public function testIsThrowingExceptionWithInvalidQuery(): void
    {
        $this->expectException(InvalidQueryException::class);
        $this->adapter->execute('UPDATE `nop` SET `dept_name` = ? WHERE `dept_no` = ?', ['Test Dept', 'd010']);
    }

    public function testIsNotCommittingTransactionWithoutCreating(): void
    {
        $this->expectException(TransactionException::class);
        $this->adapter->commit();
    }

    public function testIsNotRollingBackTransactionWithoutCreating(): void
    {
        $this->expectException(TransactionException::class);
        $this->adapter->rollBack();
    }

    public function testIsGettingLastInsertId(): void
    {
        $this->adapter->execute('INSERT INTO `departments` (`dept_no`, `dept_name`) VALUES (?, ?)', ['d010', 'Test Dept 110']);

        $actual = $this->adapter->getLastInsertId();

        $this->assertEquals(10, $actual);
    }

    public function testIsEscapingValue(): void
    {
        $value = "test'test\ntest";

        $actual = $this->adapter->escapeValue($value);

        $this->assertEquals('test\\\'test\\ntest', $actual);
    }

    protected function createDatabaseFixture(): void
    {
        $pdo = $this->getPdo();
        $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $this->driverOptions['dbname']));
        $pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`.`departments`', $this->driverOptions['dbname']));
        $pdo->exec(
            sprintf(
                'CREATE TABLE IF NOT EXISTS `%s`.`departments` (
          `dept_id` int(10) NOT NULL AUTO_INCREMENT,
          `dept_no` char(4) NOT NULL,
          `dept_name` varchar(40) NOT NULL,
          PRIMARY KEY (`dept_id`))',
                $this->driverOptions['dbname']
            )
        );
        $pdo->exec(
            sprintf(
                "INSERT INTO `%s`.`departments` (`dept_no`, `dept_name`) VALUES ('d009','Customer Service'),('d005','Development'),('d002','Finance'),
          ('d003','Human Resources'),('d001','Marketing'),('d004','Production'),('d006','Quality Management'),('d008','Research'),('d007','Sales');",
                $this->driverOptions['dbname']
            )
        );
    }

    protected function getPdo(): PDO
    {
        return new PDO(
            sprintf('mysql:host=%s;port=%s', $this->driverOptions['host'], $this->driverOptions['port']),
            $this->driverOptions['username'],
            $this->driverOptions['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
    }
}
