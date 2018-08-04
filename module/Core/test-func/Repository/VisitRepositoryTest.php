<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use ShlinkioTest\Shlink\Common\DbUnit\DatabaseTestCase;

class VisitRepositoryTest extends DatabaseTestCase
{
    protected const ENTITIES_TO_EMPTY = [
        VisitLocation::class,
        Visit::class,
        ShortUrl::class,
    ];

    /**
     * @var VisitRepository
     */
    private $repo;

    protected function setUp()
    {
        $this->repo = $this->getEntityManager()->getRepository(Visit::class);
    }

    /**
     * @test
     */
    public function findUnlocatedVisitsReturnsProperVisits()
    {
        for ($i = 0; $i < 6; $i++) {
            $visit = new Visit();

            if ($i % 2 === 0) {
                $location = new VisitLocation();
                $this->getEntityManager()->persist($location);
                $visit->setVisitLocation($location);
            }

            $this->getEntityManager()->persist($visit);
        }
        $this->getEntityManager()->flush();

        $this->assertCount(3, $this->repo->findUnlocatedVisits());
    }

    /**
     * @test
     */
    public function findVisitsByShortUrlReturnsProperData()
    {
        $shortUrl = new ShortUrl();
        $shortUrl->setOriginalUrl('');
        $this->getEntityManager()->persist($shortUrl);

        for ($i = 0; $i < 6; $i++) {
            $visit = new Visit();
            $visit->setShortUrl($shortUrl)
                  ->setDate(new \DateTime('2016-01-0' . ($i + 1)));

            $this->getEntityManager()->persist($visit);
        }
        $this->getEntityManager()->flush();

        $this->assertCount(0, $this->repo->findVisitsByShortUrl('invalid'));
        $this->assertCount(6, $this->repo->findVisitsByShortUrl($shortUrl->getId()));
        $this->assertCount(2, $this->repo->findVisitsByShortUrl($shortUrl->getId(), new DateRange(
            new \DateTime('2016-01-02'),
            new \DateTime('2016-01-03')
        )));
        $this->assertCount(4, $this->repo->findVisitsByShortUrl($shortUrl->getId(), new DateRange(
            new \DateTime('2016-01-03')
        )));
    }
}
