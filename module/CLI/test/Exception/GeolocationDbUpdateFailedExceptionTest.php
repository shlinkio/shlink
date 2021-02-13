<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Throwable;

class GeolocationDbUpdateFailedExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider providePrev
     */
    public function withOlderDbBuildsException(?Throwable $prev): void
    {
        $e = GeolocationDbUpdateFailedException::withOlderDb($prev);

        self::assertTrue($e->olderDbExists());
        self::assertEquals(
            'An error occurred while updating geolocation database, but an older DB is already present.',
            $e->getMessage(),
        );
        self::assertEquals(0, $e->getCode());
        self::assertEquals($prev, $e->getPrevious());
    }

    /**
     * @test
     * @dataProvider providePrev
     */
    public function withoutOlderDbBuildsException(?Throwable $prev): void
    {
        $e = GeolocationDbUpdateFailedException::withoutOlderDb($prev);

        self::assertFalse($e->olderDbExists());
        self::assertEquals(
            'An error occurred while updating geolocation database, and an older version could not be found.',
            $e->getMessage(),
        );
        self::assertEquals(0, $e->getCode());
        self::assertEquals($prev, $e->getPrevious());
    }

    public function providePrev(): iterable
    {
        yield 'no prev' => [null];
        yield 'RuntimeException' => [new RuntimeException('prev')];
        yield 'Exception' => [new Exception('prev')];
    }

    /** @test */
    public function withInvalidEpochInOldDbBuildsException(): void
    {
        $e = GeolocationDbUpdateFailedException::withInvalidEpochInOldDb('foobar');

        self::assertTrue($e->olderDbExists());
        self::assertEquals(
            'Build epoch with value "foobar" from existing geolocation database, could not be parsed to integer.',
            $e->getMessage(),
        );
    }
}
