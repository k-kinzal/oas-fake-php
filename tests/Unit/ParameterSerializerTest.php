<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\Parameter;
use JsonException;
use OasFake\ParameterSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParameterSerializer::class)]
final class ParameterSerializerTest extends TestCase
{
    /**
     * @throws JsonException when a value cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when parameter metadata is invalid
     */
    public function testQuerySerializesExplodedLists(): void
    {
        $parameter = new Parameter(['name' => 'tags', 'in' => 'query', 'style' => 'form', 'explode' => true]);

        self::assertSame(['tags' => ['one', 'two']], (new ParameterSerializer())->query($parameter, ['one', 'two']));
    }

    /**
     * @param array{name: string, in: string, style: string, explode: bool} $definition
     * @param array<string, list<string>|string> $expected
     *
     * @throws JsonException when a value cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when parameter metadata is invalid
     *
     * @dataProvider providerQueryStyles
     */
    #[DataProvider('providerQueryStyles')]
    public function testQueryHonorsOpenApiStyle(
        array $definition,
        mixed $value,
        array $expected,
    ): void {
        $parameter = new Parameter($definition);

        self::assertSame($expected, (new ParameterSerializer())->query($parameter, $value));
    }

    /**
     * @return iterable<string, array{
     *     array{name: string, in: string, style: string, explode: bool},
     *     mixed,
     *     array<string, list<string>|string>
     * }>
     */
    public static function providerQueryStyles(): iterable
    {
        yield 'space-delimited list' => [
            ['name' => 'tags', 'in' => 'query', 'style' => 'spaceDelimited', 'explode' => false],
            ['one', 'two'],
            ['tags' => 'one two'],
        ];
        yield 'pipe-delimited list' => [
            ['name' => 'tags', 'in' => 'query', 'style' => 'pipeDelimited', 'explode' => false],
            ['one', 'two'],
            ['tags' => 'one|two'],
        ];
        yield 'deep object' => [
            ['name' => 'filter', 'in' => 'query', 'style' => 'deepObject', 'explode' => true],
            ['name' => 'Buddy', 'active' => true],
            ['filter[name]' => 'Buddy', 'filter[active]' => 'true'],
        ];
        yield 'simple scalar' => [
            ['name' => 'limit', 'in' => 'query', 'style' => 'simple', 'explode' => false],
            10,
            ['limit' => '10'],
        ];
        yield 'compact form list' => [
            ['name' => 'tags', 'in' => 'query', 'style' => 'form', 'explode' => false],
            ['one', 'two'],
            ['tags' => 'one,two'],
        ];
        yield 'compact form object' => [
            ['name' => 'filter', 'in' => 'query', 'style' => 'form', 'explode' => false],
            ['name' => 'Buddy', 'active' => true],
            ['filter' => 'name,Buddy,active,true'],
        ];
        yield 'exploded form object' => [
            ['name' => 'filter', 'in' => 'query', 'style' => 'form', 'explode' => true],
            ['name' => 'Buddy', 'active' => true],
            ['name' => 'Buddy', 'active' => 'true'],
        ];
    }

    /**
     * @throws JsonException when a value cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when parameter metadata is invalid
     */
    public function testDelimitedAppliesLabelStyle(): void
    {
        $parameter = new Parameter(['name' => 'coords', 'in' => 'path', 'style' => 'label', 'explode' => false]);

        self::assertSame('.1.2', (new ParameterSerializer())->delimited($parameter, [1, 2]));
    }

    /**
     * @param array{name: string, in: string, style: string, explode: bool} $definition
     *
     * @throws JsonException when a value cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when parameter metadata is invalid
     *
     * @dataProvider providerDelimitedStyles
     */
    #[DataProvider('providerDelimitedStyles')]
    public function testDelimitedHonorsPathAndHeaderStyle(
        array $definition,
        mixed $value,
        string $expected,
    ): void {
        $parameter = new Parameter($definition);

        self::assertSame($expected, (new ParameterSerializer())->delimited($parameter, $value));
    }

