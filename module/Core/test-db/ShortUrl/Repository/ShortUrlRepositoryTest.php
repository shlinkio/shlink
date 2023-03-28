<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\ShortUrl\Repository;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class ShortUrlRepositoryTest extends DatabaseTestCase
{
    private ShortUrlRepository $repo;
    private PersistenceShortUrlRelationResolver $relationResolver;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(ShortUrl::class);
        $this->relationResolver = new PersistenceShortUrlRelationResolver($this->getEntityManager());
    }

    #[Test]
    public function findOneWithDomainFallbackReturnsProperData(): void
    {
        $regularOne = ShortUrl::create(ShortUrlCreation::fromRawData(['customSlug' => 'Foo', 'longUrl' => 'foo']));
        $this->getEntityManager()->persist($regularOne);

        $withDomain = ShortUrl::create(ShortUrlCreation::fromRawData(
            ['domain' => 'example.com', 'customSlug' => 'domain-short-code', 'longUrl' => 'foo'],
        ));
        $this->getEntityManager()->persist($withDomain);

        $withDomainDuplicatingRegular = ShortUrl::create(ShortUrlCreation::fromRawData(
            ['domain' => 's.test', 'customSlug' => 'Foo', 'longUrl' => 'foo_with_domain'],
        ));
        $this->getEntityManager()->persist($withDomainDuplicatingRegular);

        $this->getEntityManager()->flush();

        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($regularOne->getShortCode()),
            ShortUrlMode::STRICT,
        ));
        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain('foo'),
            ShortUrlMode::LOOSE,
        ));
        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain('fOo'),
            ShortUrlMode::LOOSE,
        ));
        self::assertNull($this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain('foo'),
            ShortUrlMode::STRICT,
        ));
        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomainDuplicatingRegular->getShortCode()),
            ShortUrlMode::STRICT,
        ));
        self::assertSame($withDomain, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomain->getShortCode(), 'example.com'),
            ShortUrlMode::STRICT,
        ));
        self::assertSame(
            $withDomainDuplicatingRegular,
            $this->repo->findOneWithDomainFallback(
                ShortUrlIdentifier::fromShortCodeAndDomain($withDomainDuplicatingRegular->getShortCode(), 's.test'),
                ShortUrlMode::STRICT,
            ),
        );
        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback(ShortUrlIdentifier::fromShortCodeAndDomain(
            $withDomainDuplicatingRegular->getShortCode(),
            'other-domain.com',
        ), ShortUrlMode::STRICT));
        self::assertNull($this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain('invalid'),
            ShortUrlMode::STRICT,
        ));
        self::assertNull($this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomain->getShortCode()),
            ShortUrlMode::STRICT,
        ));
        self::assertNull($this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomain->getShortCode(), 'other-domain.com'),
            ShortUrlMode::STRICT,
        ));
    }

    #[Test]
    public function shortCodeIsInUseLooksForShortUrlInProperSetOfTables(): void
    {
        $shortUrlWithoutDomain = ShortUrl::create(
            ShortUrlCreation::fromRawData(['customSlug' => 'my-cool-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = ShortUrl::create(
            ShortUrlCreation::fromRawData(['domain' => 's.test', 'customSlug' => 'another-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithDomain);

        $this->getEntityManager()->flush();

        self::assertTrue($this->repo->shortCodeIsInUse(ShortUrlIdentifier::fromShortCodeAndDomain('my-cool-slug')));
        self::assertFalse($this->repo->shortCodeIsInUse(
            ShortUrlIdentifier::fromShortCodeAndDomain('my-cool-slug', 's.test'),
        ));
        self::assertFalse($this->repo->shortCodeIsInUse(ShortUrlIdentifier::fromShortCodeAndDomain('slug-not-in-use')));
        self::assertFalse($this->repo->shortCodeIsInUse(ShortUrlIdentifier::fromShortCodeAndDomain('another-slug')));
        self::assertFalse($this->repo->shortCodeIsInUse(
            ShortUrlIdentifier::fromShortCodeAndDomain('another-slug', 'example.com'),
        ));
        self::assertTrue($this->repo->shortCodeIsInUse(
            ShortUrlIdentifier::fromShortCodeAndDomain('another-slug', 's.test'),
        ));
    }

    #[Test]
    public function findOneLooksForShortUrlInProperSetOfTables(): void
    {
        $shortUrlWithoutDomain = ShortUrl::create(
            ShortUrlCreation::fromRawData(['customSlug' => 'my-cool-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = ShortUrl::create(
            ShortUrlCreation::fromRawData(['domain' => 's.test', 'customSlug' => 'another-slug', 'longUrl' => 'foo']),
        );
        $this->getEntityManager()->persist($shortUrlWithDomain);

        $this->getEntityManager()->flush();

        self::assertNotNull($this->repo->findOne(ShortUrlIdentifier::fromShortCodeAndDomain('my-cool-slug')));
        self::assertNull($this->repo->findOne(ShortUrlIdentifier::fromShortCodeAndDomain('my-cool-slug', 's.test')));
        self::assertNull($this->repo->findOne(ShortUrlIdentifier::fromShortCodeAndDomain('slug-not-in-use')));
        self::assertNull($this->repo->findOne(ShortUrlIdentifier::fromShortCodeAndDomain('another-slug')));
        self::assertNull($this->repo->findOne(
            ShortUrlIdentifier::fromShortCodeAndDomain('another-slug', 'example.com'),
        ));
        self::assertNotNull($this->repo->findOne(
            ShortUrlIdentifier::fromShortCodeAndDomain('another-slug', 's.test'),
        ));
    }

    #[Test]
    public function findOneMatchingReturnsNullForNonExistingShortUrls(): void
    {
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

    #[Test]
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

        $shortUrl6 = ShortUrl::create(ShortUrlCreation::fromRawData(['domain' => 's.test', 'longUrl' => 'foo']));
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
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData(['domain' => 's.test', 'longUrl' => 'foo'])),
        );
    }

    #[Test]
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

    #[Test]
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
            'domain' => $rightDomain->authority,
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
                'domain' => $rightDomain->authority,
                'longUrl' => 'foo',
                'tags' => ['foo', 'bar'],
            ])),
        );
        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'validSince' => $start,
                'domain' => $rightDomain->authority,
                'apiKey' => $rightDomainApiKey,
                'longUrl' => 'foo',
                'tags' => ['foo', 'bar'],
            ])),
        );
        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'validSince' => $start,
                'domain' => $rightDomain->authority,
                'apiKey' => $apiKey,
                'longUrl' => 'foo',
                'tags' => ['foo', 'bar'],
            ])),
        );
        self::assertNull(
            $this->repo->findOneMatching(ShortUrlCreation::fromRawData([
                'validSince' => $start,
                'domain' => $rightDomain->authority,
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

    #[Test]
    public function importedShortUrlsAreFoundWhenExpected(): void
    {
        $buildImported = static fn (string $shortCode, ?string $domain = null) =>
            new ImportedShlinkUrl(ImportSource::BITLY, 'foo', [], Chronos::now(), $domain, $shortCode, null);

        $shortUrlWithoutDomain = ShortUrl::fromImport($buildImported('my-cool-slug'), true);
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = ShortUrl::fromImport($buildImported('another-slug', 's.test'), true);
        $this->getEntityManager()->persist($shortUrlWithDomain);

        $this->getEntityManager()->flush();

        self::assertNotNull($this->repo->findOneByImportedUrl($buildImported('my-cool-slug')));
        self::assertNotNull($this->repo->findOneByImportedUrl($buildImported('another-slug', 's.test')));
        self::assertNull($this->repo->findOneByImportedUrl($buildImported('non-existing-slug')));
        self::assertNull($this->repo->findOneByImportedUrl($buildImported('non-existing-slug', 's.test')));
        self::assertNull($this->repo->findOneByImportedUrl($buildImported('my-cool-slug', 's.test')));
        self::assertNull($this->repo->findOneByImportedUrl($buildImported('another-slug')));
    }
}
