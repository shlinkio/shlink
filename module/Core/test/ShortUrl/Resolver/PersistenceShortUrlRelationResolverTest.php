<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Resolver;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Repository\TagRepositoryInterface;

use function count;

class PersistenceShortUrlRelationResolverTest extends TestCase
{
    private PersistenceShortUrlRelationResolver $resolver;
    private MockObject & EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('getEventManager')->willReturn(new EventManager());

        $this->resolver = new PersistenceShortUrlRelationResolver($this->em);
    }

    /** @test */
    public function returnsEmptyWhenNoDomainIsProvided(): void
    {
        $this->em->expects($this->never())->method('getRepository')->with(Domain::class);
        self::assertNull($this->resolver->resolveDomain(null));
    }

    /**
     * @test
     * @dataProvider provideFoundDomains
     */
    public function findsOrCreatesDomainWhenValueIsProvided(?Domain $foundDomain, string $authority): void
    {
        $repo = $this->createMock(DomainRepositoryInterface::class);
        $repo->expects($this->once())->method('findOneBy')->with(['authority' => $authority])->willReturn($foundDomain);
        $this->em->expects($this->once())->method('getRepository')->with(Domain::class)->willReturn($repo);

        $result = $this->resolver->resolveDomain($authority);

        if ($foundDomain !== null) {
            self::assertSame($result, $foundDomain);
        }
        self::assertInstanceOf(Domain::class, $result);
        self::assertEquals($authority, $result->getAuthority());
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

        $tagRepo = $this->createMock(TagRepositoryInterface::class);
        $tagRepo->expects($this->exactly($expectedPersistedTags))->method('findOneBy')->with(
            $this->isType('array'),
        )->willReturnCallback(function (array $criteria): ?Tag {
            ['name' => $name] = $criteria;
            return $name === 'foo' ? new Tag($name) : null;
        });
        $this->em->expects($this->once())->method('getRepository')->with(Tag::class)->willReturn($tagRepo);
        $this->em->expects($this->exactly($expectedPersistedTags))->method('persist')->with(
            $this->isInstanceOf(Tag::class),
        );

        $result = $this->resolver->resolveTags($tags);

        self::assertCount($expectedPersistedTags, $result);
        self::assertEquals($expectedTags, $result->toArray());
    }

    public function provideTags(): iterable
    {
        yield 'no duplicated tags' => [['foo', 'bar', 'baz'], [new Tag('foo'), new Tag('bar'), new Tag('baz')]];
        yield 'duplicated tags' => [['foo', 'bar', 'bar'], [new Tag('foo'), new Tag('bar')]];
    }

    /** @test */
    public function returnsEmptyCollectionWhenProvidingEmptyListOfTags(): void
    {
        $this->em->expects($this->never())->method('getRepository')->with(Tag::class);
        $this->em->expects($this->never())->method('persist');

        $result = $this->resolver->resolveTags([]);

        self::assertEmpty($result);
    }

    /** @test */
    public function newDomainsAreMemoizedUntilStateIsCleared(): void
    {
        $repo = $this->createMock(DomainRepositoryInterface::class);
        $repo->expects($this->exactly(3))->method('findOneBy')->with($this->isType('array'))->willReturn(null);
        $this->em->method('getRepository')->with(Domain::class)->willReturn($repo);

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
        $tagRepo = $this->createMock(TagRepositoryInterface::class);
        $tagRepo->expects($this->exactly(6))->method('findOneBy')->with($this->isType('array'))->willReturn(null);
        $this->em->method('getRepository')->with(Tag::class)->willReturn($tagRepo);

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
