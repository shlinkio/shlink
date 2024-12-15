<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Geolocation;

use Cake\Chronos\Chronos;
use Closure;
use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\Metadata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\Core\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\Core\Geolocation\GeolocationDbUpdater;
use Shlinkio\Shlink\Core\Geolocation\GeolocationDownloadProgressHandlerInterface;
use Shlinkio\Shlink\Core\Geolocation\GeolocationResult;
use Shlinkio\Shlink\IpGeolocation\Exception\DbUpdateException;
use Shlinkio\Shlink\IpGeolocation\Exception\MissingLicenseException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Lock;
use Throwable;

use function array_map;
use function range;

class GeolocationDbUpdaterTest extends TestCase
{
    private MockObject & DbUpdaterInterface $dbUpdater;
    private MockObject & Reader $geoLiteDbReader;
    private MockObject & Lock\LockInterface $lock;
    /** @var GeolocationDownloadProgressHandlerInterface&object{beforeDownloadCalled: bool, handleProgressCalled: bool} */
    private GeolocationDownloadProgressHandlerInterface $progressHandler;

    protected function setUp(): void
    {
        $this->dbUpdater = $this->createMock(DbUpdaterInterface::class);
        $this->geoLiteDbReader = $this->createMock(Reader::class);
        $this->lock = $this->createMock(Lock\SharedLockInterface::class);
        $this->lock->method('acquire')->with($this->isTrue())->willReturn(true);
        $this->progressHandler = new class implements GeolocationDownloadProgressHandlerInterface {
            public function __construct(
                public bool $beforeDownloadCalled = false,
                public bool $handleProgressCalled = false,
            ) {
            }

            public function beforeDownload(bool $olderDbExists): void
            {
                $this->beforeDownloadCalled = true;
            }

            public function handleProgress(int $total, int $downloaded, bool $olderDbExists): void
            {
                $this->handleProgressCalled = true;
            }
        };
    }

    #[Test]
    public function properResultIsReturnedWhenLicenseIsMissing(): void
    {
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(false);
        $this->dbUpdater->expects($this->once())->method('downloadFreshCopy')->willThrowException(
            new MissingLicenseException(''),
        );
        $this->geoLiteDbReader->expects($this->never())->method('metadata');

        $result = $this->geolocationDbUpdater()->checkDbUpdate($this->progressHandler);

        self::assertTrue($this->progressHandler->beforeDownloadCalled);
        self::assertEquals(GeolocationResult::LICENSE_MISSING, $result);
    }

    #[Test]
    public function exceptionIsThrownWhenOlderDbDoesNotExistAndDownloadFails(): void
    {
        $prev = new DbUpdateException('');

        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(false);
        $this->dbUpdater->expects($this->once())->method('downloadFreshCopy')->with(
            $this->isInstanceOf(Closure::class),
        )->willThrowException($prev);
        $this->geoLiteDbReader->expects($this->never())->method('metadata');

        try {
            $this->geolocationDbUpdater()->checkDbUpdate($this->progressHandler);
            self::fail();
        } catch (Throwable $e) {
            self::assertInstanceOf(GeolocationDbUpdateFailedException::class, $e);
            self::assertSame($prev, $e->getPrevious());
            self::assertFalse($e->olderDbExists);
            self::assertTrue($this->progressHandler->beforeDownloadCalled);
        }
    }

    #[Test, DataProvider('provideBigDays')]
    public function exceptionIsThrownWhenOlderDbIsTooOldAndDownloadFails(int $days): void
    {
        $prev = new DbUpdateException('');
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(true);
        $this->dbUpdater->expects($this->once())->method('downloadFreshCopy')->with(
            $this->isInstanceOf(Closure::class),
        )->willThrowException($prev);
        $this->geoLiteDbReader->expects($this->once())->method('metadata')->with()->willReturn(
            $this->buildMetaWithBuildEpoch(Chronos::now()->subDays($days)->getTimestamp()),
        );

        try {
            $this->geolocationDbUpdater()->checkDbUpdate();
            self::fail();
        } catch (Throwable $e) {
            self::assertInstanceOf(GeolocationDbUpdateFailedException::class, $e);
            self::assertSame($prev, $e->getPrevious());
            self::assertTrue($e->olderDbExists);
        }
    }

    public static function provideBigDays(): iterable
    {
        yield [36];
        yield [50];
        yield [75];
        yield [100];
    }

    #[Test, DataProvider('provideSmallDays')]
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

    public static function provideSmallDays(): iterable
    {
        $generateParamsWithTimestamp = static function (int $days) {
            $timestamp = Chronos::now()->subDays($days)->getTimestamp();
            return [$days % 2 === 0 ? $timestamp : (string) $timestamp];
        };

        return array_map($generateParamsWithTimestamp, range(0, 34));
    }

    #[Test]
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

    #[Test, DataProvider('provideTrackingOptions')]
    public function downloadDbIsSkippedIfTrackingIsDisabled(TrackingOptions $options): void
    {
        $result = $this->geolocationDbUpdater($options)->checkDbUpdate();
        $this->dbUpdater->expects($this->never())->method('databaseFileExists');
        $this->geoLiteDbReader->expects($this->never())->method('metadata');

        self::assertEquals(GeolocationResult::CHECK_SKIPPED, $result);
    }

    public static function provideTrackingOptions(): iterable
    {
        yield 'disableTracking' => [new TrackingOptions(disableTracking: true)];
        yield 'disableIpTracking' => [new TrackingOptions(disableIpTracking: true)];
        yield 'both' => [new TrackingOptions(disableTracking: true, disableIpTracking: true)];
    }

    private function geolocationDbUpdater(TrackingOptions|null $options = null): GeolocationDbUpdater
    {
        $locker = $this->createMock(Lock\LockFactory::class);
        $locker->method('createLock')->with($this->isType('string'))->willReturn($this->lock);

        return new GeolocationDbUpdater(
            $this->dbUpdater,
            fn () => $this->geoLiteDbReader,
            $locker,
            $options ?? new TrackingOptions(),
        );
    }
}
