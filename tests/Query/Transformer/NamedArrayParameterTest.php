<?php

declare(strict_types=1);

namespace Linio\Component\Database\Query\Transformer;

use PHPUnit\Framework\TestCase;

class NamedArrayParameterTest extends TestCase
{
    public function testItDoesTransformQueryWithOneArrayParameterUsingDoubleQuotes(): void
    {
        $query = 'SELECT id, email FROM users WHERE uuid in (:uuid)';

        $params = [
            ':uuid' => [
                'abc-13',
                'def-456'
            ],
        ];

        $expectedQuery = 'SELECT id, email FROM users WHERE uuid in (:uuid_0, :uuid_1)';

        $expectedParams = [
            ':uuid_0' => 'abc-13',
            ':uuid_1' => 'def-456',
        ];

        $transformer = new NamedArrayParameter();
        $transformer->execute($query, $params);

        $this->assertEquals($expectedQuery, $query);
        $this->assertEquals($expectedParams, $params);
    }

    public function testItDoesTransformQueryWithOneArrayParameterNotUsingDoubleQuotes(): void
    {
        $query = 'SELECT id, email FROM users WHERE uuid in (:uuid)';

        $params = [
            'uuid' => [
                'abc-13',
                'def-456'
            ],
        ];

        $expectedQuery = 'SELECT id, email FROM users WHERE uuid in (:uuid_0, :uuid_1)';

        $expectedParams = [
            ':uuid_0' => 'abc-13',
            ':uuid_1' => 'def-456',
        ];

        $transformer = new NamedArrayParameter();
        $transformer->execute($query, $params);

        $this->assertEquals($expectedQuery, $query);
        $this->assertEquals($expectedParams, $params);
    }

    public function testItDoesTransformQueryWithTwoArrayParametersUsingDoubleQuotes(): void
    {
        $query = 'SELECT id, email FROM users WHERE uuid in (:uuid) and status IN (:status)';

        $params = [
            ':uuid' => [
                'abc-13',
                'def-456'
            ],
            ':status' => [
                1,
                5,
                6
            ],
        ];

        $expectedQuery = 'SELECT id, email FROM users WHERE uuid in (:uuid_0, :uuid_1) and status IN (:status_0, :status_1, :status_2)';

        $expectedParams = [
            ':uuid_0' => 'abc-13',
            ':uuid_1' => 'def-456',
            ':status_0' => 1,
            ':status_1' => 5,
            ':status_2' => 6,
        ];

        $transformer = new NamedArrayParameter();
        $transformer->execute($query, $params);

        $this->assertEquals($expectedQuery, $query);
        $this->assertEquals($expectedParams, $params);
    }

    public function testItDoesTransformQueryWithTwoArrayParametersNotUsingDoubleQuotes(): void
    {
        $query = 'SELECT id, email FROM users WHERE uuid in (:uuid) and status IN (:status)';

        $params = [
            'uuid' => [
                'abc-13',
                'def-456'
            ],
            'status' => [
                1,
                5,
                6
            ],
        ];

        $expectedQuery = 'SELECT id, email FROM users WHERE uuid in (:uuid_0, :uuid_1) and status IN (:status_0, :status_1, :status_2)';

        $expectedParams = [
            ':uuid_0' => 'abc-13',
            ':uuid_1' => 'def-456',
            ':status_0' => 1,
            ':status_1' => 5,
            ':status_2' => 6,
        ];

        $transformer = new NamedArrayParameter();
        $transformer->execute($query, $params);

        $this->assertEquals($expectedQuery, $query);
        $this->assertEquals($expectedParams, $params);
    }
}
