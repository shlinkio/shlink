<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Util;

use Cake\Chronos\Chronos;
use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\Metadata;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdater;
use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Shlinkio\Shlink\Common\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Lock;
use Throwable;

use function Functional\map;
use function range;

class GeolocationDbUpdaterTest extends TestCase
{
    /** @var GeolocationDbUpdater */
    private $geolocationDbUpdater;
    /** @var ObjectProphecy */
    private $dbUpdater;
    /** @var ObjectProphecy */
    private $geoLiteDbReader;
    /** @var ObjectProphecy */
    private $locker;
    /** @var ObjectProphecy */
    private $lock;

    public function setUp(): void
    {
        $this->dbUpdater = $this->prophesize(DbUpdaterInterface::class);
        $this->geoLiteDbReader = $this->prophesize(Reader::class);

        $this->locker = $this->prophesize(Lock\Factory::class);
        $this->lock = $this->prophesize(Lock\LockInterface::class);
        $this->lock->acquire(true)->willReturn(true);
        $this->lock->release()->will(function () {
        });
        $this->locker->createLock(Argument::type('string'))->willReturn($this->lock->reveal());

        $this->geolocationDbUpdater = new GeolocationDbUpdater(
            $this->dbUpdater->reveal(),
            $this->geoLiteDbReader->reveal(),
            $this->locker->reveal()
        );
    }

    /** @test */
    public function exceptionIsThrownWhenOlderDbDoesNotExistAndDownloadFails(): void
    {
        $mustBeUpdated = function () {
            $this->assertTrue(true);
        };
        $prev = new RuntimeException('');

        $fileExists = $this->dbUpdater->databaseFileExists()->willReturn(false);
        $getMeta = $this->geoLiteDbReader->metadata();
        $download = $this->dbUpdater->downloadFreshCopy(null)->willThrow($prev);

        try {
            $this->geolocationDbUpdater->checkDbUpdate($mustBeUpdated);
            $this->assertTrue(false); // If this is reached, the test will fail
        } catch (Throwable $e) {
            /** @var GeolocationDbUpdateFailedException $e */
            $this->assertInstanceOf(GeolocationDbUpdateFailedException::class, $e);
            $this->assertSame($prev, $e->getPrevious());
            $this->assertFalse($e->olderDbExists());
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
        $getMeta = $this->geoLiteDbReader->metadata()->willReturn(new Metadata([
            'binary_format_major_version' => '',
            'binary_format_minor_version' => '',
            'build_epoch' => Chronos::now()->subDays($days)->getTimestamp(),
            'database_type' => '',
            'languages' => '',
            'description' => '',
            'ip_version' => '',
            'node_count' => 1,
            'record_size' => 4,
        ]));
        $prev = new RuntimeException('');
        $download = $this->dbUpdater->downloadFreshCopy(null)->willThrow($prev);

        try {
            $this->geolocationDbUpdater->checkDbUpdate();
            $this->assertTrue(false); // If this is reached, the test will fail
        } catch (Throwable $e) {
            /** @var GeolocationDbUpdateFailedException $e */
            $this->assertInstanceOf(GeolocationDbUpdateFailedException::class, $e);
            $this->assertSame($prev, $e->getPrevious());
            $this->assertTrue($e->olderDbExists());
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
    public function databaseIsNotUpdatedIfItIsYoungerThanOneWeek(int $days): void
    {
        $fileExists = $this->dbUpdater->databaseFileExists()->willReturn(true);
        $getMeta = $this->geoLiteDbReader->metadata()->willReturn(new Metadata([
            'binary_format_major_version' => '',
            'binary_format_minor_version' => '',
            'build_epoch' => Chronos::now()->subDays($days)->getTimestamp(),
            'database_type' => '',
            'languages' => '',
            'description' => '',
            'ip_version' => '',
            'node_count' => 1,
            'record_size' => 4,
        ]));
        $download = $this->dbUpdater->downloadFreshCopy(null)->will(function () {
        });

        $this->geolocationDbUpdater->checkDbUpdate();

        $fileExists->shouldHaveBeenCalledOnce();
        $getMeta->shouldHaveBeenCalledOnce();
        $download->shouldNotHaveBeenCalled();
    }

    public function provideSmallDays(): iterable
    {
        return map(range(0, 34), function (int $days) {
            return [$days];
        });
    }
}
