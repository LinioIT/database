<?php

declare (strict_types = 1);

namespace Linio\Component\Database;

use Linio\Component\Database\Adapter\AdapterInterface;
use Linio\Component\Database\Entity\Connection;
use Linio\Component\Database\Entity\LazyFetch;
use Linio\Component\Database\Entity\SlaveConnectionCollection;
use Linio\Component\Database\Exception\DatabaseConnectionException;

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
    protected $hasActiveTransaction = false;

    public function __construct()
    {
        $this->setAdapterOptions();
        $this->slaveConnections = new SlaveConnectionCollection();
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

    public function fetchAll(string $query, array $params = []): array
    {
        return $this->getReadAdapter()
            ->fetchAll($query, $params);
    }

    public function fetchOne(string $query, array $params = []): array
    {
        return $this->getReadAdapter()
            ->fetchOne($query, $params);
    }

    public function fetchValue(string $query, array $params = [])
    {
        return $this->getReadAdapter()
            ->fetchValue($query, $params);
    }

    public function fetchKeyPairs(string $query, array $params = []): array
    {
        return $this->getReadAdapter()
            ->fetchKeyPairs($query, $params);
    }

    public function fetchColumn(string $query, array $params = [], int $columnIndex = 0): array
    {
        return $this->getReadAdapter()
            ->fetchColumn($query, $params, $columnIndex);
    }

    public function fetchLazy(string $query, array $params = []): LazyFetch
    {
        return $this->getReadAdapter()
            ->fetchLazy($query, $params);
    }

    public function execute(string $query, array $params = []): int
    {
        return $this->getWriteAdapter()
            ->execute($query, $params);
    }

    public function beginTransaction(): bool
    {
        if ($this->hasActiveTransaction) {
            return false;
        }

        $this->hasActiveTransaction = true;
        $this->getWriteAdapter()->beginTransaction();

        return true;
    }

    public function commit(): bool
    {
        if (!$this->hasActiveTransaction) {
            return false;
        }

        $this->getWriteAdapter()->commit();
        $this->hasActiveTransaction = false;

        return true;
    }

    public function rollBack(): bool
    {
        if (!$this->hasActiveTransaction) {
            return false;
        }

        $this->getWriteAdapter()->rollBack();
        $this->hasActiveTransaction = false;

        return true;
    }

    public function getLastInsertId(string $name = null)
    {
        return $this->getWriteAdapter()->getLastInsertId($name);
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

    protected function getReadAdapter(): AdapterInterface
    {
        if ($this->hasActiveTransaction || $this->slaveConnections->isEmpty()) {
            return $this->getWriteAdapter();
        }

        return $this->slaveConnections->getAdapter();
    }

    protected function getWriteAdapter(): AdapterInterface
    {
        return $this->masterConnection->getAdapter();
    }
}
