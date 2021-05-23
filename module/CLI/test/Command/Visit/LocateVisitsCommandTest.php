<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Visit\DownloadGeoLiteDbCommand;
use Shlinkio\Shlink\CLI\Command\Visit\LocateVisitsCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\VisitGeolocationHelperInterface;
use Shlinkio\Shlink\Core\Visit\VisitLocator;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock;

use function sprintf;

use const PHP_EOL;

class LocateVisitsCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $visitService;
    private ObjectProphecy $ipResolver;
    private ObjectProphecy $lock;
    private ObjectProphecy $downloadDbCommand;

    public function setUp(): void
    {
        $this->visitService = $this->prophesize(VisitLocator::class);
        $this->ipResolver = $this->prophesize(IpLocationResolverInterface::class);

        $locker = $this->prophesize(Lock\LockFactory::class);
        $this->lock = $this->prophesize(Lock\LockInterface::class);
        $this->lock->acquire(false)->willReturn(true);
        $this->lock->release()->will(function (): void {
        });
        $locker->createLock(Argument::type('string'), 600.0, false)->willReturn($this->lock->reveal());

        $command = new LocateVisitsCommand(
            $this->visitService->reveal(),
            $this->ipResolver->reveal(),
            $locker->reveal(),
        );

        $this->downloadDbCommand = $this->createCommandMock(DownloadGeoLiteDbCommand::NAME);
        $this->downloadDbCommand->run(Argument::cetera())->willReturn(ExitCodes::EXIT_SUCCESS);

        $this->commandTester = $this->testerForCommand($command, $this->downloadDbCommand->reveal());
    }

    /**
     * @test
     * @dataProvider provideArgs
     */
    public function expectedSetOfVisitsIsProcessedBasedOnArgs(
        int $expectedUnlocatedCalls,
        int $expectedEmptyCalls,
        int $expectedAllCalls,
        bool $expectWarningPrint,
        array $args,
    ): void {
        $visit = Visit::forValidShortUrl(ShortUrl::createEmpty(), new Visitor('', '', '1.2.3.4', ''));
        $location = VisitLocation::fromGeolocation(Location::emptyInstance());
        $mockMethodBehavior = $this->invokeHelperMethods($visit, $location);

        $locateVisits = $this->visitService->locateUnlocatedVisits(Argument::cetera())->will($mockMethodBehavior);
        $locateEmptyVisits = $this->visitService->locateVisitsWithEmptyLocation(Argument::cetera())->will(
            $mockMethodBehavior,
        );
        $locateAllVisits = $this->visitService->locateAllVisits(Argument::cetera())->will($mockMethodBehavior);
        $resolveIpLocation = $this->ipResolver->resolveIpLocation(Argument::any())->willReturn(
            Location::emptyInstance(),
        );

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute($args);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Processing IP 1.2.3.0', $output);
        if ($expectWarningPrint) {
            self::assertStringContainsString('Continue at your own', $output);
        } else {
            self::assertStringNotContainsString('Continue at your own', $output);
        }
        $locateVisits->shouldHaveBeenCalledTimes($expectedUnlocatedCalls);
        $locateEmptyVisits->shouldHaveBeenCalledTimes($expectedEmptyCalls);
        $locateAllVisits->shouldHaveBeenCalledTimes($expectedAllCalls);
        $resolveIpLocation->shouldHaveBeenCalledTimes(
            $expectedUnlocatedCalls + $expectedEmptyCalls + $expectedAllCalls,
        );
    }

    public function provideArgs(): iterable
    {
        yield 'no args' => [1, 0, 0, false, []];
        yield 'retry' => [1, 1, 0, false, ['--retry' => true]];
        yield 'all' => [0, 0, 1, true, ['--retry' => true, '--all' => true]];
    }

    /**
     * @test
     * @dataProvider provideIgnoredAddresses
     */
    public function localhostAndEmptyAddressesAreIgnored(?string $address, string $message): void
    {
        $visit = Visit::forValidShortUrl(ShortUrl::createEmpty(), new Visitor('', '', $address, ''));
        $location = VisitLocation::fromGeolocation(Location::emptyInstance());

        $locateVisits = $this->visitService->locateUnlocatedVisits(Argument::cetera())->will(
            $this->invokeHelperMethods($visit, $location),
        );
        $resolveIpLocation = $this->ipResolver->resolveIpLocation(Argument::any())->willReturn(
            Location::emptyInstance(),
        );

        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString($message, $output);
        if (empty($address)) {
            self::assertStringNotContainsString('Processing IP', $output);
        } else {
            self::assertStringContainsString('Processing IP', $output);
        }
        $locateVisits->shouldHaveBeenCalledOnce();
        $resolveIpLocation->shouldNotHaveBeenCalled();
    }

    public function provideIgnoredAddresses(): iterable
    {
        yield 'with empty address' => ['', 'Ignored visit with no IP address'];
        yield 'with null address' => [null, 'Ignored visit with no IP address'];
        yield 'with localhost address' => [IpAddress::LOCALHOST, 'Ignored localhost address'];
    }

    /** @test */
    public function errorWhileLocatingIpIsDisplayed(): void
    {
        $visit = Visit::forValidShortUrl(ShortUrl::createEmpty(), new Visitor('', '', '1.2.3.4', ''));
        $location = VisitLocation::fromGeolocation(Location::emptyInstance());

        $locateVisits = $this->visitService->locateUnlocatedVisits(Argument::cetera())->will(
            $this->invokeHelperMethods($visit, $location),
        );
        $resolveIpLocation = $this->ipResolver->resolveIpLocation(Argument::any())->willThrow(WrongIpException::class);

        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('An error occurred while locating IP. Skipped', $output);
        $locateVisits->shouldHaveBeenCalledOnce();
        $resolveIpLocation->shouldHaveBeenCalledOnce();
    }

    private function invokeHelperMethods(Visit $visit, VisitLocation $location): callable
    {
        return function (array $args) use ($visit, $location): void {
            /** @var VisitGeolocationHelperInterface $helper */
            [$helper] = $args;

            $helper->geolocateVisit($visit);
            $helper->onVisitLocated($location, $visit);
        };
    }

    /** @test */
    public function noActionIsPerformedIfLockIsAcquired(): void
    {
        $this->lock->acquire(false)->willReturn(false);

        $locateVisits = $this->visitService->locateUnlocatedVisits(Argument::cetera())->will(function (): void {
        });
        $resolveIpLocation = $this->ipResolver->resolveIpLocation(Argument::any())->willReturn([]);

        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString(
            sprintf('Command "%s" is already in progress. Skipping.', LocateVisitsCommand::NAME),
            $output,
        );
        $locateVisits->shouldNotHaveBeenCalled();
        $resolveIpLocation->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function showsProperMessageWhenGeoLiteUpdateFails(): void
    {
        $this->downloadDbCommand->run(Argument::cetera())->willReturn(ExitCodes::EXIT_FAILURE);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('It is not possible to locate visits without a GeoLite2 db file.', $output);
        $this->visitService->locateUnlocatedVisits(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function providingAllFlagOnItsOwnDisplaysNotice(): void
    {
        $this->commandTester->execute(['--all' => true]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('The --all flag has no effect on its own', $output);
    }

    /**
     * @test
     * @dataProvider provideAbortInputs
     */
    public function processingAllCancelsCommandIfUserDoesNotActivelyAgreeToConfirmation(array $inputs): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Execution aborted');

        $this->commandTester->setInputs($inputs);
        $this->commandTester->execute(['--all' => true, '--retry' => true]);
    }

    public function provideAbortInputs(): iterable
    {
        yield 'n' => [['n']];
        yield 'no' => [['no']];
        yield 'default' => [[PHP_EOL]];
    }
}
