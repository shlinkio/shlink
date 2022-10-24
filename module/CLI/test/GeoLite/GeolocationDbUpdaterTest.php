<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\GeoLite;

use Cake\Chronos\Chronos;
use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\Metadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\GeoLite\GeolocationDbUpdater;
use Shlinkio\Shlink\CLI\GeoLite\GeolocationResult;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\IpGeolocation\Exception\DbUpdateException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Lock;
use Throwable;

use function Functional\map;
use function range;

class GeolocationDbUpdaterTest extends TestCase
{
    private MockObject & DbUpdaterInterface $dbUpdater;
    private MockObject & Reader $geoLiteDbReader;
    private MockObject & Lock\LockInterface $lock;

    protected function setUp(): void
    {
        $this->dbUpdater = $this->createMock(DbUpdaterInterface::class);
        $this->geoLiteDbReader = $this->createMock(Reader::class);
        $this->lock = $this->createMock(Lock\LockInterface::class);
        $this->lock->method('acquire')->with($this->isTrue())->willReturn(true);
    }

    /** @test */
    public function exceptionIsThrownWhenOlderDbDoesNotExistAndDownloadFails(): void
    {
        $mustBeUpdated = fn () => self::assertTrue(true);
        $prev = new DbUpdateException('');

        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(false);
        $this->dbUpdater->expects($this->once())->method('downloadFreshCopy')->with(
            $this->isNull(),
        )->willThrowException($prev);
        $this->geoLiteDbReader->expects($this->never())->method('metadata');

        try {
            $this->geolocationDbUpdater()->checkDbUpdate($mustBeUpdated);
            self::fail();
        } catch (Throwable $e) {
            /** @var GeolocationDbUpdateFailedException $e */
            self::assertInstanceOf(GeolocationDbUpdateFailedException::class, $e);
            self::assertSame($prev, $e->getPrevious());
            self::assertFalse($e->olderDbExists());
        }
    }

    /**
     * @test
     * @dataProvider provideBigDays
     */
    public function exceptionIsThrownWhenOlderDbIsTooOldAndDownloadFails(int $days): void
    {
        $prev = new DbUpdateException('');
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(true);
        $this->dbUpdater->expects($this->once())->method('downloadFreshCopy')->with(
            $this->isNull(),
        )->willThrowException($prev);
        $this->geoLiteDbReader->expects($this->once())->method('metadata')->with()->willReturn(
            $this->buildMetaWithBuildEpoch(Chronos::now()->subDays($days)->getTimestamp()),
        );

        try {
            $this->geolocationDbUpdater()->checkDbUpdate();
            self::fail();
        } catch (Throwable $e) {
            /** @var GeolocationDbUpdateFailedException $e */
            self::assertInstanceOf(GeolocationDbUpdateFailedException::class, $e);
            self::assertSame($prev, $e->getPrevious());
            self::assertTrue($e->olderDbExists());
        }
    }

    public function provideBigDays(): iterable
    {
        yield [36];
        yield [50];
        yield [75];
        yield [100];
    }

    /**
     * @test
     * @dataProvider provideSmallDays
     */
    public function databaseIsNotUpdatedIfItIsNewEnough(string|int $buildEpoch): void
    {
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(true);
        $this->dbUpdater->expects($this->never())->method('downloadFreshCopy');
        $this->geoLiteDbReader->expects($this->once())->method('metadata')->with()->willReturn(
            $this->buildMetaWithBuildEpoch($buildEpoch),
        );

        $result = $this->geolocationDbUpdater()->checkDbUpdate();

        self::assertEquals(GeolocationResult::DB_IS_UP_TO_DATE, $result);
    }

    public function provideSmallDays(): iterable
    {
        $generateParamsWithTimestamp = static function (int $days) {
            $timestamp = Chronos::now()->subDays($days)->getTimestamp();
            return [$days % 2 === 0 ? $timestamp : (string) $timestamp];
        };

        return map(range(0, 34), $generateParamsWithTimestamp);
    }

    /** @test */
    public function exceptionIsThrownWhenCheckingExistingDatabaseWithInvalidBuildEpoch(): void
    {
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(true);
        $this->dbUpdater->expects($this->never())->method('downloadFreshCopy');
        $this->geoLiteDbReader->expects($this->once())->method('metadata')->with()->willReturn(
            $this->buildMetaWithBuildEpoch('invalid'),
        );

        $this->expectException(GeolocationDbUpdateFailedException::class);
        $this->expectExceptionMessage(
            'Build epoch with value "invalid" from existing geolocation database, could not be parsed to integer.',
        );

        $this->geolocationDbUpdater()->checkDbUpdate();
    }

    private function buildMetaWithBuildEpoch(string|int $buildEpoch): Metadata
    {
        return new Metadata([
            'binary_format_major_version' => '',
            'binary_format_minor_version' => '',
            'build_epoch' => $buildEpoch,
            'database_type' => '',
            'languages' => '',
            'description' => '',
            'ip_version' => '',
            'node_count' => 1,
            'record_size' => 4,
        ]);
    }

    /**
     * @test
     * @dataProvider provideTrackingOptions
     */
    public function downloadDbIsSkippedIfTrackingIsDisabled(TrackingOptions $options): void
    {
        $result = $this->geolocationDbUpdater($options)->checkDbUpdate();
        $this->dbUpdater->expects($this->never())->method('databaseFileExists');
        $this->geoLiteDbReader->expects($this->never())->method('metadata');

        self::assertEquals(GeolocationResult::CHECK_SKIPPED, $result);
    }

    public function provideTrackingOptions(): iterable
    {
        yield 'disableTracking' => [new TrackingOptions(disableTracking: true)];
        yield 'disableIpTracking' => [new TrackingOptions(disableIpTracking: true)];
        yield 'both' => [new TrackingOptions(disableTracking: true, disableIpTracking: true)];
    }

    private function geolocationDbUpdater(?TrackingOptions $options = null): GeolocationDbUpdater
    {
        $locker = $this->createMock(Lock\LockFactory::class);
        $locker->method('createLock')->with($this->isType('string'))->willReturn($this->lock);

        return new GeolocationDbUpdater(
            $this->dbUpdater,
            $this->geoLiteDbReader,
            $locker,
            $options ?? new TrackingOptions(),
        );
    }
}
