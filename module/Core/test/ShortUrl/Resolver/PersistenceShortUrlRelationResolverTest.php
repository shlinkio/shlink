<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Resolver;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepositoryInterface;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Repository\TagRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;

use function count;

class PersistenceShortUrlRelationResolverTest extends TestCase
{
    use ProphecyTrait;

    private PersistenceShortUrlRelationResolver $resolver;
    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->em->getEventManager()->willReturn(new EventManager());

        $this->resolver = new PersistenceShortUrlRelationResolver($this->em->reveal());
    }

    /** @test */
    public function returnsEmptyWhenNoDomainIsProvided(): void
    {
        $getRepository = $this->em->getRepository(Domain::class);

        self::assertNull($this->resolver->resolveDomain(null));
        $getRepository->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideFoundDomains
     */
    public function findsOrCreatesDomainWhenValueIsProvided(?Domain $foundDomain, string $authority): void
    {
        $repo = $this->prophesize(DomainRepositoryInterface::class);
        $findDomain = $repo->findOneBy(['authority' => $authority])->willReturn($foundDomain);
        $getRepository = $this->em->getRepository(Domain::class)->willReturn($repo->reveal());

        $result = $this->resolver->resolveDomain($authority);

        if ($foundDomain !== null) {
            self::assertSame($result, $foundDomain);
        }
        self::assertInstanceOf(Domain::class, $result);
        self::assertEquals($authority, $result->getAuthority());
        $findDomain->shouldHaveBeenCalledOnce();
        $getRepository->shouldHaveBeenCalledOnce();
    }

    public function provideFoundDomains(): iterable
    {
        $authority = 'doma.in';

        yield 'not found domain' => [null, $authority];
        yield 'found domain' => [Domain::withAuthority($authority), $authority];
    }

    /**
     * @test
     * @dataProvider provideTags
     */
    public function findsAndPersistsTagsWrappedIntoCollection(array $tags, array $expectedTags): void
    {
        $expectedPersistedTags = count($expectedTags);

        $tagRepo = $this->prophesize(TagRepositoryInterface::class);
        $findTag = $tagRepo->findOneBy(Argument::type('array'))->will(function (array $args): ?Tag {
            ['name' => $name] = $args[0];
            return $name === 'foo' ? new Tag($name) : null;
        });
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($tagRepo->reveal());
        $persist = $this->em->persist(Argument::type(Tag::class));

        $result = $this->resolver->resolveTags($tags);

        self::assertCount($expectedPersistedTags, $result);
        self::assertEquals($expectedTags, $result->toArray());
        $findTag->shouldHaveBeenCalledTimes($expectedPersistedTags);
        $getRepo->shouldHaveBeenCalledOnce();
        $persist->shouldHaveBeenCalledTimes($expectedPersistedTags);
    }

    public function provideTags(): iterable
    {
        yield 'no duplicated tags' => [['foo', 'bar', 'baz'], [new Tag('foo'), new Tag('bar'), new Tag('baz')]];
        yield 'duplicated tags' => [['foo', 'bar', 'bar'], [new Tag('foo'), new Tag('bar')]];
    }

    /** @test */
    public function returnsEmptyCollectionWhenProvidingEmptyListOfTags(): void
    {
        $tagRepo = $this->prophesize(TagRepositoryInterface::class);
        $findTag = $tagRepo->findOneBy(Argument::type('array'))->willReturn(null);
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($tagRepo->reveal());
        $persist = $this->em->persist(Argument::type(Tag::class));

        $result = $this->resolver->resolveTags([]);

        self::assertEmpty($result);
        $findTag->shouldNotHaveBeenCalled();
        $getRepo->shouldNotHaveBeenCalled();
        $persist->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function newDomainsAreMemoizedUntilStateIsCleared(): void
    {
        $repo = $this->prophesize(DomainRepositoryInterface::class);
        $repo->findOneBy(Argument::type('array'))->willReturn(null);
        $this->em->getRepository(Domain::class)->willReturn($repo->reveal());

        $authority = 'foo.com';
        $domain1 = $this->resolver->resolveDomain($authority);
        $domain2 = $this->resolver->resolveDomain($authority);

        self::assertSame($domain1, $domain2);

        $this->resolver->postFlush();
        $domain3 = $this->resolver->resolveDomain($authority);

        self::assertNotSame($domain1, $domain3);
    }

    /** @test */
    public function newTagsAreMemoizedUntilStateIsCleared(): void
    {
        $tagRepo = $this->prophesize(TagRepositoryInterface::class);
        $tagRepo->findOneBy(Argument::type('array'))->willReturn(null);
        $this->em->getRepository(Tag::class)->willReturn($tagRepo->reveal());
        $this->em->persist(Argument::type(Tag::class))->will(function (): void {
        });

        $tags = ['foo', 'bar'];
        [$foo1, $bar1] = $this->resolver->resolveTags($tags);
        [$foo2, $bar2] = $this->resolver->resolveTags($tags);

        self::assertSame($foo1, $foo2);
        self::assertSame($bar1, $bar2);

        $this->resolver->postFlush();
        [$foo3, $bar3] = $this->resolver->resolveTags($tags);
        self::assertNotSame($foo1, $foo3);
        self::assertNotSame($bar1, $bar3);
    }
}
