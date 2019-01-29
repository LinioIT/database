<?php

declare(strict_types=1);

namespace Linio\Component\Database\Query;

class Builder
{
    public function flatParams(array $params): array
    {
        $flatParams = [];

        $paramsKeys = array_keys($params);

        foreach ($paramsKeys as $indexOfKey => $paramKey) {
            $paramValues = $params[$paramKey];

            if (!is_array($paramValues)) {
                $paramValues = [
                    $paramValues
                ];
            }

            foreach ($paramValues as $paramValue) {
                $flatParams[] = $paramValue;
            }
        }

        return $flatParams;
    }

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
