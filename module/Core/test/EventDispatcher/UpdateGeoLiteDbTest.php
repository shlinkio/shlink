<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
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
    use ProphecyTrait;

    private UpdateGeoLiteDb $listener;
    private ObjectProphecy $dbUpdater;
    private ObjectProphecy $logger;
    private ObjectProphecy $eventDispatcher;

    protected function setUp(): void
    {
        $this->dbUpdater = $this->prophesize(GeolocationDbUpdaterInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->listener = new UpdateGeoLiteDb(
            $this->dbUpdater->reveal(),
            $this->logger->reveal(),
            $this->eventDispatcher->reveal(),
        );
    }

    /** @test */
    public function exceptionWhileUpdatingDbLogsError(): void
    {
        $e = new RuntimeException();

        $checkDbUpdate = $this->dbUpdater->checkDbUpdate(Argument::cetera())->willThrow($e);
        $logError = $this->logger->error('GeoLite2 database download failed. {e}', ['e' => $e]);

        ($this->listener)();

        $checkDbUpdate->shouldHaveBeenCalledOnce();
        $logError->shouldHaveBeenCalledOnce();
        $this->logger->notice(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->eventDispatcher->dispatch(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideFlags
     */
    public function noticeMessageIsPrintedWhenFirstCallbackIsInvoked(bool $oldDbExists, string $expectedMessage): void
    {
        $checkDbUpdate = $this->dbUpdater->checkDbUpdate(Argument::cetera())->will(
            function (array $args) use ($oldDbExists): GeolocationResult {
                [$firstCallback] = $args;
                $firstCallback($oldDbExists);

                return GeolocationResult::DB_IS_UP_TO_DATE;
            },
        );
        $logNotice = $this->logger->notice($expectedMessage);

        ($this->listener)();

        $checkDbUpdate->shouldHaveBeenCalledOnce();
        $logNotice->shouldHaveBeenCalledOnce();
        $this->logger->error(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->eventDispatcher->dispatch(Argument::cetera())->shouldNotHaveBeenCalled();
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
        $checkDbUpdate = $this->dbUpdater->checkDbUpdate(Argument::cetera())->will(
            function (array $args) use ($total, $downloaded, $oldDbExists): GeolocationResult {
                [, $secondCallback] = $args;

                // Invoke several times to ensure the log is printed only once
                $secondCallback($total, $downloaded, $oldDbExists);
                $secondCallback($total, $downloaded, $oldDbExists);
                $secondCallback($total, $downloaded, $oldDbExists);

                return GeolocationResult::DB_UPDATED;
            },
        );
        $logNotice = $this->logger->notice($expectedMessage ?? Argument::cetera());

        ($this->listener)();

        if ($expectedMessage !== null) {
            $logNotice->shouldHaveBeenCalledOnce();
        } else {
            $logNotice->shouldNotHaveBeenCalled();
        }
        $checkDbUpdate->shouldHaveBeenCalledOnce();
        $this->logger->error(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->eventDispatcher->dispatch(Argument::cetera())->shouldNotHaveBeenCalled();
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
        $checkDbUpdate = $this->dbUpdater->checkDbUpdate(Argument::cetera())->willReturn($result);

        ($this->listener)();

        $checkDbUpdate->shouldHaveBeenCalledOnce();
        $this->eventDispatcher->dispatch(new GeoLiteDbCreated())->shouldHaveBeenCalledTimes($expectedDispatches);
    }

    public function provideGeolocationResults(): iterable
    {
        return map(GeolocationResult::cases(), static fn (GeolocationResult $value) => [
            $value,
            $value === GeolocationResult::DB_CREATED ? 1 : 0,
        ]);
    }
}
