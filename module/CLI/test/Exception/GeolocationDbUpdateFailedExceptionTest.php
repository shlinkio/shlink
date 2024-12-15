<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Exception;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Core\Exception\GeolocationDbUpdateFailedException;
use Throwable;

class GeolocationDbUpdateFailedExceptionTest extends TestCase
{
    #[Test, DataProvider('providePrev')]
    public function withOlderDbBuildsException(Throwable|null $prev): void
    {
        $e = GeolocationDbUpdateFailedException::withOlderDb($prev);

        self::assertTrue($e->olderDbExists);
        self::assertEquals(
            'An error occurred while updating geolocation database, but an older DB is already present.',
            $e->getMessage(),
        );
        self::assertEquals(0, $e->getCode());
        self::assertEquals($prev, $e->getPrevious());
    }

    #[Test, DataProvider('providePrev')]
    public function withoutOlderDbBuildsException(Throwable|null $prev): void
    {
        $e = GeolocationDbUpdateFailedException::withoutOlderDb($prev);

        self::assertFalse($e->olderDbExists);
        self::assertEquals(
            'An error occurred while updating geolocation database, and an older version could not be found.',
            $e->getMessage(),
        );
        self::assertEquals(0, $e->getCode());
        self::assertEquals($prev, $e->getPrevious());
    }

    public static function providePrev(): iterable
    {
        yield 'no prev' => [null];
        yield 'RuntimeException' => [new RuntimeException('prev')];
        yield 'Exception' => [new Exception('prev')];
    }
}
