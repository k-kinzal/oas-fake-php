<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use cebe\openapi\spec\Parameter;
use JsonException;
use OasFake\FormQueryParameterSerializer;
use OasFake\ParameterSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OasFake\FormQueryParameterSerializer
 *
 * @uses \OasFake\ParameterSerializer
 */
#[CoversClass(FormQueryParameterSerializer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ParameterSerializer::class)]
final class FormQueryParameterSerializerTest extends TestCase
{
    /**
     * @throws JsonException when the value cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when parameter metadata is invalid
     */
    public function testSerializeExpandsFormObjects(): void
    {
        $parameter = new Parameter(['name' => 'filter', 'in' => 'query', 'style' => 'form', 'explode' => true]);

        self::assertSame(
            ['name' => 'Buddy', 'active' => 'true'],
            (new FormQueryParameterSerializer())->serialize($parameter, ['name' => 'Buddy', 'active' => true], new ParameterSerializer()),
        );
    }

    /**
     * @throws JsonException when the value cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when parameter metadata is invalid
     */
    public function testSerializeExpandsOnlyAssociativeDeepObjects(): void
    {
        $parameter = new Parameter(['name' => 'filter', 'in' => 'query', 'style' => 'deepObject', 'explode' => true]);
        $serializer = new FormQueryParameterSerializer();
        $values = new ParameterSerializer();

        self::assertSame(
            ['filter[name]' => 'Buddy', 'filter[active]' => 'true'],
            $serializer->serialize($parameter, ['name' => 'Buddy', 'active' => true], $values),
        );
        self::assertSame(['filter' => '["Buddy","Milo"]'], $serializer->serialize($parameter, ['Buddy', 'Milo'], $values));
    }

    /**
     * @throws JsonException when the value cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when parameter metadata is invalid
     */
    public function testSerializeKeepsNonFormValuesUnderTheirParameterName(): void
    {
        $parameter = new Parameter(['name' => 'filter', 'in' => 'query', 'style' => 'simple', 'explode' => false]);

        self::assertSame(
            ['filter' => '{"name":"Buddy"}'],
            (new FormQueryParameterSerializer())->serialize($parameter, ['name' => 'Buddy'], new ParameterSerializer()),
        );
    }

    /**
     * @throws JsonException when the value cannot be serialized
     * @throws \cebe\openapi\exceptions\TypeErrorException when parameter metadata is invalid
     */
    public function testSerializeKeepsScalarFormValuesUnderTheirParameterName(): void
    {
        $parameter = new Parameter(['name' => 'limit', 'in' => 'query', 'style' => 'form', 'explode' => true]);

        self::assertSame(
            ['limit' => '10'],
            (new FormQueryParameterSerializer())->serialize($parameter, 10, new ParameterSerializer()),
        );
    }
}
