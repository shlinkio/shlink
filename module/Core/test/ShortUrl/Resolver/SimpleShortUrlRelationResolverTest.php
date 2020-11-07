<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Resolver;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\SimpleShortUrlRelationResolver;

class SimpleShortUrlRelationResolverTest extends TestCase
{
    private SimpleShortUrlRelationResolver $resolver;

    public function setUp(): void
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

    /**
     * @test
     * @dataProvider provideKeys
     */
    public function alwaysReturnsNullForApiKeys(?string $key): void
    {
        self::assertNull($this->resolver->resolveApiKey($key));
    }

    public function provideKeys(): iterable
    {
        yield 'empty api key' => [null];
        yield 'non-empty api key' => ['abc123'];
    }
}
