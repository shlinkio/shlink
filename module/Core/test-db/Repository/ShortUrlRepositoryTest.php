<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function count;

class ShortUrlRepositoryTest extends DatabaseTestCase
{
    protected const ENTITIES_TO_EMPTY = [
        Tag::class,
        Visit::class,
        ShortUrl::class,
        Domain::class,
    ];

    /** @var ShortUrlRepository */
    private $repo;

    public function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(ShortUrl::class);
    }

    /** @test */
    public function findOneByShortCodeReturnsProperData(): void
    {
        $regularOne = new ShortUrl('foo');
        $regularOne->setShortCode('foo');
        $this->getEntityManager()->persist($regularOne);

        $notYetValid = new ShortUrl('bar', ShortUrlMeta::createFromParams(Chronos::now()->addMonth()));
        $notYetValid->setShortCode('bar_very_long_text');
        $this->getEntityManager()->persist($notYetValid);

        $expired = new ShortUrl('expired', ShortUrlMeta::createFromParams(null, Chronos::now()->subMonth()));
        $expired->setShortCode('expired');
        $this->getEntityManager()->persist($expired);

        $allVisitsComplete = new ShortUrl('baz', ShortUrlMeta::createFromRawData(['maxVisits' => 3]));
        $visits = [];
        for ($i = 0; $i < 3; $i++) {
            $visit = new Visit($allVisitsComplete, Visitor::emptyInstance());
            $this->getEntityManager()->persist($visit);
            $visits[] = $visit;
        }
        $allVisitsComplete->setShortCode('baz')
                          ->setVisits(new ArrayCollection($visits));
        $this->getEntityManager()->persist($allVisitsComplete);

        $withDomain = new ShortUrl('foo', ShortUrlMeta::createFromRawData(['domain' => 'example.com']));
        $withDomain->setShortCode('domain-short-code');
        $this->getEntityManager()->persist($withDomain);

        $withDomainDuplicatingRegular = new ShortUrl('foo_with_domain', ShortUrlMeta::createFromRawData([
            'domain' => 'doma.in',
        ]));
        $withDomainDuplicatingRegular->setShortCode('foo');
        $this->getEntityManager()->persist($withDomainDuplicatingRegular);

        $this->getEntityManager()->flush();

        $this->assertSame($regularOne, $this->repo->findOneByShortCode($regularOne->getShortCode()));
        $this->assertSame($regularOne, $this->repo->findOneByShortCode($withDomainDuplicatingRegular->getShortCode()));
        $this->assertSame($withDomain, $this->repo->findOneByShortCode($withDomain->getShortCode(), 'example.com'));
        $this->assertSame(
            $withDomainDuplicatingRegular,
            $this->repo->findOneByShortCode($withDomainDuplicatingRegular->getShortCode(), 'doma.in')
        );
        $this->assertSame(
            $regularOne,
            $this->repo->findOneByShortCode($withDomainDuplicatingRegular->getShortCode(), 'other-domain.com')
        );
        $this->assertNull($this->repo->findOneByShortCode('invalid'));
        $this->assertNull($this->repo->findOneByShortCode($withDomain->getShortCode()));
        $this->assertNull($this->repo->findOneByShortCode($withDomain->getShortCode(), 'other-domain.com'));
        $this->assertNull($this->repo->findOneByShortCode($notYetValid->getShortCode()));
        $this->assertNull($this->repo->findOneByShortCode($expired->getShortCode()));
        $this->assertNull($this->repo->findOneByShortCode($allVisitsComplete->getShortCode()));
    }

    /** @test */
    public function countListReturnsProperNumberOfResults(): void
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
    public function findListProperlyFiltersByTagAndSearchTerm(): void
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
    public function findListProperlyMapsFieldNamesToColumnNamesWhenOrdering(): void
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

    /** @test */
    public function slugIsInUseLooksForShortUrlInProperSetOfTables(): void
    {
        $shortUrlWithoutDomain = (new ShortUrl('foo'))->setShortCode('my-cool-slug');
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = (new ShortUrl(
            'foo',
            ShortUrlMeta::createFromRawData(['domain' => 'doma.in'])
        ))->setShortCode('another-slug');
        $this->getEntityManager()->persist($shortUrlWithDomain);

        $this->getEntityManager()->flush();

        $this->assertTrue($this->repo->slugIsInUse('my-cool-slug'));
        $this->assertFalse($this->repo->slugIsInUse('my-cool-slug', 'doma.in'));
        $this->assertFalse($this->repo->slugIsInUse('slug-not-in-use'));
        $this->assertFalse($this->repo->slugIsInUse('another-slug'));
        $this->assertFalse($this->repo->slugIsInUse('another-slug', 'example.com'));
        $this->assertTrue($this->repo->slugIsInUse('another-slug', 'doma.in'));
    }
}
