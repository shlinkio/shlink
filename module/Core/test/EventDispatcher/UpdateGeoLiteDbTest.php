<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\Core\EventDispatcher\UpdateGeoLiteDb;

class UpdateGeoLiteDbTest extends TestCase
{
    use ProphecyTrait;

    private UpdateGeoLiteDb $listener;
    private ObjectProphecy $dbUpdater;
    private ObjectProphecy $logger;

    protected function setUp(): void
    {
        $this->dbUpdater = $this->prophesize(GeolocationDbUpdaterInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->listener = new UpdateGeoLiteDb($this->dbUpdater->reveal(), $this->logger->reveal());
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
    }

    /**
     * @test
     * @dataProvider provideFlags
     */
    public function noticeMessageIsPrintedWhenFirstCallbackIsInvoked(bool $oldDbExists, string $expectedMessage): void
    {
        $checkDbUpdate = $this->dbUpdater->checkDbUpdate(Argument::cetera())->will(
            function (array $args) use ($oldDbExists): void {
                [$firstCallback] = $args;
                $firstCallback($oldDbExists);
            },
        );
        $logNotice = $this->logger->notice($expectedMessage);

        ($this->listener)();

        $checkDbUpdate->shouldHaveBeenCalledOnce();
        $logNotice->shouldHaveBeenCalledOnce();
        $this->logger->error(Argument::cetera())->shouldNotHaveBeenCalled();
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
        ?string $expectedMessage
    ): void {
        $checkDbUpdate = $this->dbUpdater->checkDbUpdate(Argument::cetera())->will(
            function (array $args) use ($total, $downloaded, $oldDbExists): void {
                [, $secondCallback] = $args;

                // Invoke several times to ensure the log is printed only once
                $secondCallback($total, $downloaded, $oldDbExists);
                $secondCallback($total, $downloaded, $oldDbExists);
                $secondCallback($total, $downloaded, $oldDbExists);
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
}
