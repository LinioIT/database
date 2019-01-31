<?php

declare(strict_types=1);

namespace Linio\Component\Database\Query;

use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    public function testItDoesBuildNamedPlaceholders(): void
    {
        $values = [
            '1st-value',
            '2nd-value',
            '3rd-value',
        ];

        $builder = new Builder();

        $actual = $builder->placeholders('foo', $values);

        $expected = [
            ':foo_0' => $values[0],
            ':foo_1' => $values[1],
            ':foo_2' => $values[2],
        ];

        $this->assertEquals($expected, $actual);
    }
}
