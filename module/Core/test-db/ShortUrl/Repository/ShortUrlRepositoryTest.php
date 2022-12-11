<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\ShortUrl\Repository;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionObject;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsCountFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsListFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function count;

class ShortUrlRepositoryTest extends DatabaseTestCase
{
    private ShortUrlRepository $repo;
    private PersistenceShortUrlRelationResolver $relationResolver;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(ShortUrl::class);
        $this->relationResolver = new PersistenceShortUrlRelationResolver($this->getEntityManager());
    }

    /** @test */
    public function findOneWithDomainFallbackReturnsProperData(): void
    {
        $regularOne = ShortUrl::create(ShortUrlCreation::fromRawData(['customSlug' => 'foo', 'longUrl' => 'foo']));
        $this->getEntityManager()->persist($regularOne);

        $withDomain = ShortUrl::create(ShortUrlCreation::fromRawData(
            ['domain' => 'example.com', 'customSlug' => 'domain-short-code', 'longUrl' => 'foo'],
        ));
        $this->getEntityManager()->persist($withDomain);

        $withDomainDuplicatingRegular = ShortUrl::create(ShortUrlCreation::fromRawData(
            ['domain' => 'doma.in', 'customSlug' => 'foo', 'longUrl' => 'foo_with_domain'],
        ));
        $this->getEntityManager()->persist($withDomainDuplicatingRegular);

        $this->getEntityManager()->flush();

        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($regularOne->getShortCode()),
        ));
        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomainDuplicatingRegular->getShortCode()),
        ));
        self::assertSame($withDomain, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomain->getShortCode(), 'example.com'),
        ));
        self::assertSame(
            $withDomainDuplicatingRegular,
            $this->repo->findOneWithDomainFallback(
                ShortUrlIdentifier::fromShortCodeAndDomain($withDomainDuplicatingRegular->getShortCode(), 'doma.in'),
            ),
        );
        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback(ShortUrlIdentifier::fromShortCodeAndDomain(
            $withDomainDuplicatingRegular->getShortCode(),
            'other-domain.com',
        )));
        self::assertNull($this->repo->findOneWithDomainFallback(ShortUrlIdentifier::fromShortCodeAndDomain('invalid')));
        self::assertNull($this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomain->getShortCode()),
        ));
        self::assertNull($this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomain->getShortCode(), 'other-domain.com'),
        ));
    }

    /** @test */
    public function countListReturnsProperNumberOfResults(): void
    {
        $count = 5;
        for ($i = 0; $i < $count; $i++) {
            $this->getEntityManager()->persist(ShortUrl::withLongUrl((string) $i));
        }
        $this->getEntityManager()->flush();

        self::assertEquals($count, $this->repo->countList(new ShortUrlsCountFiltering()));
    }

    /** @test */
    public function findListProperlyFiltersResult(): void
    {
        $foo = ShortUrl::create(
            ShortUrlCreation::fromRawData(['longUrl' => 'foo', 'tags' => ['bar']]),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($foo);

        $bar = ShortUrl::withLongUrl('bar');
        $visit = Visit::forValidShortUrl($bar, Visitor::emptyInstance());
        $this->getEntityManager()->persist($visit);
        $bar->setVisits(new ArrayCollection([$visit]));
        $this->getEntityManager()->persist($bar);

        $foo2 = ShortUrl::withLongUrl('foo_2');
        $ref = new ReflectionObject($foo2);
        $dateProp = $ref->getProperty('dateCreated');
        $dateProp->setAccessible(true);
        $dateProp->setValue($foo2, Chronos::now()->subDays(5));
        $this->getEntityManager()->persist($foo2);

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), 'foo', ['bar']),
        );
        self::assertCount(1, $result);
        self::assertEquals(1, $this->repo->countList(new ShortUrlsCountFiltering('foo', ['bar'])));
        self::assertSame($foo, $result[0]);

        // Assert searched text also applies to tags
        $result = $this->repo->findList(new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), 'bar'));
        self::assertCount(2, $result);
        self::assertEquals(2, $this->repo->countList(new ShortUrlsCountFiltering('bar')));
        self::assertContains($foo, $result);

        $result = $this->repo->findList(new ShortUrlsListFiltering(null, null, Ordering::emptyInstance()));
        self::assertCount(3, $result);

        $result = $this->repo->findList(new ShortUrlsListFiltering(2, null, Ordering::emptyInstance()));
        self::assertCount(2, $result);

        $result = $this->repo->findList(new ShortUrlsListFiltering(2, 1, Ordering::emptyInstance()));
        self::assertCount(2, $result);

        self::assertCount(1, $this->repo->findList(new ShortUrlsListFiltering(2, 2, Ordering::emptyInstance())));

        $result = $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::fromTuple(['visits', 'DESC'])),
        );
        self::assertCount(3, $result);
        self::assertSame($bar, $result[0]);

        $result = $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), null, [], null, DateRange::until(
                Chronos::now()->subDays(2),
            )),
        );
        self::assertCount(1, $result);
        self::assertEquals(1, $this->repo->countList(new ShortUrlsCountFiltering(null, [], null, DateRange::until(
            Chronos::now()->subDays(2),
        ))));
        self::assertSame($foo2, $result[0]);

        self::assertCount(2, $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), null, [], null, DateRange::since(
                Chronos::now()->subDays(2),
            )),
        ));
        self::assertEquals(2, $this->repo->countList(
            new ShortUrlsCountFiltering(null, [], null, DateRange::since(Chronos::now()->subDays(2))),
        ));
    }

    /** @test */
    public function findListProperlyMapsFieldNamesToColumnNamesWhenOrdering(): void
    {
        $urls = ['a', 'z', 'c', 'b'];
        foreach ($urls as $url) {
            $this->getEntityManager()->persist(ShortUrl::withLongUrl($url));
        }

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::fromTuple(['longUrl', 'ASC'])),
        );

        self::assertCount(count($urls), $result);
        self::assertEquals('a', $result[0]->getLongUrl());
        self::assertEquals('b', $result[1]->getLongUrl());
        self::assertEquals('c', $result[2]->getLongUrl());
        self::assertEquals('z', $result[3]->getLongUrl());
    }

    /** @test */
    public function findListReturnsOnlyThoseWithMatchingTags(): void
    {
        $shortUrl1 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo1',
            'tags' => ['foo', 'bar'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo2',
            'tags' => ['foo', 'baz'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo3',
            'tags' => ['foo'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl3);
        $shortUrl4 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo4',
            'tags' => ['bar', 'baz'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl4);
        $shortUrl5 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo5',
            'tags' => ['bar', 'baz'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl5);

        $this->getEntityManager()->flush();

        self::assertCount(5, $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), null, ['foo', 'bar']),
        ));
        self::assertCount(5, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['foo', 'bar'],
            TagsMode::ANY,
        )));
        self::assertCount(1, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['foo', 'bar'],
            TagsMode::ALL,
        )));
        self::assertEquals(5, $this->repo->countList(new ShortUrlsCountFiltering(null, ['foo', 'bar'])));
        self::assertEquals(5, $this->repo->countList(new ShortUrlsCountFiltering(null, ['foo', 'bar'], TagsMode::ANY)));
        self::assertEquals(1, $this->repo->countList(new ShortUrlsCountFiltering(null, ['foo', 'bar'], TagsMode::ALL)));

        self::assertCount(4, $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), null, ['bar', 'baz']),
        ));
        self::assertCount(4, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['bar', 'baz'],
            TagsMode::ANY,
        )));
        self::assertCount(2, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['bar', 'baz'],
            TagsMode::ALL,
        )));
        self::assertEquals(4, $this->repo->countList(new ShortUrlsCountFiltering(null, ['bar', 'baz'])));
        self::assertEquals(4, $this->repo->countList(
            new ShortUrlsCountFiltering(null, ['bar', 'baz'], TagsMode::ANY),
        ));
        self::assertEquals(2, $this->repo->countList(
            new ShortUrlsCountFiltering(null, ['bar', 'baz'], TagsMode::ALL),
        ));

        self::assertCount(5, $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), null, ['foo', 'bar', 'baz']),
        ));
        self::assertCount(5, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['foo', 'bar', 'baz'],
            TagsMode::ANY,
        )));
        self::assertCount(0, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['foo', 'bar', 'baz'],
            TagsMode::ALL,
        )));
        self::assertEquals(5, $this->repo->countList(new ShortUrlsCountFiltering(null, ['foo', 'bar', 'baz'])));
        self::assertEquals(5, $this->repo->countList(
            new ShortUrlsCountFiltering(null, ['foo', 'bar', 'baz'], TagsMode::ANY),
        ));
        self::assertEquals(0, $this->repo->countList(
            new ShortUrlsCountFiltering(null, ['foo', 'bar', 'baz'], TagsMode::ALL),
        ));
    }

    /** @test */
    public function findListReturnsOnlyThoseWithMatchingDomains(): void
    {
        $shortUrl1 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo1',
            'domain' => null,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo2',
            'domain' => null,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo3',
            'domain' => 'another.com',
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl3);

        $this->getEntityManager()->flush();

        $buildFiltering = static fn (string $searchTerm) => new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            searchTerm: $searchTerm,
            defaultDomain: 'deFaulT-domain.com',
        );

        self::assertCount(2, $this->repo->findList($buildFiltering('default-dom')));
        self::assertCount(2, $this->repo->findList($buildFiltering('DOM')));
        self::assertCount(1, $this->repo->findList($buildFiltering('another')));
        self::assertCount(3, $this->repo->findList($buildFiltering('foo')));
        self::assertCount(0, $this->repo->findList($buildFiltering('no results')));
    }

    /** @test */
    public function findListReturnsOnlyThoseWithoutExcludedUrls(): void
    {
        $shortUrl1 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo1',
            'validUntil' => Chronos::now()->addDays(1)->toAtomString(),
            'maxVisits' => 100,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo2',
            'validUntil' => Chronos::now()->subDays(1)->toAtomString(),
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo3',
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl3);
        $shortUrl4 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo4',
            'maxVisits' => 3,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl4);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl4, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl4, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl4, Visitor::emptyInstance()));

        $this->getEntityManager()->flush();

        $filtering = static fn (bool $excludeMaxVisitsReached, bool $excludePastValidUntil) =>
            new ShortUrlsListFiltering(
                null,
                null,
                Ordering::emptyInstance(),
                excludeMaxVisitsReached: $excludeMaxVisitsReached,
                excludePastValidUntil: $excludePastValidUntil,
            );

        self::assertCount(4, $this->repo->findList($filtering(false, false)));
        self::assertEquals(4, $this->repo->countList($filtering(false, false)));
        self::assertCount(3, $this->repo->findList($filtering(true, false)));
        self::assertEquals(3, $this->repo->countList($filtering(true, false)));
        self::assertCount(3, $this->repo->findList($filtering(false, true)));
        self::assertEquals(3, $this->repo->countList($filtering(false, true)));
        self::assertCount(2, $this->repo->findList($filtering(true, true)));
        self::assertEquals(2, $this->repo->countList($filtering(true, true)));
    }

    /** @test */
    public function shortCodeIsInUseLooksForShortUrlInProperSetOfTables(): void
    {
        $shortUrlWithoutDomain = ShortUrl::create(
            ShortUrlCreation::fromRawData(['customSlug' => 'my-cool-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = ShortUrl::create(
            ShortUrlCreation::fromRawData(['domain' => 'doma.in', 'customSlug' => 'another-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithDomain);

        $this->getEntityManager()->flush();

        self::assertTrue($this->repo->shortCodeIsInUse(ShortUrlIdentifier::fromShortCodeAndDomain('my-cool-slug')));
        self::assertFalse($this->repo->shortCodeIsInUse(
            ShortUrlIdentifier::fromShortCodeAndDomain('my-cool-slug', 'doma.in'),
        ));
        self::assertFalse($this->repo->shortCodeIsInUse(ShortUrlIdentifier::fromShortCodeAndDomain('slug-not-in-use')));
        self::assertFalse($this->repo->shortCodeIsInUse(ShortUrlIdentifier::fromShortCodeAndDomain('another-slug')));
        self::assertFalse($this->repo->shortCodeIsInUse(
            ShortUrlIdentifier::fromShortCodeAndDomain('another-slug', 'example.com'),
        ));
        self::assertTrue($this->repo->shortCodeIsInUse(
            ShortUrlIdentifier::fromShortCodeAndDomain('another-slug', 'doma.in'),
        ));
    }

    /** @test */
    public function findOneLooksForShortUrlInProperSetOfTables(): void
    {
        $shortUrlWithoutDomain = ShortUrl::create(
            ShortUrlCreation::fromRawData(['customSlug' => 'my-cool-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = ShortUrl::create(
            ShortUrlCreation::fromRawData(['domain' => 'doma.in', 'customSlug' => 'another-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithDomain);

        $this->getEntityManager()->flush();

        self::assertNotNull($this->repo->findOne(ShortUrlIdentifier::fromShortCodeAndDomain('my-cool-slug')));
        self::assertNull($this->repo->findOne(ShortUrlIdentifier::fromShortCodeAndDomain('my-cool-slug', 'doma.in')));
        self::assertNull($this->repo->findOne(ShortUrlIdentifier::fromShortCodeAndDomain('slug-not-in-use')));
        self::assertNull($this->repo->findOne(ShortUrlIdentifier::fromShortCodeAndDomain('another-slug')));
        self::assertNull($this->repo->findOne(
            ShortUrlIdentifier::fromShortCodeAndDomain('another-slug', 'example.com'),
        ));
        self::assertNotNull($this->repo->findOne(
            ShortUrlIdentifier::fromShortCodeAndDomain('another-slug', 'doma.in'),
        ));
    }

    /** @test */
    public function findOneMatchingReturnsNullForNonExistingShortUrls(): void
    {
        self::assertNull($this->repo->findOneMatching(ShortUrlCreation::createEmpty()));
        self::assertNull($this->repo->findOneMatching(ShortUrlCreation::fromRawData(['longUrl' => 'foobar'])));
        self::assertNull($this->repo->findOneMatching(
            ShortUrlCreation::fromRawData(['longUrl' => 'foobar', 'tags' => ['foo', 'bar']]),
        ));
        self::assertNull($this->repo->findOneMatching(ShortUrlCreation::fromRawData([
            'validSince' => Chronos::parse('2020-03-05 20:18:30'),
            'customSlug' => 'this_slug_does_not_exist',
            'longUrl' => 'foobar',
            'tags' => ['foo', 'bar'],
        ])));
    }

    /** @test */
    public function findOneMatchingAppliesProperConditions(): void
    {
        $start = Chronos::parse('2020-03-05 20:18:30');
        $end = Chronos::parse('2021-03-05 20:18:30');

        $shortUrl = ShortUrl::create(
            ShortUrlCreation::fromRawData(['validSince' => $start, 'longUrl' => 'foo', 'tags' => ['foo', 'bar']]),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($shortUrl);

        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData(['validUntil' => $end, 'longUrl' => 'bar']));
        $this->getEntityManager()->persist($shortUrl2);

        $shortUrl3 = ShortUrl::create(
            ShortUrlCreation::fromRawData(['validSince' => $start, 'validUntil' => $end, 'longUrl' => 'baz']),
        );
        $this->getEntityManager()->persist($shortUrl3);

        $shortUrl4 = ShortUrl::create(
            ShortUrlCreation::fromRawData(['customSlug' => 'custom', 'validUntil' => $end, 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrl4);

        $shortUrl5 = ShortUrl::create(ShortUrlCreation::fromRawData(['maxVisits' => 3, 'longUrl' => 'foo']));
        $this->getEntityManager()->persist($shortUrl5);

        $shortUrl6 = ShortUrl::create(ShortUrlCreation::fromRawData(['domain' => 'doma.in', 'longUrl' => 'foo']));
        $this->getEntityManager()->persist($shortUrl6);

        $this->getEntityManager()->flush();

        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(
                ShortUrlCreation::fromRawData(['validSince' => $start, 'longUrl' => 'foo', 'tags' => ['foo', 'bar']]),
            ),
        );
        self::assertSame(
            $shortUrl2,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData(['validUntil' => $end, 'longUrl' => 'bar'])),
        );
        self::assertSame(
            $shortUrl3,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'validSince' => $start,
                'validUntil' => $end,
                'longUrl' => 'baz',
            ])),
        );
        self::assertSame(
            $shortUrl4,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'customSlug' => 'custom',
                'validUntil' => $end,
                'longUrl' => 'foo',
            ])),
        );
        self::assertSame(
            $shortUrl5,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData(['maxVisits' => 3, 'longUrl' => 'foo'])),
        );
        self::assertSame(
            $shortUrl6,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData(['domain' => 'doma.in', 'longUrl' => 'foo'])),
        );
    }

    /** @test */
    public function findOneMatchingReturnsOldestOneWhenThereAreMultipleMatches(): void
    {
        $start = Chronos::parse('2020-03-05 20:18:30');
        $tags = ['foo', 'bar'];
        $meta = ShortUrlCreation::fromRawData(
            ['validSince' => $start, 'maxVisits' => 50, 'longUrl' => 'foo', 'tags' => $tags],
        );

        $shortUrl1 = ShortUrl::create($meta, $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $this->getEntityManager()->flush();

        $shortUrl2 = ShortUrl::create($meta, $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $this->getEntityManager()->flush();

        $shortUrl3 = ShortUrl::create($meta, $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl3);
        $this->getEntityManager()->flush();

        $result = $this->repo->findOneMatching($meta);

        self::assertSame($shortUrl1, $result);
        self::assertNotSame($shortUrl2, $result);
        self::assertNotSame($shortUrl3, $result);
    }

    /** @test */
    public function findOneMatchingAppliesProvidedApiKeyConditions(): void
    {
        $start = Chronos::parse('2020-03-05 20:18:30');

        $wrongDomain = Domain::withAuthority('wrong.com');
        $this->getEntityManager()->persist($wrongDomain);
        $rightDomain = Domain::withAuthority('right.com');
        $this->getEntityManager()->persist($rightDomain);

        $this->getEntityManager()->flush();

        $apiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($apiKey);
        $otherApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($otherApiKey);
        $wrongDomainApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forDomain($wrongDomain)));
        $this->getEntityManager()->persist($wrongDomainApiKey);
        $rightDomainApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forDomain($rightDomain)));
        $this->getEntityManager()->persist($rightDomainApiKey);
        $adminApiKey = ApiKey::create();
        $this->getEntityManager()->persist($adminApiKey);

        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'validSince' => $start,
            'apiKey' => $apiKey,
            'domain' => $rightDomain->getAuthority(),
            'longUrl' => 'foo',
            'tags' => ['foo', 'bar'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl);

        $nonDomainShortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'apiKey' => $apiKey,
            'longUrl' => 'non-domain',
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($nonDomainShortUrl);

        $this->getEntityManager()->flush();

        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(
                ShortUrlCreation::fromRawData(['validSince' => $start, 'longUrl' => 'foo', 'tags' => ['foo', 'bar']]),
            ),
        );
        self::assertSame($shortUrl, $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
            'validSince' => $start,
            'apiKey' => $apiKey,
            'longUrl' => 'foo',
            'tags' => ['foo', 'bar'],
        ])));
        self::assertSame($shortUrl, $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
            'validSince' => $start,
            'apiKey' => $adminApiKey,
            'longUrl' => 'foo',
            'tags' => ['foo', 'bar'],
        ])));
        self::assertNull($this->repo->findOneMatching(ShortUrlCreation::fromRawData([
            'validSince' => $start,
            'apiKey' => $otherApiKey,
            'longUrl' => 'foo',
            'tags' => ['foo', 'bar'],
        ])));

        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'validSince' => $start,
                'domain' => $rightDomain->getAuthority(),
                'longUrl' => 'foo',
                'tags' => ['foo', 'bar'],
            ])),
        );
        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'validSince' => $start,
                'domain' => $rightDomain->getAuthority(),
                'apiKey' => $rightDomainApiKey,
                'longUrl' => 'foo',
                'tags' => ['foo', 'bar'],
            ])),
        );
        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'validSince' => $start,
                'domain' => $rightDomain->getAuthority(),
                'apiKey' => $apiKey,
                'longUrl' => 'foo',
                'tags' => ['foo', 'bar'],
            ])),
        );
        self::assertNull(
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'validSince' => $start,
                'domain' => $rightDomain->getAuthority(),
                'apiKey' => $wrongDomainApiKey,
                'longUrl' => 'foo',
                'tags' => ['foo', 'bar'],
            ])),
        );

        self::assertSame(
            $nonDomainShortUrl,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'apiKey' => $apiKey,
                'longUrl' => 'non-domain',
            ])),
        );
        self::assertSame(
            $nonDomainShortUrl,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'apiKey' => $adminApiKey,
                'longUrl' => 'non-domain',
            ])),
        );
        self::assertNull(
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'apiKey' => $otherApiKey,
                'longUrl' => 'non-domain',
            ])),
        );
    }

    /** @test */
    public function importedShortUrlsAreFoundWhenExpected(): void
    {
        $buildImported = static fn (string $shortCode, ?String $domain = null) =>
            new ImportedShlinkUrl(ImportSource::BITLY, 'foo', [], Chronos::now(), $domain, $shortCode, null);

        $shortUrlWithoutDomain = ShortUrl::fromImport($buildImported('my-cool-slug'), true);
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = ShortUrl::fromImport($buildImported('another-slug', 'doma.in'), true);
        $this->getEntityManager()->persist($shortUrlWithDomain);

        $this->getEntityManager()->flush();

        self::assertNotNull($this->repo->findOneByImportedUrl($buildImported('my-cool-slug')));
        self::assertNotNull($this->repo->findOneByImportedUrl($buildImported('another-slug', 'doma.in')));
        self::assertNull($this->repo->findOneByImportedUrl($buildImported('non-existing-slug')));
        self::assertNull($this->repo->findOneByImportedUrl($buildImported('non-existing-slug', 'doma.in')));
        self::assertNull($this->repo->findOneByImportedUrl($buildImported('my-cool-slug', 'doma.in')));
        self::assertNull($this->repo->findOneByImportedUrl($buildImported('another-slug')));
    }

    /** @test */
    public function findCrawlableShortCodesReturnsExpectedResult(): void
    {
        $createShortUrl = fn (bool $crawlable) => ShortUrl::create(
            ShortUrlCreation::fromRawData(['crawlable' => $crawlable, 'longUrl' => 'foo.com']),
        );

        $shortUrl1 = $createShortUrl(true);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = $createShortUrl(false);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = $createShortUrl(true);
        $this->getEntityManager()->persist($shortUrl3);
        $shortUrl4 = $createShortUrl(true);
        $this->getEntityManager()->persist($shortUrl4);
        $shortUrl5 = $createShortUrl(false);
        $this->getEntityManager()->persist($shortUrl5);
        $this->getEntityManager()->flush();

        $iterable = $this->repo->findCrawlableShortCodes();
        $results = [];
        foreach ($iterable as $shortCode) {
            $results[] = $shortCode;
        }

        self::assertCount(3, $results);
        self::assertContains($shortUrl1->getShortCode(), $results);
        self::assertContains($shortUrl3->getShortCode(), $results);
        self::assertContains($shortUrl4->getShortCode(), $results);
        self::assertNotContains($shortUrl2->getShortCode(), $results);
        self::assertNotContains($shortUrl5->getShortCode(), $results);
    }
}
