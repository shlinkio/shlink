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
     * @dataProvider provideOlderDbExists
     */
    public function constructCreatesExceptionWithDefaultArgs(bool $olderDbExists): void
    {
        $e = new GeolocationDbUpdateFailedException($olderDbExists);

        $this->assertEquals($olderDbExists, $e->olderDbExists());
        $this->assertEquals('', $e->getMessage());
        $this->assertEquals(0, $e->getCode());
        $this->assertNull($e->getPrevious());
    }

    public function provideOlderDbExists(): iterable
    {
        yield 'with older DB' => [true];
        yield 'without older DB' => [false];
    }

    /**
     * @test
     * @dataProvider provideConstructorArgs
     */
    public function constructCreatesException(bool $olderDbExists, string $message, int $code, ?Throwable $prev): void
    {
        $e = new GeolocationDbUpdateFailedException($olderDbExists, $message, $code, $prev);

        $this->assertEquals($olderDbExists, $e->olderDbExists());
        $this->assertEquals($message, $e->getMessage());
        $this->assertEquals($code, $e->getCode());
        $this->assertEquals($prev, $e->getPrevious());
    }

    public function provideConstructorArgs(): iterable
    {
        yield [true, 'This is a nice error message', 99, new Exception('prev')];
        yield [false, 'Another message', 0, new RuntimeException('prev')];
        yield [true, 'An yet another message', -50, null];
    }

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
            $e->getMessage()
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
