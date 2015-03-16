<?php

namespace Linio\Component\Database\Adapter;

use Linio\Component\Database\DatabaseManager;
use Linio\Component\Database\Entity\LazyFetch;
use Linio\Component\Database\Exception\DatabaseConnectionException;
use Linio\Component\Database\Exception\DatabaseException;
use Linio\Component\Database\Exception\DatabaseStatementException;
use Linio\Component\Database\Exception\InvalidQueryException;
use Linio\Component\Database\Exception\InvalidQueryStatementException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PdoAdapter implements AdapterInterface
{
    /**
     * @var \PDO
     */
    protected $pdo;

    // @codingStandardsIgnoreStart
    /**
     * @param string $driver ;
     * @param array $options
     * @param string $role
     */
    public function __construct($driver, array $options, $role)
    {
        $this->setPdo($driver, $options);
    }
    // @codingStandardsIgnoreEnd

    /**
     * @param string $query
     * @param array $params
     *
     * @throws DatabaseException
     *
     * @return array
     */
    public function fetchAll($query, array $params = [])
    {
        $stmt = $this->executeStatement($query, $params);
        try {
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        return $rows;
    }

    /**
     * @param string $query
     * @param array $params
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @throws DatabaseException
     * @return array
     */
    public function fetchOne($query, array $params = [])
    {
        $stmt = $this->executeStatement($query, $params);
        try {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        if ($row === false) {
            $row = [];
        }

        return $row;
    }

    /**
     * @param string $query
     * @param array $params
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @throws DatabaseException
     * @return string
     */
    public function fetchValue($query, array $params = [])
    {
        $stmt = $this->executeStatement($query, $params);
        try {
            $values = $stmt->fetch(\PDO::FETCH_NUM);
        } catch (\PDOException $e) {
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
     * @param string $query
     * @param array $params
     *
     * @throws DatabaseException
     *
     * @return array
     */
    public function fetchKeyPairs($query, array $params = [])
    {
        $stmt = $this->executeStatement($query, $params);
        try {
            $keyPairs = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        return $keyPairs;
    }

    /**
     * @param string $query
     * @param array $params
     * @param int $columnIndex
     *
     * @throws DatabaseException
     *
     * @return array
     */
    public function fetchColumn($query, array $params = [], $columnIndex = 0)
    {
        $stmt = $this->executeStatement($query, $params);
        try {
            $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN, $columnIndex);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        return $rows;
    }

    /**
     * @param string $query
     * @param array $params
     *
     * @throws DatabaseException
     *
     * @return LazyFetch
     */
    public function fetchLazy($query, array $params = [])
    {
        $stmt = $this->executeStatement($query, $params);

        return new LazyFetch($stmt);
    }

    /**
     * @param string $query
     * @param array $params
     *
     * @throws DatabaseException
     *
     * @return int
     */
    public function execute($query, array $params = [])
    {
        $stmt = $this->executeStatement($query, $params);

        return $stmt->rowCount();
    }

    /**
     * @param string $query
     * @param array $params
     *
     * @throws InvalidQueryException
     *
     * @return \PDOStatement
     */
    protected function executeStatement($query, array $params)
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
        } catch (\PDOException $e) {
            throw new InvalidQueryException($e->getMessage(), 0, $e);
        }

        return $stmt;
    }

    /**
     * @param string $driver
     * @param array $options
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @throws DatabaseConnectionException
     */
    protected function setPdo($driver, array $options)
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

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @param string $driver
     * @param array $options
     */
    protected function validateAdapterOptions($driver, array $options)
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

    /**
     * @param array $options
     */
    protected function setMySqlConnection(array $options)
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $options['host'], $options['port'], $options['dbname']);
        $mySqlOptions = [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ];

        $this->createPdoConnection($dsn, $options, $mySqlOptions);
    }

    /**
     * @param array $options
     */
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

    /**
     * @param array $options
     */
    protected function setSqliteConnection(array $options)
    {
        $dsn = sprintf('sqlite:%s', $options['filepath']);

        $this->createPdoConnection($dsn);
    }

    /**
     * @param array $options
     */
    protected function setSqlServerConnection(array $options)
    {
        $dsn = sprintf('sqlsrv:Server=%s,%s;Database=%s', $options['host'], $options['port'], $options['dbname']);

        $this->createPdoConnection($dsn, $options);
    }

    /**
     * @param array $options
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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

    /**
     * @param array $options
     */
    protected function validateSqliteOptions(array $options)
    {
        if (!isset($options['filepath'])) {
            throw new DatabaseConnectionException('Missing configuration parameter: password');
        }
    }

    /**
     * @param string $dsn
     * @param array $options
     */
    protected function createPdoConnection($dsn, array $options = [], $driverOptions = [])
    {
        try {
            if (isset($options['username']) && isset($options['password'])) {
                $this->pdo = new \PDO($dsn, $options['username'], $options['password'], $driverOptions);
            } else {
                $this->pdo = new \PDO($dsn);
            }
        } catch (\PDOException $e) {
            throw new DatabaseConnectionException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
