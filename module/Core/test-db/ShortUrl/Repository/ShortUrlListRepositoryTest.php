<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\ShortUrl\Repository;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\Test;
use ReflectionObject;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\OrderableField;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlWithDeps;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsCountFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsListFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlListRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function array_map;
use function count;
use function range;
use function Shlinkio\Shlink\Core\ArrayUtils\map;

class ShortUrlListRepositoryTest extends DatabaseTestCase
{
    private ShortUrlListRepository $repo;
    private PersistenceShortUrlRelationResolver $relationResolver;

    protected function setUp(): void
    {
        $em = $this->getEntityManager();
        $this->repo = $this->createRepository(ShortUrl::class, ShortUrlListRepository::class);
        $this->relationResolver = new PersistenceShortUrlRelationResolver($em);
    }

    #[Test]
    public function countListReturnsProperNumberOfResults(): void
    {
        $count = 5;
        for ($i = 0; $i < $count; $i++) {
            $this->getEntityManager()->persist(ShortUrl::withLongUrl('https://' . $i));
        }
        $this->getEntityManager()->flush();

        self::assertEquals($count, $this->repo->countList(new ShortUrlsCountFiltering()));
    }

    #[Test]
    public function findListProperlyFiltersResult(): void
    {
        $foo = ShortUrl::create(
            ShortUrlCreation::fromRawData(['longUrl' => 'https://foo', 'tags' => ['bar']]),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($foo);

        $bar = ShortUrl::withLongUrl('https://bar');
        $visits = array_map(function () use ($bar) {
            $visit = Visit::forValidShortUrl($bar, Visitor::botInstance());
            $this->getEntityManager()->persist($visit);

            return $visit;
        }, range(0, 5));
        $bar->setVisits(new ArrayCollection($visits));
        $this->getEntityManager()->persist($bar);

        $foo2 = ShortUrl::withLongUrl('https://foo_2');
        $visits2 = array_map(function () use ($foo2) {
            $visit = Visit::forValidShortUrl($foo2, Visitor::empty());
            $this->getEntityManager()->persist($visit);

            return $visit;
        }, range(0, 3));
        $foo2->setVisits(new ArrayCollection($visits2));
        $ref = new ReflectionObject($foo2);
        $dateProp = $ref->getProperty('dateCreated');
        $dateProp->setValue($foo2, Chronos::now()->subDays(5));
        $this->getEntityManager()->persist($foo2);

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(new ShortUrlsListFiltering(searchTerm: 'foo', tags: ['bar']));
        self::assertCount(1, $result);
        self::assertEquals(1, $this->repo->countList(new ShortUrlsCountFiltering('foo', ['bar'])));
        self::assertSame($foo, $result[0]->shortUrl);

        // Assert searched text also applies to tags
        $result = $this->repo->findList(new ShortUrlsListFiltering(searchTerm: 'bar'));
        self::assertCount(2, $result);
        self::assertEquals(2, $this->repo->countList(new ShortUrlsCountFiltering('bar')));
        self::assertContains($foo, map($result, fn (ShortUrlWithDeps $s) => $s->shortUrl));

        $result = $this->repo->findList(new ShortUrlsListFiltering());
        self::assertCount(3, $result);

        $result = $this->repo->findList(new ShortUrlsListFiltering(limit: 2));
        self::assertCount(2, $result);

        $result = $this->repo->findList(new ShortUrlsListFiltering(limit: 2, offset: 1));
        self::assertCount(2, $result);

        self::assertCount(1, $this->repo->findList(new ShortUrlsListFiltering(limit: 2, offset: 2)));

        $result = $this->repo->findList(new ShortUrlsListFiltering(
            orderBy: Ordering::fromFieldDesc(OrderableField::VISITS->value),
        ));
        self::assertCount(3, $result);
        self::assertSame($bar, $result[0]->shortUrl);

        $result = $this->repo->findList(new ShortUrlsListFiltering(
            orderBy: Ordering::fromFieldDesc(OrderableField::NON_BOT_VISITS->value),
        ));
        self::assertCount(3, $result);
        self::assertSame($foo2, $result[0]->shortUrl);

        $result = $this->repo->findList(new ShortUrlsListFiltering(
            dateRange: DateRange::until(Chronos::now()->subDays(2)),
        ));
        self::assertCount(1, $result);
        self::assertEquals(1, $this->repo->countList(new ShortUrlsCountFiltering(
            dateRange: DateRange::until(Chronos::now()->subDays(2)),
        )));
        self::assertSame($foo2, $result[0]->shortUrl);

        self::assertCount(2, $this->repo->findList(new ShortUrlsListFiltering(
            dateRange: DateRange::since(Chronos::now()->subDays(2)),
        )));
        self::assertEquals(2, $this->repo->countList(
            new ShortUrlsCountFiltering(dateRange: DateRange::since(Chronos::now()->subDays(2))),
        ));
    }

    #[Test]
    public function findListProperlyMapsFieldNamesToColumnNamesWhenOrdering(): void
    {
        $urls = ['https://a', 'https://z', 'https://c', 'https://b'];
        foreach ($urls as $url) {
            $this->getEntityManager()->persist(ShortUrl::withLongUrl($url));
        }

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(new ShortUrlsListFiltering(orderBy: Ordering::fromFieldAsc('longUrl')));

        self::assertCount(count($urls), $result);
        self::assertEquals('https://a', $result[0]->shortUrl->getLongUrl());
        self::assertEquals('https://b', $result[1]->shortUrl->getLongUrl());
        self::assertEquals('https://c', $result[2]->shortUrl->getLongUrl());
        self::assertEquals('https://z', $result[3]->shortUrl->getLongUrl());
    }

    #[Test]
    public function findListReturnsOnlyThoseWithMatchingTags(): void
    {
        $shortUrl1 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo1',
            'tags' => ['foo', 'bar'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo2',
            'tags' => ['foo', 'baz'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo3',
            'tags' => ['foo'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl3);
        $shortUrl4 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo4',
            'tags' => ['bar', 'baz'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl4);
        $shortUrl5 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo5',
            'tags' => ['bar', 'baz'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl5);

        $this->getEntityManager()->flush();

        self::assertCount(5, $this->repo->findList(new ShortUrlsListFiltering(tags: ['foo', 'bar'])));
        self::assertCount(5, $this->repo->findList(new ShortUrlsListFiltering(
            tags: ['foo', 'bar'],
            tagsMode: TagsMode::ANY,
        )));
        self::assertCount(1, $this->repo->findList(new ShortUrlsListFiltering(
            tags: ['foo', 'bar'],
            tagsMode: TagsMode::ALL,
        )));
        self::assertEquals(5, $this->repo->countList(new ShortUrlsCountFiltering(tags: ['foo', 'bar'])));
        self::assertEquals(5, $this->repo->countList(
            new ShortUrlsCountFiltering(tags: ['foo', 'bar'], tagsMode: TagsMode::ANY),
        ));
        self::assertEquals(1, $this->repo->countList(
            new ShortUrlsCountFiltering(tags: ['foo', 'bar'], tagsMode: TagsMode::ALL),
        ));

        self::assertCount(4, $this->repo->findList(new ShortUrlsListFiltering(tags: ['bar', 'baz'])));
        self::assertCount(4, $this->repo->findList(new ShortUrlsListFiltering(
            tags: ['bar', 'baz'],
            tagsMode: TagsMode::ANY,
        )));
        self::assertCount(2, $this->repo->findList(new ShortUrlsListFiltering(
            tags: ['bar', 'baz'],
            tagsMode: TagsMode::ALL,
        )));
        self::assertEquals(4, $this->repo->countList(new ShortUrlsCountFiltering(tags: ['bar', 'baz'])));
        self::assertEquals(4, $this->repo->countList(
            new ShortUrlsCountFiltering(tags: ['bar', 'baz'], tagsMode: TagsMode::ANY),
        ));
        self::assertEquals(2, $this->repo->countList(
            new ShortUrlsCountFiltering(tags: ['bar', 'baz'], tagsMode: TagsMode::ALL),
        ));

        self::assertCount(5, $this->repo->findList(new ShortUrlsListFiltering(tags: ['foo', 'bar', 'baz'])));
        self::assertCount(5, $this->repo->findList(new ShortUrlsListFiltering(
            tags: ['foo', 'bar', 'baz'],
            tagsMode: TagsMode::ANY,
        )));
        self::assertCount(0, $this->repo->findList(new ShortUrlsListFiltering(
            tags: ['foo', 'bar', 'baz'],
            tagsMode: TagsMode::ALL,
        )));
        self::assertEquals(5, $this->repo->countList(new ShortUrlsCountFiltering(tags: ['foo', 'bar', 'baz'])));
        self::assertEquals(5, $this->repo->countList(
            new ShortUrlsCountFiltering(tags: ['foo', 'bar', 'baz'], tagsMode: TagsMode::ANY),
        ));
        self::assertEquals(0, $this->repo->countList(
            new ShortUrlsCountFiltering(tags: ['foo', 'bar', 'baz'], tagsMode: TagsMode::ALL),
        ));

        self::assertEquals(2, $this->repo->countList(new ShortUrlsCountFiltering(excludeTags: ['foo'])));
        self::assertEquals(0, $this->repo->countList(new ShortUrlsCountFiltering(excludeTags: ['foo', 'bar'])));
        self::assertEquals(4, $this->repo->countList(new ShortUrlsCountFiltering(
            excludeTags: ['foo', 'bar'],
            excludeTagsMode: TagsMode::ALL,
        )));

        self::assertEquals(2, $this->repo->countList(new ShortUrlsCountFiltering(tags: ['foo'], excludeTags: ['bar'])));
        self::assertEquals(1, $this->repo->countList(new ShortUrlsCountFiltering(
            tags: ['foo'],
            excludeTags: ['bar', 'baz'],
        )));
        self::assertEquals(3, $this->repo->countList(new ShortUrlsCountFiltering(
            tags: ['foo'],
            excludeTags: ['bar', 'baz'],
            excludeTagsMode: TagsMode::ALL,
        )));
    }

    #[Test]
    public function findListReturnsOnlyThoseWithMatchingDomains(): void
    {
        $shortUrl1 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo1',
            'domain' => null,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo2',
            'domain' => null,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo3',
            'domain' => 'another.com',
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl3);

        $this->getEntityManager()->flush();

        $buildFiltering = static fn (string $searchTerm = '', string|null $domain = null) => new ShortUrlsListFiltering(
            searchTerm: $searchTerm,
            defaultDomain: 'deFaulT-domain.com',
            domain: $domain,
        );

        self::assertCount(2, $this->repo->findList($buildFiltering(searchTerm: 'default-dom')));
        self::assertCount(2, $this->repo->findList($buildFiltering(searchTerm: 'DOM')));
        self::assertCount(1, $this->repo->findList($buildFiltering(searchTerm: 'another')));
        self::assertCount(3, $this->repo->findList($buildFiltering(searchTerm: 'foo')));
        self::assertCount(0, $this->repo->findList($buildFiltering(searchTerm: 'no results')));
        self::assertCount(1, $this->repo->findList($buildFiltering(domain: 'another.com')));
        self::assertCount(0, $this->repo->findList($buildFiltering(
            searchTerm: 'default-domain.com',
            domain: 'another.com',
        )));
        self::assertCount(2, $this->repo->findList($buildFiltering(domain: Domain::DEFAULT_AUTHORITY)));
    }

    #[Test]
    public function findListReturnsOnlyThoseWithoutExcludedUrls(): void
    {
        $shortUrl1 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo1',
            'validUntil' => Chronos::now()->addDays(1)->toAtomString(),
            'maxVisits' => 100,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo2',
            'validUntil' => Chronos::now()->subDays(1)->toAtomString(),
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo3',
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl3);
        $shortUrl4 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo4',
            'maxVisits' => 3,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl4);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl4, Visitor::empty()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl4, Visitor::empty()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl4, Visitor::empty()));

        $this->getEntityManager()->flush();

        $filtering = static fn (bool $excludeMaxVisitsReached, bool $excludePastValidUntil) =>
            new ShortUrlsListFiltering(
                excludeMaxVisitsReached: $excludeMaxVisitsReached,
                excludePastValidUntil: $excludePastValidUntil,
            );

        self::assertCount(4, $this->repo->findList($filtering(
            excludeMaxVisitsReached: false,
            excludePastValidUntil: false,
        )));
        self::assertEquals(4, $this->repo->countList($filtering(
            excludeMaxVisitsReached: false,
            excludePastValidUntil: false,
        )));
        self::assertCount(3, $this->repo->findList($filtering(
            excludeMaxVisitsReached: true,
            excludePastValidUntil: false,
        )));
        self::assertEquals(3, $this->repo->countList($filtering(
            excludeMaxVisitsReached: true,
            excludePastValidUntil: false,
        )));
        self::assertCount(3, $this->repo->findList($filtering(
            excludeMaxVisitsReached: false,
            excludePastValidUntil: true,
        )));
        self::assertEquals(3, $this->repo->countList($filtering(
            excludeMaxVisitsReached: false,
            excludePastValidUntil: true,
        )));
        self::assertCount(2, $this->repo->findList($filtering(
            excludeMaxVisitsReached: true,
            excludePastValidUntil: true,
        )));
        self::assertEquals(2, $this->repo->countList($filtering(
            excludeMaxVisitsReached: true,
            excludePastValidUntil: true,
        )));
    }

    #[Test]
    public function filteringByApiKeyNameIsPossible(): void
    {
        $apiKey1 = ApiKey::create();
        $this->getEntityManager()->persist($apiKey1);
        $apiKey2 = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($apiKey2);
        $apiKey3 = ApiKey::create();
        $this->getEntityManager()->persist($apiKey3);

        $shortUrl1 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo1',
            'apiKey' => $apiKey1,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo2',
            'apiKey' => $apiKey1,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo3',
            'apiKey' => $apiKey2,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl3);
        $shortUrl4 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo4',
            'apiKey' => $apiKey1,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl4);

        $this->getEntityManager()->flush();

        // It is possible to filter by API key name when no API key or ADMIN API key is provided
        self::assertCount(3, $this->repo->findList(new ShortUrlsListFiltering(apiKeyName: $apiKey1->name)));
        self::assertCount(1, $this->repo->findList(new ShortUrlsListFiltering(apiKeyName: $apiKey2->name)));
        self::assertCount(0, $this->repo->findList(new ShortUrlsListFiltering(apiKeyName: $apiKey3->name)));

        self::assertCount(3, $this->repo->findList(new ShortUrlsListFiltering(
            apiKey: $apiKey1,
            apiKeyName: $apiKey1->name,
        )));
        self::assertCount(1, $this->repo->findList(new ShortUrlsListFiltering(
            apiKey: $apiKey1,
            apiKeyName: $apiKey2->name,
        )));
        self::assertCount(0, $this->repo->findList(new ShortUrlsListFiltering(
            apiKey: $apiKey1,
            apiKeyName: $apiKey3->name,
        )));

        // When a non-admin API key is passed, it allows to filter by itself only
        self::assertCount(1, $this->repo->findList(new ShortUrlsListFiltering(
            apiKey: $apiKey2,
            apiKeyName: $apiKey1->name, // Ignored. Only API key 2 results are returned
        )));
        self::assertCount(1, $this->repo->findList(new ShortUrlsListFiltering(
            apiKey: $apiKey2,
            apiKeyName: $apiKey2->name,
        )));
        self::assertCount(1, $this->repo->findList(new ShortUrlsListFiltering(
            apiKey: $apiKey2,
            apiKeyName: $apiKey3->name, // Ignored. Only API key 2 results are returned
        )));
    }
}
