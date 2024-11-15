<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use Cake\Chronos\Chronos;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\ShortUrl\GetShortUrlVisitsCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

use function Shlinkio\Shlink\Common\buildDateRange;
use function sprintf;

class GetShortUrlVisitsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & VisitsStatsHelperInterface $visitsHelper;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->createMock(VisitsStatsHelperInterface::class);
        $command = new GetShortUrlVisitsCommand($this->visitsHelper);
        $this->commandTester = CliTestUtils::testerForCommand($command);
    }

    #[Test]
    public function noDateFlagsTriesToListWithoutDateRange(): void
    {
        $shortCode = 'abc123';
        $this->visitsHelper->expects($this->once())->method('visitsForShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsParams(DateRange::allTime()),
        )->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->execute(['shortCode' => $shortCode]);
    }

    #[Test]
    public function providingDateFlagsTheListGetsFiltered(): void
    {
        $shortCode = 'abc123';
        $startDate = '2016-01-01';
        $endDate = '2016-02-01';
        $this->visitsHelper->expects($this->once())->method('visitsForShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsParams(buildDateRange(Chronos::parse($startDate), Chronos::parse($endDate))),
        )->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->execute([
            'shortCode' => $shortCode,
            '--start-date' => $startDate,
            '--end-date' => $endDate,
        ]);
    }

    #[Test]
    public function providingInvalidDatesPrintsWarning(): void
    {
        $shortCode = 'abc123';
        $startDate = 'foo';
        $this->visitsHelper->expects($this->once())->method('visitsForShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsParams(DateRange::allTime()),
        )->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->execute([
            'shortCode' => $shortCode,
            '--start-date' => $startDate,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString(
            sprintf('Ignored provided "start-date" since its value "%s" is not a valid date', $startDate),
            $output,
        );
    }

    #[Test]
    public function outputIsProperlyGenerated(): void
    {
        $visit = Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::fromParams('bar', 'foo', ''))->locate(
            VisitLocation::fromGeolocation(new Location('', 'Spain', '', 'Madrid', 0, 0, '')),
        );
        $shortCode = 'abc123';
        $this->visitsHelper->expects($this->once())->method('visitsForShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            $this->anything(),
        )->willReturn(new Paginator(new ArrayAdapter([$visit])));

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(
            <<<OUTPUT
            +---------+---------------------------+------------+---------+--------+
            | Referer | Date                      | User agent | Country | City   |
            +---------+---------------------------+------------+---------+--------+
            | foo     | {$visit->date->toAtomString()} | bar        | Spain   | Madrid |
            +---------+---------------------------+------------+---------+--------+

            OUTPUT,
            $output,
        );
    }
}
