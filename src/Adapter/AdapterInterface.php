<?php
declare(strict_types=1);

namespace Linio\Component\Database\Adapter;

use Linio\Component\Database\Entity\LazyFetch;

interface AdapterInterface
{
    public function __construct(string $driver, array $options, string $role);
    public function fetchAll(string $query, array $params = []): array;
    public function fetchOne(string $query, array $params = []): array;
    public function fetchValue(string $query, array $params = []);
    public function fetchKeyPairs(string $query, array $params = []): array;
    public function fetchColumn(string $query, array $params = [], int $columnIndex = 0): array;
    public function fetchLazy(string $query, array $params = []): LazyFetch;
    public function execute(string $query, array $params = []): int;
    public function beginTransaction();
    public function commit();
    public function rollBack();
    public function getLastInsertId(string $name = null);
    public function escapeValue(string $value);
}
