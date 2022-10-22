<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\CLI\GeoLite\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\CLI\GeoLite\GeolocationResult;
use Shlinkio\Shlink\Core\EventDispatcher\Event\GeoLiteDbCreated;
use Shlinkio\Shlink\Core\EventDispatcher\UpdateGeoLiteDb;

use function Functional\map;

class UpdateGeoLiteDbTest extends TestCase
{
    private UpdateGeoLiteDb $listener;
    private MockObject $dbUpdater;
    private MockObject $logger;
    private MockObject $eventDispatcher;

    protected function setUp(): void
    {
        $this->dbUpdater = $this->createMock(GeolocationDbUpdaterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new UpdateGeoLiteDb($this->dbUpdater, $this->logger, $this->eventDispatcher);
    }

    /** @test */
    public function exceptionWhileUpdatingDbLogsError(): void
    {
        $e = new RuntimeException();

        $this->dbUpdater->expects($this->once())->method('checkDbUpdate')->withAnyParameters()->willThrowException($e);
        $this->logger->expects($this->once())->method('error')->with(
            $this->equalTo('GeoLite2 database download failed. {e}'),
            $this->equalTo(['e' => $e]),
        );
        $this->logger->expects($this->never())->method('notice');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->listener)();
    }

    /**
     * @test
     * @dataProvider provideFlags
     */
    public function noticeMessageIsPrintedWhenFirstCallbackIsInvoked(bool $oldDbExists, string $expectedMessage): void
    {
        $this->dbUpdater->expects($this->once())->method('checkDbUpdate')->withAnyParameters()->willReturnCallback(
            function (callable $firstCallback) use ($oldDbExists): GeolocationResult {
                $firstCallback($oldDbExists);
                return GeolocationResult::DB_IS_UP_TO_DATE;
            },
        );
        $this->logger->expects($this->once())->method('notice')->with($this->equalTo($expectedMessage));
        $this->logger->expects($this->never())->method('error');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->listener)();
    }

    public function provideFlags(): iterable
    {
        yield 'existing old db' => [true, 'Updating GeoLite2 db file...'];
        yield 'not existing old db' => [false, 'Downloading GeoLite2 db file...'];
    }

    /**
     * @test
     * @dataProvider provideDownloaded
     */
    public function noticeMessageIsPrintedWhenSecondCallbackIsInvoked(
        int $total,
        int $downloaded,
        bool $oldDbExists,
        ?string $expectedMessage,
    ): void {
        $this->dbUpdater->expects($this->once())->method('checkDbUpdate')->withAnyParameters()->willReturnCallback(
            function ($_, callable $secondCallback) use ($total, $downloaded, $oldDbExists): GeolocationResult {
                // Invoke several times to ensure the log is printed only once
                $secondCallback($total, $downloaded, $oldDbExists);
                $secondCallback($total, $downloaded, $oldDbExists);
                $secondCallback($total, $downloaded, $oldDbExists);

                return GeolocationResult::DB_UPDATED;
            },
        );
        $logNoticeExpectation = $expectedMessage !== null ? $this->once() : $this->never();
        $this->logger->expects($logNoticeExpectation)->method('notice')->with($this->equalTo($expectedMessage));
        $this->logger->expects($this->never())->method('error');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->listener)();
    }

    public function provideDownloaded(): iterable
    {
        yield [100, 0, true, null];
        yield [100, 0, false, null];
        yield [100, 99, true, null];
        yield [100, 99, false, null];
        yield [100, 100, true, 'Finished updating GeoLite2 db file'];
        yield [100, 100, false, 'Finished downloading GeoLite2 db file'];
        yield [100, 101, true, 'Finished updating GeoLite2 db file'];
        yield [100, 101, false, 'Finished downloading GeoLite2 db file'];
    }

    /**
     * @test
     * @dataProvider provideGeolocationResults
     */
    public function dispatchesEventOnlyWhenDbFileHasBeenCreatedForTheFirstTime(
        GeolocationResult $result,
        int $expectedDispatches,
    ): void {
        $this->dbUpdater->expects($this->once())->method('checkDbUpdate')->withAnyParameters()->willReturn($result);
        $this->eventDispatcher->expects($this->exactly($expectedDispatches))->method('dispatch')->with(
            $this->equalTo(new GeoLiteDbCreated()),
        );

        ($this->listener)();
    }

    public function provideGeolocationResults(): iterable
    {
        return map(GeolocationResult::cases(), static fn (GeolocationResult $value) => [
            $value,
            $value === GeolocationResult::DB_CREATED ? 1 : 0,
        ]);
    }
}
