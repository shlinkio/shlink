<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionObject;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\ShortUrlsOrdering;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function count;

class ShortUrlRepositoryTest extends DatabaseTestCase
{
    private ShortUrlRepository $repo;
    private PersistenceShortUrlRelationResolver $relationResolver;

    public function beforeEach(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(ShortUrl::class);
        $this->relationResolver = new PersistenceShortUrlRelationResolver($this->getEntityManager());
    }

    /** @test */
    public function findOneWithDomainFallbackReturnsProperData(): void
    {
        $regularOne = ShortUrl::fromMeta(ShortUrlMeta::fromRawData(['customSlug' => 'foo', 'longUrl' => 'foo']));
        $this->getEntityManager()->persist($regularOne);

        $withDomain = ShortUrl::fromMeta(ShortUrlMeta::fromRawData(
            ['domain' => 'example.com', 'customSlug' => 'domain-short-code', 'longUrl' => 'foo'],
        ));
        $this->getEntityManager()->persist($withDomain);

        $withDomainDuplicatingRegular = ShortUrl::fromMeta(ShortUrlMeta::fromRawData(
            ['domain' => 'doma.in', 'customSlug' => 'foo', 'longUrl' => 'foo_with_domain'],
        ));
        $this->getEntityManager()->persist($withDomainDuplicatingRegular);

        $this->getEntityManager()->flush();

        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback($regularOne->getShortCode()));
        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback(
            $withDomainDuplicatingRegular->getShortCode(),
        ));
        self::assertSame($withDomain, $this->repo->findOneWithDomainFallback(
            $withDomain->getShortCode(),
            'example.com',
        ));
        self::assertSame(
            $withDomainDuplicatingRegular,
            $this->repo->findOneWithDomainFallback($withDomainDuplicatingRegular->getShortCode(), 'doma.in'),
        );
        self::assertSame(
            $regularOne,
            $this->repo->findOneWithDomainFallback($withDomainDuplicatingRegular->getShortCode(), 'other-domain.com'),
        );
        self::assertNull($this->repo->findOneWithDomainFallback('invalid'));
        self::assertNull($this->repo->findOneWithDomainFallback($withDomain->getShortCode()));
        self::assertNull($this->repo->findOneWithDomainFallback($withDomain->getShortCode(), 'other-domain.com'));
    }

    /** @test */
    public function countListReturnsProperNumberOfResults(): void
    {
        $count = 5;
        for ($i = 0; $i < $count; $i++) {
            $this->getEntityManager()->persist(ShortUrl::withLongUrl((string) $i));
        }
        $this->getEntityManager()->flush();

        self::assertEquals($count, $this->repo->countList());
    }

    /** @test */
    public function findListProperlyFiltersResult(): void
    {
        $foo = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['longUrl' => 'foo', 'tags' => ['bar']]),
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

        $result = $this->repo->findList(null, null, 'foo', ['bar']);
        self::assertCount(1, $result);
        self::assertEquals(1, $this->repo->countList('foo', ['bar']));
        self::assertSame($foo, $result[0]);

        $result = $this->repo->findList();
        self::assertCount(3, $result);

        $result = $this->repo->findList(2);
        self::assertCount(2, $result);

        $result = $this->repo->findList(2, 1);
        self::assertCount(2, $result);

        self::assertCount(1, $this->repo->findList(2, 2));

        $result = $this->repo->findList(null, null, null, [], ShortUrlsOrdering::fromRawData([
            'orderBy' => ['visits' => 'DESC'],
        ]));
        self::assertCount(3, $result);
        self::assertSame($bar, $result[0]);

        $result = $this->repo->findList(null, null, null, [], null, new DateRange(null, Chronos::now()->subDays(2)));
        self::assertCount(1, $result);
        self::assertEquals(1, $this->repo->countList(null, [], new DateRange(null, Chronos::now()->subDays(2))));
        self::assertSame($foo2, $result[0]);

        self::assertCount(
            2,
            $this->repo->findList(null, null, null, [], null, new DateRange(Chronos::now()->subDays(2))),
        );
        self::assertEquals(2, $this->repo->countList(null, [], new DateRange(Chronos::now()->subDays(2))));
    }

    /** @test */
    public function findListProperlyMapsFieldNamesToColumnNamesWhenOrdering(): void
    {
        $urls = ['a', 'z', 'c', 'b'];
        foreach ($urls as $url) {
            $this->getEntityManager()->persist(ShortUrl::withLongUrl($url));
        }

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(null, null, null, [], ShortUrlsOrdering::fromRawData([
            'orderBy' => ['longUrl' => 'ASC'],
        ]));

        self::assertCount(count($urls), $result);
        self::assertEquals('a', $result[0]->getLongUrl());
        self::assertEquals('b', $result[1]->getLongUrl());
        self::assertEquals('c', $result[2]->getLongUrl());
        self::assertEquals('z', $result[3]->getLongUrl());
    }

    /** @test */
    public function shortCodeIsInUseLooksForShortUrlInProperSetOfTables(): void
    {
        $shortUrlWithoutDomain = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['customSlug' => 'my-cool-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['domain' => 'doma.in', 'customSlug' => 'another-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithDomain);

        $this->getEntityManager()->flush();

        self::assertTrue($this->repo->shortCodeIsInUse('my-cool-slug'));
        self::assertFalse($this->repo->shortCodeIsInUse('my-cool-slug', 'doma.in'));
        self::assertFalse($this->repo->shortCodeIsInUse('slug-not-in-use'));
        self::assertFalse($this->repo->shortCodeIsInUse('another-slug'));
        self::assertFalse($this->repo->shortCodeIsInUse('another-slug', 'example.com'));
        self::assertTrue($this->repo->shortCodeIsInUse('another-slug', 'doma.in'));
    }

    /** @test */
    public function findOneLooksForShortUrlInProperSetOfTables(): void
    {
        $shortUrlWithoutDomain = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['customSlug' => 'my-cool-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['domain' => 'doma.in', 'customSlug' => 'another-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithDomain);

        $this->getEntityManager()->flush();

        self::assertNotNull($this->repo->findOne('my-cool-slug'));
        self::assertNull($this->repo->findOne('my-cool-slug', 'doma.in'));
        self::assertNull($this->repo->findOne('slug-not-in-use'));
        self::assertNull($this->repo->findOne('another-slug'));
        self::assertNull($this->repo->findOne('another-slug', 'example.com'));
        self::assertNotNull($this->repo->findOne('another-slug', 'doma.in'));
    }

    /** @test */
    public function findOneMatchingReturnsNullForNonExistingShortUrls(): void
    {
        self::assertNull($this->repo->findOneMatching(ShortUrlMeta::createEmpty()));
        self::assertNull($this->repo->findOneMatching(ShortUrlMeta::fromRawData(['longUrl' => 'foobar'])));
        self::assertNull($this->repo->findOneMatching(
            ShortUrlMeta::fromRawData(['longUrl' => 'foobar', 'tags' => ['foo', 'bar']]),
        ));
        self::assertNull($this->repo->findOneMatching(ShortUrlMeta::fromRawData([
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

        $shortUrl = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['validSince' => $start, 'longUrl' => 'foo', 'tags' => ['foo', 'bar']]),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($shortUrl);

        $shortUrl2 = ShortUrl::fromMeta(ShortUrlMeta::fromRawData(['validUntil' => $end, 'longUrl' => 'bar']));
        $this->getEntityManager()->persist($shortUrl2);

        $shortUrl3 = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['validSince' => $start, 'validUntil' => $end, 'longUrl' => 'baz']),
        );
        $this->getEntityManager()->persist($shortUrl3);

        $shortUrl4 = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['customSlug' => 'custom', 'validUntil' => $end, 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrl4);

        $shortUrl5 = ShortUrl::fromMeta(ShortUrlMeta::fromRawData(['maxVisits' => 3, 'longUrl' => 'foo']));
        $this->getEntityManager()->persist($shortUrl5);

        $shortUrl6 = ShortUrl::fromMeta(ShortUrlMeta::fromRawData(['domain' => 'doma.in', 'longUrl' => 'foo']));
        $this->getEntityManager()->persist($shortUrl6);

        $this->getEntityManager()->flush();

        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(
                ShortUrlMeta::fromRawData(['validSince' => $start, 'longUrl' => 'foo', 'tags' => ['foo', 'bar']]),
            ),
        );
        self::assertSame(
            $shortUrl2,
            $this->repo->findOneMatching(ShortUrlMeta::fromRawData(['validUntil' => $end, 'longUrl' => 'bar'])),
        );
        self::assertSame(
            $shortUrl3,
            $this->repo->findOneMatching(ShortUrlMeta::fromRawData([
                'validSince' => $start,
                'validUntil' => $end,
                'longUrl' => 'baz',
            ])),
        );
        self::assertSame(
            $shortUrl4,
            $this->repo->findOneMatching(ShortUrlMeta::fromRawData([
                'customSlug' => 'custom',
                'validUntil' => $end,
                'longUrl' => 'foo',
            ])),
        );
        self::assertSame(
            $shortUrl5,
            $this->repo->findOneMatching(ShortUrlMeta::fromRawData(['maxVisits' => 3, 'longUrl' => 'foo'])),
        );
        self::assertSame(
            $shortUrl6,
            $this->repo->findOneMatching(ShortUrlMeta::fromRawData(['domain' => 'doma.in', 'longUrl' => 'foo'])),
        );
    }

    /** @test */
    public function findOneMatchingReturnsOldestOneWhenThereAreMultipleMatches(): void
    {
        $start = Chronos::parse('2020-03-05 20:18:30');
        $tags = ['foo', 'bar'];
        $meta = ShortUrlMeta::fromRawData(
            ['validSince' => $start, 'maxVisits' => 50, 'longUrl' => 'foo', 'tags' => $tags],
        );

        $shortUrl1 = ShortUrl::fromMeta($meta, $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $this->getEntityManager()->flush();

        $shortUrl2 = ShortUrl::fromMeta($meta, $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $this->getEntityManager()->flush();

        $shortUrl3 = ShortUrl::fromMeta($meta, $this->relationResolver);
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

        $wrongDomain = new Domain('wrong.com');
        $this->getEntityManager()->persist($wrongDomain);
        $rightDomain = new Domain('right.com');
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

        $shortUrl = ShortUrl::fromMeta(ShortUrlMeta::fromRawData([
            'validSince' => $start,
            'apiKey' => $apiKey,
            'domain' => $rightDomain->getAuthority(),
            'longUrl' => 'foo',
            'tags' => ['foo', 'bar'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl);

        $this->getEntityManager()->flush();

        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(
                ShortUrlMeta::fromRawData(['validSince' => $start, 'longUrl' => 'foo', 'tags' => ['foo', 'bar']]),
            ),
        );
        self::assertSame($shortUrl, $this->repo->findOneMatching(ShortUrlMeta::fromRawData([
            'validSince' => $start,
            'apiKey' => $apiKey,
            'longUrl' => 'foo',
            'tags' => ['foo', 'bar'],
        ])));
        self::assertNull($this->repo->findOneMatching(ShortUrlMeta::fromRawData([
            'validSince' => $start,
            'apiKey' => $otherApiKey,
            'longUrl' => 'foo',
            'tags' => ['foo', 'bar'],
        ])));

        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(ShortUrlMeta::fromRawData([
                'validSince' => $start,
                'domain' => $rightDomain->getAuthority(),
                'longUrl' => 'foo',
                'tags' => ['foo', 'bar'],
            ])),
        );
        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(ShortUrlMeta::fromRawData([
                'validSince' => $start,
                'domain' => $rightDomain->getAuthority(),
                'apiKey' => $rightDomainApiKey,
                'longUrl' => 'foo',
                'tags' => ['foo', 'bar'],
            ])),
        );
        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(ShortUrlMeta::fromRawData([
                'validSince' => $start,
                'domain' => $rightDomain->getAuthority(),
                'apiKey' => $apiKey,
                'longUrl' => 'foo',
                'tags' => ['foo', 'bar'],
            ])),
        );
        self::assertNull(
            $this->repo->findOneMatching(ShortUrlMeta::fromRawData([
                'validSince' => $start,
                'domain' => $rightDomain->getAuthority(),
                'apiKey' => $wrongDomainApiKey,
                'longUrl' => 'foo',
                'tags' => ['foo', 'bar'],
            ])),
        );
    }

    /** @test */
    public function importedShortUrlsAreFoundWhenExpected(): void
    {
        $buildImported = static fn (string $shortCode, ?String $domain = null) =>
            new ImportedShlinkUrl('', 'foo', [], Chronos::now(), $domain, $shortCode, null);

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
}
