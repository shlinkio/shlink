<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Visit\Repository;

use Cake\Chronos\Chronos;
use ReflectionObject;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepository;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function is_string;
use function sprintf;
use function str_pad;

use const STR_PAD_LEFT;

class VisitRepositoryTest extends DatabaseTestCase
{
    private VisitRepository $repo;
    private PersistenceShortUrlRelationResolver $relationResolver;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Visit::class);
        $this->relationResolver = new PersistenceShortUrlRelationResolver($this->getEntityManager());
    }

    /** @test */
    public function findVisitsByShortCodeReturnsProperData(): void
    {
        [$shortCode, $domain] = $this->createShortUrlsAndVisits();

        self::assertCount(0, $this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain('invalid'),
            new VisitsListFiltering(),
        ));
        self::assertCount(6, $this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsListFiltering(),
        ));
        self::assertCount(4, $this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsListFiltering(null, true),
        ));
        self::assertCount(3, $this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain),
            new VisitsListFiltering(),
        ));
        self::assertCount(2, $this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsListFiltering(
                DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
            ),
        ));
        self::assertCount(4, $this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsListFiltering(DateRange::since(Chronos::parse('2016-01-03'))),
        ));
        self::assertCount(1, $this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain),
            new VisitsListFiltering(DateRange::since(Chronos::parse('2016-01-03'))),
        ));
        self::assertCount(3, $this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsListFiltering(null, false, null, 3, 2),
        ));
        self::assertCount(2, $this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsListFiltering(null, false, null, 5, 4),
        ));
        self::assertCount(1, $this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain),
            new VisitsListFiltering(null, false, null, 3, 2),
        ));
    }

    /** @test */
    public function countVisitsByShortCodeReturnsProperData(): void
    {
        [$shortCode, $domain] = $this->createShortUrlsAndVisits();

        self::assertEquals(0, $this->repo->countVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain('invalid'),
            new VisitsCountFiltering(),
        ));
        self::assertEquals(6, $this->repo->countVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsCountFiltering(),
        ));
        self::assertEquals(4, $this->repo->countVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsCountFiltering(null, true),
        ));
        self::assertEquals(3, $this->repo->countVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain),
            new VisitsCountFiltering(),
        ));
        self::assertEquals(2, $this->repo->countVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsCountFiltering(
                DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
            ),
        ));
        self::assertEquals(4, $this->repo->countVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            new VisitsCountFiltering(DateRange::since(Chronos::parse('2016-01-03'))),
        ));
        self::assertEquals(1, $this->repo->countVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain),
            new VisitsCountFiltering(DateRange::since(Chronos::parse('2016-01-03'))),
        ));
    }

    /** @test */
    public function findVisitsByShortCodeReturnsProperDataWhenUsingAPiKeys(): void
    {
        $adminApiKey = ApiKey::create();
        $this->getEntityManager()->persist($adminApiKey);

        $restrictedApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($restrictedApiKey);

        $this->getEntityManager()->flush();

        [$shortCode1] = $this->createShortUrlsAndVisits(true, [], $adminApiKey);
        [$shortCode2] = $this->createShortUrlsAndVisits('bar.com', [], $restrictedApiKey);

        self::assertNotEmpty($this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode1),
            new VisitsListFiltering(null, false, $adminApiKey),
        ));
        self::assertNotEmpty($this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode2),
            new VisitsListFiltering(null, false, $adminApiKey),
        ));
        self::assertEmpty($this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode1),
            new VisitsListFiltering(null, false, $restrictedApiKey),
        ));
        self::assertNotEmpty($this->repo->findVisitsByShortCode(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode2),
            new VisitsListFiltering(null, false, $restrictedApiKey),
        ));
    }

    /** @test */
    public function findVisitsByTagReturnsProperData(): void
    {
        $foo = 'foo';

        $this->createShortUrlsAndVisits(false, [$foo]);
        $this->getEntityManager()->flush();

        $this->createShortUrlsAndVisits(false, [$foo]);
        $this->getEntityManager()->flush();

        $this->createShortUrlsAndVisits(false, [$foo]);
        $this->getEntityManager()->flush();

        self::assertCount(0, $this->repo->findVisitsByTag('invalid', new VisitsListFiltering()));
        self::assertCount(18, $this->repo->findVisitsByTag($foo, new VisitsListFiltering()));
        self::assertCount(12, $this->repo->findVisitsByTag($foo, new VisitsListFiltering(null, true)));
        self::assertCount(6, $this->repo->findVisitsByTag($foo, new VisitsListFiltering(
            DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
        )));
        self::assertCount(12, $this->repo->findVisitsByTag($foo, new VisitsListFiltering(
            DateRange::since(Chronos::parse('2016-01-03')),
        )));
    }

    /** @test */
    public function countVisitsByTagReturnsProperData(): void
    {
        $foo = 'foo';

        $this->createShortUrlsAndVisits(false, [$foo]);
        $this->getEntityManager()->flush();

        $this->createShortUrlsAndVisits(false, [$foo]);
        $this->getEntityManager()->flush();

        self::assertEquals(0, $this->repo->countVisitsByTag('invalid', new VisitsCountFiltering()));
        self::assertEquals(12, $this->repo->countVisitsByTag($foo, new VisitsCountFiltering()));
        self::assertEquals(8, $this->repo->countVisitsByTag($foo, new VisitsCountFiltering(null, true)));
        self::assertEquals(4, $this->repo->countVisitsByTag($foo, new VisitsCountFiltering(
            DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
        )));
        self::assertEquals(8, $this->repo->countVisitsByTag($foo, new VisitsCountFiltering(
            DateRange::since(Chronos::parse('2016-01-03')),
        )));
    }

    /** @test */
    public function findVisitsByDomainReturnsProperData(): void
    {
        $this->createShortUrlsAndVisits('s.test');
        $this->getEntityManager()->flush();

        self::assertCount(0, $this->repo->findVisitsByDomain('invalid', new VisitsListFiltering()));
        self::assertCount(6, $this->repo->findVisitsByDomain('DEFAULT', new VisitsListFiltering()));
        self::assertCount(3, $this->repo->findVisitsByDomain('s.test', new VisitsListFiltering()));
        self::assertCount(1, $this->repo->findVisitsByDomain('s.test', new VisitsListFiltering(null, true)));
        self::assertCount(2, $this->repo->findVisitsByDomain('s.test', new VisitsListFiltering(
            DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
        )));
        self::assertCount(1, $this->repo->findVisitsByDomain('s.test', new VisitsListFiltering(
            DateRange::since(Chronos::parse('2016-01-03')),
        )));
        self::assertCount(2, $this->repo->findVisitsByDomain('DEFAULT', new VisitsListFiltering(
            DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
        )));
        self::assertCount(4, $this->repo->findVisitsByDomain('DEFAULT', new VisitsListFiltering(
            DateRange::since(Chronos::parse('2016-01-03')),
        )));
    }

    /** @test */
    public function countVisitsByDomainReturnsProperData(): void
    {
        $this->createShortUrlsAndVisits('s.test');
        $this->getEntityManager()->flush();

        self::assertEquals(0, $this->repo->countVisitsByDomain('invalid', new VisitsListFiltering()));
        self::assertEquals(6, $this->repo->countVisitsByDomain('DEFAULT', new VisitsListFiltering()));
        self::assertEquals(3, $this->repo->countVisitsByDomain('s.test', new VisitsListFiltering()));
        self::assertEquals(1, $this->repo->countVisitsByDomain('s.test', new VisitsListFiltering(null, true)));
        self::assertEquals(2, $this->repo->countVisitsByDomain('s.test', new VisitsListFiltering(
            DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
        )));
        self::assertEquals(1, $this->repo->countVisitsByDomain('s.test', new VisitsListFiltering(
            DateRange::since(Chronos::parse('2016-01-03')),
        )));
        self::assertEquals(2, $this->repo->countVisitsByDomain('DEFAULT', new VisitsListFiltering(
            DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
        )));
        self::assertEquals(4, $this->repo->countVisitsByDomain('DEFAULT', new VisitsListFiltering(
            DateRange::since(Chronos::parse('2016-01-03')),
        )));
    }

    /** @test */
    public function countVisitsReturnsExpectedResultBasedOnApiKey(): void
    {
        $domain = Domain::withAuthority('foo.com');
        $this->getEntityManager()->persist($domain);

        $this->getEntityManager()->flush();

        $apiKey1 = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($apiKey1);
        $shortUrl = ShortUrl::create(
            ShortUrlCreation::fromRawData(['apiKey' => $apiKey1, 'domain' => $domain->getAuthority(), 'longUrl' => '']),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($shortUrl);
        $this->createVisitsForShortUrl($shortUrl, 4);

        $apiKey2 = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($apiKey2);
        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData(['apiKey' => $apiKey2, 'longUrl' => '']));
        $this->getEntityManager()->persist($shortUrl2);
        $this->createVisitsForShortUrl($shortUrl2, 5);

        $shortUrl3 = ShortUrl::create(
            ShortUrlCreation::fromRawData(['apiKey' => $apiKey2, 'domain' => $domain->getAuthority(), 'longUrl' => '']),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($shortUrl3);
        $this->createVisitsForShortUrl($shortUrl3, 7);

        $domainApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forDomain($domain)));
        $this->getEntityManager()->persist($domainApiKey);

        // Visits not linked to any short URL
        $this->getEntityManager()->persist(Visit::forBasePath(Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forInvalidShortUrl(Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forRegularNotFound(Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forRegularNotFound(Visitor::botInstance()));

        $this->getEntityManager()->flush();

        self::assertEquals(4 + 5 + 7, $this->repo->countNonOrphanVisits(new VisitsCountFiltering()));
        self::assertEquals(4, $this->repo->countNonOrphanVisits(VisitsCountFiltering::withApiKey($apiKey1)));
        self::assertEquals(5 + 7, $this->repo->countNonOrphanVisits(VisitsCountFiltering::withApiKey($apiKey2)));
        self::assertEquals(4 + 7, $this->repo->countNonOrphanVisits(VisitsCountFiltering::withApiKey($domainApiKey)));
        self::assertEquals(4, $this->repo->countNonOrphanVisits(new VisitsCountFiltering(DateRange::since(
            Chronos::parse('2016-01-05')->startOfDay(),
        ))));
        self::assertEquals(2, $this->repo->countNonOrphanVisits(new VisitsCountFiltering(DateRange::since(
            Chronos::parse('2016-01-03')->startOfDay(),
        ), false, $apiKey1)));
        self::assertEquals(1, $this->repo->countNonOrphanVisits(new VisitsCountFiltering(DateRange::since(
            Chronos::parse('2016-01-07')->startOfDay(),
        ), false, $apiKey2)));
        self::assertEquals(3 + 5, $this->repo->countNonOrphanVisits(new VisitsCountFiltering(null, true, $apiKey2)));
        self::assertEquals(4, $this->repo->countOrphanVisits(new VisitsCountFiltering()));
        self::assertEquals(3, $this->repo->countOrphanVisits(new VisitsCountFiltering(null, true)));
    }

    /** @test */
    public function findOrphanVisitsReturnsExpectedResult(): void
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData(['longUrl' => '']));
        $this->getEntityManager()->persist($shortUrl);
        $this->createVisitsForShortUrl($shortUrl, 7);

        $botsCount = 3;
        for ($i = 0; $i < 6; $i++) {
            $this->getEntityManager()->persist($this->setDateOnVisit(
                Visit::forBasePath($botsCount < 1 ? Visitor::emptyInstance() : Visitor::botInstance()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));
            $this->getEntityManager()->persist($this->setDateOnVisit(
                Visit::forInvalidShortUrl(Visitor::emptyInstance()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));
            $this->getEntityManager()->persist($this->setDateOnVisit(
                Visit::forRegularNotFound(Visitor::emptyInstance()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));

            $botsCount--;
        }

        $this->getEntityManager()->flush();

        self::assertCount(18, $this->repo->findOrphanVisits(new VisitsListFiltering()));
        self::assertCount(15, $this->repo->findOrphanVisits(new VisitsListFiltering(null, true)));
        self::assertCount(5, $this->repo->findOrphanVisits(new VisitsListFiltering(null, false, null, 5)));
        self::assertCount(10, $this->repo->findOrphanVisits(new VisitsListFiltering(null, false, null, 15, 8)));
        self::assertCount(9, $this->repo->findOrphanVisits(new VisitsListFiltering(
            DateRange::since(Chronos::parse('2020-01-04')),
            false,
            null,
            15,
        )));
        self::assertCount(2, $this->repo->findOrphanVisits(new VisitsListFiltering(
            DateRange::between(Chronos::parse('2020-01-02'), Chronos::parse('2020-01-03')),
            false,
            null,
            6,
            4,
        )));
        self::assertCount(3, $this->repo->findOrphanVisits(new VisitsListFiltering(
            DateRange::until(Chronos::parse('2020-01-01')),
        )));
    }

    /** @test */
    public function countOrphanVisitsReturnsExpectedResult(): void
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData(['longUrl' => '']));
        $this->getEntityManager()->persist($shortUrl);
        $this->createVisitsForShortUrl($shortUrl, 7);

        for ($i = 0; $i < 6; $i++) {
            $this->getEntityManager()->persist($this->setDateOnVisit(
                Visit::forBasePath(Visitor::emptyInstance()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));
            $this->getEntityManager()->persist($this->setDateOnVisit(
                Visit::forInvalidShortUrl(Visitor::emptyInstance()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));
            $this->getEntityManager()->persist($this->setDateOnVisit(
                Visit::forRegularNotFound(Visitor::emptyInstance()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));
        }

        $this->getEntityManager()->flush();

        self::assertEquals(18, $this->repo->countOrphanVisits(new VisitsCountFiltering()));
        self::assertEquals(18, $this->repo->countOrphanVisits(new VisitsCountFiltering(DateRange::allTime())));
        self::assertEquals(9, $this->repo->countOrphanVisits(
            new VisitsCountFiltering(DateRange::since(Chronos::parse('2020-01-04'))),
        ));
        self::assertEquals(6, $this->repo->countOrphanVisits(new VisitsCountFiltering(
            DateRange::between(Chronos::parse('2020-01-02'), Chronos::parse('2020-01-03')),
        )));
        self::assertEquals(3, $this->repo->countOrphanVisits(
            new VisitsCountFiltering(DateRange::until(Chronos::parse('2020-01-01'))),
        ));
    }

    /** @test */
    public function findNonOrphanVisitsReturnsExpectedResult(): void
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData(['longUrl' => '1']));
        $this->getEntityManager()->persist($shortUrl);
        $this->createVisitsForShortUrl($shortUrl, 7);

        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData(['longUrl' => '2']));
        $this->getEntityManager()->persist($shortUrl2);
        $this->createVisitsForShortUrl($shortUrl2, 4);

        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData(['longUrl' => '3']));
        $this->getEntityManager()->persist($shortUrl3);
        $this->createVisitsForShortUrl($shortUrl3, 10);

        $this->getEntityManager()->flush();

        self::assertCount(21, $this->repo->findNonOrphanVisits(new VisitsListFiltering()));
        self::assertCount(21, $this->repo->findNonOrphanVisits(new VisitsListFiltering(DateRange::allTime())));
        self::assertCount(7, $this->repo->findNonOrphanVisits(new VisitsListFiltering(DateRange::since(
            Chronos::parse('2016-01-05')->endOfDay(),
        ))));
        self::assertCount(12, $this->repo->findNonOrphanVisits(new VisitsListFiltering(DateRange::until(
            Chronos::parse('2016-01-04')->endOfDay(),
        ))));
        self::assertCount(6, $this->repo->findNonOrphanVisits(new VisitsListFiltering(DateRange::between(
            Chronos::parse('2016-01-03')->startOfDay(),
            Chronos::parse('2016-01-04')->endOfDay(),
        ))));
        self::assertCount(13, $this->repo->findNonOrphanVisits(new VisitsListFiltering(DateRange::between(
            Chronos::parse('2016-01-03')->startOfDay(),
            Chronos::parse('2016-01-08')->endOfDay(),
        ))));
        self::assertCount(3, $this->repo->findNonOrphanVisits(new VisitsListFiltering(DateRange::between(
            Chronos::parse('2016-01-03')->startOfDay(),
            Chronos::parse('2016-01-08')->endOfDay(),
        ), false, null, 10, 10)));
        self::assertCount(15, $this->repo->findNonOrphanVisits(new VisitsListFiltering(null, true)));
        self::assertCount(10, $this->repo->findNonOrphanVisits(new VisitsListFiltering(null, false, null, 10)));
        self::assertCount(1, $this->repo->findNonOrphanVisits(new VisitsListFiltering(null, false, null, 10, 20)));
        self::assertCount(5, $this->repo->findNonOrphanVisits(new VisitsListFiltering(null, false, null, 5, 5)));
    }

    /** @test */
    public function findMostRecentOrphanVisitReturnsExpectedVisit(): void
    {
        $this->assertNull($this->repo->findMostRecentOrphanVisit());

        $lastVisit = Visit::forBasePath(Visitor::emptyInstance());
        $this->getEntityManager()->persist($lastVisit);
        $this->getEntityManager()->flush();

        $this->assertSame($lastVisit, $this->repo->findMostRecentOrphanVisit());

        $lastVisit2 = Visit::forRegularNotFound(Visitor::botInstance());
        $this->getEntityManager()->persist($lastVisit2);
        $this->getEntityManager()->flush();

        $this->assertSame($lastVisit2, $this->repo->findMostRecentOrphanVisit());
    }

    /**
     * @return array{string, string, \Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl}
     */
    private function createShortUrlsAndVisits(
        bool|string $withDomain = true,
        array $tags = [],
        ?ApiKey $apiKey = null,
    ): array {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            ShortUrlInputFilter::LONG_URL => '',
            ShortUrlInputFilter::TAGS => $tags,
            ShortUrlInputFilter::API_KEY => $apiKey,
        ]), $this->relationResolver);
        $domain = is_string($withDomain) ? $withDomain : 'example.com';
        $shortCode = $shortUrl->getShortCode();
        $this->getEntityManager()->persist($shortUrl);

        $this->createVisitsForShortUrl($shortUrl);

        if ($withDomain !== false) {
            $shortUrlWithDomain = ShortUrl::create(ShortUrlCreation::fromRawData([
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

    private function createVisitsForShortUrl(ShortUrl $shortUrl, int $amount = 6, int $botsAmount = 2): void
    {
        for ($i = 0; $i < $amount; $i++) {
            $visit = $this->setDateOnVisit(
                Visit::forValidShortUrl(
                    $shortUrl,
                    $botsAmount < 1 ? Visitor::emptyInstance() : Visitor::botInstance(),
                ),
                Chronos::parse(sprintf('2016-01-%s', str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)))->startOfDay(),
            );
            $botsAmount--;

            $this->getEntityManager()->persist($visit);
        }
    }

    private function setDateOnVisit(Visit $visit, Chronos $date): Visit
    {
        $ref = new ReflectionObject($visit);
        $dateProp = $ref->getProperty('date');
        $dateProp->setAccessible(true);
        $dateProp->setValue($visit, $date);

        return $visit;
    }
}
