<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Visit\ProcessVisitsCommand;
use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Common\IpGeolocation\IpApiLocationResolver;
use Shlinkio\Shlink\Common\IpGeolocation\Model\Location;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Service\VisitService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock;
use function array_shift;
use function sprintf;

class ProcessVisitsCommandTest extends TestCase
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

    public function setUp(): void
    {
        $this->visitService = $this->prophesize(VisitService::class);
        $this->ipResolver = $this->prophesize(IpApiLocationResolver::class);

        $this->locker = $this->prophesize(Lock\Factory::class);
        $this->lock = $this->prophesize(Lock\LockInterface::class);
        $this->lock->acquire()->willReturn(true);
        $this->lock->release()->will(function () {
        });
        $this->locker->createLock(Argument::type('string'))->willReturn($this->lock->reveal());

        $command = new ProcessVisitsCommand(
            $this->visitService->reveal(),
            $this->ipResolver->reveal(),
            $this->locker->reveal()
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

        $locateVisits = $this->visitService->locateVisits(Argument::cetera())->will(
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

        $this->commandTester->execute([
            'command' => 'visit:process',
        ]);
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

        $locateVisits = $this->visitService->locateVisits(Argument::cetera())->will(
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

        $this->commandTester->execute([
            'command' => 'visit:process',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString($message, $output);
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

        $locateVisits = $this->visitService->locateVisits(Argument::cetera())->will(
            function (array $args) use ($visit, $location) {
                $firstCallback = array_shift($args);
                $firstCallback($visit);

                $secondCallback = array_shift($args);
                $secondCallback($location, $visit);
            }
        );
        $resolveIpLocation = $this->ipResolver->resolveIpLocation(Argument::any())->willThrow(WrongIpException::class);

        $this->commandTester->execute([
            'command' => 'visit:process',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('An error occurred while locating IP. Skipped', $output);
        $locateVisits->shouldHaveBeenCalledOnce();
        $resolveIpLocation->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function noActionIsPerformedIfLockIsAcquired()
    {
        $this->lock->acquire()->willReturn(false);

        $locateVisits = $this->visitService->locateVisits(Argument::cetera())->will(function () {
        });
        $resolveIpLocation = $this->ipResolver->resolveIpLocation(Argument::any())->willReturn([]);

        $this->commandTester->execute([
            'command' => 'visit:process',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString(
            sprintf('There is already an instance of the "%s" command', ProcessVisitsCommand::NAME),
            $output
        );
        $locateVisits->shouldNotHaveBeenCalled();
        $resolveIpLocation->shouldNotHaveBeenCalled();
    }
}
