<?php
declare(strict_types=1);

namespace Linio\Component\Database;

trait DatabaseAware
{
    /**
     * @var DatabaseManager
     */
    protected $database;

    public function getDatabase(): DatabaseManager
    {
        return $this->database;
    }

    public function setDatabase(DatabaseManager $database)
    {
        $this->database = $database;
    }
}
