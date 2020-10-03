<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\Exception\MissingAuthenticationException;

use function implode;
use function sprintf;

class MissingAuthenticationExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideExpectedTypes
     */
    public function exceptionIsProperlyCreatedFromExpectedTypes(array $expectedTypes): void
    {
        $expectedMessage = sprintf(
            'Expected one of the following authentication headers, ["%s"], but none were provided',
            implode('", "', $expectedTypes),
        );

        $e = MissingAuthenticationException::fromExpectedTypes($expectedTypes);

        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals('Invalid authorization', $e->getTitle());
        self::assertEquals('INVALID_AUTHORIZATION', $e->getType());
        self::assertEquals(401, $e->getStatus());
        self::assertEquals(['expectedTypes' => $expectedTypes], $e->getAdditionalData());
    }

    public function provideExpectedTypes(): iterable
    {
        yield [['foo', 'bar']];
        yield [['something']];
        yield [[]];
        yield [['foo', 'bar', 'baz']];
    }
}
