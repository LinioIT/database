<?php

declare(strict_types=1);

namespace Linio\Component\Database\Adapter;

use Linio\Component\Database\Entity\LazyFetch;
use Linio\Component\Database\Exception\DatabaseConnectionException;
use Linio\Component\Database\Exception\DatabaseException;
use Linio\Component\Database\Exception\InvalidQueryException;

interface AdapterInterface
{
    /**
     * @throws DatabaseConnectionException
     */
    public function __construct(string $driver, array $options, string $role);

    /**
     * @throws InvalidQueryException
     * @throws DatabaseException
     */
    public function fetchAll(string $query, array $params = []): array;

    /**
     * @throws InvalidQueryException
     * @throws DatabaseException
     */
    public function fetchOne(string $query, array $params = []): array;

    /**
     * @throws InvalidQueryException
     * @throws DatabaseException
     */
    public function fetchValue(string $query, array $params = []);

    /**
     * @throws InvalidQueryException
     * @throws DatabaseException
     */
    public function fetchKeyPairs(string $query, array $params = []): array;

    /**
     * @throws InvalidQueryException
     * @throws DatabaseException
     */
    public function fetchColumn(string $query, array $params = [], int $columnIndex = 0): array;

    /**
     * @throws InvalidQueryException
     */
    public function fetchLazy(string $query, array $params = []): LazyFetch;

    /**
     * @throws InvalidQueryException
     * @throws DatabaseException
     */
    public function execute(string $query, array $params = []): int;

    public function beginTransaction();

    public function commit();

    public function rollBack();

    public function getLastInsertId(string $name = null);

    public function escapeValue(string $value);
}
