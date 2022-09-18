<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\GeoLite;

use Cake\Chronos\Chronos;
use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\Metadata;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\GeoLite\GeolocationDbUpdater;
use Shlinkio\Shlink\CLI\GeoLite\GeolocationResult;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\IpGeolocation\Exception\RuntimeException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Lock;
use Throwable;

use function Functional\map;
use function range;

class GeolocationDbUpdaterTest extends TestCase
{
    use ProphecyTrait;

    private GeolocationDbUpdater $geolocationDbUpdater;
    private ObjectProphecy $dbUpdater;
    private ObjectProphecy $geoLiteDbReader;
    private ObjectProphecy $lock;

    protected function setUp(): void
    {
        $this->dbUpdater = $this->prophesize(DbUpdaterInterface::class);
        $this->geoLiteDbReader = $this->prophesize(Reader::class);
        $this->trackingOptions = new TrackingOptions();

        $this->lock = $this->prophesize(Lock\LockInterface::class);
        $this->lock->acquire(true)->willReturn(true);
        $this->lock->release()->will(function (): void {
        });
    }

    /** @test */
    public function exceptionIsThrownWhenOlderDbDoesNotExistAndDownloadFails(): void
    {
        $mustBeUpdated = fn () => self::assertTrue(true);
        $prev = new RuntimeException('');

        $fileExists = $this->dbUpdater->databaseFileExists()->willReturn(false);
        $getMeta = $this->geoLiteDbReader->metadata();
        $download = $this->dbUpdater->downloadFreshCopy(null)->willThrow($prev);

        try {
            $this->geolocationDbUpdater()->checkDbUpdate($mustBeUpdated);
            self::assertTrue(false); // If this is reached, the test will fail
        } catch (Throwable $e) {
            /** @var GeolocationDbUpdateFailedException $e */
            self::assertInstanceOf(GeolocationDbUpdateFailedException::class, $e);
            self::assertSame($prev, $e->getPrevious());
            self::assertFalse($e->olderDbExists());
        }

        $fileExists->shouldHaveBeenCalledOnce();
        $getMeta->shouldNotHaveBeenCalled();
        $download->shouldHaveBeenCalledOnce();
        $this->lock->acquire(true)->shouldHaveBeenCalledOnce();
        $this->lock->release()->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideBigDays
     */
    public function exceptionIsThrownWhenOlderDbIsTooOldAndDownloadFails(int $days): void
    {
        $fileExists = $this->dbUpdater->databaseFileExists()->willReturn(true);
        $getMeta = $this->geoLiteDbReader->metadata()->willReturn($this->buildMetaWithBuildEpoch(
            Chronos::now()->subDays($days)->getTimestamp(),
        ));
        $prev = new RuntimeException('');
        $download = $this->dbUpdater->downloadFreshCopy(null)->willThrow($prev);

        try {
            $this->geolocationDbUpdater()->checkDbUpdate();
            self::assertTrue(false); // If this is reached, the test will fail
        } catch (Throwable $e) {
            /** @var GeolocationDbUpdateFailedException $e */
            self::assertInstanceOf(GeolocationDbUpdateFailedException::class, $e);
            self::assertSame($prev, $e->getPrevious());
            self::assertTrue($e->olderDbExists());
        }

        $fileExists->shouldHaveBeenCalledOnce();
        $getMeta->shouldHaveBeenCalledOnce();
        $download->shouldHaveBeenCalledOnce();
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
        $fileExists = $this->dbUpdater->databaseFileExists()->willReturn(true);
        $getMeta = $this->geoLiteDbReader->metadata()->willReturn($this->buildMetaWithBuildEpoch($buildEpoch));
        $download = $this->dbUpdater->downloadFreshCopy(null)->will(function (): void {
        });

        $result = $this->geolocationDbUpdater()->checkDbUpdate();

        self::assertEquals(GeolocationResult::DB_IS_UP_TO_DATE, $result);
        $fileExists->shouldHaveBeenCalledOnce();
        $getMeta->shouldHaveBeenCalledOnce();
        $download->shouldNotHaveBeenCalled();
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
        $fileExists = $this->dbUpdater->databaseFileExists()->willReturn(true);
        $getMeta = $this->geoLiteDbReader->metadata()->willReturn($this->buildMetaWithBuildEpoch('invalid'));
        $download = $this->dbUpdater->downloadFreshCopy(null)->will(function (): void {
        });

        $this->expectException(GeolocationDbUpdateFailedException::class);
        $this->expectExceptionMessage(
            'Build epoch with value "invalid" from existing geolocation database, could not be parsed to integer.',
        );
        $fileExists->shouldBeCalledOnce();
        $getMeta->shouldBeCalledOnce();
        $download->shouldNotBeCalled();

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

        self::assertEquals(GeolocationResult::CHECK_SKIPPED, $result);
        $this->dbUpdater->databaseFileExists(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->geoLiteDbReader->metadata(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function provideTrackingOptions(): iterable
    {
        yield 'disableTracking' => [new TrackingOptions(disableTracking: true)];
        yield 'disableIpTracking' => [new TrackingOptions(disableIpTracking: true)];
        yield 'both' => [new TrackingOptions(disableTracking: true, disableIpTracking: true)];
    }

    private function geolocationDbUpdater(?TrackingOptions $options = null): GeolocationDbUpdater
    {
        $locker = $this->prophesize(Lock\LockFactory::class);
        $locker->createLock(Argument::type('string'))->willReturn($this->lock->reveal());

        return new GeolocationDbUpdater(
            $this->dbUpdater->reveal(),
            $this->geoLiteDbReader->reveal(),
            $locker->reveal(),
            $options ?? new TrackingOptions(),
        );
    }
}
