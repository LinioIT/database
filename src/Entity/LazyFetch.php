<?php

declare(strict_types=1);

namespace Linio\Component\Database\Entity;

use Linio\Component\Database\Exception\FetchException;
use PDOStatement;

class LazyFetch
{
    /**
     * @var PDOStatement
     */
    protected $pdoStatement;

    public function __construct(PDOStatement $pdoStatement)
    {
        $this->pdoStatement = $pdoStatement;
    }

    /**
     * @throws FetchException
     */
    public function fetch(): array
    {
        try {
            $row = $this->pdoStatement->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new FetchException($e->getMessage(), $e->getCode(), $e);
        }

        if ($row === false) {
            $row = [];
        }

        return $row;
    }
}
