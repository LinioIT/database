<?php

declare(strict_types=1);

namespace Linio\Component\Database\Query\Transformer;

use Linio\Component\Database\Exception\InvalidQueryException;
use Linio\Component\Database\Query\Transformer;

class NamedArrayParameter implements Transformer
{
    public function execute(string &$query, array &$params = []): void
    {
        foreach ($params as $paramKey => $paramValue) {
            $isKeyNumeric = is_numeric($paramKey);
            $isArray = is_array($paramValue);

            if ($isKeyNumeric && $isArray) {
                throw new InvalidQueryException(sprintf('Unnamed parameter "%s" can\'t have array value.', $paramKey));
            }

            if ($isKeyNumeric || !$isArray) {
                continue;
            }

            $placeholders = $this->placeholders($paramKey, $paramValue);
            $params += $placeholders;

            unset($params[$paramKey]);

            $paramNames = array_keys($placeholders);

            $startsWithColon = $paramKey[0] === ':';

            $paramNameToReplace = $startsWithColon ? $paramKey : ':' . $paramKey;

            $query = str_replace($paramNameToReplace, implode(', ', $paramNames), $query);
        }
    }

    protected function placeholders(string $paramName, array $values): array
    {
        $placeholders = [];

        $counter = 0;

        foreach ($values as $value) {
            $name = sprintf(':%s_%d', ltrim($paramName, ':'), $counter++);
            $placeholders[$name] = $value;
        }

        return $placeholders;
    }
}
