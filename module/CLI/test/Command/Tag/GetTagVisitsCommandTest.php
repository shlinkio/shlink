<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Tag\GetTagVisitsCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

class GetTagVisitsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & VisitsStatsHelperInterface $visitsHelper;
    private MockObject & ShortUrlStringifierInterface $stringifier;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->createMock(VisitsStatsHelperInterface::class);
        $this->stringifier = $this->createMock(ShortUrlStringifierInterface::class);

        $this->commandTester = CliTestUtils::testerForCommand(
            new GetTagVisitsCommand($this->visitsHelper, $this->stringifier),
        );
    }

    #[Test]
    public function outputIsProperlyGenerated(): void
    {
        $shortUrl = ShortUrl::createFake();
        $visit = Visit::forValidShortUrl($shortUrl, Visitor::fromParams('bar', 'foo', ''))->locate(
            VisitLocation::fromLocation(new Location('', 'Spain', '', 'Madrid', 0, 0, '')),
        );
        $tag = 'abc123';
        $this->visitsHelper->expects($this->once())->method('visitsForTag')->with($tag, $this->anything())->willReturn(
            new Paginator(new ArrayAdapter([$visit])),
        );
        $this->stringifier->expects($this->once())->method('stringify')->with($shortUrl)->willReturn('the_short_url');

        $this->commandTester->execute(['tag' => $tag]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(
            <<<OUTPUT
            +---------+---------------------------+------------+---------+--------+---------------+
            | Referer | Date                      | User agent | Country | City   | Short Url     |
            +---------+---------------------------+------------+---------+--------+---------------+
            | foo     | {$visit->date->toAtomString()} | bar        | Spain   | Madrid | the_short_url |
            +---------+-------------------------- Page 1 of 1 -+---------+--------+---------------+

            OUTPUT,
            $output,
        );
    }
}
