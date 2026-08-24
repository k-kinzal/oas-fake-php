<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\Parameter;
use OasFake\ParameterSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParameterSerializer::class)]
final class ParameterSerializerTest extends TestCase
{
    public function testQuerySerializesExplodedLists(): void
    {
        $parameter = new Parameter(['name' => 'tags', 'in' => 'query', 'style' => 'form', 'explode' => true]);

        self::assertSame(['tags' => ['one', 'two']], (new ParameterSerializer())->query($parameter, ['one', 'two']));
    }

    public function testDelimitedAppliesLabelStyle(): void
    {
        $parameter = new Parameter(['name' => 'coords', 'in' => 'path', 'style' => 'label', 'explode' => false]);

        self::assertSame('.1.2', (new ParameterSerializer())->delimited($parameter, [1, 2]));
    }

    public function testMatrixSerializesExplodedObjects(): void
    {
        self::assertSame(';x=1;y=2', (new ParameterSerializer())->matrix('point', ['x' => 1, 'y' => 2], true));
    }

    public function testDelimitedValueSerializesObjects(): void
    {
        self::assertSame('x,1,y,2', (new ParameterSerializer())->delimitedValue(['x' => 1, 'y' => 2], ',', false));
    }

    public function testScalarUsesWireBooleanValues(): void
    {
        self::assertSame('true', (new ParameterSerializer())->scalar(true));
        self::assertSame('false', (new ParameterSerializer())->scalar(false));
    }

    public function testIsListDistinguishesObjects(): void
    {
        $serializer = new ParameterSerializer();

        self::assertTrue($serializer->isList(['one', 'two']));
        self::assertFalse($serializer->isList(['name' => 'one']));
    }
}
