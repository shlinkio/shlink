<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Visit\Repository;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Repository\VisitIterationRepository;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function array_map;
use function range;

class VisitIterationRepositoryTest extends DatabaseTestCase
{
    private VisitIterationRepository $repo;

    protected function setUp(): void
    {
        $em = $this->getEntityManager();
        $this->repo = new VisitIterationRepository($em, $em->getClassMetadata(Visit::class));
    }

    #[Test, DataProvider('provideBlockSize')]
    public function findVisitsReturnsProperVisits(int $blockSize): void
    {
        $shortUrl = ShortUrl::createFake();
        $this->getEntityManager()->persist($shortUrl);

        $unmodifiedDate = Chronos::now();
        for ($i = 0; $i < 6; $i++) {
            Chronos::setTestNow($unmodifiedDate->subDays($i)); // Enforce a different day for every visit
            $visit = Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance());

            if ($i >= 2) {
                $location = VisitLocation::fromGeolocation(Location::emptyInstance());
                $this->getEntityManager()->persist($location);
                $visit->locate($location);
            }

            $this->getEntityManager()->persist($visit);
        }
        Chronos::setTestNow();
        $this->getEntityManager()->flush();

        $withEmptyLocation = $this->repo->findVisitsWithEmptyLocation($blockSize);
        $unlocated = $this->repo->findUnlocatedVisits($blockSize);
        $all = $this->repo->findAllVisits(blockSize: $blockSize);
        $lastThreeDays = $this->repo->findAllVisits(
            dateRange: DateRange::since(Chronos::now()->subDays(2)),
            blockSize: $blockSize,
        );
        $firstTwoDays = $this->repo->findAllVisits(
            dateRange: DateRange::until(Chronos::now()->subDays(4)),
            blockSize: $blockSize,
        );
        $daysInBetween = $this->repo->findAllVisits(
            dateRange: DateRange::between(
                startDate: Chronos::now()->subDays(5),
                endDate: Chronos::now()->subDays(2),
            ),
            blockSize: $blockSize,
        );

        self::assertCount(2, [...$unlocated]);
        self::assertCount(4, [...$withEmptyLocation]);
        self::assertCount(6, [...$all]);
        self::assertCount(3, [...$lastThreeDays]);
        self::assertCount(2, [...$firstTwoDays]);
        self::assertCount(4, [...$daysInBetween]);
    }

    public static function provideBlockSize(): iterable
    {
        return array_map(static fn (int $value) => [$value], range(1, 10));
    }
}
