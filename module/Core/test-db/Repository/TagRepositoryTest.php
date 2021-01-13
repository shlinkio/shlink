<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function array_chunk;

class TagRepositoryTest extends DatabaseTestCase
{
    private TagRepository $repo;

    protected function beforeEach(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Tag::class);
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
        $tags = [];
        foreach ($names as $name) {
            $tag = new Tag($name);
            $tags[] = $tag;
            $this->getEntityManager()->persist($tag);
        }

        [$firstUrlTags] = array_chunk($tags, 3);
        $secondUrlTags = [$tags[0]];

        $shortUrl = new ShortUrl('');
        $shortUrl->setTags(new ArrayCollection($firstUrlTags));
        $this->getEntityManager()->persist($shortUrl);
        $this->getEntityManager()->persist(new Visit($shortUrl, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(new Visit($shortUrl, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(new Visit($shortUrl, Visitor::emptyInstance()));

        $shortUrl2 = new ShortUrl('');
        $shortUrl2->setTags(new ArrayCollection($secondUrlTags));
        $this->getEntityManager()->persist($shortUrl2);
        $this->getEntityManager()->persist(new Visit($shortUrl2, Visitor::emptyInstance()));

        $this->getEntityManager()->flush();

        $result = $this->repo->findTagsWithInfo();

        self::assertCount(4, $result);
        self::assertEquals(
            ['tag' => $tags[3], 'shortUrlsCount' => 0, 'visitsCount' => 0],
            $result[0]->jsonSerialize(),
        );
        self::assertEquals(
            ['tag' => $tags[1], 'shortUrlsCount' => 1, 'visitsCount' => 3],
            $result[1]->jsonSerialize(),
        );
        self::assertEquals(
            ['tag' => $tags[2], 'shortUrlsCount' => 1, 'visitsCount' => 3],
            $result[2]->jsonSerialize(),
        );
        self::assertEquals(
            ['tag' => $tags[0], 'shortUrlsCount' => 2, 'visitsCount' => 4],
            $result[3]->jsonSerialize(),
        );
    }

    /** @test */
    public function tagExistsReturnsExpectedResultBasedOnApiKey(): void
    {
        $domain = new Domain('foo.com');
        $this->getEntityManager()->persist($domain);
        $this->getEntityManager()->flush();

        $authorApiKey = ApiKey::withRoles(RoleDefinition::forAuthoredShortUrls());
        $this->getEntityManager()->persist($authorApiKey);
        $domainApiKey = ApiKey::withRoles(RoleDefinition::forDomain($domain));
        $this->getEntityManager()->persist($domainApiKey);

        $names = ['foo', 'bar', 'baz', 'another'];
        $tags = [];
        foreach ($names as $name) {
            $tag = new Tag($name);
            $tags[] = $tag;
            $this->getEntityManager()->persist($tag);
        }

        [$firstUrlTags, $secondUrlTags] = array_chunk($tags, 3);

        $shortUrl = new ShortUrl('', ShortUrlMeta::fromRawData(['apiKey' => $authorApiKey]));
        $shortUrl->setTags(new ArrayCollection($firstUrlTags));
        $this->getEntityManager()->persist($shortUrl);

        $shortUrl2 = new ShortUrl(
            '',
            ShortUrlMeta::fromRawData(['domain' => $domain->getAuthority()]),
            new PersistenceShortUrlRelationResolver($this->getEntityManager()),
        );
        $shortUrl2->setTags(new ArrayCollection($secondUrlTags));
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
