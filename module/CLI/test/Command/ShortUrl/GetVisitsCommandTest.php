<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\GetVisitsCommand;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use function strpos;

class GetVisitsCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $visitsTracker;

    public function setUp()
    {
        $this->visitsTracker = $this->prophesize(VisitsTrackerInterface::class);
        $command = new GetVisitsCommand($this->visitsTracker->reveal());
        $app = new Application();
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function noDateFlagsTriesToListWithoutDateRange()
    {
        $shortCode = 'abc123';
        $this->visitsTracker->info($shortCode, new DateRange(null, null))->willReturn([])
                                                                         ->shouldBeCalledOnce();

        $this->commandTester->execute([
            'command' => 'shortcode:visits',
            'shortCode' => $shortCode,
        ]);
    }

    /**
     * @test
     */
    public function providingDateFlagsTheListGetsFiltered()
    {
        $shortCode = 'abc123';
        $startDate = '2016-01-01';
        $endDate = '2016-02-01';
        $this->visitsTracker->info($shortCode, new DateRange(Chronos::parse($startDate), Chronos::parse($endDate)))
            ->willReturn([])
            ->shouldBeCalledOnce();

        $this->commandTester->execute([
            'command' => 'shortcode:visits',
            'shortCode' => $shortCode,
            '--startDate' => $startDate,
            '--endDate' => $endDate,
        ]);
    }

    /**
     * @test
     */
    public function outputIsProperlyGenerated()
    {
        $shortCode = 'abc123';
        $this->visitsTracker->info($shortCode, Argument::any())->willReturn([
            (new Visit(new ShortUrl(''), new Visitor('bar', 'foo', '')))->locate(
                new VisitLocation(['country_name' => 'Spain'])
            ),
        ])->shouldBeCalledOnce();

        $this->commandTester->execute([
            'command' => 'shortcode:visits',
            'shortCode' => $shortCode,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertGreaterThan(0, strpos($output, 'foo'));
        $this->assertGreaterThan(0, strpos($output, 'Spain'));
        $this->assertGreaterThan(0, strpos($output, 'bar'));
    }
}
