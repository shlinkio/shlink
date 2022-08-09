<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Visit\GetNonOrphanVisitsCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

class GetNonOrphanVisitsCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $visitsHelper;
    private ObjectProphecy $stringifier;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->prophesize(VisitsStatsHelperInterface::class);
        $this->stringifier = $this->prophesize(ShortUrlStringifierInterface::class);

        $this->commandTester = $this->testerForCommand(
            new GetNonOrphanVisitsCommand($this->visitsHelper->reveal(), $this->stringifier->reveal()),
        );
    }

    /** @test */
    public function outputIsProperlyGenerated(): void
    {
        $shortUrl = ShortUrl::createEmpty();
        $visit = Visit::forValidShortUrl($shortUrl, new Visitor('bar', 'foo', '', ''))->locate(
            VisitLocation::fromGeolocation(new Location('', 'Spain', '', 'Madrid', 0, 0, '')),
        );
        $getVisits = $this->visitsHelper->nonOrphanVisits(Argument::any())->willReturn(
            new Paginator(new ArrayAdapter([$visit])),
        );
        $stringify = $this->stringifier->stringify($shortUrl)->willReturn('the_short_url');

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(
            <<<OUTPUT
            +---------+---------------------------+------------+---------+--------+---------------+
            | Referer | Date                      | User agent | Country | City   | Short Url     |
            +---------+---------------------------+------------+---------+--------+---------------+
            | foo     | {$visit->getDate()->toAtomString()} | bar        | Spain   | Madrid | the_short_url |
            +---------+---------------------------+------------+---------+--------+---------------+

            OUTPUT,
            $output,
        );
        $getVisits->shouldHaveBeenCalledOnce();
        $stringify->shouldHaveBeenCalledOnce();
    }
}
