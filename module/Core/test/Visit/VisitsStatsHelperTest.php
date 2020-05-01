<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelper;

use function Functional\map;
use function range;

class VisitsStatsHelperTest extends TestCase
{
    private VisitsStatsHelper $helper;
    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->helper = new VisitsStatsHelper($this->em->reveal());
    }

    /**
     * @test
     * @dataProvider provideCounts
     */
    public function returnsExpectedVisitsStats(int $expectedCount): void
    {
        $repo = $this->prophesize(VisitRepository::class);
        $count = $repo->count([])->willReturn($expectedCount);
        $getRepo = $this->em->getRepository(Visit::class)->willReturn($repo->reveal());

        $stats = $this->helper->getVisitsStats();

        $this->assertEquals(new VisitsStats($expectedCount), $stats);
        $count->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
    }

    public function provideCounts(): iterable
    {
        return map(range(0, 50, 5), fn (int $value) => [$value]);
    }
}
