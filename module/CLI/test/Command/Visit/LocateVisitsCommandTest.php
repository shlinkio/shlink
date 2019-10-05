<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Visit\LocateVisitsCommand;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Service\VisitService;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpApiLocationResolver;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock;

use function array_shift;
use function sprintf;

class LocateVisitsCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $visitService;
    /** @var ObjectProphecy */
    private $ipResolver;
    /** @var ObjectProphecy */
    private $locker;
    /** @var ObjectProphecy */
    private $lock;
    /** @var ObjectProphecy */
    private $dbUpdater;

    public function setUp(): void
    {
        $this->visitService = $this->prophesize(VisitService::class);
        $this->ipResolver = $this->prophesize(IpApiLocationResolver::class);
        $this->dbUpdater = $this->prophesize(GeolocationDbUpdaterInterface::class);

        $this->locker = $this->prophesize(Lock\Factory::class);
        $this->lock = $this->prophesize(Lock\LockInterface::class);
        $this->lock->acquire(false)->willReturn(true);
        $this->lock->release()->will(function () {
        });
        $this->locker->createLock(Argument::type('string'), 90.0, false)->willReturn($this->lock->reveal());

        $command = new LocateVisitsCommand(
            $this->visitService->reveal(),
            $this->ipResolver->reveal(),
            $this->locker->reveal(),
            $this->dbUpdater->reveal()
        );
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function allPendingVisitsAreProcessed(): void
    {
        $visit = new Visit(new ShortUrl(''), new Visitor('', '', '1.2.3.4'));
        $location = new VisitLocation(Location::emptyInstance());

        $locateVisits = $this->visitService->locateUnlocatedVisits(Argument::cetera())->will(
            function (array $args) use ($visit, $location) {
                $firstCallback = array_shift($args);
                $firstCallback($visit);

                $secondCallback = array_shift($args);
                $secondCallback($location, $visit);
            }
        );
        $resolveIpLocation = $this->ipResolver->resolveIpLocation(Argument::any())->willReturn(
            Location::emptyInstance()
        );

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Processing IP 1.2.3.0', $output);
        $locateVisits->shouldHaveBeenCalledOnce();
        $resolveIpLocation->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideIgnoredAddresses
     */
    public function localhostAndEmptyAddressesAreIgnored(?string $address, string $message): void
    {
        $visit = new Visit(new ShortUrl(''), new Visitor('', '', $address));
        $location = new VisitLocation(Location::emptyInstance());

        $locateVisits = $this->visitService->locateUnlocatedVisits(Argument::cetera())->will(
            function (array $args) use ($visit, $location) {
                $firstCallback = array_shift($args);
                $firstCallback($visit);

                $secondCallback = array_shift($args);
                $secondCallback($location, $visit);
            }
        );
        $resolveIpLocation = $this->ipResolver->resolveIpLocation(Argument::any())->willReturn(
            Location::emptyInstance()
        );

        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString($message, $output);
        if (empty($address)) {
            $this->assertStringNotContainsString('Processing IP', $output);
        } else {
            $this->assertStringContainsString('Processing IP', $output);
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
        $visit = new Visit(new ShortUrl(''), new Visitor('', '', '1.2.3.4'));
        $location = new VisitLocation(Location::emptyInstance());

        $locateVisits = $this->visitService->locateUnlocatedVisits(Argument::cetera())->will(
            function (array $args) use ($visit, $location) {
                $firstCallback = array_shift($args);
                $firstCallback($visit);

                $secondCallback = array_shift($args);
                $secondCallback($location, $visit);
            }
        );
        $resolveIpLocation = $this->ipResolver->resolveIpLocation(Argument::any())->willThrow(WrongIpException::class);

        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('An error occurred while locating IP. Skipped', $output);
        $locateVisits->shouldHaveBeenCalledOnce();
        $resolveIpLocation->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function noActionIsPerformedIfLockIsAcquired(): void
    {
        $this->lock->acquire(false)->willReturn(false);

        $locateVisits = $this->visitService->locateUnlocatedVisits(Argument::cetera())->will(function () {
        });
        $resolveIpLocation = $this->ipResolver->resolveIpLocation(Argument::any())->willReturn([]);

        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString(
            sprintf('Command "%s" is already in progress. Skipping.', LocateVisitsCommand::NAME),
            $output
        );
        $locateVisits->shouldNotHaveBeenCalled();
        $resolveIpLocation->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideParams
     */
    public function showsProperMessageWhenGeoLiteUpdateFails(bool $olderDbExists, string $expectedMessage): void
    {
        $locateVisits = $this->visitService->locateUnlocatedVisits(Argument::cetera())->will(function () {
        });
        $checkDbUpdate = $this->dbUpdater->checkDbUpdate(Argument::cetera())->will(
            function (array $args) use ($olderDbExists) {
                [$mustBeUpdated, $handleProgress] = $args;

                $mustBeUpdated($olderDbExists);
                $handleProgress(100, 50);

                throw GeolocationDbUpdateFailedException::create($olderDbExists);
            }
        );

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString(
            sprintf('%s GeoLite2 database...', $olderDbExists ? 'Updating' : 'Downloading'),
            $output
        );
        $this->assertStringContainsString($expectedMessage, $output);
        $locateVisits->shouldHaveBeenCalledTimes((int) $olderDbExists);
        $checkDbUpdate->shouldHaveBeenCalledOnce();
    }

    public function provideParams(): iterable
    {
        yield [true, '[Warning] GeoLite2 database update failed. Proceeding with old version.'];
        yield [false, 'GeoLite2 database download failed. It is not possible to locate visits.'];
    }
}
