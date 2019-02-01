<?php

declare(strict_types=1);

namespace Linio\Component\Database\Query;

use Linio\Component\Database\Exception\InvalidQueryException;

interface Transformer
{
    /**
     * @throws InvalidQueryException
     */
    public function execute(string &$query, array &$params = []): void;
}
