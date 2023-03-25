<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Tag\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\OrderableField;
use Shlinkio\Shlink\Core\Tag\Model\TagsListFiltering;
use Shlinkio\Shlink\Core\Tag\Repository\TagRepository;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function array_chunk;
use function count;

class TagRepositoryTest extends DatabaseTestCase
{
    private TagRepository $repo;
    private PersistenceShortUrlRelationResolver $relationResolver;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Tag::class);
        $this->relationResolver = new PersistenceShortUrlRelationResolver($this->getEntityManager());
    }

    #[Test]
    public function deleteByNameDoesNothingWhenEmptyListIsProvided(): void
    {
        self::assertEquals(0, $this->repo->deleteByName([]));
    }

    #[Test]
    public function allTagsWhichMatchNameAreDeleted(): void
    {
        $names = ['foo', 'bar', 'baz'];
        $toDelete = ['foo', 'baz'];

        foreach ($names as $name) {
            $this->getEntityManager()->persist(new Tag($name));
        }
        $this->getEntityManager()->flush();

        self::assertEquals(2, $this->repo->deleteByName($toDelete));
    }

    #[Test, DataProvider('provideFilters')]
    public function properTagsInfoIsReturned(?TagsListFiltering $filtering, array $expectedList): void
    {
        $names = ['foo', 'bar', 'baz', 'another'];
        foreach ($names as $name) {
            $this->getEntityManager()->persist(new Tag($name));
        }

        $apiKey = $filtering?->apiKey;
        if ($apiKey !== null) {
            $this->getEntityManager()->persist($apiKey);
        }

        $this->getEntityManager()->flush();

        [$firstUrlTags] = array_chunk($names, 3);
        $secondUrlTags = [$names[0]];
        $metaWithTags = static fn (array $tags, ?ApiKey $apiKey) => ShortUrlCreation::fromRawData(
            ['longUrl' => 'https://longUrl', 'tags' => $tags, 'apiKey' => $apiKey],
        );

        $shortUrl = ShortUrl::create($metaWithTags($firstUrlTags, $apiKey), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl, Visitor::botInstance()));

        $shortUrl2 = ShortUrl::create($metaWithTags($secondUrlTags, null), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl2, Visitor::emptyInstance()));

        // One of the tags has two extra short URLs, but with no visits
        $this->getEntityManager()->persist(
            ShortUrl::create($metaWithTags(['bar'], null), $this->relationResolver),
        );
        $this->getEntityManager()->persist(
            ShortUrl::create($metaWithTags(['bar'], $apiKey), $this->relationResolver),
        );

        $this->getEntityManager()->flush();

        $result = $this->repo->findTagsWithInfo($filtering);

        self::assertCount(count($expectedList), $result);
        foreach ($expectedList as $index => [$tag, $shortUrlsCount, $visitsCount, $nonBotVisitsCount]) {
            self::assertEquals($shortUrlsCount, $result[$index]->shortUrlsCount);
            self::assertEquals($visitsCount, $result[$index]->visitsSummary->total);
            self::assertEquals($nonBotVisitsCount, $result[$index]->visitsSummary->nonBots);
            self::assertEquals($tag, $result[$index]->tag);
        }
    }

    public static function provideFilters(): iterable
    {
        $defaultList = [
            ['another', 0, 0, 0],
            ['bar', 3, 3, 2],
            ['baz', 1, 3, 2],
            ['foo', 2, 4, 3],
        ];

        yield 'no filter' => [null, $defaultList];
        yield 'empty filter' => [new TagsListFiltering(), $defaultList];
        yield 'limit' => [new TagsListFiltering(2), [
            ['another', 0, 0, 0],
            ['bar', 3, 3, 2],
        ]];
        yield 'offset' => [new TagsListFiltering(null, 3), [
            ['foo', 2, 4, 3],
        ]];
        yield 'limit and offset' => [new TagsListFiltering(2, 1), [
            ['bar', 3, 3, 2],
            ['baz', 1, 3, 2],
        ]];
        yield 'search term' => [new TagsListFiltering(null, null, 'ba'), [
            ['bar', 3, 3, 2],
            ['baz', 1, 3, 2],
        ]];
        yield 'ASC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple([OrderableField::TAG->value, 'ASC'])),
            $defaultList,
        ];
        yield 'DESC ordering' => [new TagsListFiltering(null, null, null, Ordering::fromTuple(
            [OrderableField::TAG->value, 'DESC'],
        )), [
            ['foo', 2, 4, 3],
            ['baz', 1, 3, 2],
            ['bar', 3, 3, 2],
            ['another', 0, 0, 0],
        ]];
        yield 'short URLs count ASC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(
                [OrderableField::SHORT_URLS_COUNT->value, 'ASC'],
            )),
            [
                ['another', 0, 0, 0],
                ['baz', 1, 3, 2],
                ['foo', 2, 4, 3],
                ['bar', 3, 3, 2],
            ],
        ];
        yield 'short URLs count DESC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(
                [OrderableField::SHORT_URLS_COUNT->value, 'DESC'],
            )),
            [
                ['bar', 3, 3, 2],
                ['foo', 2, 4, 3],
                ['baz', 1, 3, 2],
                ['another', 0, 0, 0],
            ],
        ];
        yield 'visits count ASC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple([OrderableField::VISITS->value, 'ASC'])),
            [
                ['another', 0, 0, 0],
                ['bar', 3, 3, 2],
                ['baz', 1, 3, 2],
                ['foo', 2, 4, 3],
            ],
        ];
        yield 'non-bot visits count ASC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(
                [OrderableField::NON_BOT_VISITS->value, 'ASC'],
            )),
            [
                ['another', 0, 0, 0],
                ['bar', 3, 3, 2],
                ['baz', 1, 3, 2],
                ['foo', 2, 4, 3],
            ],
        ];
        yield 'visits count DESC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple([OrderableField::VISITS->value, 'DESC'])),
            [
                ['foo', 2, 4, 3],
                ['bar', 3, 3, 2],
                ['baz', 1, 3, 2],
                ['another', 0, 0, 0],
            ],
        ];
        yield 'visits count DESC ordering and limit' => [
            new TagsListFiltering(2, null, null, Ordering::fromTuple([OrderableField::VISITS_COUNT->value, 'DESC'])),
            [
                ['foo', 2, 4, 3],
                ['bar', 3, 3, 2],
            ],
        ];
        yield 'api key' => [new TagsListFiltering(null, null, null, null, ApiKey::fromMeta(
            ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()),
        )), [
            ['bar', 2, 3, 2],
            ['baz', 1, 3, 2],
            ['foo', 1, 3, 2],
        ]];
        yield 'combined' => [new TagsListFiltering(1, null, null, Ordering::fromTuple(
            [OrderableField::SHORT_URLS_COUNT->value, 'DESC'],
        ), ApiKey::fromMeta(
            ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()),
        )), [
            ['bar', 2, 3, 2],
        ]];
    }

    #[Test]
    public function tagExistsReturnsExpectedResultBasedOnApiKey(): void
    {
        $domain = Domain::withAuthority('foo.com');
        $this->getEntityManager()->persist($domain);
        $this->getEntityManager()->flush();

        $authorApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()));
        $this->getEntityManager()->persist($authorApiKey);
        $domainApiKey = ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forDomain($domain)));
        $this->getEntityManager()->persist($domainApiKey);

        $names = ['foo', 'bar', 'baz', 'another'];
        foreach ($names as $name) {
            $this->getEntityManager()->persist(new Tag($name));
        }
        $this->getEntityManager()->flush();

        [$firstUrlTags, $secondUrlTags] = array_chunk($names, 3);

        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData(
            ['apiKey' => $authorApiKey, 'longUrl' => 'https://longUrl', 'tags' => $firstUrlTags],
        ), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl);

        $shortUrl2 = ShortUrl::create(
            ShortUrlCreation::fromRawData(
                ['domain' => $domain->authority, 'longUrl' => 'https://longUrl', 'tags' => $secondUrlTags],
            ),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($shortUrl2);

        $this->getEntityManager()->flush();

        self::assertTrue($this->repo->tagExists('foo'));
        self::assertTrue($this->repo->tagExists('bar'));
        self::assertTrue($this->repo->tagExists('baz'));
        self::assertTrue($this->repo->tagExists('another'));
        self::assertFalse($this->repo->tagExists('invalid'));

        self::assertTrue($this->repo->tagExists('foo', $authorApiKey));
        self::assertTrue($this->repo->tagExists('bar', $authorApiKey));
        self::assertTrue($this->repo->tagExists('baz', $authorApiKey));
        self::assertFalse($this->repo->tagExists('another', $authorApiKey));
        self::assertFalse($this->repo->tagExists('invalid', $authorApiKey));

        self::assertFalse($this->repo->tagExists('foo', $domainApiKey));
        self::assertFalse($this->repo->tagExists('bar', $domainApiKey));
        self::assertFalse($this->repo->tagExists('baz', $domainApiKey));
        self::assertTrue($this->repo->tagExists('another', $domainApiKey));
        self::assertFalse($this->repo->tagExists('invalid', $domainApiKey));
    }
}
