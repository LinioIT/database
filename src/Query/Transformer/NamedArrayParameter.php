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

            $arrayParamKeys = array_keys($valuesIndexedByPlaceholder);

            $startsWithColon = $paramKey[0] === ':';

            $paramNameToReplace = $startsWithColon ? $paramKey : ':' . $paramKey;

            $query = str_replace($paramNameToReplace, implode(', ', $arrayParamKeys), $query);
        }

        $params = $finalParams;
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
