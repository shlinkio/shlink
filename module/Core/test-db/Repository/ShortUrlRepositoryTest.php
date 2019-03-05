<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use ShlinkioTest\Shlink\Common\DbTest\DatabaseTestCase;

use function count;

class ShortUrlRepositoryTest extends DatabaseTestCase
{
    protected const ENTITIES_TO_EMPTY = [
        Tag::class,
        Visit::class,
        ShortUrl::class,
    ];

    /** @var ShortUrlRepository */
    private $repo;

    public function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(ShortUrl::class);
    }

    /** @test */
    public function findOneByShortCodeReturnsProperData()
    {
        $foo = new ShortUrl('foo');
        $foo->setShortCode('foo');
        $this->getEntityManager()->persist($foo);

        $bar = new ShortUrl('bar', ShortUrlMeta::createFromParams(Chronos::now()->addMonth()));
        $bar->setShortCode('bar_very_long_text');
        $this->getEntityManager()->persist($bar);

        $baz = new ShortUrl('baz', ShortUrlMeta::createFromRawData(['maxVisits' => 3]));
        $visits = [];
        for ($i = 0; $i < 3; $i++) {
            $visit = new Visit($baz, Visitor::emptyInstance());
            $this->getEntityManager()->persist($visit);
            $visits[] = $visit;
        }
        $baz->setShortCode('baz')
            ->setVisits(new ArrayCollection($visits));
        $this->getEntityManager()->persist($baz);

        $this->getEntityManager()->flush();

        $this->assertSame($foo, $this->repo->findOneByShortCode($foo->getShortCode()));
        $this->assertNull($this->repo->findOneByShortCode('invalid'));
        $this->assertNull($this->repo->findOneByShortCode($bar->getShortCode()));
        $this->assertNull($this->repo->findOneByShortCode($baz->getShortCode()));
    }

    /** @test */
    public function countListReturnsProperNumberOfResults()
    {
        $count = 5;
        for ($i = 0; $i < $count; $i++) {
            $this->getEntityManager()->persist(
                (new ShortUrl((string) $i))->setShortCode((string) $i)
            );
        }
        $this->getEntityManager()->flush();

        $this->assertEquals($count, $this->repo->countList());
    }

    /** @test */
    public function findListProperlyFiltersByTagAndSearchTerm()
    {
        $tag = new Tag('bar');
        $this->getEntityManager()->persist($tag);

        $foo = new ShortUrl('foo');
        $foo->setShortCode('foo')
            ->setTags(new ArrayCollection([$tag]));
        $this->getEntityManager()->persist($foo);

        $bar = new ShortUrl('bar');
        $visit = new Visit($bar, Visitor::emptyInstance());
        $this->getEntityManager()->persist($visit);
        $bar->setShortCode('bar_very_long_text')
            ->setVisits(new ArrayCollection([$visit]));
        $this->getEntityManager()->persist($bar);

        $foo2 = new ShortUrl('foo_2');
        $foo2->setShortCode('foo_2');
        $this->getEntityManager()->persist($foo2);

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(null, null, 'foo', ['bar']);
        $this->assertCount(1, $result);
        $this->assertSame($foo, $result[0]);

        $result = $this->repo->findList();
        $this->assertCount(3, $result);

        $result = $this->repo->findList(2);
        $this->assertCount(2, $result);

        $result = $this->repo->findList(2, 1);
        $this->assertCount(2, $result);

        $result = $this->repo->findList(2, 2);
        $this->assertCount(1, $result);

        $result = $this->repo->findList(null, null, null, [], ['visits' => 'DESC']);
        $this->assertCount(3, $result);
        $this->assertSame($bar, $result[0]);
    }

    /** @test */
    public function findListProperlyMapsFieldNamesToColumnNamesWhenOrdering()
    {
        $urls = ['a', 'z', 'c', 'b'];
        foreach ($urls as $url) {
            $this->getEntityManager()->persist(
                (new ShortUrl($url))->setShortCode($url)
            );
        }

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(null, null, null, [], ['longUrl' => 'ASC']);

        $this->assertCount(count($urls), $result);
        $this->assertEquals('a', $result[0]->getLongUrl());
        $this->assertEquals('b', $result[1]->getLongUrl());
        $this->assertEquals('c', $result[2]->getLongUrl());
        $this->assertEquals('z', $result[3]->getLongUrl());
    }
}
