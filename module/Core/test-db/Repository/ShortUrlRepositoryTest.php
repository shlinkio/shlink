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
use Shlinkio\Shlink\Core\Util\TagManagerTrait;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function count;

class ShortUrlRepositoryTest extends DatabaseTestCase
{
    use TagManagerTrait;

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
    public function findOneWithDomainFallbackReturnsProperData(): void
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

        $this->assertSame($regularOne, $this->repo->findOneWithDomainFallback($regularOne->getShortCode()));
        $this->assertSame($regularOne, $this->repo->findOneWithDomainFallback(
            $withDomainDuplicatingRegular->getShortCode(),
        ));
        $this->assertSame($withDomain, $this->repo->findOneWithDomainFallback(
            $withDomain->getShortCode(),
            'example.com',
        ));
        $this->assertSame(
            $withDomainDuplicatingRegular,
            $this->repo->findOneWithDomainFallback($withDomainDuplicatingRegular->getShortCode(), 'doma.in'),
        );
        $this->assertSame(
            $regularOne,
            $this->repo->findOneWithDomainFallback($withDomainDuplicatingRegular->getShortCode(), 'other-domain.com'),
        );
        $this->assertNull($this->repo->findOneWithDomainFallback('invalid'));
        $this->assertNull($this->repo->findOneWithDomainFallback($withDomain->getShortCode()));
        $this->assertNull($this->repo->findOneWithDomainFallback($withDomain->getShortCode(), 'other-domain.com'));
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

    /** @test */
    public function findOneLooksForShortUrlInProperSetOfTables(): void
    {
        $shortUrlWithoutDomain = new ShortUrl('foo', ShortUrlMeta::fromRawData(['customSlug' => 'my-cool-slug']));
        $this->getEntityManager()->persist($shortUrlWithoutDomain);

        $shortUrlWithDomain = new ShortUrl(
            'foo',
            ShortUrlMeta::fromRawData(['domain' => 'doma.in', 'customSlug' => 'another-slug']),
        );
        $this->getEntityManager()->persist($shortUrlWithDomain);

        $this->getEntityManager()->flush();

        $this->assertNotNull($this->repo->findOne('my-cool-slug'));
        $this->assertNull($this->repo->findOne('my-cool-slug', 'doma.in'));
        $this->assertNull($this->repo->findOne('slug-not-in-use'));
        $this->assertNull($this->repo->findOne('another-slug'));
        $this->assertNull($this->repo->findOne('another-slug', 'example.com'));
        $this->assertNotNull($this->repo->findOne('another-slug', 'doma.in'));
    }

    /** @test */
    public function findOneMatchingReturnsNullForNonExistingShortUrls(): void
    {
        $this->assertNull($this->repo->findOneMatching('', [], ShortUrlMeta::createEmpty()));
        $this->assertNull($this->repo->findOneMatching('foobar', [], ShortUrlMeta::createEmpty()));
        $this->assertNull($this->repo->findOneMatching('foobar', ['foo', 'bar'], ShortUrlMeta::createEmpty()));
        $this->assertNull($this->repo->findOneMatching('foobar', ['foo', 'bar'], ShortUrlMeta::fromRawData([
            'validSince' => Chronos::parse('2020-03-05 20:18:30'),
            'customSlug' => 'this_slug_does_not_exist',
        ])));
    }

    /** @test */
    public function findOneMatchingAppliesProperConditions(): void
    {
        $start = Chronos::parse('2020-03-05 20:18:30');
        $end = Chronos::parse('2021-03-05 20:18:30');

        $shortUrl = new ShortUrl('foo', ShortUrlMeta::fromRawData(['validSince' => $start]));
        $shortUrl->setTags($this->tagNamesToEntities($this->getEntityManager(), ['foo', 'bar']));
        $this->getEntityManager()->persist($shortUrl);

        $shortUrl2 = new ShortUrl('bar', ShortUrlMeta::fromRawData(['validUntil' => $end]));
        $this->getEntityManager()->persist($shortUrl2);

        $shortUrl3 = new ShortUrl('baz', ShortUrlMeta::fromRawData(['validSince' => $start, 'validUntil' => $end]));
        $this->getEntityManager()->persist($shortUrl3);

        $shortUrl4 = new ShortUrl('foo', ShortUrlMeta::fromRawData(['customSlug' => 'custom', 'validUntil' => $end]));
        $this->getEntityManager()->persist($shortUrl4);

        $shortUrl5 = new ShortUrl('foo', ShortUrlMeta::fromRawData(['maxVisits' => 3]));
        $this->getEntityManager()->persist($shortUrl5);

        $shortUrl6 = new ShortUrl('foo', ShortUrlMeta::fromRawData(['domain' => 'doma.in']));
        $this->getEntityManager()->persist($shortUrl6);

        $this->getEntityManager()->flush();

        $this->assertSame(
            $shortUrl,
            $this->repo->findOneMatching('foo', ['foo', 'bar'], ShortUrlMeta::fromRawData(['validSince' => $start])),
        );
        $this->assertSame(
            $shortUrl2,
            $this->repo->findOneMatching('bar', [], ShortUrlMeta::fromRawData(['validUntil' => $end])),
        );
        $this->assertSame(
            $shortUrl3,
            $this->repo->findOneMatching('baz', [], ShortUrlMeta::fromRawData([
                'validSince' => $start,
                'validUntil' => $end,
            ])),
        );
        $this->assertSame(
            $shortUrl4,
            $this->repo->findOneMatching('foo', [], ShortUrlMeta::fromRawData([
                'customSlug' => 'custom',
                'validUntil' => $end,
            ])),
        );
        $this->assertSame(
            $shortUrl5,
            $this->repo->findOneMatching('foo', [], ShortUrlMeta::fromRawData(['maxVisits' => 3])),
        );
        $this->assertSame(
            $shortUrl6,
            $this->repo->findOneMatching('foo', [], ShortUrlMeta::fromRawData(['domain' => 'doma.in'])),
        );
    }

    /** @test */
    public function findOneMatchingReturnsOldestOneWhenThereAreMultipleMatches(): void
    {
        $start = Chronos::parse('2020-03-05 20:18:30');
        $meta = ['validSince' => $start, 'maxVisits' => 50];
        $tags = ['foo', 'bar'];
        $tagEntities = $this->tagNamesToEntities($this->getEntityManager(), $tags);

        $shortUrl1 = new ShortUrl('foo', ShortUrlMeta::fromRawData($meta));
        $shortUrl1->setTags($tagEntities);
        $this->getEntityManager()->persist($shortUrl1);

        $shortUrl2 = new ShortUrl('foo', ShortUrlMeta::fromRawData($meta));
        $shortUrl2->setTags($tagEntities);
        $this->getEntityManager()->persist($shortUrl2);

        $shortUrl3 = new ShortUrl('foo', ShortUrlMeta::fromRawData($meta));
        $shortUrl3->setTags($tagEntities);
        $this->getEntityManager()->persist($shortUrl3);

        $this->getEntityManager()->flush();

        $this->assertSame(
            $shortUrl1,
            $this->repo->findOneMatching('foo', $tags, ShortUrlMeta::fromRawData($meta)),
        );
        $this->assertNotSame(
            $shortUrl2,
            $this->repo->findOneMatching('foo', $tags, ShortUrlMeta::fromRawData($meta)),
        );
        $this->assertNotSame(
            $shortUrl3,
            $this->repo->findOneMatching('foo', $tags, ShortUrlMeta::fromRawData($meta)),
        );
    }
}
