<?php

declare(strict_types=1);

namespace Linio\Component\Database\Query;

class Builder
{
    public function placeholders(string $paramName, array $values): array
    {
        $placeholders = [];

        foreach ($values as $v => $value) {
            $name = sprintf(':%s_%d', ltrim($paramName, ':'), $v);
            $placeholders[$name] = $value;
        }

        return $placeholders;
    }
}
