<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Repository;

use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Tag\Model\TagsListFiltering;
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

    /** @test */
    public function deleteByNameDoesNothingWhenEmptyListIsProvided(): void
    {
        self::assertEquals(0, $this->repo->deleteByName([]));
    }

    /** @test */
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

    /**
     * @test
     * @dataProvider provideFilters
     */
    public function properTagsInfoIsReturned(?TagsListFiltering $filtering, array $expectedList): void
    {
        $names = ['foo', 'bar', 'baz', 'another'];
        foreach ($names as $name) {
            $this->getEntityManager()->persist(new Tag($name));
        }

        $apiKey = $filtering?->apiKey();
        if ($apiKey !== null) {
            $this->getEntityManager()->persist($apiKey);
        }

        $this->getEntityManager()->flush();

        [$firstUrlTags] = array_chunk($names, 3);
        $secondUrlTags = [$names[0]];
        $metaWithTags = fn (array $tags, ?ApiKey $apiKey) => ShortUrlMeta::fromRawData(
            ['longUrl' => '', 'tags' => $tags, 'apiKey' => $apiKey],
        );

        $shortUrl = ShortUrl::fromMeta($metaWithTags($firstUrlTags, $apiKey), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance()));

        $shortUrl2 = ShortUrl::fromMeta($metaWithTags($secondUrlTags, null), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl2, Visitor::emptyInstance()));

        // One of the tags has two extra short URLs, but with no visits
        $this->getEntityManager()->persist(
            ShortUrl::fromMeta($metaWithTags(['bar'], null), $this->relationResolver),
        );
        $this->getEntityManager()->persist(
            ShortUrl::fromMeta($metaWithTags(['bar'], $apiKey), $this->relationResolver),
        );

        $this->getEntityManager()->flush();

        $result = $this->repo->findTagsWithInfo($filtering);

        self::assertCount(count($expectedList), $result);
        foreach ($expectedList as $index => [$tag, $shortUrlsCount, $visitsCount]) {
            self::assertEquals($shortUrlsCount, $result[$index]->shortUrlsCount());
            self::assertEquals($visitsCount, $result[$index]->visitsCount());
            self::assertEquals($tag, $result[$index]->tag());
        }
    }

    public function provideFilters(): iterable
    {
        $defaultList = [
            ['another', 0, 0],
            ['bar', 3, 3],
            ['baz', 1, 3],
            ['foo', 2, 4],
        ];

        yield 'no filter' => [null, $defaultList];
        yield 'empty filter' => [new TagsListFiltering(), $defaultList];
        yield 'limit' => [new TagsListFiltering(2), [
            ['another', 0, 0],
            ['bar', 3, 3],
        ]];
        yield 'offset' => [new TagsListFiltering(null, 3), [
            ['foo', 2, 4],
        ]];
        yield 'limit and offset' => [new TagsListFiltering(2, 1), [
            ['bar', 3, 3],
            ['baz', 1, 3],
        ]];
        yield 'search term' => [new TagsListFiltering(null, null, 'ba'), [
            ['bar', 3, 3],
            ['baz', 1, 3],
        ]];
        yield 'ASC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(['tag', 'ASC'])),
            $defaultList,
        ];
        yield 'DESC ordering' => [new TagsListFiltering(null, null, null, Ordering::fromTuple(['tag', 'DESC'])), [
            ['foo', 2, 4],
            ['baz', 1, 3],
            ['bar', 3, 3],
            ['another', 0, 0],
        ]];
        yield 'short URLs count ASC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(['shortUrlsCount', 'ASC'])),
            [
                ['another', 0, 0],
                ['baz', 1, 3],
                ['foo', 2, 4],
                ['bar', 3, 3],
            ],
        ];
        yield 'short URLs count DESC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(['shortUrlsCount', 'DESC'])),
            [
                ['bar', 3, 3],
                ['foo', 2, 4],
                ['baz', 1, 3],
                ['another', 0, 0],
            ],
        ];
        yield 'visits count ASC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(['visitsCount', 'ASC'])),
            [
                ['another', 0, 0],
                ['bar', 3, 3],
                ['baz', 1, 3],
                ['foo', 2, 4],
            ],
        ];
        yield 'visits count DESC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(['visitsCount', 'DESC'])),
            [
                ['foo', 2, 4],
                ['bar', 3, 3],
                ['baz', 1, 3],
                ['another', 0, 0],
            ],
        ];
        yield 'visits count DESC ordering and limit' => [
            new TagsListFiltering(2, null, null, Ordering::fromTuple(['visitsCount', 'DESC'])),
            [
                ['foo', 2, 4],
                ['bar', 3, 3],
            ],
        ];
        yield 'api key' => [new TagsListFiltering(null, null, null, null, ApiKey::fromMeta(
            ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()),
        )), [
            ['bar', 2, 3],
            ['baz', 1, 3],
            ['foo', 1, 3],
        ]];
        yield 'combined' => [new TagsListFiltering(1, null, null, Ordering::fromTuple(
            ['shortUrls', 'DESC'],
        ), ApiKey::fromMeta(
            ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()),
        )), [
            ['foo', 1, 3],
        ]];
    }

    /** @test */
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

        $shortUrl = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['apiKey' => $authorApiKey, 'longUrl' => '', 'tags' => $firstUrlTags]),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($shortUrl);

        $shortUrl2 = ShortUrl::fromMeta(
            ShortUrlMeta::fromRawData(['domain' => $domain->getAuthority(), 'longUrl' => '', 'tags' => $secondUrlTags]),
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
