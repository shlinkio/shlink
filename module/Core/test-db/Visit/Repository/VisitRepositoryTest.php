<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Visit\Repository;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Visit\Entity\OrphanVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\ShortUrlVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitType;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Repository\OrphanVisitsCountRepository;
use Shlinkio\Shlink\Core\Visit\Repository\ShortUrlVisitsCountRepository;
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
    private ShortUrlVisitsCountRepository $countRepo;
    private OrphanVisitsCountRepository $orphanCountRepo;
    private PersistenceShortUrlRelationResolver $relationResolver;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Visit::class);

        // Testing the visits count repositories in this very same test, helps checking the fact that results should
        // match what VisitRepository returns
        $this->countRepo = $this->getEntityManager()->getRepository(ShortUrlVisitsCount::class);
        $this->orphanCountRepo = $this->getEntityManager()->getRepository(OrphanVisitsCount::class);

        $this->relationResolver = new PersistenceShortUrlRelationResolver($this->getEntityManager());
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function findVisitsByDomainReturnsProperData(): void
    {
        $this->createShortUrlsAndVisits('s.test');
        $this->getEntityManager()->flush();

        self::assertCount(0, $this->repo->findVisitsByDomain('invalid', new VisitsListFiltering()));
        self::assertCount(6, $this->repo->findVisitsByDomain(Domain::DEFAULT_AUTHORITY, new VisitsListFiltering()));
        self::assertCount(3, $this->repo->findVisitsByDomain('s.test', new VisitsListFiltering()));
        self::assertCount(1, $this->repo->findVisitsByDomain('s.test', new VisitsListFiltering(null, true)));
        self::assertCount(2, $this->repo->findVisitsByDomain('s.test', new VisitsListFiltering(
            DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
        )));
        self::assertCount(1, $this->repo->findVisitsByDomain('s.test', new VisitsListFiltering(
            DateRange::since(Chronos::parse('2016-01-03')),
        )));
        self::assertCount(2, $this->repo->findVisitsByDomain(Domain::DEFAULT_AUTHORITY, new VisitsListFiltering(
            DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
        )));
        self::assertCount(4, $this->repo->findVisitsByDomain(Domain::DEFAULT_AUTHORITY, new VisitsListFiltering(
            DateRange::since(Chronos::parse('2016-01-03')),
        )));
    }

    #[Test]
    public function countVisitsByDomainReturnsProperData(): void
    {
        $this->createShortUrlsAndVisits('s.test');
        $this->getEntityManager()->flush();

        self::assertEquals(0, $this->repo->countVisitsByDomain('invalid', new VisitsListFiltering()));
        self::assertEquals(6, $this->repo->countVisitsByDomain(Domain::DEFAULT_AUTHORITY, new VisitsListFiltering()));
        self::assertEquals(3, $this->repo->countVisitsByDomain('s.test', new VisitsListFiltering()));
        self::assertEquals(1, $this->repo->countVisitsByDomain('s.test', new VisitsListFiltering(null, true)));
        self::assertEquals(2, $this->repo->countVisitsByDomain('s.test', new VisitsListFiltering(
            DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
        )));
        self::assertEquals(1, $this->repo->countVisitsByDomain('s.test', new VisitsListFiltering(
            DateRange::since(Chronos::parse('2016-01-03')),
        )));
        self::assertEquals(2, $this->repo->countVisitsByDomain(Domain::DEFAULT_AUTHORITY, new VisitsListFiltering(
            DateRange::between(Chronos::parse('2016-01-02'), Chronos::parse('2016-01-03')),
        )));
        self::assertEquals(4, $this->repo->countVisitsByDomain(Domain::DEFAULT_AUTHORITY, new VisitsListFiltering(
            DateRange::since(Chronos::parse('2016-01-03')),
        )));
    }

    #[Test]
    public function countVisitsReturnsExpectedResultBasedOnApiKey(): void
    {
        $domain = Domain::withAuthority('foo.com');
        $this->getEntityManager()->persist($domain);

        $this->getEntityManager()->flush();

        $noOrphanVisitsApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forNoOrphanVisits()));
        $this->getEntityManager()->persist($noOrphanVisitsApiKey);

        $apiKey1 = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($apiKey1);
        $shortUrl = ShortUrl::create(
            ShortUrlCreation::fromRawData(
                ['apiKey' => $apiKey1, 'domain' => $domain->authority, 'longUrl' => 'https://longUrl'],
            ),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($shortUrl);
        $this->createVisitsForShortUrl($shortUrl, 4);

        $apiKey2 = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($apiKey2);
        $shortUrl2 = ShortUrl::create(
            ShortUrlCreation::fromRawData(['apiKey' => $apiKey2, 'longUrl' => 'https://longUrl']),
        );
        $this->getEntityManager()->persist($shortUrl2);
        $this->createVisitsForShortUrl($shortUrl2, 5);

        $shortUrl3 = ShortUrl::create(
            ShortUrlCreation::fromRawData(
                ['apiKey' => $apiKey2, 'domain' => $domain->authority, 'longUrl' => 'https://longUrl'],
            ),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($shortUrl3);
        $this->createVisitsForShortUrl($shortUrl3, 7);

        $domainApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forDomain($domain)));
        $this->getEntityManager()->persist($domainApiKey);

        // Visits not linked to any short URL
        $this->getEntityManager()->persist(Visit::forBasePath(Visitor::empty()));
        $this->getEntityManager()->persist(Visit::forInvalidShortUrl(Visitor::empty()));
        $this->getEntityManager()->persist(Visit::forRegularNotFound(Visitor::empty()));
        $this->getEntityManager()->persist(Visit::forRegularNotFound(Visitor::botInstance()));

        $this->getEntityManager()->flush();

        self::assertEquals(4 + 5 + 7, $this->repo->countNonOrphanVisits(new VisitsCountFiltering()));
        self::assertEquals(4 + 5 + 7, $this->countRepo->countNonOrphanVisits(new VisitsCountFiltering()));
        self::assertEquals(4, $this->repo->countNonOrphanVisits(new VisitsCountFiltering(apiKey: $apiKey1)));
        self::assertEquals(4, $this->countRepo->countNonOrphanVisits(new VisitsCountFiltering(apiKey: $apiKey1)));
        self::assertEquals(5 + 7, $this->repo->countNonOrphanVisits(new VisitsCountFiltering(apiKey: $apiKey2)));
        self::assertEquals(5 + 7, $this->countRepo->countNonOrphanVisits(new VisitsCountFiltering(apiKey: $apiKey2)));
        self::assertEquals(4 + 7, $this->repo->countNonOrphanVisits(new VisitsCountFiltering(apiKey: $domainApiKey)));
        self::assertEquals(4 + 7, $this->countRepo->countNonOrphanVisits(new VisitsCountFiltering(
            apiKey: $domainApiKey,
        )));
        self::assertEquals(0, $this->repo->countOrphanVisits(new OrphanVisitsCountFiltering(
            apiKey: $noOrphanVisitsApiKey,
        )));
        self::assertEquals(0, $this->orphanCountRepo->countOrphanVisits(new OrphanVisitsCountFiltering(
            apiKey: $noOrphanVisitsApiKey,
        )));
        self::assertEquals(4, $this->repo->countNonOrphanVisits(new VisitsCountFiltering(DateRange::since(
            Chronos::parse('2016-01-05')->startOfDay(),
        ))));
        self::assertEquals(2, $this->repo->countNonOrphanVisits(new VisitsCountFiltering(DateRange::since(
            Chronos::parse('2016-01-03')->startOfDay(),
        ), false, $apiKey1)));
        self::assertEquals(1, $this->repo->countNonOrphanVisits(new VisitsCountFiltering(DateRange::since(
            Chronos::parse('2016-01-07')->startOfDay(),
        ), false, $apiKey2)));
        self::assertEquals(3 + 5, $this->repo->countNonOrphanVisits(
            new VisitsCountFiltering(excludeBots: true, apiKey: $apiKey2),
        ));
        self::assertEquals(3 + 5, $this->countRepo->countNonOrphanVisits(
            new VisitsCountFiltering(excludeBots: true, apiKey: $apiKey2),
        ));
        self::assertEquals(4, $this->repo->countOrphanVisits(new OrphanVisitsCountFiltering()));
        self::assertEquals(4, $this->orphanCountRepo->countOrphanVisits(new OrphanVisitsCountFiltering()));
        self::assertEquals(3, $this->repo->countOrphanVisits(new OrphanVisitsCountFiltering(excludeBots: true)));
        self::assertEquals(3, $this->orphanCountRepo->countOrphanVisits(
            new OrphanVisitsCountFiltering(excludeBots: true),
        ));
    }

    #[Test]
    public function findOrphanVisitsReturnsExpectedResult(): void
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData(['longUrl' => 'https://longUrl']));
        $this->getEntityManager()->persist($shortUrl);
        $this->createVisitsForShortUrl($shortUrl, 7);

        $noOrphanVisitsApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forNoOrphanVisits()));
        $this->getEntityManager()->persist($noOrphanVisitsApiKey);

        $botsCount = 3;
        for ($i = 0; $i < 6; $i++) {
            $this->getEntityManager()->persist($this->setDateOnVisit(
                fn () => Visit::forBasePath($botsCount < 1 ? Visitor::empty() : Visitor::botInstance()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));
            $this->getEntityManager()->persist($this->setDateOnVisit(
                fn () => Visit::forInvalidShortUrl(Visitor::empty()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));
            $this->getEntityManager()->persist($this->setDateOnVisit(
                fn () => Visit::forRegularNotFound(Visitor::empty()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));

            $botsCount--;
        }

        $this->getEntityManager()->flush();

        self::assertCount(0, $this->repo->findOrphanVisits(new OrphanVisitsListFiltering(
            apiKey: $noOrphanVisitsApiKey,
        )));
        self::assertCount(18, $this->repo->findOrphanVisits(new OrphanVisitsListFiltering()));
        self::assertCount(15, $this->repo->findOrphanVisits(new OrphanVisitsListFiltering(excludeBots:  true)));
        self::assertCount(5, $this->repo->findOrphanVisits(new OrphanVisitsListFiltering(limit: 5)));
        self::assertCount(10, $this->repo->findOrphanVisits(new OrphanVisitsListFiltering(limit: 15, offset: 8)));
        self::assertCount(9, $this->repo->findOrphanVisits(new OrphanVisitsListFiltering(
            dateRange: DateRange::since(Chronos::parse('2020-01-04')),
            limit: 15,
        )));
        self::assertCount(2, $this->repo->findOrphanVisits(new OrphanVisitsListFiltering(
            dateRange: DateRange::between(Chronos::parse('2020-01-02'), Chronos::parse('2020-01-03')),
            limit: 6,
            offset: 4,
        )));
        self::assertCount(2, $this->repo->findOrphanVisits(new OrphanVisitsListFiltering(
            dateRange: DateRange::between(Chronos::parse('2020-01-02'), Chronos::parse('2020-01-03')),
            type: OrphanVisitType::INVALID_SHORT_URL,
        )));
        self::assertCount(3, $this->repo->findOrphanVisits(new OrphanVisitsListFiltering(
            DateRange::until(Chronos::parse('2020-01-01')),
        )));
        self::assertCount(6, $this->repo->findOrphanVisits(new OrphanVisitsListFiltering(
            type: OrphanVisitType::REGULAR_404,
        )));
        self::assertCount(4, $this->repo->findOrphanVisits(new OrphanVisitsListFiltering(
            type: OrphanVisitType::BASE_URL,
            limit: 4,
        )));
    }

    #[Test]
    public function countOrphanVisitsReturnsExpectedResult(): void
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData(['longUrl' => 'https://longUrl']));
        $this->getEntityManager()->persist($shortUrl);
        $this->createVisitsForShortUrl($shortUrl, 7);

        for ($i = 0; $i < 6; $i++) {
            $this->getEntityManager()->persist($this->setDateOnVisit(
                fn () => Visit::forBasePath(Visitor::empty()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));
            $this->getEntityManager()->persist($this->setDateOnVisit(
                fn () => Visit::forInvalidShortUrl(Visitor::empty()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));
            $this->getEntityManager()->persist($this->setDateOnVisit(
                fn () => Visit::forRegularNotFound(Visitor::empty()),
                Chronos::parse(sprintf('2020-01-0%s', $i + 1)),
            ));
        }

        $this->getEntityManager()->flush();

        self::assertEquals(18, $this->repo->countOrphanVisits(new OrphanVisitsCountFiltering()));
        self::assertEquals(18, $this->orphanCountRepo->countOrphanVisits(new OrphanVisitsCountFiltering()));
        self::assertEquals(18, $this->repo->countOrphanVisits(new OrphanVisitsCountFiltering(DateRange::allTime())));
        self::assertEquals(9, $this->repo->countOrphanVisits(
            new OrphanVisitsCountFiltering(DateRange::since(Chronos::parse('2020-01-04'))),
        ));
        self::assertEquals(6, $this->repo->countOrphanVisits(new OrphanVisitsCountFiltering(
            DateRange::between(Chronos::parse('2020-01-02'), Chronos::parse('2020-01-03')),
        )));
        self::assertEquals(3, $this->repo->countOrphanVisits(
            new OrphanVisitsCountFiltering(DateRange::until(Chronos::parse('2020-01-01'))),
        ));
        self::assertEquals(2, $this->repo->countOrphanVisits(new OrphanVisitsCountFiltering(
            dateRange: DateRange::between(Chronos::parse('2020-01-02'), Chronos::parse('2020-01-03')),
            type: OrphanVisitType::BASE_URL,
        )));
        self::assertEquals(6, $this->repo->countOrphanVisits(new OrphanVisitsCountFiltering(
            type: OrphanVisitType::INVALID_SHORT_URL,
        )));
        self::assertEquals(6, $this->repo->countOrphanVisits(new OrphanVisitsCountFiltering(
            type: OrphanVisitType::REGULAR_404,
        )));
    }

    #[Test]
    public function findNonOrphanVisitsReturnsExpectedResult(): void
    {
        $authoredApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($authoredApiKey);

        $this->createShortUrlsAndVisits(withDomain: false, visitsAmount: 7);
        $this->createShortUrlsAndVisits(withDomain: false, apiKey: $authoredApiKey, visitsAmount: 4);
        $this->createShortUrlsAndVisits(withDomain: false, visitsAmount: 10);

        $this->getEntityManager()->flush();

        self::assertCount(21, $this->repo->findNonOrphanVisits(new VisitsListFiltering()));
        self::assertCount(21, $this->repo->findNonOrphanVisits(new VisitsListFiltering(DateRange::allTime())));
        self::assertCount(4, $this->repo->findNonOrphanVisits(new VisitsListFiltering(apiKey: $authoredApiKey)));
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
        ), limit: 10, offset: 10)));
        self::assertCount(15, $this->repo->findNonOrphanVisits(new VisitsListFiltering(excludeBots: true)));
        self::assertCount(10, $this->repo->findNonOrphanVisits(new VisitsListFiltering(limit: 10)));
        self::assertCount(1, $this->repo->findNonOrphanVisits(new VisitsListFiltering(limit: 10, offset: 20)));
        self::assertCount(5, $this->repo->findNonOrphanVisits(new VisitsListFiltering(limit: 5, offset: 5)));
    }

    #[Test]
    public function findMostRecentOrphanVisitReturnsExpectedVisit(): void
    {
        $this->assertNull($this->repo->findMostRecentOrphanVisit());

        $lastVisit = Visit::forBasePath(Visitor::empty());
        $this->getEntityManager()->persist($lastVisit);
        $this->getEntityManager()->flush();

        $this->assertSame($lastVisit, $this->repo->findMostRecentOrphanVisit());

        $lastVisit2 = Visit::forRegularNotFound(Visitor::botInstance());
        $this->getEntityManager()->persist($lastVisit2);
        $this->getEntityManager()->flush();

        $this->assertSame($lastVisit2, $this->repo->findMostRecentOrphanVisit());
    }

    /**
     * @return array{string, string, ShortUrl}
     */
    private function createShortUrlsAndVisits(
        bool|string $withDomain = true,
        array $tags = [],
        ApiKey|null $apiKey = null,
        int $visitsAmount = 6,
    ): array {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            ShortUrlInputFilter::LONG_URL => 'https://longUrl',
            ShortUrlInputFilter::TAGS => $tags,
            ShortUrlInputFilter::API_KEY => $apiKey,
        ]), $this->relationResolver);
        $domain = is_string($withDomain) ? $withDomain : 'example.com';
        $shortCode = $shortUrl->getShortCode();
        $this->getEntityManager()->persist($shortUrl);

        $this->createVisitsForShortUrl($shortUrl, $visitsAmount);

        if ($withDomain !== false) {
            $shortUrlWithDomain = ShortUrl::create(ShortUrlCreation::fromRawData([
                'customSlug' => $shortCode,
                'domain' => $domain,
                'longUrl' => 'https://longUrl',
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
                fn () => Visit::forValidShortUrl(
                    $shortUrl,
                    $botsAmount < 1 ? Visitor::empty() : Visitor::botInstance(),
                ),
                Chronos::parse(sprintf('2016-01-%s', str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)))->startOfDay(),
            );
            $botsAmount--;

            $this->getEntityManager()->persist($visit);
        }
    }

    /**
     * @param callable(): Visit $createVisit
     */
    private function setDateOnVisit(callable $createVisit, Chronos $date): Visit
    {
        Chronos::setTestNow($date);
        $visit = $createVisit();
        Chronos::setTestNow();

        return $visit;
    }
}
