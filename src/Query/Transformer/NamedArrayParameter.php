<?php

declare(strict_types=1);

namespace Linio\Component\Database\Query\Transformer;

use Linio\Component\Database\Exception\InvalidQueryException;
use Linio\Component\Database\Query\Transformer;

class NamedArrayParameter implements Transformer
{
    public function execute(string &$query, array &$params = []): void
    {
        $finalParams = [];

        foreach ($params as $paramKey => $paramValue) {
            if (!is_array($paramValue)) {
                $finalParams[$paramKey] = $paramValue;
                continue;
            }

            if (is_numeric($paramKey)) {
                throw new InvalidQueryException(sprintf('Unnamed parameter "%s" can\'t have array value.', $paramKey));
            }

            $valuesIndexedByPlaceholder = $this->getValuesIndexedByPlaceholder($paramKey, $paramValue);

            $finalParams += $valuesIndexedByPlaceholder;

            $placeholders = array_keys($valuesIndexedByPlaceholder);

            $query = $this->transformQuery($query, $paramKey, $placeholders);
        }

        $params = $finalParams;
    }

    protected function transformQuery(string $query, string $currentPlaceholder, array $newPlaceholders): string
    {
        $startsWithColon = $currentPlaceholder[0] === ':';

        $placeholderToReplace = $startsWithColon ? $currentPlaceholder : ':' . $currentPlaceholder;

        $transformedQuery = str_replace($placeholderToReplace, implode(', ', $newPlaceholders), $query);

        return $transformedQuery;
    }

    protected function getValuesIndexedByPlaceholder(string $paramName, array $paramValues): array
    {
        $placeholders = [];

        $counter = 0;

        foreach ($paramValues as $value) {
            $placeholderName = sprintf(':%s_%d', ltrim($paramName, ':'), $counter++);
            $placeholders[$placeholderName] = $value;
        }

        return $placeholders;
    }
}
