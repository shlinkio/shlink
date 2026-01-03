<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Visit\GetNonOrphanVisitsCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

class GetNonOrphanVisitsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & VisitsStatsHelperInterface $visitsHelper;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->createMock(VisitsStatsHelperInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new GetNonOrphanVisitsCommand($this->visitsHelper));
    }

    #[Test]
    public function outputIsProperlyGenerated(): void
    {
        $shortUrl = ShortUrl::createFake();
        $visit = Visit::forValidShortUrl($shortUrl, Visitor::fromParams('bar', 'foo', ''))->locate(
            VisitLocation::fromLocation(new Location('', 'Spain', '', 'Madrid', 0, 0, '')),
        );
        $this->visitsHelper->expects($this->once())->method('nonOrphanVisits')->withAnyParameters()->willReturn(
            new Paginator(new ArrayAdapter([$visit])),
        );

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        $type = VisitType::VALID_SHORT_URL->value;

        self::assertEquals(
            // phpcs:disable Generic.Files.LineLength
            <<<OUTPUT
            +---------------------------+---------------+------------+---------+---------+--------+--------+-------------+--------------+-----------------+
            | Date                      | Potential bot | User agent | Referer | Country | Region | City   | Visited URL | Redirect URL | Type            |
            +---------------------------+---------------+------------+---------+---------+--------+--------+-------------+--------------+-----------------+
            | {$visit->date->toAtomString()} |               | bar        | foo     | Spain   |        | Madrid |             | Unknown      | {$type} |
            +---------------------------+---------------+------------+------- Page 1 of 1 --------+--------+-------------+--------------+-----------------+

            OUTPUT,
            // phpcs:enable
            $output,
        );
    }
}
