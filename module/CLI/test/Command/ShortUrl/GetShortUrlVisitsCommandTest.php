<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use Cake\Chronos\Chronos;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\ShortUrl\GetShortUrlVisitsCommand;
use Shlinkio\Shlink\CLI\Input\VisitsListFormat;
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

        $this->commandTester->execute(['short-code' => $shortCode]);
    }

    #[Test]
    public function shortCodeIsAskedIfNotProvided(): void
    {
        $shortCode = 'abc123';
        $this->visitsHelper->expects($this->once())->method('visitsForShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            $this->anything(),
        )->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->setInputs([$shortCode]);
        $this->commandTester->execute([]);
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
            'short-code' => $shortCode,
            '--start-date' => $startDate,
            '--end-date' => $endDate,
        ]);
    }

    /**
     * @param callable(Chronos $date): string $getExpectedOutput
     */
    #[Test, DataProvider('provideOutput')]
    public function outputIsProperlyGenerated(VisitsListFormat $format, callable $getExpectedOutput): void
    {
        $visit = Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::fromParams('bar', 'foo', ''))->locate(
            VisitLocation::fromLocation(new Location('', 'Spain', '', 'Madrid', 0, 0, '')),
        );
        $shortCode = 'abc123';
        $this->visitsHelper->expects($this->once())->method('visitsForShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            $this->anything(),
        )->willReturn(new Paginator(new ArrayAdapter([$visit])));

        $this->commandTester->execute(['short-code' => $shortCode, '--format' => $format->value]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals($getExpectedOutput($visit->date), $output);
    }

    public static function provideOutput(): iterable
    {
        yield 'regular' => [
            VisitsListFormat::FULL,
            // phpcs:disable Generic.Files.LineLength
            static fn (Chronos $date) => <<<OUTPUT
                +---------------------------+---------------+------------+---------+---------+--------+--------+-------------+--------------+-----------------+
                | Date                      | Potential bot | User agent | Referer | Country | Region | City   | Visited URL | Redirect URL | Type            |
                +---------------------------+---------------+------------+---------+---------+--------+--------+-------------+--------------+-----------------+
                | {$date->toAtomString()} |               | bar        | foo     | Spain   |        | Madrid |             | Unknown      | valid_short_url |
                +---------------------------+---------------+------------+------- Page 1 of 1 --------+--------+-------------+--------------+-----------------+

                OUTPUT,
            // phpcs:enable
        ];
        yield 'CSV' => [
            VisitsListFormat::CSV,
            static fn (Chronos $date) => <<<OUTPUT
                Date,"Potential bot","User agent",Referer,Country,Region,City,"Visited URL","Redirect URL",Type
                {$date->toAtomString()},,bar,foo,Spain,,Madrid,,Unknown,valid_short_url

                OUTPUT,
        ];
    }
}
