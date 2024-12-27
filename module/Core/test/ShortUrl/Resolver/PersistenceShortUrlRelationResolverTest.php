<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Resolver;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Repository\TagRepository;

use function count;

class PersistenceShortUrlRelationResolverTest extends TestCase
{
    private PersistenceShortUrlRelationResolver $resolver;
    private MockObject & EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('getEventManager')->willReturn(new EventManager());

        $this->resolver = new PersistenceShortUrlRelationResolver($this->em, new UrlShortenerOptions('default.com'));
    }

    #[Test, DataProvider('provideDomainsThatEmpty')]
    public function returnsEmptyInSomeCases(string|null $domain): void
    {
        $this->em->expects($this->never())->method('getRepository')->with(Domain::class);
        self::assertNull($this->resolver->resolveDomain($domain));
    }

    public static function provideDomainsThatEmpty(): iterable
    {
        yield 'null' => [null];
        yield 'default domain' => ['default.com'];
    }

    #[Test, DataProvider('provideFoundDomains')]
    public function findsOrCreatesDomainWhenValueIsProvided(Domain|null $foundDomain, string $authority): void
    {
        $repo = $this->createMock(DomainRepository::class);
        $repo->expects($this->once())->method('findOneBy')->with(['authority' => $authority])->willReturn($foundDomain);
        $this->em->expects($this->once())->method('getRepository')->with(Domain::class)->willReturn($repo);

        $result = $this->resolver->resolveDomain($authority);

        if ($foundDomain !== null) {
            self::assertSame($result, $foundDomain);
        }
        self::assertInstanceOf(Domain::class, $result);
        self::assertEquals($authority, $result->authority);
    }

    public static function provideFoundDomains(): iterable
    {
        $authority = 's.test';

        yield 'not found domain' => [null, $authority];
        yield 'found domain' => [Domain::withAuthority($authority), $authority];
    }

    #[Test, DataProvider('provideTags')]
    public function findsAndPersistsTagsWrappedIntoCollection(array $tags, array $expectedTags): void
    {
        $expectedLookedOutTags = count($expectedTags);
        // One of the tags will already exist. The rest will be new
        $expectedPersistedTags = $expectedLookedOutTags - 1;

        $tagRepo = $this->createMock(TagRepository::class);
        $tagRepo->expects($this->exactly($expectedLookedOutTags))->method('findOneBy')->with(
            $this->isArray(),
        )->willReturnCallback(function (array $criteria): Tag|null {
            ['name' => $name] = $criteria;
            return $name === 'foo' ? new Tag($name) : null;
        });
        $this->em->expects($this->once())->method('getRepository')->with(Tag::class)->willReturn($tagRepo);
        $this->em->expects($this->exactly($expectedPersistedTags))->method('persist')->with(
            $this->isInstanceOf(Tag::class),
        );

        $result = $this->resolver->resolveTags($tags);

        self::assertCount($expectedLookedOutTags, $result);
        self::assertEquals($expectedTags, $result->toArray());
    }

    public static function provideTags(): iterable
    {
        yield 'no duplicated tags' => [['foo', 'bar', 'baz'], [new Tag('foo'), new Tag('bar'), new Tag('baz')]];
        yield 'duplicated tags' => [['foo', 'bar', 'bar'], [new Tag('foo'), new Tag('bar')]];
    }

    #[Test]
    public function returnsEmptyCollectionWhenProvidingEmptyListOfTags(): void
    {
        $this->em->expects($this->never())->method('getRepository')->with(Tag::class);
        $this->em->expects($this->never())->method('persist');

        $result = $this->resolver->resolveTags([]);

        self::assertEmpty($result);
    }

    #[Test]
    public function newDomainsAreMemoizedUntilStateIsCleared(): void
    {
        $repo = $this->createMock(DomainRepository::class);
        $repo->expects($this->exactly(3))->method('findOneBy')->with($this->isArray())->willReturn(null);
        $this->em->method('getRepository')->with(Domain::class)->willReturn($repo);

        $authority = 'foo.com';
        $domain1 = $this->resolver->resolveDomain($authority);
        $domain2 = $this->resolver->resolveDomain($authority);

        self::assertSame($domain1, $domain2);

        $this->resolver->postFlush();
        $domain3 = $this->resolver->resolveDomain($authority);

        self::assertNotSame($domain1, $domain3);
    }

    #[Test]
    public function newTagsAreMemoizedUntilStateIsCleared(): void
    {
        $tagRepo = $this->createMock(TagRepository::class);
        $tagRepo->expects($this->exactly(6))->method('findOneBy')->with($this->isArray())->willReturn(null);
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
