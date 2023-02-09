<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use Exception;
use LogicException;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Exception\RuntimeException;
use Shlinkio\Shlink\Core\Visit\Model\UnlocatableIpType;
use Throwable;

class IpCannotBeLocatedExceptionTest extends TestCase
{
    /** @test */
    public function forEmptyAddressInitializesException(): void
    {
        $e = IpCannotBeLocatedException::forEmptyAddress();

        self::assertTrue($e->isNonLocatableAddress());
        self::assertEquals('Ignored visit with no IP address', $e->getMessage());
        self::assertEquals(0, $e->getCode());
        self::assertNull($e->getPrevious());
        self::assertEquals(UnlocatableIpType::EMPTY_ADDRESS, $e->type);
    }

    /** @test */
    public function forLocalhostInitializesException(): void
    {
        $e = IpCannotBeLocatedException::forLocalhost();

        self::assertTrue($e->isNonLocatableAddress());
        self::assertEquals('Ignored localhost address', $e->getMessage());
        self::assertEquals(0, $e->getCode());
        self::assertNull($e->getPrevious());
        self::assertEquals(UnlocatableIpType::LOCALHOST, $e->type);
    }

    /**
     * @test
     * @dataProvider provideErrors
     */
    public function forErrorInitializesException(Throwable $prev): void
    {
        $e = IpCannotBeLocatedException::forError($prev);

        self::assertFalse($e->isNonLocatableAddress());
        self::assertEquals('An error occurred while locating IP', $e->getMessage());
        self::assertEquals($prev->getCode(), $e->getCode());
        self::assertSame($prev, $e->getPrevious());
        self::assertEquals(UnlocatableIpType::ERROR, $e->type);
    }

    public static function provideErrors(): iterable
    {
        yield 'Simple exception with positive code' => [new Exception('Some message', 100)];
        yield 'Runtime exception with negative code' => [new RuntimeException('Something went wrong', -50)];
        yield 'Logic exception with default code' => [new LogicException('Conditions unmet')];
    }
}
