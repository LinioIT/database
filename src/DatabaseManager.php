<?php

declare(strict_types=1);

namespace Linio\Component\Database;

use Linio\Component\Database\Adapter\AdapterInterface;
use Linio\Component\Database\Entity\Connection;
use Linio\Component\Database\Entity\LazyFetch;
use Linio\Component\Database\Entity\SlaveConnectionCollection;
use Linio\Component\Database\Exception\DatabaseConnectionException;
use Linio\Component\Database\Exception\DatabaseException;
use Linio\Component\Database\Exception\FetchException;
use Linio\Component\Database\Exception\InvalidQueryException;
use Linio\Component\Database\Exception\TransactionException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DatabaseManager
{
    // Drivers
    const DRIVER_MYSQL = 'mysql';
    const DRIVER_PGSQL = 'pgsql';
    const DRIVER_SQLITE = 'sqlite';
    const DRIVER_SQLSRV = 'sqlsrv';

    // Roles
    const ROLE_MASTER = 'master';
    const ROLE_SLAVE = 'slave';

    /**
     * @var Connection
     */
    protected $masterConnection;

    /**
     * @var SlaveConnectionCollection
     */
    protected $slaveConnections;

    /**
     * @var array
     */
    protected $adapterOptions = [];

    /**
     * @var bool
     */
    protected $safeMode;

    /**
     * @var bool
     */
    protected $hasActiveTransaction = false;

    /**
     * @var bool
     */
    protected $hasUsedWriteAdapter = false;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct($safeMode = true)
    {
        $this->setAdapterOptions();
        $this->slaveConnections = new SlaveConnectionCollection();
        $this->safeMode = $safeMode;
        $this->logger = new NullLogger();
    }

    public function addConnection(string $driver, array $options, string $role = self::ROLE_MASTER, int $weight = 1): bool
    {
        $this->checkValidRole($role);
        $this->checkValidDriver($driver);
        $connection = $this->createConnection($driver, $options, $role, $weight);

        if ($role == self::ROLE_MASTER) {
            $this->masterConnection = $connection;
        } else {
            $this->slaveConnections->add($connection);
        }

        return true;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return Connection[]
     */
    public function getConnections(): array
    {
        return [
            self::ROLE_MASTER => $this->masterConnection,
            self::ROLE_SLAVE => $this->slaveConnections->toArray(),
        ];
    }

    /**
     * @throws InvalidQueryException
     * @throws FetchException
     */
    public function fetchAll(string $query, array $params = [], bool $forceMasterConnection = false): array
    {
        try {
            return $this->getReadAdapter($forceMasterConnection)->fetchAll($query, $params);
        } catch (DatabaseException $exception) {
            $this->logQueryException($exception, $query, $params);

            throw $exception;
        }
    }

    /**
     * @throws InvalidQueryException
     * @throws FetchException
     */
    public function fetchOne(string $query, array $params = [], bool $forceMasterConnection = false): array
    {
        try {
            return $this->getReadAdapter($forceMasterConnection)->fetchOne($query, $params);
        } catch (DatabaseException $exception) {
            $this->logQueryException($exception, $query, $params);

            throw $exception;
        }
    }

    /**
     * @throws InvalidQueryException
     * @throws FetchException
     */
    public function fetchValue(string $query, array $params = [], bool $forceMasterConnection = false)
    {
        try {
            return $this->getReadAdapter($forceMasterConnection)->fetchValue($query, $params);
        } catch (DatabaseException $exception) {
            $this->logQueryException($exception, $query, $params);

            throw $exception;
        }
    }

    /**
     * @throws InvalidQueryException
     * @throws FetchException
     */
    public function fetchKeyPairs(string $query, array $params = [], bool $forceMasterConnection = false): array
    {
        try {
            return $this->getReadAdapter($forceMasterConnection)->fetchKeyPairs($query, $params);
        } catch (DatabaseException $exception) {
            $this->logQueryException($exception, $query, $params);

            throw $exception;
        }
    }

    /**
     * @throws InvalidQueryException
     * @throws FetchException
     */
    public function fetchColumn(string $query, array $params = [], int $columnIndex = 0, bool $forceMasterConnection = false): array
    {
        try {
            return $this->getReadAdapter($forceMasterConnection)->fetchColumn($query, $params, $columnIndex);
        } catch (DatabaseException $exception) {
            $this->logQueryException($exception, $query, $params);

            throw $exception;
        }
    }

    /**
     * @throws InvalidQueryException
     */
    public function fetchLazy(string $query, array $params = [], bool $forceMasterConnection = false): LazyFetch
    {
        try {
            return $this->getReadAdapter($forceMasterConnection)->fetchLazy($query, $params);
        } catch (DatabaseException $exception) {
            $this->logQueryException($exception, $query, $params);

            throw $exception;
        }
    }

    /**
     * @throws InvalidQueryException
     */
    public function execute(string $query, array $params = []): int
    {
        try {
            return $this->getWriteAdapter()->execute($query, $params);
        } catch (DatabaseException $exception) {
            $this->logQueryException($exception, $query, $params);

            throw $exception;
        }
    }

    /**
     * @throws TransactionException
     */
    public function beginTransaction(): bool
    {
        if ($this->hasActiveTransaction) {
            return false;
        }

        $this->hasActiveTransaction = true;
        try {
            $this->getWriteAdapter()->beginTransaction();
        } catch (DatabaseException $exception) {
            $this->logException($exception);
        }

        return true;
    }

    /**
     * @throws TransactionException
     */
    public function commit(): bool
    {
        if (!$this->hasActiveTransaction) {
            return false;
        }

        try {
            $this->getWriteAdapter()->commit();
        } catch (DatabaseException $exception) {
            $this->logException($exception);
        }

        $this->hasActiveTransaction = false;

        return true;
    }

    /**
     * @throws TransactionException
     */
    public function rollBack(): bool
    {
        if (!$this->hasActiveTransaction) {
            return false;
        }

        try {
            $this->getWriteAdapter()->rollBack();
        } catch (DatabaseException $exception) {
            $this->logException($exception);
        }

        $this->hasActiveTransaction = false;

        return true;
    }

    /**
     * @throws DatabaseException
     */
    public function getLastInsertId(string $name = null): string
    {
        try {
            return $this->getWriteAdapter()->getLastInsertId($name);
        } catch (DatabaseException $exception) {
            $this->logException($exception);
        }
    }

    /**
     * @throws DatabaseException
     */
    public function escapeValue(string $value): string
    {
        try {
            return $this->getWriteAdapter()->escapeValue($value);
        } catch (DatabaseException $exception) {
            $this->logException($exception);
        }
    }

    /**
     * @throws DatabaseException
     */
    public function escapeValues(array $values): array
    {
        $escapedValues = [];

        foreach ($values as $key => $value) {
            $escapedValues[$key] = $this->escapeValue($value);
        }

        return $escapedValues;
    }

    protected function setAdapterOptions()
    {
        $this->adapterOptions = [
            self::DRIVER_MYSQL => 'PdoAdapter',
            self::DRIVER_PGSQL => 'PdoAdapter',
            self::DRIVER_SQLITE => 'PdoAdapter',
            self::DRIVER_SQLSRV => 'PdoAdapter',
        ];
    }

    protected function createAdapter(string $driver, array $options, string $role = self::ROLE_MASTER): AdapterInterface
    {
        $driverAdapterClass = sprintf('%s\\Adapter\\%s', __NAMESPACE__, $this->adapterOptions[$driver]);

        return new $driverAdapterClass($driver, $options, $role);
    }

    /**
     * @throws DatabaseConnectionException
     */
    protected function checkMasterExists()
    {
        if ($this->masterConnection) {
            throw new DatabaseConnectionException('Invalid role: only one master connection is allowed');
        }
    }

    protected function createConnection(string $driver, array $options = [], string $role = self::ROLE_MASTER, int $weight = 1): Connection
    {
        $connection = new Connection();
        $connection->setDriver($driver);
        $connection->setOptions($options);
        $connection->setRole($role);
        $connection->setWeight($weight);
        $connection->setAdapter($this->createAdapter($driver, $options, $role));

        return $connection;
    }

    /**
     * @throws DatabaseConnectionException
     */
    protected function checkValidDriver(string $driver)
    {
        if (!in_array($driver, [self::DRIVER_MYSQL, self::DRIVER_PGSQL, self::DRIVER_SQLITE, self::DRIVER_SQLSRV])) {
            throw new DatabaseConnectionException('Invalid driver: ' . $driver);
        }
    }

    /**
     * @throws DatabaseConnectionException
     */
    protected function checkValidRole(string $role)
    {
        if ($role == self::ROLE_MASTER) {
            $this->checkMasterExists();
        } elseif ($role != self::ROLE_SLAVE) {
            throw new DatabaseConnectionException('Invalid role: ' . $role);
        }
    }

    protected function getReadAdapter(bool $forceMasterConnection): AdapterInterface
    {
        if ($this->safeMode && $this->hasUsedWriteAdapter) {
            return $this->getWriteAdapter();
        }

        if ($forceMasterConnection || $this->hasActiveTransaction || $this->slaveConnections->isEmpty()) {
            return $this->getWriteAdapter();
        }

        return $this->slaveConnections->getAdapter();
    }

    protected function getWriteAdapter(): AdapterInterface
    {
        $this->hasUsedWriteAdapter = true;

        return $this->masterConnection->getAdapter();
    }

    protected function logQueryException(DatabaseException $exception, string $query, array $params)
    {
        $message = sprintf('A database exception occurred [%s]', $exception->getMessage());

        $this->logger->critical($message, [
            'exception' => $exception,
            'query' => $query,
            'parameters' => $params,
        ]);
    }

    protected function logException(DatabaseException $exception)
    {
        $message = sprintf('A database exception occurred [%s]', $exception->getMessage());

        $this->logger->critical($message, [
            'exception' => $exception,
        ]);
    }
}
