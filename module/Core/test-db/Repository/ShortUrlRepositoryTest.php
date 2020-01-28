<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionObject;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\ShortUrlsOrdering;
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

    private ShortUrlRepository $repo;

    public function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(ShortUrl::class);
    }

    /** @test */
    public function findOneByShortCodeReturnsProperData(): void
    {
        $regularOne = new ShortUrl('foo', ShortUrlMeta::fromRawData(['customSlug' => 'foo']));
        $this->getEntityManager()->persist($regularOne);

        $withDomain = new ShortUrl('foo', ShortUrlMeta::fromRawData(
            ['domain' => 'example.com', 'customSlug' => 'domain-short-code'],
        ));
        $this->getEntityManager()->persist($withDomain);

        $withDomainDuplicatingRegular = new ShortUrl('foo_with_domain', ShortUrlMeta::fromRawData(
            ['domain' => 'doma.in', 'customSlug' => 'foo'],
        ));
        $this->getEntityManager()->persist($withDomainDuplicatingRegular);

        $this->getEntityManager()->flush();

        $this->assertSame($regularOne, $this->repo->findOneByShortCode($regularOne->getShortCode()));
        $this->assertSame($regularOne, $this->repo->findOneByShortCode($withDomainDuplicatingRegular->getShortCode()));
        $this->assertSame($withDomain, $this->repo->findOneByShortCode($withDomain->getShortCode(), 'example.com'));
        $this->assertSame(
            $withDomainDuplicatingRegular,
            $this->repo->findOneByShortCode($withDomainDuplicatingRegular->getShortCode(), 'doma.in'),
        );
        $this->assertSame(
            $regularOne,
            $this->repo->findOneByShortCode($withDomainDuplicatingRegular->getShortCode(), 'other-domain.com'),
        );
        $this->assertNull($this->repo->findOneByShortCode('invalid'));
        $this->assertNull($this->repo->findOneByShortCode($withDomain->getShortCode()));
        $this->assertNull($this->repo->findOneByShortCode($withDomain->getShortCode(), 'other-domain.com'));
    }

    /** @test */
    public function countListReturnsProperNumberOfResults(): void
    {
        $count = 5;
        for ($i = 0; $i < $count; $i++) {
            $this->getEntityManager()->persist(new ShortUrl((string) $i));
        }
        $this->getEntityManager()->flush();

        $this->assertEquals($count, $this->repo->countList());
    }

    /** @test */
    public function findListProperlyFiltersResult(): void
    {
        $tag = new Tag('bar');
        $this->getEntityManager()->persist($tag);

        $foo = new ShortUrl('foo');
        $foo->setTags(new ArrayCollection([$tag]));
        $this->getEntityManager()->persist($foo);

        $bar = new ShortUrl('bar');
        $visit = new Visit($bar, Visitor::emptyInstance());
        $this->getEntityManager()->persist($visit);
        $bar->setVisits(new ArrayCollection([$visit]));
        $this->getEntityManager()->persist($bar);

        $foo2 = new ShortUrl('foo_2');
        $ref = new ReflectionObject($foo2);
        $dateProp = $ref->getProperty('dateCreated');
        $dateProp->setAccessible(true);
        $dateProp->setValue($foo2, Chronos::now()->subDays(5));
        $this->getEntityManager()->persist($foo2);

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(null, null, 'foo', ['bar']);
        $this->assertCount(1, $result);
        $this->assertEquals(1, $this->repo->countList('foo', ['bar']));
        $this->assertSame($foo, $result[0]);

        $result = $this->repo->findList();
        $this->assertCount(3, $result);

        $result = $this->repo->findList(2);
        $this->assertCount(2, $result);

        $result = $this->repo->findList(2, 1);
        $this->assertCount(2, $result);

        $this->assertCount(1, $this->repo->findList(2, 2));

        $result = $this->repo->findList(null, null, null, [], ShortUrlsOrdering::fromRawData([
            'orderBy' => ['visits' => 'DESC'],
        ]));
        $this->assertCount(3, $result);
        $this->assertSame($bar, $result[0]);

        $result = $this->repo->findList(null, null, null, [], null, new DateRange(null, Chronos::now()->subDays(2)));
        $this->assertCount(1, $result);
        $this->assertEquals(1, $this->repo->countList(null, [], new DateRange(null, Chronos::now()->subDays(2))));
        $this->assertSame($foo2, $result[0]);

        $this->assertCount(
            2,
            $this->repo->findList(null, null, null, [], null, new DateRange(Chronos::now()->subDays(2))),
        );
        $this->assertEquals(2, $this->repo->countList(null, [], new DateRange(Chronos::now()->subDays(2))));
    }

    /** @test */
    public function findListProperlyMapsFieldNamesToColumnNamesWhenOrdering(): void
    {
        $urls = ['a', 'z', 'c', 'b'];
        foreach ($urls as $url) {
            $this->getEntityManager()->persist(new ShortUrl($url));
        }

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(null, null, null, [], ShortUrlsOrdering::fromRawData([
            'orderBy' => ['longUrl' => 'ASC'],
        ]));

        $this->assertCount(count($urls), $result);
        $this->assertEquals('a', $result[0]->getLongUrl());
        $this->assertEquals('b', $result[1]->getLongUrl());
        $this->assertEquals('c', $result[2]->getLongUrl());
        $this->assertEquals('z', $result[3]->getLongUrl());
    }

    /** @test */
    public function shortCodeIsInUseLooksForShortUrlInProperSetOfTables(): void
    {
        $shortUrlWithoutDomain = new ShortUrl('foo', ShortUrlMeta::fromRawData(['customSlug' => 'my-cool-slug']));
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = new ShortUrl(
            'foo',
            ShortUrlMeta::fromRawData(['domain' => 'doma.in', 'customSlug' => 'another-slug']),
        );
        $this->getEntityManager()->persist($shortUrlWithDomain);

        $this->getEntityManager()->flush();

        $this->assertTrue($this->repo->shortCodeIsInUse('my-cool-slug'));
        $this->assertFalse($this->repo->shortCodeIsInUse('my-cool-slug', 'doma.in'));
        $this->assertFalse($this->repo->shortCodeIsInUse('slug-not-in-use'));
        $this->assertFalse($this->repo->shortCodeIsInUse('another-slug'));
        $this->assertFalse($this->repo->shortCodeIsInUse('another-slug', 'example.com'));
        $this->assertTrue($this->repo->shortCodeIsInUse('another-slug', 'doma.in'));
    }
}
