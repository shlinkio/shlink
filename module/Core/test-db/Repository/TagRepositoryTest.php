<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function array_chunk;

class TagRepositoryTest extends DatabaseTestCase
{
    protected const ENTITIES_TO_EMPTY = [
        Visit::class,
        ShortUrl::class,
        Tag::class,
    ];

    private TagRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Tag::class);
    }

    /** @test */
    public function deleteByNameDoesNothingWhenEmptyListIsProvided(): void
    {
        $this->assertEquals(0, $this->repo->deleteByName([]));
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

        $this->assertEquals(2, $this->repo->deleteByName($toDelete));
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

        $this->assertCount(4, $result);
        $this->assertEquals(
            ['tag' => $tags[3], 'shortUrlsCount' => 0, 'visitsCount' => 0],
            $result[0]->jsonSerialize(),
        );
        $this->assertEquals(
            ['tag' => $tags[1], 'shortUrlsCount' => 1, 'visitsCount' => 3],
            $result[1]->jsonSerialize(),
        );
        $this->assertEquals(
            ['tag' => $tags[2], 'shortUrlsCount' => 1, 'visitsCount' => 3],
            $result[2]->jsonSerialize(),
        );
        $this->assertEquals(
            ['tag' => $tags[0], 'shortUrlsCount' => 2, 'visitsCount' => 4],
            $result[3]->jsonSerialize(),
        );
    }
}
