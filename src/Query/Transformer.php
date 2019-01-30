<?php

declare(strict_types=1);

namespace Linio\Component\Database\Query;

interface Transformer
{
    public function execute(string &$query, array &$params = []): void;
}
