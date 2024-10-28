<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Resolver;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\SimpleShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;

class SimpleShortUrlRelationResolverTest extends TestCase
{
    private SimpleShortUrlRelationResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new SimpleShortUrlRelationResolver();
    }

    #[Test, DataProvider('provideDomains')]
    public function resolvesExpectedDomain(string|null $domain): void
    {
        $result = $this->resolver->resolveDomain($domain);

        if ($domain === null) {
            self::assertNull($result);
        } else {
            self::assertInstanceOf(Domain::class, $result);
            self::assertEquals($domain, $result->authority);
        }
    }

    public static function provideDomains(): iterable
    {
        yield 'empty domain' => [null];
        yield 'non-empty domain' => ['domain.com'];
    }

    #[Test]
    public function tagsAreWrappedInEntityCollection(): void
    {
        $tags = ['foo', 'bar', 'baz'];

        $result = $this->resolver->resolveTags($tags);

        self::assertCount(3, $result);
        self::assertEquals([new Tag('foo'), new Tag('bar'), new Tag('baz')], $result->toArray());
    }
}
