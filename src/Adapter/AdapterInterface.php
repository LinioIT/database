<?php

namespace Linio\Component\Database\Adapter;

use Linio\Component\Database\Entity\LazyFetch;

interface AdapterInterface
{
    /**
     * @param string $driver
     * @param array $options
     * @param string $role
     */
    public function __construct($driver, array $options, $role);

    /**
     * @param string $query
     * @param array $params
     *
     * @return array
     */
    public function fetchAll($query, array $params = []);

    /**
     * @param string $query
     * @param array $params
     *
     * @return array
     */
    public function fetchOne($query, array $params = []);

    /**
     * @param string $query
     * @param array $params
     *
     * @return string
     */
    public function fetchValue($query, array $params = []);

    /**
     * @param string $query
     * @param array $params
     *
     * @return array
     */
    public function fetchKeyPairs($query, array $params = []);

    /**
     * @param string $query
     * @param array $params
     * @param int $columnIndex
     *
     * @return array
     */
    public function fetchColumn($query, array $params = [], $columnIndex = 0);

    /**
     * @param string $query
     * @param array $params
     *
     * @return LazyFetch
     */
    public function fetchLazy($query, array $params = []);

    /**
     * @param string $query
     * @param array $params
     *
     * @return int
     */
    public function execute($query, array $params = []);
}
