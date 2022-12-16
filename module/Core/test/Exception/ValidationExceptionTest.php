<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\InputFilter\InputFilterInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Throwable;

use function array_keys;
use function print_r;

class ValidationExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function createsExceptionFromInputFilter(?Throwable $prev): void
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

        $inputFilter = $this->createMock(InputFilterInterface::class);
        $inputFilter->expects($this->once())->method('getMessages')->with()->willReturn($invalidData);

        $e = ValidationException::fromInputFilter($inputFilter, $prev);

        self::assertEquals($invalidData, $e->getInvalidElements());
        self::assertEquals(['invalidElements' => array_keys($invalidData)], $e->getAdditionalData());
        self::assertEquals('Provided data is not valid', $e->getMessage());
        self::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $e->getCode());
        self::assertEquals($prev, $e->getPrevious());
        self::assertStringContainsString($expectedStringRepresentation, (string) $e);
    }

    public function provideExceptions(): iterable
    {
        return [[null], [new RuntimeException()], [new LogicException()]];
    }
}
