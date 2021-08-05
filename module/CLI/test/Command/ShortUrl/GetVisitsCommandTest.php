<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use Cake\Chronos\Chronos;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\GetVisitsCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

use function sprintf;

class GetVisitsCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $visitsHelper;

    public function setUp(): void
    {
        $this->visitsHelper = $this->prophesize(VisitsStatsHelperInterface::class);
        $command = new GetVisitsCommand($this->visitsHelper->reveal());
        $this->commandTester = $this->testerForCommand($command);
    }

    /** @test */
    public function noDateFlagsTriesToListWithoutDateRange(): void
    {
        $shortCode = 'abc123';
        $this->visitsHelper->visitsForShortUrl(
            new ShortUrlIdentifier($shortCode),
            new VisitsParams(DateRange::emptyInstance()),
        )
            ->willReturn(new Paginator(new ArrayAdapter([])))
            ->shouldBeCalledOnce();

        $this->commandTester->execute(['shortCode' => $shortCode]);
    }

    /** @test */
    public function providingDateFlagsTheListGetsFiltered(): void
    {
        $shortCode = 'abc123';
        $startDate = '2016-01-01';
        $endDate = '2016-02-01';
        $this->visitsHelper->visitsForShortUrl(
            new ShortUrlIdentifier($shortCode),
            new VisitsParams(DateRange::withStartAndEndDate(Chronos::parse($startDate), Chronos::parse($endDate))),
        )
            ->willReturn(new Paginator(new ArrayAdapter([])))
            ->shouldBeCalledOnce();

        $this->commandTester->execute([
            'shortCode' => $shortCode,
            '--start-date' => $startDate,
            '--end-date' => $endDate,
        ]);
    }

    /** @test */
    public function providingInvalidDatesPrintsWarning(): void
    {
        $shortCode = 'abc123';
        $startDate = 'foo';
        $info = $this->visitsHelper->visitsForShortUrl(
            new ShortUrlIdentifier($shortCode),
            new VisitsParams(DateRange::emptyInstance()),
        )->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->execute([
            'shortCode' => $shortCode,
            '--start-date' => $startDate,
        ]);
        $output = $this->commandTester->getDisplay();

        $info->shouldHaveBeenCalledOnce();
        self::assertStringContainsString(
            sprintf('Ignored provided "start-date" since its value "%s" is not a valid date', $startDate),
            $output,
        );
    }

    /** @test */
    public function outputIsProperlyGenerated(): void
    {
        $shortCode = 'abc123';
        $this->visitsHelper->visitsForShortUrl(new ShortUrlIdentifier($shortCode), Argument::any())->willReturn(
            new Paginator(new ArrayAdapter([
                Visit::forValidShortUrl(ShortUrl::createEmpty(), new Visitor('bar', 'foo', '', ''))->locate(
                    VisitLocation::fromGeolocation(new Location('', 'Spain', '', '', 0, 0, '')),
                ),
            ])),
        )->shouldBeCalledOnce();

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('foo', $output);
        self::assertStringContainsString('Spain', $output);
        self::assertStringContainsString('bar', $output);
    }
}
