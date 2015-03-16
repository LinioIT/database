<?php

namespace Linio\Component\Database\Entity;

use Linio\Component\Database\Exception\DatabaseException;

class LazyFetch
{
    /**
     * @var \PDOStatement
     */
    protected $pdoStatement;

    /**
     * @param \PDOStatement $pdoStatement
     */
    public function __construct(\PDOStatement $pdoStatement)
    {
        $this->pdoStatement = $pdoStatement;
    }

    /**
     * @return array
     */
    public function fetch()
    {
        try {
            $row = $this->pdoStatement->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        if ($row === false) {
            $row = [];
        }

        return $row;
    }
}
