<?php

declare(strict_types=1);

namespace Linio\Component\Database;

trait DatabaseAware
{
    protected DatabaseManager $database;

    public function getDatabase(): DatabaseManager
    {
        return $this->database;
    }

    public function setDatabase(DatabaseManager $database): void
    {
        $this->database = $database;
    }
}
