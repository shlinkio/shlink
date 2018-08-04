<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Visit\ProcessVisitsCommand;
use Shlinkio\Shlink\Common\Service\IpApiLocationResolver;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Service\VisitService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;

class ProcessVisitsCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    protected $commandTester;
    /**
     * @var ObjectProphecy
     */
    protected $visitService;
    /**
     * @var ObjectProphecy
     */
    protected $ipResolver;

    public function setUp()
    {
        $this->visitService = $this->prophesize(VisitService::class);
        $this->ipResolver = $this->prophesize(IpApiLocationResolver::class);
        $this->ipResolver->getApiLimit()->willReturn(10000000000);

        $command = new ProcessVisitsCommand(
            $this->visitService->reveal(),
            $this->ipResolver->reveal(),
            Translator::factory([])
        );
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function allReturnedVisitsIpsAreProcessed()
    {
        $visits = [
            (new Visit())->setRemoteAddr('1.2.3.4'),
            (new Visit())->setRemoteAddr('4.3.2.1'),
            (new Visit())->setRemoteAddr('12.34.56.78'),
        ];
        $this->visitService->getUnlocatedVisits()->willReturn($visits)
                                                 ->shouldBeCalledTimes(1);

        $this->visitService->saveVisit(Argument::any())->shouldBeCalledTimes(count($visits));
        $this->ipResolver->resolveIpLocation(Argument::any())->willReturn([])
                                                             ->shouldBeCalledTimes(count($visits));

        $this->commandTester->execute([
            'command' => 'visit:process',
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertTrue(strpos($output, 'Processing IP 1.2.3.4') === 0);
        $this->assertTrue(strpos($output, 'Processing IP 4.3.2.1') > 0);
        $this->assertTrue(strpos($output, 'Processing IP 12.34.56.78') > 0);
    }

    /**
     * @test
     */
    public function localhostAddressIsIgnored()
    {
        $visits = [
            (new Visit())->setRemoteAddr('1.2.3.4'),
            (new Visit())->setRemoteAddr('4.3.2.1'),
            (new Visit())->setRemoteAddr('12.34.56.78'),
            (new Visit())->setRemoteAddr('127.0.0.1'),
            (new Visit())->setRemoteAddr('127.0.0.1'),
        ];
        $this->visitService->getUnlocatedVisits()->willReturn($visits)
            ->shouldBeCalledTimes(1);

        $this->visitService->saveVisit(Argument::any())->shouldBeCalledTimes(count($visits) - 2);
        $this->ipResolver->resolveIpLocation(Argument::any())->willReturn([])
                                                             ->shouldBeCalledTimes(count($visits) - 2);

        $this->commandTester->execute([
            'command' => 'visit:process',
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertTrue(strpos($output, 'Ignored localhost address') > 0);
    }

    /**
     * @test
     */
    public function sleepsEveryTimeTheApiLimitIsReached()
    {
        $visits = [
            (new Visit())->setRemoteAddr('1.2.3.4'),
            (new Visit())->setRemoteAddr('4.3.2.1'),
            (new Visit())->setRemoteAddr('12.34.56.78'),
            (new Visit())->setRemoteAddr('1.2.3.4'),
            (new Visit())->setRemoteAddr('4.3.2.1'),
            (new Visit())->setRemoteAddr('12.34.56.78'),
            (new Visit())->setRemoteAddr('1.2.3.4'),
            (new Visit())->setRemoteAddr('4.3.2.1'),
            (new Visit())->setRemoteAddr('12.34.56.78'),
            (new Visit())->setRemoteAddr('4.3.2.1'),
        ];
        $apiLimit = 3;

        $this->visitService->getUnlocatedVisits()->willReturn($visits);
        $this->visitService->saveVisit(Argument::any())->will(function () {
        });

        $getApiLimit = $this->ipResolver->getApiLimit()->willReturn($apiLimit);
        $getApiInterval = $this->ipResolver->getApiInterval()->willReturn(0);
        $resolveIpLocation = $this->ipResolver->resolveIpLocation(Argument::any())->willReturn([])
            ->shouldBeCalledTimes(count($visits));

        $this->commandTester->execute([
            'command' => 'visit:process',
        ]);

        $getApiLimit->shouldHaveBeenCalledTimes(\count($visits));
        $getApiInterval->shouldHaveBeenCalledTimes(\round(\count($visits) / $apiLimit));
        $resolveIpLocation->shouldHaveBeenCalledTimes(\count($visits));
    }
}
