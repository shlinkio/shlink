<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\ShortUrl\Repository;

use Cake\Chronos\Chronos;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
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
        $regularOne = ShortUrl::create(new ShortUrlCreation(longUrl: 'https://foo', customSlug: 'Foo'));
        $this->getEntityManager()->persist($regularOne);

        $withDomain = ShortUrl::create(new ShortUrlCreation(
            longUrl: 'https://foo',
            customSlug: 'domain-short-code',
            domain: 'example.com',
        ));
        $this->getEntityManager()->persist($withDomain);

        $withDomainDuplicatingRegular = ShortUrl::create(new ShortUrlCreation(
            longUrl: 'https://foo_with_domain',
            customSlug: 'Foo',
            domain: 's.test',
        ));
        $this->getEntityManager()->persist($withDomainDuplicatingRegular);

        $this->getEntityManager()->flush();

        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($regularOne->shortCode),
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
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomainDuplicatingRegular->shortCode),
            ShortUrlMode::STRICT,
        ));
        self::assertSame($withDomain, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomain->shortCode, 'example.com'),
            ShortUrlMode::STRICT,
        ));
        self::assertSame(
            $withDomainDuplicatingRegular,
            $this->repo->findOneWithDomainFallback(
                ShortUrlIdentifier::fromShortCodeAndDomain($withDomainDuplicatingRegular->shortCode, 's.test'),
                ShortUrlMode::STRICT,
            ),
        );
        self::assertSame($regularOne, $this->repo->findOneWithDomainFallback(ShortUrlIdentifier::fromShortCodeAndDomain(
            $withDomainDuplicatingRegular->shortCode,
            'other-domain.com',
        ), ShortUrlMode::STRICT));
        self::assertNull($this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain('invalid'),
            ShortUrlMode::STRICT,
        ));
        self::assertNull($this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomain->shortCode),
            ShortUrlMode::STRICT,
        ));
        self::assertNull($this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($withDomain->shortCode, 'other-domain.com'),
            ShortUrlMode::STRICT,
        ));
    }

    #[Test, DataProvider('provideMissingShortCodes')]
    public function findOneWithDomainFallbackReturnsNullForMissingShortCodes(
        string $shortCode,
        ShortUrlMode $mode,
        string|null $domain,
    ): void {
        self::assertNull($this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain),
            $mode,
        ));
    }

    /** @return iterable<array{string, ShortUrlMode, string|null}> */
    public static function provideMissingShortCodes(): iterable
    {
        return self::provideShortCodeLookups(['unknown', '不存在', "\xE2\x81\xA3"]);
    }

    #[Test, DataProvider('provideMalformedShortCodes')]
    public function findOneWithDomainFallbackPreservesDatabaseEncodingHandling(
        string $shortCode,
        ShortUrlMode $mode,
        string|null $domain,
    ): void {
        $platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();
        if ($platform instanceof SQLServerPlatform) {
            // PDO_SQLSRV already rejects malformed input before SQL execution. Preserve its specific driver error.
            $this->expectException(DriverException::class);
            $this->expectExceptionCode(-7);
            $this->expectExceptionMessage('SQLSTATE[IMSSP]');
        }

        self::assertNull($this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain),
            $mode,
        ));
    }

    /** @return iterable<array{string, ShortUrlMode, string|null}> */
    public static function provideMalformedShortCodes(): iterable
    {
        return self::provideShortCodeLookups(["\xC0", "\xFF", "Foo\xC0", "\xE2\x81", "\xED\xA0\x80"]);
    }

    /**
     * @param list<string> $shortCodes
     * @return iterable<array{string, ShortUrlMode, string|null}>
     */
    private static function provideShortCodeLookups(array $shortCodes): iterable
    {
        foreach ([ShortUrlMode::STRICT, ShortUrlMode::LOOSE] as $mode) {
            foreach ([null, 'example.com', 'other-domain.com'] as $domain) {
                foreach ($shortCodes as $shortCode) {
                    yield [$shortCode, $mode, $domain];
                }
            }
        }
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function malformedLookupsPreservePostgresTransactionsAndEarlierWrites(bool $nestedTransaction): void
    {
        $connection = $this->getEntityManager()->getConnection();
        if (!$connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            self::markTestSkipped('Transaction recovery after SQLSTATE 22021 is specific to Postgres.');
        }

        $shortUrl = ShortUrl::create(new ShortUrlCreation('https://example.com', customSlug: 'Foo'));
        $this->getEntityManager()->persist($shortUrl);
        $this->getEntityManager()->flush();

        if ($nestedTransaction) {
            $connection->beginTransaction();
        }

        try {
            $transactionLevel = $connection->getTransactionNestingLevel();
            foreach (self::provideMalformedShortCodes() as [$shortCode, $mode, $domain]) {
                self::assertNull($this->repo->findOneWithDomainFallback(
                    ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain),
                    $mode,
                ));
                self::assertSame($transactionLevel, $connection->getTransactionNestingLevel());
                // Query again to prove the transaction is usable and the caller's earlier insert survived.
                self::assertSame($shortUrl, $this->repo->findOneWithDomainFallback(
                    ShortUrlIdentifier::fromShortCodeAndDomain('Foo', $domain),
                    $mode,
                ));
            }
        } finally {
            if ($nestedTransaction) {
                $connection->rollBack();
            }
        }
    }

    #[Test, TestWith([ShortUrlMode::STRICT]), TestWith([ShortUrlMode::LOOSE])]
    public function findOneWithDomainFallbackPreservesMultibyteSlugs(ShortUrlMode $mode): void
    {
        $regular = ShortUrl::create(new ShortUrlCreation('https://example.com', customSlug: 'Café-東京'));
        $withDomain = ShortUrl::create(new ShortUrlCreation(
            'https://example.com/domain',
            customSlug: 'Café-東京',
            domain: 'example.com',
        ));
        $this->getEntityManager()->persist($regular);
        $this->getEntityManager()->persist($withDomain);
        $this->getEntityManager()->flush();

        self::assertSame($withDomain, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain('Café-東京', 'example.com'),
            $mode,
        ));
        self::assertSame($regular, $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain('Café-東京', 'other-domain.com'),
            $mode,
        ));
    }

    /**
     * A deliberately missing table must raise a schema error rather than be reported as a missing short URL.
     * This proves the invalid-encoding handler does not hide unrelated database failures.
     */
    #[Test]
    public function missingTablesAreNotReportedAsMissingShortUrls(): void
    {
        $connection = $this->getEntityManager()->getConnection();
        if (!$connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            self::markTestSkipped('Postgres transactional DDL is needed to restore the table after this test.');
        }

        // A real schema error must escape the encoding-error catch, rather than become a missing short URL.
        $connection->executeStatement('ALTER TABLE short_urls RENAME TO short_urls_unavailable');

        $this->expectException(TableNotFoundException::class);
        $this->repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain('Foo'),
            ShortUrlMode::STRICT,
        );
    }

    #[Test]
    public function shortCodeIsInUseLooksForShortUrlInProperSetOfTables(): void
    {
        $shortUrlWithoutDomain = ShortUrl::create(new ShortUrlCreation('https://foo', customSlug: 'my-cool-slug'));
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = ShortUrl::create(new ShortUrlCreation(
            longUrl: 'https://foo',
            customSlug: 'another-slug',
            domain: 's.test',
        ));
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
            new ShortUrlCreation(longUrl: 'https://foo', customSlug: 'my-cool-slug'),
        );
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = ShortUrl::create(
            new ShortUrlCreation(longUrl: 'https://foo', customSlug: 'another-slug', domain: 's.test'),
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
        self::assertNull($this->repo->findOneMatching(new ShortUrlCreation('https://foobar')));
        self::assertNull($this->repo->findOneMatching(new ShortUrlCreation('https://foobar', tags: ['foo', 'bar'])));
        self::assertNull($this->repo->findOneMatching(new ShortUrlCreation(
            longUrl: 'https://foobar',
            validSince: Chronos::parse('2020-03-05 20:18:30'),
            customSlug: 'this_slug_does_not_exist',
            tags: ['foo', 'bar'],
        )));
    }

    #[Test]
    public function findOneMatchingAppliesProperConditions(): void
    {
        $start = Chronos::parse('2020-03-05 20:18:30');
        $end = Chronos::parse('2021-03-05 20:18:30');

        $shortUrl = ShortUrl::create(
            new ShortUrlCreation(longUrl: 'https://foo', validSince: $start, tags: ['foo', 'bar']),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($shortUrl);

        $shortUrl2 = ShortUrl::create(new ShortUrlCreation(longUrl: 'https://bar', validUntil: $end));
        $this->getEntityManager()->persist($shortUrl2);

        $shortUrl3 = ShortUrl::create(
            new ShortUrlCreation(longUrl: 'https://baz', validSince: $start, validUntil: $end),
        );
        $this->getEntityManager()->persist($shortUrl3);

        $shortUrl4 = ShortUrl::create(
            new ShortUrlCreation(longUrl: 'https://foo', validUntil: $end, customSlug: 'custom'),
        );
        $this->getEntityManager()->persist($shortUrl4);

        $shortUrl5 = ShortUrl::create(new ShortUrlCreation(longUrl: 'https://foo', maxVisits: 3));
        $this->getEntityManager()->persist($shortUrl5);

        $shortUrl6 = ShortUrl::create(
            new ShortUrlCreation(longUrl: 'https://foo', domain: 's.test'),
        );
        $this->getEntityManager()->persist($shortUrl6);

        $this->getEntityManager()->flush();

        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(
                new ShortUrlCreation(longUrl: 'https://foo', validSince: $start, tags: ['foo', 'bar']),
            ),
        );
        self::assertSame(
            $shortUrl2,
            $this->repo->findOneMatching(
                new ShortUrlCreation(longUrl: 'https://bar', validUntil: $end),
            ),
        );
        self::assertSame(
            $shortUrl3,
            $this->repo->findOneMatching(new ShortUrlCreation(
                longUrl: 'https://baz',
                validSince: $start,
                validUntil: $end,
            )),
        );
        self::assertSame(
            $shortUrl4,
            $this->repo->findOneMatching(new ShortUrlCreation(
                longUrl: 'https://foo',
                validUntil: $end,
                customSlug: 'custom',
            )),
        );
        self::assertSame(
            $shortUrl5,
            $this->repo->findOneMatching(new ShortUrlCreation(longUrl: 'https://foo', maxVisits: 3)),
        );
        self::assertSame(
            $shortUrl6,
            $this->repo->findOneMatching(new ShortUrlCreation(longUrl: 'https://foo', domain: 's.test')),
        );
    }

    #[Test]
    public function findOneMatchingReturnsOldestOneWhenThereAreMultipleMatches(): void
    {
        $start = Chronos::parse('2020-03-05 20:18:30');
        $tags = ['foo', 'bar'];
        $meta = new ShortUrlCreation('https://foo', validSince: $start, maxVisits: 50, tags: $tags);

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

        $shortUrl = ShortUrl::create(new ShortUrlCreation(
            longUrl: 'https://foo',
            validSince: $start,
            domain: $rightDomain->authority,
            apiKey: $apiKey,
            tags: ['foo', 'bar'],
        ), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl);

        $nonDomainShortUrl = ShortUrl::create(new ShortUrlCreation(
            longUrl: 'https://non-domain',
            apiKey: $apiKey,
        ), $this->relationResolver);
        $this->getEntityManager()->persist($nonDomainShortUrl);

        $this->getEntityManager()->flush();

        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(
                new ShortUrlCreation(longUrl: 'https://foo', validSince: $start, tags: ['foo', 'bar']),
            ),
        );
        self::assertSame($shortUrl, $this->repo->findOneMatching(new ShortUrlCreation(
            longUrl: 'https://foo',
            validSince: $start,
            apiKey: $apiKey,
            tags: ['foo', 'bar'],
        )));
        self::assertSame($shortUrl, $this->repo->findOneMatching(new ShortUrlCreation(
            longUrl: 'https://foo',
            validSince: $start,
            apiKey: $adminApiKey,
            tags: ['foo', 'bar'],
        )));
        self::assertNull($this->repo->findOneMatching(new ShortUrlCreation(
            longUrl: 'https://foo',
            validSince: $start,
            apiKey: $otherApiKey,
            tags: ['foo', 'bar'],
        )));

        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(new ShortUrlCreation(
                longUrl: 'https://foo',
                validSince: $start,
                domain: $rightDomain->authority,
                tags: ['foo', 'bar'],
            )),
        );
        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(new ShortUrlCreation(
                longUrl: 'https://foo',
                validSince: $start,
                domain: $rightDomain->authority,
                apiKey: $rightDomainApiKey,
                tags: ['foo', 'bar'],
            )),
        );
        self::assertSame(
            $shortUrl,
            $this->repo->findOneMatching(new ShortUrlCreation(
                longUrl: 'https://foo',
                validSince: $start,
                domain: $rightDomain->authority,
                apiKey: $apiKey,
                tags: ['foo', 'bar'],
            )),
        );
        self::assertNull(
            $this->repo->findOneMatching(new ShortUrlCreation(
                longUrl: 'https://foo',
                validSince: $start,
                domain: $rightDomain->authority,
                apiKey: $wrongDomainApiKey,
                tags: ['foo', 'bar'],
            )),
        );

        self::assertSame(
            $nonDomainShortUrl,
            $this->repo->findOneMatching(new ShortUrlCreation('https://non-domain', apiKey: $apiKey)),
        );
        self::assertSame(
            $nonDomainShortUrl,
            $this->repo->findOneMatching(new ShortUrlCreation('https://non-domain', apiKey: $adminApiKey)),
        );
        self::assertNull(
            $this->repo->findOneMatching(new ShortUrlCreation('https://non-domain', apiKey: $otherApiKey)),
        );
    }

    #[Test]
    public function importedShortUrlsAreFoundWhenExpected(): void
    {
        $buildImported = static fn (string $shortCode, string|null $domain = null) => new ImportedShlinkUrl(
            ImportSource::BITLY,
            'https://foo',
            [],
            Chronos::now(),
            $domain,
            $shortCode,
            null,
        );

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