    /**
     * @return iterable<string, array{
     *     array{name: string, in: string, style: string, explode: bool},
     *     mixed,
     *     string
     * }>
     */
    public static function providerDelimitedStyles(): iterable
    {
        yield 'exploded label object' => [
            ['name' => 'point', 'in' => 'path', 'style' => 'label', 'explode' => true],
            ['x' => 1, 'y' => 2],
            '.x=1.y=2',
        ];
        yield 'matrix scalar' => [
            ['name' => 'id', 'in' => 'path', 'style' => 'matrix', 'explode' => false],
            3,
            ';id=3',
        ];
        yield 'compact matrix list' => [
            ['name' => 'id', 'in' => 'path', 'style' => 'matrix', 'explode' => false],
            [1, 2],
            ';id=1,2',
        ];
        yield 'exploded matrix list' => [
            ['name' => 'id', 'in' => 'path', 'style' => 'matrix', 'explode' => true],
            [1, 2],
            ';id=1;id=2',
        ];
        yield 'compact matrix object' => [
            ['name' => 'point', 'in' => 'path', 'style' => 'matrix', 'explode' => false],
            ['x' => 1, 'y' => 2],
            ';point=x,1,y,2',
        ];
        yield 'exploded matrix object' => [
            ['name' => 'point', 'in' => 'path', 'style' => 'matrix', 'explode' => true],
            ['x' => 1, 'y' => 2],
            ';x=1;y=2',
        ];
        yield 'simple header list' => [
            ['name' => 'X-Ids', 'in' => 'header', 'style' => 'simple', 'explode' => false],
            [1, 2],
            '1,2',
        ];
        yield 'exploded simple header object' => [
            ['name' => 'X-Point', 'in' => 'header', 'style' => 'simple', 'explode' => true],
            ['x' => 1, 'y' => 2],
            'x=1,y=2',
        ];
    }

    /**
     * @throws JsonException when a value cannot be serialized
     */
    public function testMatrixSerializesExplodedObjects(): void
    {
        self::assertSame(';x=1;y=2', (new ParameterSerializer())->matrix('point', ['x' => 1, 'y' => 2], true));
    }

    /**
     * @throws JsonException when a value cannot be serialized
     */
    public function testDelimitedValueSerializesObjects(): void
    {
        self::assertSame('x,1,y,2', (new ParameterSerializer())->delimitedValue(['x' => 1, 'y' => 2], ',', false));
    }

    /**
     * @throws JsonException when a value cannot be serialized
     *
     * @dataProvider providerDelimitedValues
     */
    #[DataProvider('providerDelimitedValues')]
    public function testDelimitedValuePreservesValueShape(
        mixed $value,
        string $delimiter,
        bool $explode,
        string $expected,
    ): void {
        self::assertSame($expected, (new ParameterSerializer())->delimitedValue($value, $delimiter, $explode));
    }

    /**
     * @return iterable<string, array{mixed, string, bool, string}>
     */
    public static function providerDelimitedValues(): iterable
    {
        yield 'scalar' => [42, ',', false, '42'];
        yield 'list' => [[1, 2], '|', false, '1|2'];
        yield 'exploded object' => [['x' => 1, 'y' => 2], '&', true, 'x=1&y=2'];
    }

    /**
     * @throws JsonException when a value cannot be serialized
     */
    public function testScalarUsesWireBooleanValues(): void
    {
        self::assertSame('true', (new ParameterSerializer())->scalar(true));
        self::assertSame('false', (new ParameterSerializer())->scalar(false));
    }

    /**
     * @throws JsonException when a value cannot be serialized
     *
     * @dataProvider providerScalarValues
     */
    #[DataProvider('providerScalarValues')]
    public function testScalarUsesWireRepresentation(mixed $value, string $expected): void
    {
        self::assertSame($expected, (new ParameterSerializer())->scalar($value));
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function providerScalarValues(): iterable
    {
        yield 'integer' => [42, '42'];
        yield 'string' => ['Buddy', 'Buddy'];
        yield 'null' => [null, ''];
        yield 'object value' => [['name' => 'Buddy'], '{"name":"Buddy"}'];
    }

    public function testIsListDistinguishesObjects(): void
    {
        $serializer = new ParameterSerializer();

        self::assertTrue($serializer->isList(['one', 'two']));
        self::assertFalse($serializer->isList(['name' => 'one']));
        self::assertTrue($serializer->isList([]));
        self::assertFalse($serializer->isList([1 => 'one']));
    }
}
