<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Resolver;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\SimpleShortUrlRelationResolver;

class SimpleShortUrlRelationResolverTest extends TestCase
{
    private SimpleShortUrlRelationResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new SimpleShortUrlRelationResolver();
    }

    /**
     * @test
     * @dataProvider provideDomains
     */
    public function resolvesExpectedDomain(?string $domain): void
    {
        $result = $this->resolver->resolveDomain($domain);

        if ($domain === null) {
            self::assertNull($result);
        } else {
            self::assertInstanceOf(Domain::class, $result);
            self::assertEquals($domain, $result->getAuthority());
        }
    }

    public function provideDomains(): iterable
    {
        yield 'empty domain' => [null];
        yield 'non-empty domain' => ['domain.com'];
    }

    /** @test */
    public function tagsAreWrappedInEntityCollection(): void
    {
        $tags = ['foo', 'bar', 'baz'];

        $result = $this->resolver->resolveTags($tags);

        self::assertCount(3, $result);
        self::assertEquals([new Tag('foo'), new Tag('bar'), new Tag('baz')], $result->toArray());
    }
}
