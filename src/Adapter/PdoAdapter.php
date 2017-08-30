<?php

declare(strict_types=1);

namespace Linio\Component\Database\Adapter;

use Linio\Component\Database\DatabaseManager;
use Linio\Component\Database\Entity\LazyFetch;
use Linio\Component\Database\Exception\DatabaseConnectionException;
use Linio\Component\Database\Exception\DatabaseException;
use Linio\Component\Database\Exception\InvalidQueryException;
use PDO;
use PDOException;
use PDOStatement;

class PdoAdapter implements AdapterInterface
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $driver;

    public function __construct(string $driver, array $options, string $role)
    {
        $this->driver = $driver;
        $this->setPdo($driver, $options);
    }

    /**
     * @throws DatabaseException
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->executeStatement($query, $params);
        try {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        return $rows;
    }

    /**
     * @throws DatabaseException
     */
    public function fetchOne(string $query, array $params = []): array
    {
        $stmt = $this->executeStatement($query, $params);
        try {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        if ($row === false) {
            $row = [];
        }

        return $row;
    }

    /**
     * @throws DatabaseException
     */
    public function fetchValue(string $query, array $params = [])
    {
        $stmt = $this->executeStatement($query, $params);
        try {
            $values = $stmt->fetch(PDO::FETCH_NUM);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        if ($values === false) {
            $values = [];
        }

        $value = null;
        if (isset($values[0])) {
            $value = $values[0];
        }

        return $value;
    }

    /**
     * @throws DatabaseException
     */
    public function fetchKeyPairs(string $query, array $params = []): array
    {
        $stmt = $this->executeStatement($query, $params);
        try {
            $keyPairs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        return $keyPairs;
    }

    /**
     * @throws DatabaseException
     */
    public function fetchColumn(string $query, array $params = [], int $columnIndex = 0): array
    {
        $stmt = $this->executeStatement($query, $params);
        try {
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, $columnIndex);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        return $rows;
    }

    /**
     * @throws DatabaseException
     */
    public function fetchLazy(string $query, array $params = []): LazyFetch
    {
        $stmt = $this->executeStatement($query, $params);

        return new LazyFetch($stmt);
    }

    /**
     * @throws DatabaseException
     */
    public function execute(string $query, array $params = []): int
    {
        if (empty($params)) {
            try {
                return $this->pdo->exec($query);
            } catch (PDOException $e) {
                throw new InvalidQueryException($e->getMessage(), 0, $e);
            }
        }

        $stmt = $this->executeStatement($query, $params);

        return $stmt->rowCount();
    }

    /**
     * @throws InvalidQueryException
     */
    protected function executeStatement(string $query, array $params): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
        } catch (PDOException $e) {
            throw new InvalidQueryException($e->getMessage(), 0, $e);
        }

        return $stmt;
    }

    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    public function commit()
    {
        $this->pdo->commit();
    }

    public function rollBack()
    {
        $this->pdo->rollBack();
    }

    public function getLastInsertId(string $name = null)
    {
        return $this->pdo->lastInsertId($name);
    }

    public function escapeValue(string $value): string
    {
        switch ($this->driver) {
            case DatabaseManager::DRIVER_MYSQL:
                return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $value);
            default:
                throw new \RuntimeException('Method not yet implemented for this database');
        }
    }

    /**
     * @throws DatabaseConnectionException
     */
    protected function setPdo(string $driver, array $options)
    {
        $this->validateAdapterOptions($driver, $options);
        switch ($driver) {
            case DatabaseManager::DRIVER_MYSQL:
                $this->setMySqlConnection($options);
                break;

            case DatabaseManager::DRIVER_PGSQL:
                $this->setPgSqlConnection($options);
                break;

            case DatabaseManager::DRIVER_SQLITE:
                $this->setSqliteConnection($options);
                break;

            case DatabaseManager::DRIVER_SQLSRV:
                $this->setSqlServerConnection($options);
                break;

            default:
                throw new DatabaseConnectionException('Unknown PDO Driver: ' . $driver);
        }
    }

    protected function validateAdapterOptions(string $driver, array $options)
    {
        switch ($driver) {
            case DatabaseManager::DRIVER_MYSQL:
            case DatabaseManager::DRIVER_PGSQL:
            case DatabaseManager::DRIVER_SQLSRV:
                $this->validateStandardDatabaseOptions($options);
                break;

            case DatabaseManager::DRIVER_SQLITE:
                $this->validateSqliteOptions($options);
                break;
        }
    }

    protected function setMySqlConnection(array $options)
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $options['host'], $options['port'], $options['dbname']);
        $mySqlOptions = [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ];

        $this->createPdoConnection($dsn, $options, $mySqlOptions);
    }

    protected function setPgSqlConnection(array $options)
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s',
            $options['host'],
            $options['port'],
            $options['dbname'],
            $options['username'],
            $options['password']
        );

        $this->createPdoConnection($dsn, $options);
    }

    protected function setSqliteConnection(array $options)
    {
        $dsn = sprintf('sqlite:%s', $options['filepath']);

        $this->createPdoConnection($dsn);
    }

    protected function setSqlServerConnection(array $options)
    {
        $dsn = sprintf('sqlsrv:Server=%s,%s;Database=%s', $options['host'], $options['port'], $options['dbname']);

        $this->createPdoConnection($dsn, $options);
    }

    protected function validateStandardDatabaseOptions(array $options)
    {
        if (!isset($options['host'])) {
            throw new DatabaseConnectionException('Missing configuration parameter: host');
        }
        if (!isset($options['port'])) {
            throw new DatabaseConnectionException('Missing configuration parameter: port');
        }
        if (!isset($options['dbname'])) {
            throw new DatabaseConnectionException('Missing configuration parameter: dbname');
        }
        if (!isset($options['username'])) {
            throw new DatabaseConnectionException('Missing configuration parameter: username');
        }
        if (!isset($options['password'])) {
            throw new DatabaseConnectionException('Missing configuration parameter: password');
        }
    }

    protected function validateSqliteOptions(array $options)
    {
        if (!isset($options['filepath'])) {
            throw new DatabaseConnectionException('Missing configuration parameter: password');
        }
    }

    protected function createPdoConnection(string $dsn, array $options = [], array $driverOptions = [])
    {
        $defaultPdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $driverOptions = $defaultPdoOptions + $driverOptions;

        if (isset($options['pdo_attributes']) && is_array($options['pdo_attributes'])) {
            $driverOptions = $options['pdo_attributes'] + $driverOptions;
        }

        try {
            if (isset($options['username']) && isset($options['password'])) {
                $this->pdo = new PDO($dsn, $options['username'], $options['password'], $driverOptions);
            } else {
                $this->pdo = new PDO($dsn, null, null, $driverOptions);
            }
        } catch (PDOException $e) {
            throw new DatabaseConnectionException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
