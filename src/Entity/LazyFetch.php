<?php

declare(strict_types=1);

namespace Linio\Component\Database\Entity;

use Linio\Component\Database\Exception\FetchException;
use PDOException;
use PDOStatement;

class LazyFetch
{
    protected PDOStatement $pdoStatement;

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
        } catch (PDOException $exception) {
            throw new FetchException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if ($row === false) {
            $row = [];
        }

        return $row;
    }
}
