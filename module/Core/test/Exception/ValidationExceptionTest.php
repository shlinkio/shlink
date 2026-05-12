<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Throwable;

use function array_keys;
use function print_r;

class ValidationExceptionTest extends TestCase
{
    #[Test, DataProvider('provideExceptions')]
    public function createsExceptionFromArray(Throwable|null $prev): void
    {
        $invalidData = [
            'foo' => 'bar',
            'something' => ['baz', 'foo'],
        ];
        $barValue = print_r(['baz', 'foo'], true);
        $expectedStringRepresentation = <<<EOT
            'foo' => bar
            'something' => {$barValue}
        EOT;

        $e = ValidationException::fromArray($invalidData, $prev);

        self::assertEquals($invalidData, $e->invalidElements);
        self::assertEquals(['invalidElements' => array_keys($invalidData)], $e->getAdditionalData());
        self::assertEquals('Provided data is not valid', $e->getMessage());
        self::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $e->getCode());
        self::assertEquals($prev, $e->getPrevious());
        self::assertStringContainsString($expectedStringRepresentation, (string) $e);
    }

    public static function provideExceptions(): iterable
    {
        return [[null], [new RuntimeException()], [new LogicException()]];
    }
}
