<?php

declare(strict_types=1);

namespace Linio\Component\Database\Adapter;

use Linio\Component\Database\Entity\LazyFetch;
use Linio\Component\Database\Exception\DatabaseConnectionException;
use Linio\Component\Database\Exception\DatabaseException;
use Linio\Component\Database\Exception\FetchException;
use Linio\Component\Database\Exception\InvalidQueryException;
use Linio\Component\Database\Exception\TransactionException;

interface AdapterInterface
{
    /**
     * @throws DatabaseConnectionException
     */
    public function __construct(string $driver, array $options, string $role);

    /**
     * @throws InvalidQueryException
     * @throws FetchException
     */
    public function fetchAll(string $query, array $params = []): array;

    /**
     * @throws InvalidQueryException
     * @throws FetchException
     */
    public function fetchOne(string $query, array $params = []): array;

    /**
     * @throws InvalidQueryException
     * @throws FetchException
     *
     * @return mixed
     */
    public function fetchValue(string $query, array $params = []);

    /**
     * @throws InvalidQueryException
     * @throws FetchException
     */
    public function fetchKeyPairs(string $query, array $params = []): array;

    /**
     * @throws InvalidQueryException
     * @throws FetchException
     */
    public function fetchColumn(string $query, array $params = [], int $columnIndex = 0): array;

    /**
     * @throws InvalidQueryException
     */
    public function fetchLazy(string $query, array $params = []): LazyFetch;

    /**
     * @throws InvalidQueryException
     */
    public function execute(string $query, array $params = []): int;

    /**
     * @throws TransactionException
     */
    public function beginTransaction(): void;

    /**
     * @throws TransactionException
     */
    public function commit(): void;

    /**
     * @throws TransactionException
     */
    public function rollBack(): void;

    /**
     * @throws DatabaseException
     */
    public function getLastInsertId(string $name = null): string;

    /**
     * @throws DatabaseException Support for the database has not been implemented yet
     */
    public function escapeValue(string $value): string;
}
