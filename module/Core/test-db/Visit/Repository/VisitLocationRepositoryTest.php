<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Visit\Repository;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Repository\VisitLocationRepository;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function Functional\map;
use function range;

class VisitLocationRepositoryTest extends DatabaseTestCase
{
    private VisitLocationRepository $repo;

    protected function setUp(): void
    {
        $em = $this->getEntityManager();
        $this->repo = new VisitLocationRepository($em, $em->getClassMetadata(Visit::class));
    }

    /**
     * @test
     * @dataProvider provideBlockSize
     */
    public function findVisitsReturnsProperVisits(int $blockSize): void
    {
        $shortUrl = ShortUrl::createEmpty();
        $this->getEntityManager()->persist($shortUrl);

        for ($i = 0; $i < 6; $i++) {
            $visit = Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance());

            if ($i >= 2) {
                $location = VisitLocation::fromGeolocation(Location::emptyInstance());
                $this->getEntityManager()->persist($location);
                $visit->locate($location);
            }

            $this->getEntityManager()->persist($visit);
        }
        $this->getEntityManager()->flush();

        $withEmptyLocation = $this->repo->findVisitsWithEmptyLocation($blockSize);
        $unlocated = $this->repo->findUnlocatedVisits($blockSize);
        $all = $this->repo->findAllVisits($blockSize);

        self::assertCount(2, [...$unlocated]);
        self::assertCount(4, [...$withEmptyLocation]);
        self::assertCount(6, [...$all]);
    }

    public function provideBlockSize(): iterable
    {
        return map(range(1, 10), fn (int $value) => [$value]);
    }
}
