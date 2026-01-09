<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Geolocation;

use Cake\Chronos\Chronos;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\Core\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\Core\Geolocation\Entity\GeolocationDbUpdate;
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

#[AllowMockObjectsWithoutExpectations]
class GeolocationDbUpdaterTest extends TestCase
{
    private MockObject & DbUpdaterInterface $dbUpdater;
    private MockObject & Lock\LockInterface $lock;
    private MockObject & EntityManagerInterface $em;
    /** @var MockObject&EntityRepository<GeolocationDbUpdate> */
    private MockObject & EntityRepository $repo;
    /** @var GeolocationDownloadProgressHandlerInterface&object{beforeDownloadCalled: bool, handleProgressCalled: bool} */
    private GeolocationDownloadProgressHandlerInterface $progressHandler;

    protected function setUp(): void
    {
        $this->dbUpdater = $this->createMock(DbUpdaterInterface::class);

        $this->lock = $this->createMock(Lock\SharedLockInterface::class);
        $this->lock->method('acquire')->willReturn(true);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->willReturn($this->repo);

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
    public function properResultIsReturnedIfMostRecentUpdateIsInProgress(): void
    {
        $this->repo->expects($this->once())->method('findBy')->willReturn([GeolocationDbUpdate::withReason('')]);
        $this->dbUpdater->expects($this->never())->method('databaseFileExists');

        $result = $this->geolocationDbUpdater()->checkDbUpdate();

        self::assertEquals(GeolocationResult::UPDATE_IN_PROGRESS, $result);
    }

    #[Test]
    public function properResultIsReturnedIfMaxConsecutiveErrorsAreReached(): void
    {
        $this->repo->expects($this->once())->method('findBy')->willReturn([
            GeolocationDbUpdate::withReason('')->finishWithError(''),
            GeolocationDbUpdate::withReason('')->finishWithError(''),
            GeolocationDbUpdate::withReason('')->finishWithError(''),
        ]);
        $this->dbUpdater->expects($this->never())->method('databaseFileExists');

        $result = $this->geolocationDbUpdater()->checkDbUpdate();

        self::assertEquals(GeolocationResult::MAX_ERRORS_REACHED, $result);
    }

    #[Test]
    public function properResultIsReturnedWhenLicenseIsMissing(): void
    {
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(false);
        $this->dbUpdater->expects($this->once())->method('downloadFreshCopy')->willThrowException(
            new MissingLicenseException(''),
        );
        $this->repo->expects($this->once())->method('findBy')->willReturn([
            GeolocationDbUpdate::withReason('')->finishSuccessfully(),
        ]);

        $result = $this->geolocationDbUpdater()->checkDbUpdate($this->progressHandler);

        self::assertTrue($this->progressHandler->beforeDownloadCalled);
        self::assertEquals(GeolocationResult::LICENSE_MISSING, $result);
    }

    #[Test, DataProvider('provideDbDoesNotExist')]
    public function exceptionIsThrownWhenOlderDbDoesNotExistAndDownloadFails(Closure $setUp): void
    {
        $prev = new DbUpdateException('');

        $expectedReason = $setUp($this);

        $this->dbUpdater->expects($this->once())->method('downloadFreshCopy')->with(
            $this->isInstanceOf(Closure::class),
        )->willThrowException($prev);
        $this->em->expects($this->once())->method('persist')->with($this->callback(
            fn (GeolocationDbUpdate $newUpdate): bool => $newUpdate->reason === $expectedReason,
        ));

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

    public static function provideDbDoesNotExist(): iterable
    {
        yield 'file does not exist' => [function (self $test): string {
            $test->repo->expects($test->once())->method('findBy')->willReturn([
                GeolocationDbUpdate::withReason('')->finishSuccessfully(),
            ]);
            $test->dbUpdater->expects($test->once())->method('databaseFileExists')->willReturn(false);
            return 'Geolocation db file does not exist';
        }];
        yield 'no attempts' => [function (self $test): string {
            $test->repo->expects($test->once())->method('findBy')->willReturn([]);
            $test->dbUpdater->expects($test->never())->method('databaseFileExists');
            return 'No download attempts tracked for this instance';
        }];
    }

    #[Test, DataProvider('provideBigDays')]
    public function exceptionIsThrownWhenOlderDbIsOldEnoughAndDownloadFails(int $days): void
    {
        $prev = new DbUpdateException('');
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(true);
        $this->dbUpdater->expects($this->once())->method('downloadFreshCopy')->with(
            $this->isInstanceOf(Closure::class),
        )->willThrowException($prev);
        $this->repo->expects($this->once())->method('findBy')->willReturn([self::createFinishedOldUpdate($days)]);

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
        yield [31];
        yield [50];
        yield [75];
        yield [100];
    }

    #[Test]
    public function exceptionIsThrownWhenUnknownErrorHappens(): void
    {
        $this->dbUpdater->expects($this->once())->method('downloadFreshCopy')->with(
            $this->isInstanceOf(Closure::class),
        )->willThrowException(new RuntimeException('An error occurred'));

        $newUpdate = null;
        $this->em->expects($this->once())->method('persist')->with($this->callback(
            function (GeolocationDbUpdate $u) use (&$newUpdate): bool {
                $newUpdate = $u;
                return true;
            },
        ));

        try {
            $this->geolocationDbUpdater()->checkDbUpdate($this->progressHandler);
            self::fail();
        } catch (Throwable) {
        }

        self::assertTrue($this->progressHandler->beforeDownloadCalled);
        self::assertNotNull($newUpdate);
        self::assertTrue($newUpdate->isError());
    }

    #[Test, DataProvider('provideNotAldEnoughDays')]
    public function databaseIsNotUpdatedIfItIsNewEnough(int $days): void
    {
        $this->dbUpdater->expects($this->once())->method('databaseFileExists')->willReturn(true);
        $this->dbUpdater->expects($this->never())->method('downloadFreshCopy');
        $this->repo->expects($this->once())->method('findBy')->willReturn([self::createFinishedOldUpdate($days)]);

        $result = $this->geolocationDbUpdater()->checkDbUpdate();

        self::assertEquals(GeolocationResult::DB_IS_UP_TO_DATE, $result);
    }

    public static function provideNotAldEnoughDays(): iterable
    {
        return array_map(static fn (int $value) => [$value], range(0, 29));
    }

    #[Test, DataProvider('provideUpdatesThatWillDownload')]
    public function properResultIsReturnedWhenDownloadSucceeds(
        array $updates,
        GeolocationResult $expectedResult,
        string $expectedReason,
    ): void {
        $this->repo->expects($this->once())->method('findBy')->willReturn($updates);
        $this->dbUpdater->method('databaseFileExists')->willReturn(true);
        $this->dbUpdater->expects($this->once())->method('downloadFreshCopy');
        $this->em->expects($this->once())->method('persist')->with($this->callback(
            fn (GeolocationDbUpdate $newUpdate): bool => $newUpdate->reason === $expectedReason,
        ));

        $result = $this->geolocationDbUpdater()->checkDbUpdate();

        self::assertEquals($expectedResult, $result);
    }

    public static function provideUpdatesThatWillDownload(): iterable
    {
        yield 'no updates' => [[], GeolocationResult::DB_CREATED, 'No download attempts tracked for this instance'];
        yield 'old successful update' => [
            [self::createFinishedOldUpdate(days: 31)],
            GeolocationResult::DB_UPDATED,
            'Last successful attempt is old enough',
        ];
        yield 'not enough errors' => [
            [self::createFinishedOldUpdate(days: 3, successful: false)],
            GeolocationResult::DB_UPDATED,
            'Max consecutive errors not reached',
        ];
    }

    public static function createFinishedOldUpdate(int $days, bool $successful = true): GeolocationDbUpdate
    {
        Chronos::setTestNow(Chronos::now()->subDays($days));
        $update = GeolocationDbUpdate::withReason('');
        if ($successful) {
            $update->finishSuccessfully();
        } else {
            $update->finishWithError('');
        }
        Chronos::setTestNow();

        return $update;
    }

    #[Test, DataProvider('provideTrackingOptions')]
    public function downloadDbIsSkippedIfTrackingIsDisabled(TrackingOptions $options): void
    {
        $this->dbUpdater->expects($this->never())->method('databaseFileExists');
        $this->em->expects($this->never())->method('getRepository');

        $result = $this->geolocationDbUpdater($options)->checkDbUpdate();

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
        $locker->method('createLock')->willReturn($this->lock);

        return new GeolocationDbUpdater($this->dbUpdater, $locker, $options ?? new TrackingOptions(), $this->em, 3);
    }
}
