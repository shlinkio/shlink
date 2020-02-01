<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function Functional\map;
use function range;
use function sprintf;

class VisitRepositoryTest extends DatabaseTestCase
{
    protected const ENTITIES_TO_EMPTY = [
        VisitLocation::class,
        Visit::class,
        ShortUrl::class,
        Domain::class,
    ];

    private VisitRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Visit::class);
    }

    /**
     * @test
     * @dataProvider provideBlockSize
     */
    public function findUnlocatedVisitsReturnsProperVisits(int $blockSize): void
    {
        $shortUrl = new ShortUrl('');
        $this->getEntityManager()->persist($shortUrl);

        for ($i = 0; $i < 6; $i++) {
            $visit = new Visit($shortUrl, Visitor::emptyInstance());

            if ($i % 2 === 0) {
                $location = new VisitLocation(Location::emptyInstance());
                $this->getEntityManager()->persist($location);
                $visit->locate($location);
            }

            $this->getEntityManager()->persist($visit);
        }
        $this->getEntityManager()->flush();

        $resultsCount = 0;
        $results = $this->repo->findUnlocatedVisits(true, $blockSize);
        foreach ($results as $value) {
            $resultsCount++;
        }

        $this->assertEquals(3, $resultsCount);
    }

    public function provideBlockSize(): iterable
    {
        return map(range(1, 5), fn (int $value) => [$value]);
    }

    /** @test */
    public function findVisitsByShortCodeReturnsProperData(): void
    {
        [$shortCode, $domain] = $this->createShortUrlsAndVisits();

        $this->assertCount(0, $this->repo->findVisitsByShortCode('invalid'));
        $this->assertCount(6, $this->repo->findVisitsByShortCode($shortCode));
        $this->assertCount(3, $this->repo->findVisitsByShortCode($shortCode, $domain));
        $this->assertCount(2, $this->repo->findVisitsByShortCode($shortCode, null, new DateRange(
            Chronos::parse('2016-01-02'),
            Chronos::parse('2016-01-03'),
        )));
        $this->assertCount(4, $this->repo->findVisitsByShortCode($shortCode, null, new DateRange(
            Chronos::parse('2016-01-03'),
        )));
        $this->assertCount(1, $this->repo->findVisitsByShortCode($shortCode, $domain, new DateRange(
            Chronos::parse('2016-01-03'),
        )));
        $this->assertCount(3, $this->repo->findVisitsByShortCode($shortCode, null, null, 3, 2));
        $this->assertCount(2, $this->repo->findVisitsByShortCode($shortCode, null, null, 5, 4));
        $this->assertCount(1, $this->repo->findVisitsByShortCode($shortCode, $domain, null, 3, 2));
    }

    /** @test */
    public function countVisitsByShortCodeReturnsProperData(): void
    {
        [$shortCode, $domain] = $this->createShortUrlsAndVisits();

        $this->assertEquals(0, $this->repo->countVisitsByShortCode('invalid'));
        $this->assertEquals(6, $this->repo->countVisitsByShortCode($shortCode));
        $this->assertEquals(3, $this->repo->countVisitsByShortCode($shortCode, $domain));
        $this->assertEquals(2, $this->repo->countVisitsByShortCode($shortCode, null, new DateRange(
            Chronos::parse('2016-01-02'),
            Chronos::parse('2016-01-03'),
        )));
        $this->assertEquals(4, $this->repo->countVisitsByShortCode($shortCode, null, new DateRange(
            Chronos::parse('2016-01-03'),
        )));
        $this->assertEquals(1, $this->repo->countVisitsByShortCode($shortCode, $domain, new DateRange(
            Chronos::parse('2016-01-03'),
        )));
    }

    private function createShortUrlsAndVisits(): array
    {
        $shortUrl = new ShortUrl('');
        $domain = 'example.com';
        $shortCode = $shortUrl->getShortCode();
        $shortUrlWithDomain = new ShortUrl('', ShortUrlMeta::fromRawData([
            'customSlug' => $shortCode,
            'domain' => $domain,
        ]));

        $this->getEntityManager()->persist($shortUrl);
        $this->getEntityManager()->persist($shortUrlWithDomain);

        for ($i = 0; $i < 6; $i++) {
            $visit = new Visit($shortUrl, Visitor::emptyInstance(), Chronos::parse(sprintf('2016-01-0%s', $i + 1)));
            $this->getEntityManager()->persist($visit);
        }
        for ($i = 0; $i < 3; $i++) {
            $visit = new Visit(
                $shortUrlWithDomain,
                Visitor::emptyInstance(),
                Chronos::parse(sprintf('2016-01-0%s', $i + 1)),
            );
            $this->getEntityManager()->persist($visit);
        }
        $this->getEntityManager()->flush();

        return [$shortCode, $domain];
    }
}
