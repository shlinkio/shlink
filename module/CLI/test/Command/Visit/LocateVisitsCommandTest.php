<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Visit\DownloadGeoLiteDbCommand;
use Shlinkio\Shlink\CLI\Command\Visit\LocateVisitsCommand;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitGeolocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitLocatorInterface;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitToLocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock;

use function sprintf;

use const PHP_EOL;

class LocateVisitsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & VisitLocatorInterface $visitService;
    private MockObject & VisitToLocationHelperInterface $visitToLocation;
    private MockObject & Lock\LockInterface $lock;
    private MockObject & Command $downloadDbCommand;

    protected function setUp(): void
    {
        $this->visitService = $this->createMock(VisitLocatorInterface::class);
        $this->visitToLocation = $this->createMock(VisitToLocationHelperInterface::class);

        $locker = $this->createMock(Lock\LockFactory::class);
        $this->lock = $this->createMock(Lock\SharedLockInterface::class);
        $locker->method('createLock')->with($this->isType('string'), 600.0, false)->willReturn($this->lock);

        $command = new LocateVisitsCommand($this->visitService, $this->visitToLocation, $locker);

        $this->downloadDbCommand = CliTestUtils::createCommandMock(DownloadGeoLiteDbCommand::NAME);
        $this->commandTester = CliTestUtils::testerForCommand($command, $this->downloadDbCommand);
    }

    #[Test, DataProvider('provideArgs')]
    public function expectedSetOfVisitsIsProcessedBasedOnArgs(
        int $expectedUnlocatedCalls,
        int $expectedEmptyCalls,
        int $expectedAllCalls,
        bool $expectWarningPrint,
        array $args,
    ): void {
        $visit = Visit::forValidShortUrl(ShortUrl::createFake(), new Visitor('', '', '1.2.3.4', ''));
        $location = VisitLocation::fromGeolocation(Location::emptyInstance());
        $mockMethodBehavior = $this->invokeHelperMethods($visit, $location);

        $this->lock->method('acquire')->with($this->isFalse())->willReturn(true);
        $this->visitService->expects($this->exactly($expectedUnlocatedCalls))
                           ->method('locateUnlocatedVisits')
                           ->withAnyParameters()
                           ->willReturnCallback($mockMethodBehavior);
        $this->visitService->expects($this->exactly($expectedEmptyCalls))
                           ->method('locateVisitsWithEmptyLocation')
                           ->withAnyParameters()
                           ->willReturnCallback($mockMethodBehavior);
        $this->visitService->expects($this->exactly($expectedAllCalls))
                           ->method('locateAllVisits')
                           ->withAnyParameters()
                           ->willReturnCallback($mockMethodBehavior);
        $this->visitToLocation->expects(
            $this->exactly($expectedUnlocatedCalls + $expectedEmptyCalls + $expectedAllCalls),
        )->method('resolveVisitLocation')->withAnyParameters()->willReturn(Location::emptyInstance());
        $this->downloadDbCommand->method('run')->withAnyParameters()->willReturn(ExitCode::EXIT_SUCCESS);

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute($args);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Processing IP 1.2.3.0', $output);
        if ($expectWarningPrint) {
            self::assertStringContainsString('Continue at your own', $output);
        } else {
            self::assertStringNotContainsString('Continue at your own', $output);
        }
    }

    public static function provideArgs(): iterable
    {
        yield 'no args' => [1, 0, 0, false, []];
        yield 'retry' => [1, 1, 0, false, ['--retry' => true]];
        yield 'all' => [0, 0, 1, true, ['--retry' => true, '--all' => true]];
    }

    #[Test, DataProvider('provideIgnoredAddresses')]
    public function localhostAndEmptyAddressesAreIgnored(IpCannotBeLocatedException $e, string $message): void
    {
        $visit = Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::emptyInstance());
        $location = VisitLocation::fromGeolocation(Location::emptyInstance());

        $this->lock->method('acquire')->with($this->isFalse())->willReturn(true);
        $this->visitService->expects($this->once())
                           ->method('locateUnlocatedVisits')
                           ->withAnyParameters()
                           ->willReturnCallback($this->invokeHelperMethods($visit, $location));
        $this->visitToLocation->expects($this->once())->method('resolveVisitLocation')->willThrowException($e);
        $this->downloadDbCommand->method('run')->withAnyParameters()->willReturn(ExitCode::EXIT_SUCCESS);

        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Processing IP', $output);
        self::assertStringContainsString($message, $output);
    }

    public static function provideIgnoredAddresses(): iterable
    {
        yield 'empty address' => [IpCannotBeLocatedException::forEmptyAddress(), 'Ignored visit with no IP address'];
        yield 'localhost address' => [IpCannotBeLocatedException::forLocalhost(), 'Ignored localhost address'];
    }

    #[Test]
    public function errorWhileLocatingIpIsDisplayed(): void
    {
        $visit = Visit::forValidShortUrl(ShortUrl::createFake(), new Visitor('', '', '1.2.3.4', ''));
        $location = VisitLocation::fromGeolocation(Location::emptyInstance());

        $this->lock->method('acquire')->with($this->isFalse())->willReturn(true);
        $this->visitService->expects($this->once())
                           ->method('locateUnlocatedVisits')
                           ->withAnyParameters()
                           ->willReturnCallback($this->invokeHelperMethods($visit, $location));
        $this->visitToLocation->expects($this->once())->method('resolveVisitLocation')->willThrowException(
            IpCannotBeLocatedException::forError(WrongIpException::fromIpAddress('1.2.3.4')),
        );
        $this->downloadDbCommand->method('run')->withAnyParameters()->willReturn(ExitCode::EXIT_SUCCESS);

        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('An error occurred while locating IP. Skipped', $output);
    }

    private function invokeHelperMethods(Visit $visit, VisitLocation $location): callable
    {
        return static function (VisitGeolocationHelperInterface $helper) use ($visit, $location): void {
            $helper->geolocateVisit($visit);
            $helper->onVisitLocated($location, $visit);
        };
    }

    #[Test]
    public function noActionIsPerformedIfLockIsAcquired(): void
    {
        $this->lock->method('acquire')->with($this->isFalse())->willReturn(false);

        $this->visitService->expects($this->never())->method('locateUnlocatedVisits');
        $this->visitToLocation->expects($this->never())->method('resolveVisitLocation');
        $this->downloadDbCommand->method('run')->withAnyParameters()->willReturn(ExitCode::EXIT_SUCCESS);

        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString(
            sprintf('Command "%s" is already in progress. Skipping.', LocateVisitsCommand::NAME),
            $output,
        );
    }

    #[Test]
    public function showsProperMessageWhenGeoLiteUpdateFails(): void
    {
        $this->lock->method('acquire')->with($this->isFalse())->willReturn(true);
        $this->downloadDbCommand->method('run')->withAnyParameters()->willReturn(ExitCode::EXIT_FAILURE);
        $this->visitService->expects($this->never())->method('locateUnlocatedVisits');

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('It is not possible to locate visits without a GeoLite2 db file.', $output);
    }

    #[Test]
    public function providingAllFlagOnItsOwnDisplaysNotice(): void
    {
        $this->lock->method('acquire')->with($this->isFalse())->willReturn(true);
        $this->downloadDbCommand->method('run')->withAnyParameters()->willReturn(ExitCode::EXIT_SUCCESS);

        $this->commandTester->execute(['--all' => true]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('The --all flag has no effect on its own', $output);
    }

    #[Test, DataProvider('provideAbortInputs')]
    public function processingAllCancelsCommandIfUserDoesNotActivelyAgreeToConfirmation(array $inputs): void
    {
        $this->downloadDbCommand->method('run')->withAnyParameters()->willReturn(ExitCode::EXIT_SUCCESS);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Execution aborted');

        $this->commandTester->setInputs($inputs);
        $this->commandTester->execute(['--all' => true, '--retry' => true]);
    }

    public static function provideAbortInputs(): iterable
    {
        yield 'n' => [['n']];
        yield 'no' => [['no']];
        yield 'default' => [[PHP_EOL]];
    }
}
