<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Throwable;
use Zend\InputFilter\InputFilterInterface;

use function print_r;
use function random_int;

class ValidationExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideExceptionData
     */
    public function createsExceptionWrappingExpectedData(
        array $args,
        string $expectedMessage,
        array $expectedInvalidElements,
        int $expectedCode,
        ?Throwable $expectedPrev
    ): void {
        $e = new ValidationException(...$args);

        $this->assertEquals($expectedMessage, $e->getMessage());
        $this->assertEquals($expectedInvalidElements, $e->getInvalidElements());
        $this->assertEquals($expectedCode, $e->getCode());
        $this->assertEquals($expectedPrev, $e->getPrevious());
    }

    public function provideExceptionData(): iterable
    {
        yield 'empty args' => [[], '', [], 0, null];
        yield 'with message' => [['something'], 'something', [], 0, null];
        yield 'with elements' => [['something_else', [1, 2, 3]], 'something_else', [1, 2, 3], 0, null];
        yield 'with code' => [['foo', [], $foo = random_int(-100, 100)], 'foo', [], $foo, null];
        yield 'with prev' => [['bar', [], 8, $e = new RuntimeException()], 'bar', [], 8, $e];
    }

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

        $inputFilter = $this->prophesize(InputFilterInterface::class);
        $getMessages = $inputFilter->getMessages()->willReturn($invalidData);

        $e = ValidationException::fromInputFilter($inputFilter->reveal());

        $this->assertEquals($invalidData, $e->getInvalidElements());
        $this->assertEquals('Provided data is not valid', $e->getMessage());
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $e->getCode());
        $this->assertEquals($prev, $e->getPrevious());
        $this->assertStringContainsString($expectedStringRepresentation, (string) $e);
        $getMessages->shouldHaveBeenCalledOnce();
    }

    public function provideExceptions(): iterable
    {
        return [[null, new RuntimeException(), new LogicException()]];
    }
}
