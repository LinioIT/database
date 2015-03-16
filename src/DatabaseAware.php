<?php

namespace Linio\Component\Database;

trait DatabaseAware
{
    /**
     * @var DatabaseManager
     */
    protected $database;

    /**
     * @return DatabaseManager
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param DatabaseManager $database
     */
    public function setDatabase(DatabaseManager $database)
    {
        $this->database = $database;
    }
}
