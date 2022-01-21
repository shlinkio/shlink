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
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagsListFiltering;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function array_chunk;

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
    public function properTagsInfoIsReturned(?TagsListFiltering $filtering, callable $asserts): void
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
            ShortUrl::fromMeta($metaWithTags(['bar'], null), $this->relationResolver),
        );

        $this->getEntityManager()->flush();

        $result = $this->repo->findTagsWithInfo($filtering);

        $asserts($result, $names);
    }

    public function provideFilters(): iterable
    {
        $defaultAsserts = static function (array $result, array $tagNames): void {
            /** @var TagInfo[] $result */
            self::assertCount(4, $result);
            self::assertEquals(0, $result[0]->shortUrlsCount());
            self::assertEquals(0, $result[0]->visitsCount());
            self::assertEquals($tagNames[3], $result[0]->tag()->__toString());

            self::assertEquals(3, $result[1]->shortUrlsCount());
            self::assertEquals(3, $result[1]->visitsCount());
            self::assertEquals($tagNames[1], $result[1]->tag()->__toString());

            self::assertEquals(1, $result[2]->shortUrlsCount());
            self::assertEquals(3, $result[2]->visitsCount());
            self::assertEquals($tagNames[2], $result[2]->tag()->__toString());

            self::assertEquals(2, $result[3]->shortUrlsCount());
            self::assertEquals(4, $result[3]->visitsCount());
            self::assertEquals($tagNames[0], $result[3]->tag()->__toString());
        };

        yield 'no filter' => [null, $defaultAsserts];
        yield 'empty filter' => [new TagsListFiltering(), $defaultAsserts];
        yield 'limit' => [new TagsListFiltering(2), static function (array $result, array $tagNames): void {
            /** @var TagInfo[] $result */
            self::assertCount(2, $result);
            self::assertEquals(0, $result[0]->shortUrlsCount());
            self::assertEquals(0, $result[0]->visitsCount());
            self::assertEquals($tagNames[3], $result[0]->tag()->__toString());

            self::assertEquals(3, $result[1]->shortUrlsCount());
            self::assertEquals(3, $result[1]->visitsCount());
            self::assertEquals($tagNames[1], $result[1]->tag()->__toString());
        }];
        yield 'offset' => [new TagsListFiltering(null, 3), static function (array $result, array $tagNames): void {
            /** @var TagInfo[] $result */
            self::assertCount(1, $result);
            self::assertEquals(2, $result[0]->shortUrlsCount());
            self::assertEquals(4, $result[0]->visitsCount());
            self::assertEquals($tagNames[0], $result[0]->tag()->__toString());
        }];
        yield 'limit and offset' => [
            new TagsListFiltering(2, 1),
            static function (array $result, array $tagNames): void {
                /** @var TagInfo[] $result */
                self::assertCount(2, $result);
                self::assertEquals(3, $result[0]->shortUrlsCount());
                self::assertEquals(3, $result[0]->visitsCount());
                self::assertEquals($tagNames[1], $result[0]->tag()->__toString());

                self::assertEquals(1, $result[1]->shortUrlsCount());
                self::assertEquals(3, $result[1]->visitsCount());
                self::assertEquals($tagNames[2], $result[1]->tag()->__toString());
            },
        ];
        yield 'search term' => [
            new TagsListFiltering(null, null, 'ba'),
            static function (array $result, array $tagNames): void {
                /** @var TagInfo[] $result */
                self::assertCount(2, $result);
                self::assertEquals(3, $result[0]->shortUrlsCount());
                self::assertEquals(3, $result[0]->visitsCount());
                self::assertEquals($tagNames[1], $result[0]->tag()->__toString());

                self::assertEquals(1, $result[1]->shortUrlsCount());
                self::assertEquals(3, $result[1]->visitsCount());
                self::assertEquals($tagNames[2], $result[1]->tag()->__toString());
            },
        ];
        yield 'ASC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(['tag', 'ASC'])),
            $defaultAsserts,
        ];
        yield 'DESC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(['tag', 'DESC'])),
            static function (array $result, array $tagNames): void {
                /** @var TagInfo[] $result */
                self::assertCount(4, $result);
                self::assertEquals(0, $result[3]->shortUrlsCount());
                self::assertEquals(0, $result[3]->visitsCount());
                self::assertEquals($tagNames[3], $result[3]->tag()->__toString());

                self::assertEquals(3, $result[2]->shortUrlsCount());
                self::assertEquals(3, $result[2]->visitsCount());
                self::assertEquals($tagNames[1], $result[2]->tag()->__toString());

                self::assertEquals(1, $result[1]->shortUrlsCount());
                self::assertEquals(3, $result[1]->visitsCount());
                self::assertEquals($tagNames[2], $result[1]->tag()->__toString());

                self::assertEquals(2, $result[0]->shortUrlsCount());
                self::assertEquals(4, $result[0]->visitsCount());
                self::assertEquals($tagNames[0], $result[0]->tag()->__toString());
            },
        ];
        yield 'short URLs count ASC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(['shortUrlsCount', 'ASC'])),
            static function (array $result, array $tagNames): void {
                /** @var TagInfo[] $result */
                self::assertCount(4, $result);
                self::assertEquals(0, $result[0]->shortUrlsCount());
                self::assertEquals(0, $result[0]->visitsCount());
                self::assertEquals($tagNames[3], $result[0]->tag()->__toString());

                self::assertEquals(1, $result[1]->shortUrlsCount());
                self::assertEquals(3, $result[1]->visitsCount());
                self::assertEquals($tagNames[2], $result[1]->tag()->__toString());

                self::assertEquals(2, $result[2]->shortUrlsCount());
                self::assertEquals(4, $result[2]->visitsCount());
                self::assertEquals($tagNames[0], $result[2]->tag()->__toString());

                self::assertEquals(3, $result[3]->shortUrlsCount());
                self::assertEquals(3, $result[3]->visitsCount());
                self::assertEquals($tagNames[1], $result[3]->tag()->__toString());
            },
        ];
        yield 'short URLs count DESC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(['shortUrlsCount', 'DESC'])),
            static function (array $result, array $tagNames): void {
                /** @var TagInfo[] $result */
                self::assertCount(4, $result);
                self::assertEquals(3, $result[0]->shortUrlsCount());
                self::assertEquals(3, $result[0]->visitsCount());
                self::assertEquals($tagNames[1], $result[0]->tag()->__toString());

                self::assertEquals(2, $result[1]->shortUrlsCount());
                self::assertEquals(4, $result[1]->visitsCount());
                self::assertEquals($tagNames[0], $result[1]->tag()->__toString());

                self::assertEquals(1, $result[2]->shortUrlsCount());
                self::assertEquals(3, $result[2]->visitsCount());
                self::assertEquals($tagNames[2], $result[2]->tag()->__toString());

                self::assertEquals(0, $result[3]->shortUrlsCount());
                self::assertEquals(0, $result[3]->visitsCount());
                self::assertEquals($tagNames[3], $result[3]->tag()->__toString());
            },
        ];
        yield 'visits count ASC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(['visitsCount', 'ASC'])),
            static function (array $result, array $tagNames): void {
                /** @var TagInfo[] $result */
                self::assertCount(4, $result);
                self::assertEquals(0, $result[0]->shortUrlsCount());
                self::assertEquals(0, $result[0]->visitsCount());
                self::assertEquals($tagNames[3], $result[0]->tag()->__toString());

                self::assertEquals(3, $result[1]->shortUrlsCount());
                self::assertEquals(3, $result[1]->visitsCount());
                self::assertEquals($tagNames[1], $result[1]->tag()->__toString());

                self::assertEquals(1, $result[2]->shortUrlsCount());
                self::assertEquals(3, $result[2]->visitsCount());
                self::assertEquals($tagNames[2], $result[2]->tag()->__toString());

                self::assertEquals(2, $result[3]->shortUrlsCount());
                self::assertEquals(4, $result[3]->visitsCount());
                self::assertEquals($tagNames[0], $result[3]->tag()->__toString());
            },
        ];
        yield 'visits count DESC ordering' => [
            new TagsListFiltering(null, null, null, Ordering::fromTuple(['visitsCount', 'DESC'])),
            static function (array $result, array $tagNames): void {
                /** @var TagInfo[] $result */
                self::assertCount(4, $result);
                self::assertEquals(2, $result[0]->shortUrlsCount());
                self::assertEquals(4, $result[0]->visitsCount());
                self::assertEquals($tagNames[0], $result[0]->tag()->__toString());

                self::assertEquals(3, $result[1]->shortUrlsCount());
                self::assertEquals(3, $result[1]->visitsCount());
                self::assertEquals($tagNames[1], $result[1]->tag()->__toString());

                self::assertEquals(1, $result[2]->shortUrlsCount());
                self::assertEquals(3, $result[2]->visitsCount());
                self::assertEquals($tagNames[2], $result[2]->tag()->__toString());

                self::assertEquals(0, $result[3]->shortUrlsCount());
                self::assertEquals(0, $result[3]->visitsCount());
                self::assertEquals($tagNames[3], $result[3]->tag()->__toString());
            },
        ];
        yield 'visits count DESC ordering and limit' => [
            new TagsListFiltering(2, null, null, Ordering::fromTuple(['visitsCount', 'DESC'])),
            static function (array $result, array $tagNames): void {
                /** @var TagInfo[] $result */
                self::assertCount(2, $result);
                self::assertEquals(2, $result[0]->shortUrlsCount());
                self::assertEquals(4, $result[0]->visitsCount());
                self::assertEquals($tagNames[0], $result[0]->tag()->__toString());

                self::assertEquals(3, $result[1]->shortUrlsCount());
                self::assertEquals(3, $result[1]->visitsCount());
                self::assertEquals($tagNames[1], $result[1]->tag()->__toString());
            },
        ];
        yield 'api key' => [new TagsListFiltering(null, null, null, null, ApiKey::fromMeta(
            ApiKeyMeta::withRoles(RoleDefinition::forAuthoredShortUrls()),
        )), static function (array $result, array $tagNames): void {
            /** @var TagInfo[] $result */
            self::assertCount(3, $result);
            self::assertEquals(1, $result[0]->shortUrlsCount());
            self::assertEquals(3, $result[0]->visitsCount());
            self::assertEquals($tagNames[1], $result[0]->tag()->__toString());

            self::assertEquals(1, $result[1]->shortUrlsCount());
            self::assertEquals(3, $result[1]->visitsCount());
            self::assertEquals($tagNames[2], $result[1]->tag()->__toString());

            self::assertEquals(1, $result[2]->shortUrlsCount());
            self::assertEquals(3, $result[2]->visitsCount());
            self::assertEquals($tagNames[0], $result[2]->tag()->__toString());
        }];
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
