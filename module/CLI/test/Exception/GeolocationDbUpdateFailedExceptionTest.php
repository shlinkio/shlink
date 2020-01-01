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
     * @dataProvider provideCreateArgs
     */
    public function createBuildsException(bool $olderDbExists, ?Throwable $prev): void
    {
        $e = GeolocationDbUpdateFailedException::create($olderDbExists, $prev);

        $this->assertEquals($olderDbExists, $e->olderDbExists());
        $this->assertEquals(
            'An error occurred while updating geolocation database, and an older version could not be found',
            $e->getMessage(),
        );
        $this->assertEquals(0, $e->getCode());
        $this->assertEquals($prev, $e->getPrevious());
    }

    public function provideCreateArgs(): iterable
    {
        yield 'older DB and no prev' => [true, null];
        yield 'older DB and prev' => [true, new RuntimeException('prev')];
        yield 'no older DB and no prev' => [false, null];
        yield 'no older DB and prev' => [false, new Exception('prev')];
    }
}
