<?php

declare(strict_types=1);

namespace Linio\Component\Database\Query\Transformer;

use Linio\Component\Database\Exception\InvalidQueryException;
use Linio\Component\Database\Query\Builder;
use Linio\Component\Database\Query\Transformer;

class NamedArrayParameter implements Transformer
{
    public function execute(string &$query, array &$params = []): void
    {
        $builder = new Builder();

        foreach ($params as $paramKey => $paramValue) {
            if (is_numeric($paramKey)) {
                if (is_array($paramValue)) {
                    throw new InvalidQueryException(sprintf(
                        'Unnamed parameter "%s" can\'t have array value: %s',
                        $paramKey,
                        var_export($paramValue, true)
                    ));
                }

                continue;
            }

            if (is_array($paramValue)) {
                $placeholders = $builder->placeholders($paramKey, $paramValue);
                $params += $placeholders;

                unset($params[$paramKey]);

                $paramNames = array_keys($placeholders);

                $hasDoubleColon = ($paramKey[0] === ':');

                $paramNameToReplace = $hasDoubleColon ? $paramKey : ':' . $paramKey;

                $query = str_replace($paramNameToReplace, implode(', ', $paramNames), $query);
            }
        }
    }
}
