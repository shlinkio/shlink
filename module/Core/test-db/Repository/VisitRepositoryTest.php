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
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function Functional\map;
use function range;
use function sprintf;

class VisitRepositoryTest extends DatabaseTestCase
{
    private VisitRepository $repo;
    private PersistenceShortUrlRelationResolver $relationResolver;

    protected function beforeEach(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Visit::class);
        $this->relationResolver = new PersistenceShortUrlRelationResolver($this->getEntityManager());
    }

    /**
     * @test
     * @dataProvider provideBlockSize
     */
    public function findVisitsReturnsProperVisits(int $blockSize): void
    {
        $shortUrl = ShortUrl::createEmpty();
        $this->getEntityManager()->persist($shortUrl);
        $countIterable = function (iterable $results): int {
            $resultsCount = 0;
            foreach ($results as $value) {
                $resultsCount++;
            }

            return $resultsCount;
        };

        for ($i = 0; $i < 6; $i++) {
            $visit = Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance());

            if ($i >= 2) {
                $location = new VisitLocation(Location::emptyInstance());
                $this->getEntityManager()->persist($location);
                $visit->locate($location);
            }

            $this->getEntityManager()->persist($visit);
        }
        $this->getEntityManager()->flush();

        $withEmptyLocation = $this->repo->findVisitsWithEmptyLocation($blockSize);
        $unlocated = $this->repo->findUnlocatedVisits($blockSize);
        $all = $this->repo->findAllVisits($blockSize);

        // Important! assertCount will not work here, as this iterable object loads data dynamically and the count
        // is 0 if not iterated
        self::assertEquals(2, $countIterable($unlocated));
        self::assertEquals(4, $countIterable($withEmptyLocation));
        self::assertEquals(6, $countIterable($all));
    }

    public function provideBlockSize(): iterable
    {
        return map(range(1, 10), fn (int $value) => [$value]);
    }

    /** @test */
    public function findVisitsByShortCodeReturnsProperData(): void
    {
        [$shortCode, $domain] = $this->createShortUrlsAndVisits();

        self::assertCount(0, $this->repo->findVisitsByShortCode('invalid'));
        self::assertCount(6, $this->repo->findVisitsByShortCode($shortCode));
        self::assertCount(3, $this->repo->findVisitsByShortCode($shortCode, $domain));
        self::assertCount(2, $this->repo->findVisitsByShortCode($shortCode, null, new DateRange(
            Chronos::parse('2016-01-02'),
            Chronos::parse('2016-01-03'),
        )));
        self::assertCount(4, $this->repo->findVisitsByShortCode($shortCode, null, new DateRange(
            Chronos::parse('2016-01-03'),
        )));
        self::assertCount(1, $this->repo->findVisitsByShortCode($shortCode, $domain, new DateRange(
            Chronos::parse('2016-01-03'),
        )));
        self::assertCount(3, $this->repo->findVisitsByShortCode($shortCode, null, null, 3, 2));
        self::assertCount(2, $this->repo->findVisitsByShortCode($shortCode, null, null, 5, 4));
        self::assertCount(1, $this->repo->findVisitsByShortCode($shortCode, $domain, null, 3, 2));
    }

    /** @test */
    public function countVisitsByShortCodeReturnsProperData(): void
    {
        [$shortCode, $domain] = $this->createShortUrlsAndVisits();

        self::assertEquals(0, $this->repo->countVisitsByShortCode('invalid'));
        self::assertEquals(6, $this->repo->countVisitsByShortCode($shortCode));
        self::assertEquals(3, $this->repo->countVisitsByShortCode($shortCode, $domain));
        self::assertEquals(2, $this->repo->countVisitsByShortCode($shortCode, null, new DateRange(
            Chronos::parse('2016-01-02'),
            Chronos::parse('2016-01-03'),
        )));
        self::assertEquals(4, $this->repo->countVisitsByShortCode($shortCode, null, new DateRange(
            Chronos::parse('2016-01-03'),
        )));
        self::assertEquals(1, $this->repo->countVisitsByShortCode($shortCode, $domain, new DateRange(
            Chronos::parse('2016-01-03'),
        )));
    }

    /** @test */
    public function findVisitsByTagReturnsProperData(): void
    {
        $foo = 'foo';

        /** @var ShortUrl $shortUrl */
        $this->createShortUrlsAndVisits(false, [$foo]);
        $this->getEntityManager()->flush();

        $this->createShortUrlsAndVisits(false, [$foo]);
        $this->getEntityManager()->flush();

        $this->createShortUrlsAndVisits(false, [$foo]);
        $this->getEntityManager()->flush();

        self::assertCount(0, $this->repo->findVisitsByTag('invalid'));
        self::assertCount(18, $this->repo->findVisitsByTag($foo));
        self::assertCount(6, $this->repo->findVisitsByTag($foo, new DateRange(
            Chronos::parse('2016-01-02'),
            Chronos::parse('2016-01-03'),
        )));
        self::assertCount(12, $this->repo->findVisitsByTag($foo, new DateRange(Chronos::parse('2016-01-03'))));
    }

    /** @test */
    public function countVisitsByTagReturnsProperData(): void
    {
        $foo = 'foo';

        $this->createShortUrlsAndVisits(false, [$foo]);
        $this->getEntityManager()->flush();

        $this->createShortUrlsAndVisits(false, [$foo]);
        $this->getEntityManager()->flush();

        self::assertEquals(0, $this->repo->countVisitsByTag('invalid'));
        self::assertEquals(12, $this->repo->countVisitsByTag($foo));
        self::assertEquals(4, $this->repo->countVisitsByTag($foo, new DateRange(
            Chronos::parse('2016-01-02'),
            Chronos::parse('2016-01-03'),
        )));
        self::assertEquals(8, $this->repo->countVisitsByTag($foo, new DateRange(Chronos::parse('2016-01-03'))));
    }

    /** @test */
    public function countVisitsReturnsExpectedResultBasedOnApiKey(): void
    {
        $domain = new Domain('foo.com');
        $this->getEntityManager()->persist($domain);

        $this->getEntityManager()->flush();

        $apiKey1 = ApiKey::withRoles(RoleDefinition::forAuthoredShortUrls());
        $this->getEntityManager()->persist($apiKey1);
        $shortUrl = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['apiKey' => $apiKey1, 'domain' => $domain->getAuthority(), 'longUrl' => '']),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($shortUrl);
        $this->createVisitsForShortUrl($shortUrl, 4);

        $apiKey2 = ApiKey::withRoles(RoleDefinition::forAuthoredShortUrls());
        $this->getEntityManager()->persist($apiKey2);
        $shortUrl2 = ShortUrl::fromMeta(ShortUrlMeta::fromRawData(['apiKey' => $apiKey2, 'longUrl' => '']));
        $this->getEntityManager()->persist($shortUrl2);
        $this->createVisitsForShortUrl($shortUrl2, 5);

        $shortUrl3 = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['apiKey' => $apiKey2, 'domain' => $domain->getAuthority(), 'longUrl' => '']),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($shortUrl3);
        $this->createVisitsForShortUrl($shortUrl3, 7);

        $domainApiKey = ApiKey::withRoles(RoleDefinition::forDomain($domain));
        $this->getEntityManager()->persist($domainApiKey);

        // Visits not linked to any short URL
        $this->getEntityManager()->persist(Visit::forBasePath(Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forInvalidShortUrl(Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forRegularNotFound(Visitor::emptyInstance()));

        $this->getEntityManager()->flush();

        self::assertEquals(4 + 5 + 7, $this->repo->countVisits());
        self::assertEquals(4, $this->repo->countVisits($apiKey1));
        self::assertEquals(5 + 7, $this->repo->countVisits($apiKey2));
        self::assertEquals(4 + 7, $this->repo->countVisits($domainApiKey));
        self::assertEquals(3, $this->repo->countOrphanVisits());
    }

    private function createShortUrlsAndVisits(bool $withDomain = true, array $tags = []): array
    {
        $shortUrl = ShortUrl::fromMeta(ShortUrlMeta::fromRawData([
            'longUrl' => '',
            'tags' => $tags,
        ]), $this->relationResolver);
        $domain = 'example.com';
        $shortCode = $shortUrl->getShortCode();
        $this->getEntityManager()->persist($shortUrl);

        $this->createVisitsForShortUrl($shortUrl);

        if ($withDomain) {
            $shortUrlWithDomain = ShortUrl::fromMeta(ShortUrlMeta::fromRawData([
                'customSlug' => $shortCode,
                'domain' => $domain,
                'longUrl' => '',
            ]));
            $this->getEntityManager()->persist($shortUrlWithDomain);
            $this->createVisitsForShortUrl($shortUrlWithDomain, 3);
            $this->getEntityManager()->flush();
        }

        return [$shortCode, $domain, $shortUrl];
    }

    private function createVisitsForShortUrl(ShortUrl $shortUrl, int $amount = 6): void
    {
        for ($i = 0; $i < $amount; $i++) {
            $visit = new Visit(
                $shortUrl,
                Visitor::emptyInstance(),
                true,
                Chronos::parse(sprintf('2016-01-0%s', $i + 1)),
            );
            $this->getEntityManager()->persist($visit);
        }
    }
}
