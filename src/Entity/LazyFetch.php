<?php

declare(strict_types=1);

namespace Linio\Component\Database\Entity;

use Linio\Component\Database\Exception\FetchException;
use PDO;
use PDOException;
use PDOStatement;

class LazyFetch
{
    public function __construct(protected PDOStatement $pdoStatement)
    {
    }

    /**
     * @throws FetchException
     */
    public function fetch(): array
    {
        try {
            $row = $this->pdoStatement->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new FetchException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        if ($row === false) {
            $row = [];
        }

        return $row;
    }
}
