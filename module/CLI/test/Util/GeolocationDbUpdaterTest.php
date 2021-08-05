<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Util;

use Cake\Chronos\Chronos;
use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\Metadata;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdater;
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
    private TrackingOptions $trackingOptions;

    public function setUp(): void
    {
        $this->dbUpdater = $this->prophesize(DbUpdaterInterface::class);
        $this->geoLiteDbReader = $this->prophesize(Reader::class);
        $this->trackingOptions = new TrackingOptions();

        $locker = $this->prophesize(Lock\LockFactory::class);
        $lock = $this->prophesize(Lock\LockInterface::class);
        $lock->acquire(true)->willReturn(true);
        $lock->release()->will(function (): void {
        });
        $locker->createLock(Argument::type('string'))->willReturn($lock->reveal());

        $this->geolocationDbUpdater = new GeolocationDbUpdater(
            $this->dbUpdater->reveal(),
            $this->geoLiteDbReader->reveal(),
            $locker->reveal(),
            $this->trackingOptions,
        );
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
            $this->geolocationDbUpdater->checkDbUpdate($mustBeUpdated);
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
            $this->geolocationDbUpdater->checkDbUpdate();
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
    public function databaseIsNotUpdatedIfItIsYoungerThanOneWeek(string|int $buildEpoch): void
    {
        $fileExists = $this->dbUpdater->databaseFileExists()->willReturn(true);
        $getMeta = $this->geoLiteDbReader->metadata()->willReturn($this->buildMetaWithBuildEpoch($buildEpoch));
        $download = $this->dbUpdater->downloadFreshCopy(null)->will(function (): void {
        });

        $this->geolocationDbUpdater->checkDbUpdate();

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

        $this->geolocationDbUpdater->checkDbUpdate();
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
    public function downloadDbIsSkippedIfTrackingIsDisabled(array $props): void
    {
        foreach ($props as $prop) {
            $this->trackingOptions->{$prop} = true;
        }

        $this->geolocationDbUpdater->checkDbUpdate();

        $this->dbUpdater->databaseFileExists(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->geoLiteDbReader->metadata(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function provideTrackingOptions(): iterable
    {
        yield 'disableTracking' => [['disableTracking']];
        yield 'disableIpTracking' => [['disableIpTracking']];
        yield 'both' => [['disableTracking', 'disableIpTracking']];
    }
}
