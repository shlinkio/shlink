<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Util;

use Cake\Chronos\Chronos;
use GeoIp2\Database\Reader;
use InvalidArgumentException;
use MaxMind\Db\Reader\Metadata;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdater;
use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Shlinkio\Shlink\Common\IpGeolocation\GeoLite2\DbUpdaterInterface;
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

    public function setUp(): void
    {
        $this->dbUpdater = $this->prophesize(DbUpdaterInterface::class);
        $this->geoLiteDbReader = $this->prophesize(Reader::class);

        $this->geolocationDbUpdater = new GeolocationDbUpdater(
            $this->dbUpdater->reveal(),
            $this->geoLiteDbReader->reveal()
        );
    }

    /** @test */
    public function exceptionIsThrownWhenOlderDbDoesNotExistAndDownloadFails(): void
    {
        $getMeta = $this->geoLiteDbReader->metadata()->willThrow(InvalidArgumentException::class);
        $prev = new RuntimeException('');
        $download = $this->dbUpdater->downloadFreshCopy(null)->willThrow($prev);

        try {
            $this->geolocationDbUpdater->checkDbUpdate();
            $this->assertTrue(false); // If this is reached, the test will fail
        } catch (Throwable $e) {
            /** @var GeolocationDbUpdateFailedException $e */
            $this->assertInstanceOf(GeolocationDbUpdateFailedException::class, $e);
            $this->assertSame($prev, $e->getPrevious());
            $this->assertFalse($e->olderDbExists());
        }

        $getMeta->shouldHaveBeenCalledOnce();
        $download->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideDaysBiggerThanSeven
     */
    public function exceptionIsThrownWhenOlderDbIsTooOldAndDownloadFails(int $days): void
    {
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

        $getMeta->shouldHaveBeenCalledOnce();
        $download->shouldHaveBeenCalledOnce();
    }

    public function provideDaysBiggerThanSeven(): iterable
    {
        yield [8];
        yield [9];
        yield [10];
        yield [100];
    }

    /**
     * @test
     * @dataProvider provideDaysSmallerThanSeven
     */
    public function databaseIsNotUpdatedIfItIsYoungerThanOneWeek(int $days): void
    {
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

        $getMeta->shouldHaveBeenCalledOnce();
        $download->shouldNotHaveBeenCalled();
    }

    public function provideDaysSmallerThanSeven(): iterable
    {
        return map(range(0, 6), function (int $days) {
            return [$days];
        });
    }
}
