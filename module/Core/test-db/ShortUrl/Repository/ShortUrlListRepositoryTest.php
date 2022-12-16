<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\ShortUrl\Repository;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionObject;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\OrderableField;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsCountFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsListFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlListRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function count;
use function Functional\map;
use function range;

class ShortUrlListRepositoryTest extends DatabaseTestCase
{
    private ShortUrlListRepository $repo;
    private PersistenceShortUrlRelationResolver $relationResolver;

    protected function setUp(): void
    {
        $em = $this->getEntityManager();
        $this->repo = new ShortUrlListRepository($em, $em->getClassMetadata(ShortUrl::class));
        $this->relationResolver = new PersistenceShortUrlRelationResolver($em);
    }

    /** @test */
    public function countListReturnsProperNumberOfResults(): void
    {
        $count = 5;
        for ($i = 0; $i < $count; $i++) {
            $this->getEntityManager()->persist(ShortUrl::withLongUrl((string) $i));
        }
        $this->getEntityManager()->flush();

        self::assertEquals($count, $this->repo->countList(new ShortUrlsCountFiltering()));
    }

    /** @test */
    public function findListProperlyFiltersResult(): void
    {
        $foo = ShortUrl::create(
            ShortUrlCreation::fromRawData(['longUrl' => 'foo', 'tags' => ['bar']]),
            $this->relationResolver,
        );
        $this->getEntityManager()->persist($foo);

        $bar = ShortUrl::withLongUrl('bar');
        $visits = map(range(0, 5), function () use ($bar) {
            $visit = Visit::forValidShortUrl($bar, Visitor::botInstance());
            $this->getEntityManager()->persist($visit);

            return $visit;
        });
        $bar->setVisits(new ArrayCollection($visits));
        $this->getEntityManager()->persist($bar);

        $foo2 = ShortUrl::withLongUrl('foo_2');
        $visits2 = map(range(0, 3), function () use ($foo2) {
            $visit = Visit::forValidShortUrl($foo2, Visitor::emptyInstance());
            $this->getEntityManager()->persist($visit);

            return $visit;
        });
        $foo2->setVisits(new ArrayCollection($visits2));
        $ref = new ReflectionObject($foo2);
        $dateProp = $ref->getProperty('dateCreated');
        $dateProp->setAccessible(true);
        $dateProp->setValue($foo2, Chronos::now()->subDays(5));
        $this->getEntityManager()->persist($foo2);

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), 'foo', ['bar']),
        );
        self::assertCount(1, $result);
        self::assertEquals(1, $this->repo->countList(new ShortUrlsCountFiltering('foo', ['bar'])));
        self::assertSame($foo, $result[0]);

        // Assert searched text also applies to tags
        $result = $this->repo->findList(new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), 'bar'));
        self::assertCount(2, $result);
        self::assertEquals(2, $this->repo->countList(new ShortUrlsCountFiltering('bar')));
        self::assertContains($foo, $result);

        $result = $this->repo->findList(new ShortUrlsListFiltering(null, null, Ordering::emptyInstance()));
        self::assertCount(3, $result);

        $result = $this->repo->findList(new ShortUrlsListFiltering(2, null, Ordering::emptyInstance()));
        self::assertCount(2, $result);

        $result = $this->repo->findList(new ShortUrlsListFiltering(2, 1, Ordering::emptyInstance()));
        self::assertCount(2, $result);

        self::assertCount(1, $this->repo->findList(new ShortUrlsListFiltering(2, 2, Ordering::emptyInstance())));

        $result = $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::fromTuple([OrderableField::VISITS->value, 'DESC'])),
        );
        self::assertCount(3, $result);
        self::assertSame($bar, $result[0]);

        $result = $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::fromTuple(
                [OrderableField::NON_BOT_VISITS->value, 'DESC'],
            )),
        );
        self::assertCount(3, $result);
        self::assertSame($foo2, $result[0]);

        $result = $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), null, [], null, DateRange::until(
                Chronos::now()->subDays(2),
            )),
        );
        self::assertCount(1, $result);
        self::assertEquals(1, $this->repo->countList(new ShortUrlsCountFiltering(null, [], null, DateRange::until(
            Chronos::now()->subDays(2),
        ))));
        self::assertSame($foo2, $result[0]);

        self::assertCount(2, $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), null, [], null, DateRange::since(
                Chronos::now()->subDays(2),
            )),
        ));
        self::assertEquals(2, $this->repo->countList(
            new ShortUrlsCountFiltering(null, [], null, DateRange::since(Chronos::now()->subDays(2))),
        ));
    }

    /** @test */
    public function findListProperlyMapsFieldNamesToColumnNamesWhenOrdering(): void
    {
        $urls = ['a', 'z', 'c', 'b'];
        foreach ($urls as $url) {
            $this->getEntityManager()->persist(ShortUrl::withLongUrl($url));
        }

        $this->getEntityManager()->flush();

        $result = $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::fromTuple(['longUrl', 'ASC'])),
        );

        self::assertCount(count($urls), $result);
        self::assertEquals('a', $result[0]->getLongUrl());
        self::assertEquals('b', $result[1]->getLongUrl());
        self::assertEquals('c', $result[2]->getLongUrl());
        self::assertEquals('z', $result[3]->getLongUrl());
    }

    /** @test */
    public function findListReturnsOnlyThoseWithMatchingTags(): void
    {
        $shortUrl1 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo1',
            'tags' => ['foo', 'bar'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo2',
            'tags' => ['foo', 'baz'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo3',
            'tags' => ['foo'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl3);
        $shortUrl4 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo4',
            'tags' => ['bar', 'baz'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl4);
        $shortUrl5 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo5',
            'tags' => ['bar', 'baz'],
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl5);

        $this->getEntityManager()->flush();

        self::assertCount(5, $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), null, ['foo', 'bar']),
        ));
        self::assertCount(5, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['foo', 'bar'],
            TagsMode::ANY,
        )));
        self::assertCount(1, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['foo', 'bar'],
            TagsMode::ALL,
        )));
        self::assertEquals(5, $this->repo->countList(new ShortUrlsCountFiltering(null, ['foo', 'bar'])));
        self::assertEquals(5, $this->repo->countList(new ShortUrlsCountFiltering(null, ['foo', 'bar'], TagsMode::ANY)));
        self::assertEquals(1, $this->repo->countList(new ShortUrlsCountFiltering(null, ['foo', 'bar'], TagsMode::ALL)));

        self::assertCount(4, $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), null, ['bar', 'baz']),
        ));
        self::assertCount(4, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['bar', 'baz'],
            TagsMode::ANY,
        )));
        self::assertCount(2, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['bar', 'baz'],
            TagsMode::ALL,
        )));
        self::assertEquals(4, $this->repo->countList(new ShortUrlsCountFiltering(null, ['bar', 'baz'])));
        self::assertEquals(4, $this->repo->countList(
            new ShortUrlsCountFiltering(null, ['bar', 'baz'], TagsMode::ANY),
        ));
        self::assertEquals(2, $this->repo->countList(
            new ShortUrlsCountFiltering(null, ['bar', 'baz'], TagsMode::ALL),
        ));

        self::assertCount(5, $this->repo->findList(
            new ShortUrlsListFiltering(null, null, Ordering::emptyInstance(), null, ['foo', 'bar', 'baz']),
        ));
        self::assertCount(5, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['foo', 'bar', 'baz'],
            TagsMode::ANY,
        )));
        self::assertCount(0, $this->repo->findList(new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            null,
            ['foo', 'bar', 'baz'],
            TagsMode::ALL,
        )));
        self::assertEquals(5, $this->repo->countList(new ShortUrlsCountFiltering(null, ['foo', 'bar', 'baz'])));
        self::assertEquals(5, $this->repo->countList(
            new ShortUrlsCountFiltering(null, ['foo', 'bar', 'baz'], TagsMode::ANY),
        ));
        self::assertEquals(0, $this->repo->countList(
            new ShortUrlsCountFiltering(null, ['foo', 'bar', 'baz'], TagsMode::ALL),
        ));
    }

    /** @test */
    public function findListReturnsOnlyThoseWithMatchingDomains(): void
    {
        $shortUrl1 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo1',
            'domain' => null,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo2',
            'domain' => null,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo3',
            'domain' => 'another.com',
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl3);

        $this->getEntityManager()->flush();

        $buildFiltering = static fn (string $searchTerm) => new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            searchTerm: $searchTerm,
            defaultDomain: 'deFaulT-domain.com',
        );

        self::assertCount(2, $this->repo->findList($buildFiltering('default-dom')));
        self::assertCount(2, $this->repo->findList($buildFiltering('DOM')));
        self::assertCount(1, $this->repo->findList($buildFiltering('another')));
        self::assertCount(3, $this->repo->findList($buildFiltering('foo')));
        self::assertCount(0, $this->repo->findList($buildFiltering('no results')));
    }

    /** @test */
    public function findListReturnsOnlyThoseWithoutExcludedUrls(): void
    {
        $shortUrl1 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo1',
            'validUntil' => Chronos::now()->addDays(1)->toAtomString(),
            'maxVisits' => 100,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl1);
        $shortUrl2 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo2',
            'validUntil' => Chronos::now()->subDays(1)->toAtomString(),
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl2);
        $shortUrl3 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo3',
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl3);
        $shortUrl4 = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'foo4',
            'maxVisits' => 3,
        ]), $this->relationResolver);
        $this->getEntityManager()->persist($shortUrl4);
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl4, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl4, Visitor::emptyInstance()));
        $this->getEntityManager()->persist(Visit::forValidShortUrl($shortUrl4, Visitor::emptyInstance()));

        $this->getEntityManager()->flush();

        $filtering = static fn (bool $excludeMaxVisitsReached, bool $excludePastValidUntil) =>
        new ShortUrlsListFiltering(
            null,
            null,
            Ordering::emptyInstance(),
            excludeMaxVisitsReached: $excludeMaxVisitsReached,
            excludePastValidUntil: $excludePastValidUntil,
        );

        self::assertCount(4, $this->repo->findList($filtering(false, false)));
        self::assertEquals(4, $this->repo->countList($filtering(false, false)));
        self::assertCount(3, $this->repo->findList($filtering(true, false)));
        self::assertEquals(3, $this->repo->countList($filtering(true, false)));
        self::assertCount(3, $this->repo->findList($filtering(false, true)));
        self::assertEquals(3, $this->repo->countList($filtering(false, true)));
        self::assertCount(2, $this->repo->findList($filtering(true, true)));
        self::assertEquals(2, $this->repo->countList($filtering(true, true)));
    }
}
