<?php

namespace Linio\Component\Database;

use Linio\Component\Database\Adapter\AdapterInterface;
use Linio\Component\Database\Entity\Connection;
use Linio\Component\Database\Entity\LazyFetch;
use Linio\Component\Database\Entity\SlaveConnectionCollection;
use Linio\Component\Database\Exception\DatabaseConnectionException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
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
    protected $adapterOptions;

    /**
     * @var bool
     */
    protected $hasActiveTransaction = false;

    public function __construct()
    {
        $this->setAdapterOptions();
        $this->slaveConnections = new SlaveConnectionCollection();
    }

    /**
     * @param string $driver
     * @param array  $options
     * @param string $role
     * @param int    $weight
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @return bool
     */
    public function addConnection($driver, array $options, $role = self::ROLE_MASTER, $weight = 1)
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
    public function getConnections()
    {
        return [
            self::ROLE_MASTER => $this->masterConnection,
            self::ROLE_SLAVE => $this->slaveConnections->toArray(),
        ];
    }

    /**
     * @param string $query
     * @param array  $params
     *
     * @return array
     */
    public function fetchAll($query, array $params = [])
    {
        return $this->getReadAdapter()
            ->fetchAll($query, $params);
    }

    /**
     * @param string $query
     * @param array  $params
     *
     * @return array
     */
    public function fetchOne($query, array $params = [])
    {
        return $this->getReadAdapter()
            ->fetchOne($query, $params);
    }

    /**
     * @param string $query
     * @param array  $params
     *
     * @return string
     */
    public function fetchValue($query, array $params = [])
    {
        return $this->getReadAdapter()
            ->fetchValue($query, $params);
    }

    /**
     * @param string $query
     * @param array  $params
     *
     * @return array
     */
    public function fetchKeyPairs($query, array $params = [])
    {
        return $this->getReadAdapter()
            ->fetchKeyPairs($query, $params);
    }

    /**
     * @param string $query
     * @param array  $params
     * @param int    $columnIndex
     *
     * @return array
     */
    public function fetchColumn($query, array $params = [], $columnIndex = 0)
    {
        return $this->getReadAdapter()
            ->fetchColumn($query, $params, $columnIndex);
    }

    /**
     * @param string $query
     * @param array  $params
     *
     * @return LazyFetch
     */
    public function fetchLazy($query, array $params = [])
    {
        return $this->getReadAdapter()
            ->fetchLazy($query, $params);
    }

    /**
     * @param string $query
     * @param array  $params
     *
     * @return int
     */
    public function execute($query, array $params = [])
    {
        return $this->getWriteAdapter()
            ->execute($query, $params);
    }

    /**
     * @return bool
     */
    public function beginTransaction()
    {
        if ($this->hasActiveTransaction) {
            return false;
        }

        $this->hasActiveTransaction = true;
        $this->getWriteAdapter()->beginTransaction();

        return true;
    }

    /**
     * @return bool
     */
    public function commit()
    {
        if (!$this->hasActiveTransaction) {
            return false;
        }

        $this->getWriteAdapter()->commit();
        $this->hasActiveTransaction = false;

        return true;
    }

    /**
     * @return bool
     */
    public function rollBack()
    {
        if (!$this->hasActiveTransaction) {
            return false;
        }

        $this->getWriteAdapter()->rollBack();
        $this->hasActiveTransaction = false;

        return true;
    }

    /**
     * @param string|null $name
     *
     * @return mixed
     */
    public function getLastInsertId($name = null)
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

    /**
     * @param string $driver
     * @param array  $options
     * @param string $role
     *
     * @return AdapterInterface
     */
    protected function createAdapter($driver, array $options, $role = self::ROLE_MASTER)
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

    /**
     * @param string $driver
     * @param array  $options
     * @param string $role
     * @param int    $weight
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @return Connection
     */
    protected function createConnection($driver, array $options = [], $role = self::ROLE_MASTER, $weight = 1)
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
     * @param string $driver
     *
     * @throws DatabaseConnectionException
     */
    protected function checkValidDriver($driver)
    {
        if (!in_array($driver, [self::DRIVER_MYSQL, self::DRIVER_PGSQL, self::DRIVER_SQLITE, self::DRIVER_SQLSRV])) {
            throw new DatabaseConnectionException('Invalid driver: ' . $driver);
        }
    }

    /**
     * @param string $role
     *
     * @throws DatabaseConnectionException
     */
    protected function checkValidRole($role)
    {
        if ($role == self::ROLE_MASTER) {
            $this->checkMasterExists();
        } elseif ($role != self::ROLE_SLAVE) {
            throw new DatabaseConnectionException('Invalid role: ' . $role);
        }
    }

    /**
     * @return AdapterInterface
     */
    protected function getReadAdapter()
    {
        if ($this->hasActiveTransaction || $this->slaveConnections->isEmpty()) {
            return $this->getWriteAdapter();
        }

        return $this->slaveConnections->getAdapter();
    }

    /**
     * @return AdapterInterface
     */
    protected function getWriteAdapter()
    {
        return $this->masterConnection->getAdapter();
    }
}
