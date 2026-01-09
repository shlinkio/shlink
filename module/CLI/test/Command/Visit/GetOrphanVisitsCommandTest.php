<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Visit\GetOrphanVisitsCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitType;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

class GetOrphanVisitsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & VisitsStatsHelperInterface $visitsHelper;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->createMock(VisitsStatsHelperInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new GetOrphanVisitsCommand($this->visitsHelper));
    }

    #[Test]
    #[TestWith([[], false])]
    #[TestWith([['--type' => OrphanVisitType::BASE_URL->value], true])]
    public function outputIsProperlyGenerated(array $args, bool $includesType): void
    {
        $visit = Visit::forBasePath(Visitor::fromParams('bar', 'foo', ''))->locate(
            VisitLocation::fromLocation(new Location('', 'Spain', '', 'Madrid', 0, 0, '')),
        );
        $this->visitsHelper->expects($this->once())->method('orphanVisits')->with($this->callback(
            fn (OrphanVisitsParams $param) => (
                ($includesType && $param->type !== null) || (!$includesType && $param->type === null)
            ),
        ))->willReturn(new Paginator(new ArrayAdapter([$visit])));

        $this->commandTester->execute($args);
        $output = $this->commandTester->getDisplay();
        $type = OrphanVisitType::BASE_URL->value;

        self::assertEquals(
            // phpcs:disable Generic.Files.LineLength
            <<<OUTPUT
            +---------------------------+---------------+------------+---------+---------+--------+--------+-------------+--------------+----------+
            | Date                      | Potential bot | User agent | Referer | Country | Region | City   | Visited URL | Redirect URL | Type     |
            +---------------------------+---------------+------------+---------+---------+--------+--------+-------------+--------------+----------+
            | {$visit->date->toAtomString()} |               | bar        | foo     | Spain   |        | Madrid |             | Unknown      | {$type} |
            +---------------------------+---------------+------------+--- Page 1 of 1 ---+--------+--------+-------------+--------------+----------+

            OUTPUT,
            // phpcs:enable
            $output,
        );
    }
}
