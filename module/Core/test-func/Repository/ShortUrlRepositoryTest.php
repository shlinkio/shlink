<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use ShlinkioTest\Shlink\Common\DbUnit\DatabaseTestCase;

class ShortUrlRepositoryTest extends DatabaseTestCase
{
    protected const ENTITIES_TO_EMPTY = [
        ShortUrl::class,
        Visit::class,
        Tag::class,
    ];

    /**
     * @var ShortUrlRepository
     */
    private $repo;

    public function setUp()
    {
        $this->repo = $this->getEntityManager()->getRepository(ShortUrl::class);
    }

    /**
     * @test
     */
    public function findOneByShortCodeReturnsProperData()
    {
        $foo = new ShortUrl();
        $foo->setOriginalUrl('foo')
            ->setShortCode('foo');
        $this->getEntityManager()->persist($foo);

        $bar = new ShortUrl();
        $bar->setOriginalUrl('bar')
            ->setShortCode('bar_very_long_text')
            ->setValidSince((new \DateTime())->add(new \DateInterval('P1M')));
        $this->getEntityManager()->persist($bar);

        $visits = [];
        for ($i = 0; $i < 3; $i++) {
            $visit = new Visit();
            $this->getEntityManager()->persist($visit);
            $visits[] = $visit;
        }
        $baz = new ShortUrl();
        $baz->setOriginalUrl('baz')
            ->setShortCode('baz')
            ->setVisits(new ArrayCollection($visits))
            ->setMaxVisits(3);
        $this->getEntityManager()->persist($baz);

        $this->getEntityManager()->flush();

        $this->assertSame($foo, $this->repo->findOneByShortCode($foo->getShortCode()));
        $this->assertNull($this->repo->findOneByShortCode('invalid'));
        $this->assertNull($this->repo->findOneByShortCode($bar->getShortCode()));
        $this->assertNull($this->repo->findOneByShortCode($baz->getShortCode()));
    }

    /**
     * @test
     */
    public function countListReturnsProperNumberOfResults()
    {
        $count = 5;
        for ($i = 0; $i < $count; $i++) {
            $this->getEntityManager()->persist(
                (new ShortUrl())->setOriginalUrl((string) $i)
                                ->setShortCode((string) $i)
            );
        }
        $this->getEntityManager()->flush();

        $this->assertEquals($count, $this->repo->countList());
    }

    /**
     * @test
     */
    public function findListProperlyFiltersByTagAndSearchTerm()
    {
        $tag = new Tag('bar');
        $this->getEntityManager()->persist($tag);

        $foo = new ShortUrl();
        $foo->setOriginalUrl('foo')
            ->setShortCode('foo')
            ->setTags(new ArrayCollection([$tag]));
        $this->getEntityManager()->persist($foo);

        $bar = new ShortUrl();
        $bar->setOriginalUrl('bar')
            ->setShortCode('bar_very_long_text');
        $this->getEntityManager()->persist($bar);

        $foo2 = new ShortUrl();
        $foo2->setOriginalUrl('foo_2')
            ->setShortCode('foo_2');
        $this->getEntityManager()->persist($foo2);

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(null, null, 'foo', ['bar']);
        $this->assertCount(1, $result);
        $this->assertSame($foo, $result[0]);
    }

    /**
     * @test
     */
    public function findListProperlyMapsFieldNamesToColumnNamesWhenOrdering()
    {
        $urls = ['a', 'z', 'c', 'b'];
        foreach ($urls as $url) {
            $this->getEntityManager()->persist(
                (new ShortUrl())->setShortCode($url)
                                ->setLongUrl($url)
            );
        }

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(null, null, null, [], ['longUrl' => 'ASC']);

        $this->assertCount(\count($urls), $result);
        $this->assertEquals('a', $result[0]->getLongUrl());
        $this->assertEquals('b', $result[1]->getLongUrl());
        $this->assertEquals('c', $result[2]->getLongUrl());
        $this->assertEquals('z', $result[3]->getLongUrl());
    }
}
