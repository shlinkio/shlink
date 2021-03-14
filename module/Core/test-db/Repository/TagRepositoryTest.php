<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function array_chunk;

class TagRepositoryTest extends DatabaseTestCase
{
    private TagRepository $repo;
    private PersistenceShortUrlRelationResolver $relationResolver;

    protected function beforeEach(): void
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

    /** @test */
    public function properTagsInfoIsReturned(): void
    {
        $names = ['foo', 'bar', 'baz', 'another'];
        foreach ($names as $name) {
            $this->getEntityManager()->persist(new Tag($name));
        }
        $this->getEntityManager()->flush();

        [$firstUrlTags] = array_chunk($names, 3);
        $secondUrlTags = [$names[0]];
        $metaWithTags = fn (array $tags) => ShortUrlMeta::fromRawData(['longUrl' => '', 'tags' => $tags]);

        $shortUrl = ShortUrl::fromMeta($metaWithTags($firstUrlTags), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl, Visitor::emptyInstance()));

        $shortUrl2 = ShortUrl::fromMeta($metaWithTags($secondUrlTags), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl2, Visitor::emptyInstance()));
        $this->getEntityManager()->flush();

        $result = $this->repo->findTagsWithInfo();

        self::assertCount(4, $result);
        self::assertEquals(0, $result[0]->shortUrlsCount());
        self::assertEquals(0, $result[0]->visitsCount());
        self::assertEquals($names[3], $result[0]->tag()->__toString());

        self::assertEquals(1, $result[1]->shortUrlsCount());
        self::assertEquals(3, $result[1]->visitsCount());
        self::assertEquals($names[1], $result[1]->tag()->__toString());

        self::assertEquals(1, $result[2]->shortUrlsCount());
        self::assertEquals(3, $result[2]->visitsCount());
        self::assertEquals($names[2], $result[2]->tag()->__toString());

        self::assertEquals(2, $result[3]->shortUrlsCount());
        self::assertEquals(4, $result[3]->visitsCount());
        self::assertEquals($names[0], $result[3]->tag()->__toString());
    }

    /** @test */
    public function tagExistsReturnsExpectedResultBasedOnApiKey(): void
    {
        $domain = new Domain('foo.com');
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
