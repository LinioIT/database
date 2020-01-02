<?php

declare(strict_types=1);

namespace Linio\Component\Database\Entity;

use Linio\Component\Database\Adapter\AdapterInterface;

class SlaveConnections
{
    protected array $connections = [];
    protected bool $isEmpty = true;
    protected bool $hasOnlyOneConnection = true;
    protected array $originalWeights = [];
    protected array $connectionMap = [];
    protected int $totalWeight;

    public function add(Connection $connection): void
    {
        $this->connections[] = $connection;
        $this->originalWeights[] = $connection->getWeight();

        if (count($this->connections) == 1) {
            $this->hasOnlyOneConnection = true;
        } else {
            $this->hasOnlyOneConnection = false;
            $this->updateConnectionMap();
        }

        $this->isEmpty = false;
    }

    public function getAdapter(): AdapterInterface
    {
        if ($this->hasOnlyOneConnection) {
            $connection = $this->connections[0];
        } else {
            $connection = $this->getWeightedRandomConnection();
        }

        return $connection->getAdapter();
    }

    /**
     * @return Connection[]
     */
    public function toArray(): array
    {
        return $this->connections;
    }

    public function isEmpty(): bool
    {
        return $this->isEmpty;
    }

    protected function updateConnectionMap(): void
    {
        $connectionMap = [];
        $totalWeight = 0;
        $connectionIndex = 0;
        $mapIndex = 1;

        foreach ($this->originalWeights as $weight) {
            $totalWeight += $weight;

            for ($i = 0; $i < $weight; $i++) {
                $connectionMap[$mapIndex] = $connectionIndex;
                $mapIndex++;
            }

            $connectionIndex++;
        }

        $this->connectionMap = $connectionMap;
        $this->totalWeight = $totalWeight;
    }

    protected function getWeightedRandomConnection(): Connection
    {
        $rand = random_int(1, $this->totalWeight);

        return $this->connections[$this->connectionMap[$rand]];
    }
}
